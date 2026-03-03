<?php

declare(strict_types=1);

namespace App\Imports;

use App\Models\Emanating;
use App\Models\EmanatingItem;
use App\Models\PPMP;
use App\Models\PPMPCategory;
use App\Models\PPMPItem;
use App\Models\Project;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;

class EmanatingImport implements ToCollection, WithCustomCsvSettings
{
    protected ?Project $project = null;

    protected ?PPMP $ppmp = null;

    protected ?PPMPCategory $ppmpCategory = null;

    protected ?int $ppmpCategoryId = null;

    protected int $itemsCreated = 0;

    protected bool $itemsMatchPPMP = true;

    protected array $matchingResults = [];

    public function __construct(?Project $project = null, ?PPMP $ppmp = null, ?int $ppmpCategoryId = null)
    {
        $this->project = $project;
        $this->ppmp = $ppmp;
        $this->ppmpCategoryId = $ppmpCategoryId;

        // Load the PPMPCategory if ID is provided
        if ($ppmpCategoryId) {
            $this->ppmpCategory = PPMPCategory::find($ppmpCategoryId);
        }
    }

    public function getCsvSettings(): array
    {
        return [
            'input_encoding' => 'UTF-8',
            'delimiter' => ',',
            'enclosure' => '"',
            'escape_character' => '\\',
            'contiguous' => false,
            'use_bom' => false,
        ];
    }

    public function collection(Collection $rows): void
    {
        $rowArray = $rows->toArray();

        // Parse metadata
        $metadata = $this->parseMetadata($rowArray);

        // If project/ppmp not provided in constructor, try to find them
        if (! $this->project && isset($metadata['office_name'])) {
            $this->findRelatedModels($metadata);
        }

        // Create Emanating record
        $emanating = Emanating::create([
            'fund_id' => $this->ppmp?->fund_id,
            'ppmp_id' => $this->ppmp?->id,
            'project_id' => $this->project?->id,
            'ppmp_category_id' => $this->ppmpCategoryId,
            'charged_to_code' => $metadata['charged_to_code'] ?? null,
            'pr_no' => $metadata['pr_no'] ?? null,
            'fiscal_year' => $metadata['fiscal_year'] ?? null,
            'quarter' => $metadata['quarter'] ?? null,
            'month' => $metadata['month'] ?? null,
            'purpose' => $metadata['purpose'] ?? '',
            'is_addendum' => false,
            'reimbursement' => false,
            'items_match_ppmp' => false, // Will update after parsing items
        ]);

        // Parse and create items
        $this->parseItems($rowArray, $emanating);

        // Update items_match_ppmp flag
        $emanating->update([
            'items_match_ppmp' => $this->itemsMatchPPMP,
        ]);
    }

    /**
     * Parse metadata from CSV rows
     */
    private function parseMetadata(array $rows): array
    {
        $metadata = [];

        // Row 1: PR No.
        if (isset($rows[1][3])) {
            $metadata['pr_no'] = trim($rows[1][3]);
        }

        // Cell A8 (row 7): Office/Department name
        if (isset($rows[7][0])) {
            $officeText = trim($rows[7][0]);
            // Extract office name (might have prefixes like "Office:" or "Department:")
            if (preg_match('/(?:Office|Department|Div(?:ision)?)[:\s]*(.+)/i', $officeText, $matches)) {
                $metadata['office_name'] = trim($matches[1]);
            } else {
                $metadata['office_name'] = $officeText;
            }
        }

        // Cell A14 (row 13): Parse quarter/month/fiscal year from request text
        if (isset($rows[13][0])) {
            $requestText = $rows[13][0];
            $timeInfo = $this->parseTimeInfo($requestText);
            $metadata = array_merge($metadata, $timeInfo);
        }

        // Cell A23 (row 22): Charged to code
        if (isset($rows[22][0])) {
            $chargedTo = $rows[22][0];
            if (preg_match('/Charged to:\s*(\d+)/', $chargedTo, $matches)) {
                $metadata['charged_to_code'] = $matches[1];
            }
        }

        // Row 23: Account code (PPMP category code)
        if (isset($rows[23][1])) {
            $accountText = $rows[23][1];
            if (preg_match('/Account:\s*([\d-]+)/', $accountText, $matches)) {
                $metadata['ppmp_category_code'] = $matches[1];
            }
        }

        // Cell B27 (row 26, column 1): Purpose
        if (isset($rows[26][1])) {
            $metadata['purpose'] = trim($rows[26][1]);
        }

        return $metadata;
    }

    /**
     * Parse time information from request text
     */
    private function parseTimeInfo(string $text): array
    {
        $info = [];

        // Extract fiscal year
        if (preg_match('/(\d{4})/', $text, $matches)) {
            $info['fiscal_year'] = (int) $matches[1];
        }

        // Extract quarter
        if (preg_match('/(\d+)(?:st|nd|rd|th)\s+quarter/i', $text, $matches)) {
            $info['quarter'] = (int) $matches[1];
        }

        // Extract month
        $months = [
            'january' => 1,
            'february' => 2,
            'march' => 3,
            'april' => 4,
            'may' => 5,
            'june' => 6,
            'july' => 7,
            'august' => 8,
            'september' => 9,
            'october' => 10,
            'november' => 11,
            'december' => 12,
        ];

        foreach ($months as $monthName => $monthNum) {
            if (stripos($text, $monthName) !== false) {
                $info['month'] = $monthNum;
                break;
            }
        }

        return $info;
    }

    /**
     * Parse items from CSV
     */
    private function parseItems(array $rows, Emanating $emanating): array
    {
        $items = [];
        $emanatingItemsData = [];
        $counter = count($rows);

        // Step 1: Parse all emanating items from CSV
        for ($i = 15; $i < $counter; ++$i) {
            $row = $rows[$i];

            // Stop when we reach empty rows or metadata section
            if (! isset($row[0]) || trim($row[0]) === '' || stripos($row[0], 'Charged to') !== false) {
                break;
            }

            $qtyUnit = trim($row[0]);
            $description = trim($row[1] ?? '');
            $estimatedPrice = $this->parseAmount($row[2] ?? '0');

            if ($description === '') {
                continue;
            }

            // Parse quantity and unit
            preg_match('/^(\d+)\s*(.*)$/', $qtyUnit, $matches);
            $quantity = isset($matches[1]) ? (int) $matches[1] : 0;
            $unit = isset($matches[2]) ? trim($matches[2]) : 'pcs';

            $emanatingItemsData[] = [
                'description' => $description,
                'quantity' => $quantity,
                'unit' => $unit,
                'price' => $estimatedPrice,
            ];
        }

        // Step 2: Get all PPMP items for this category
        $ppmpItems = $this->ppmpCategory instanceof \App\Models\PPMPCategory
            ? PPMPItem::where('ppmp_category_id', $this->ppmpCategory->id)->get()
            : collect();

        // Step 3: Perform bidirectional matching
        $matchedPPMPItemIds = [];

        foreach ($emanatingItemsData as $emanatingItemData) {
            $ppmpItem = $this->findMatchingPPMPItem(
                $emanatingItemData['description']
            );

            if (! $ppmpItem instanceof \App\Models\PPMPItem) {
                $this->itemsMatchPPMP = false;
                $this->matchingResults[] = [
                    'description' => $emanatingItemData['description'],
                    'quantity' => $emanatingItemData['quantity'],
                    'unit' => $emanatingItemData['unit'],
                    'matched' => false,
                    'message' => 'No matching PPMP item found',
                ];

                continue;
            }

            // PPMP item found - create the emanating item regardless of quantity/unit
            // The comparison logic will validate and report mismatches
            $matchedPPMPItemIds[] = $ppmpItem->id;

            $this->matchingResults[] = [
                'description' => $emanatingItemData['description'],
                'quantity' => $emanatingItemData['quantity'],
                'unit' => $emanatingItemData['unit'],
                'matched' => true,
                'ppmp_item_id' => $ppmpItem->id,
                'ppmp_item_name' => $ppmpItem->name,
            ];

            // Create emanating item
            $emanatingItem = EmanatingItem::create([
                'emanating_id' => $emanating->id,
                'ppmp_item_id' => $ppmpItem->id,
                'quantity' => $emanatingItemData['quantity'],
                'unit' => $emanatingItemData['unit'],
                'total_price' => $emanatingItemData['price'],
            ]);

            $items[] = $emanatingItem;
            ++$this->itemsCreated;
        }

        // Step 4: Verify all PPMP items are accounted for in emanating request
        $unmatchedPPMPItems = $ppmpItems->whereNotIn('id', $matchedPPMPItemIds);

        if ($unmatchedPPMPItems->isNotEmpty()) {
            $this->itemsMatchPPMP = false;

            foreach ($unmatchedPPMPItems as $unmatchedPPMPItem) {
                $this->matchingResults[] = [
                    'description' => $unmatchedPPMPItem->name,
                    'quantity' => $unmatchedPPMPItem->quantity,
                    'unit' => $unmatchedPPMPItem->unit,
                    'matched' => false,
                    'message' => 'PPMP item not found in Emanating request',
                    'ppmp_item_id' => $unmatchedPPMPItem->id,
                    'is_missing_from_emanating' => true,
                ];
            }
        }

        // Step 5: Final validation - counts must match
        if (count($emanatingItemsData) !== $ppmpItems->count() || $unmatchedPPMPItems->isNotEmpty()) {
            $this->itemsMatchPPMP = false;
        }

        return $items;
    }

    /**
     * Find matching PPMP item by description and quantity
     */
    private function findMatchingPPMPItem(string $description): ?PPMPItem
    {
        if (! $this->ppmpCategory instanceof \App\Models\PPMPCategory) {
            return null;
        }

        // Try exact match first
        $item = PPMPItem::where('ppmp_category_id', $this->ppmpCategory->id)
            ->where('name', $description)
            ->first();

        if ($item) {
            return $item;
        }

        // Try fuzzy match (contains)
        $item = PPMPItem::where('ppmp_category_id', $this->ppmpCategory->id)
            ->where('name', 'like', '%' . $description . '%')
            ->first();

        if ($item) {
            return $item;
        }

        // Try reverse fuzzy match (description contains item name)
        $item = PPMPItem::where('ppmp_category_id', $this->ppmpCategory->id)
            ->whereRaw("? like ('%' || name || '%')", [$description])
            ->first();

        return $item;
    }

    /**
     * Find related models (Project, PPMP, PPMPCategory) based on office name and PPMP category code
     */
    private function findRelatedModels(array $metadata): void
    {
        if (! isset($metadata['office_name']) || ! isset($metadata['ppmp_category_code'])) {
            return;
        }

        $officeName = $metadata['office_name'];
        $ppmpCategoryCode = $metadata['ppmp_category_code'];
        $fiscalYear = $metadata['fiscal_year'] ?? date('Y');

        // Find project by matching office through fund relationship
        $this->project = Project::whereHas('fund.office', function ($query) use ($officeName): void {
            $query->where('name', 'like', '%' . $officeName . '%')
                ->orWhere('name', $officeName);
        })
            ->with('fund.office')
            ->first();

        if (! $this->project instanceof \App\Models\Project) {
            Log::warning('[EmanatingImport] Project not found for office', ['office_name' => $officeName]);

            return;
        }

        // Find PPMP category by code
        $this->ppmpCategory = PPMPCategory::where('code', $ppmpCategoryCode)
            ->whereHas('ppmp', function ($query) use ($fiscalYear): void {
                $query->where('fiscal_year', $fiscalYear);
            })
            ->with('ppmp')
            ->first();

        if ($this->ppmpCategory instanceof \App\Models\PPMPCategory) {
            $this->ppmp = $this->ppmpCategory->ppmp;
        } else {
            Log::warning('[EmanatingImport] PPMP category not found', [
                'code' => $ppmpCategoryCode,
                'fiscal_year' => $fiscalYear,
            ]);
        }
    }

    /**
     * Parse amount from CSV (removes commas, spaces, and currency symbols)
     */
    private function parseAmount(string $amount): float
    {
        $cleaned = preg_replace('/[^0-9.]/', '', $amount);

        return $cleaned !== '' ? (float) $cleaned : 0.0;
    }

    public function getItemsCreated(): int
    {
        return $this->itemsCreated;
    }

    public function getItemsMatchPPMP(): bool
    {
        return $this->itemsMatchPPMP;
    }

    public function getMatchingResults(): array
    {
        return $this->matchingResults;
    }
}
