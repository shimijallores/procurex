<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FundResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'type' => $this->type,
            'fiscal_year' => $this->fiscal_year,
            'remarks' => $this->remarks,
            'office' => OfficeResource::make($this->whenLoaded('office')),
            'office_id' => $this->office_id,
            'project' => $this->whenLoaded('project', function () {
                return [
                    'id' => $this->project->id,
                    'remarks' => $this->project->remarks,
                    'work_program' => $this->project->workProgram ? [
                        'id' => $this->project->workProgram->id,
                        'file_url' => $this->project->workProgram->file_url,
                    ] : null,
                    'project_brief' => $this->project->projectBrief ? [
                        'id' => $this->project->projectBrief->id,
                        'file_url' => $this->project->projectBrief->file_url,
                    ] : null,
                    'project_proposal' => $this->project->projectProposal ? [
                        'id' => $this->project->projectProposal->id,
                        'file_url' => $this->project->projectProposal->file_url,
                    ] : null,
                ];
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
