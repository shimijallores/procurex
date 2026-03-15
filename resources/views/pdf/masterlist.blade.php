<table border="0" cellspacing="0" cellpadding="0" style="width: 100%; border: none; border-collapse: collapse; margin: 0;">
    <tbody>
        <tr>
            <td style="width: 15%; border: none; text-align: center; vertical-align: middle;">
                @if (!empty($sealSrc))
                <img src="{{ $sealSrc }}" width="58" height="58" alt="Batangas Seal" />
                @endif
            </td>
            <td style="width: 70%; border: none; text-align: center; vertical-align: middle; line-height: 1.2;">
                <strong>Republic of the Philippines</strong><br />
                <strong>Provincial Government of Batangas</strong><br />
                Capitol Site, Kumintang Ibaba, Batangas City 4200<br />
                <strong>Master List Items</strong>
            </td>
            <td style="width: 15%; border: none; text-align: center; vertical-align: middle;">
                @if (!empty($bagongSrc))
                <img src="{{ $bagongSrc }}" width="74" height="58" alt="Bagong Pilipinas" />
                @endif
            </td>
        </tr>
    </tbody>
</table>

<p style="margin: 0; line-height: 1;">&nbsp;</p>

<table style="width: 100%; border: 0; border-collapse: collapse; margin-top: 8px;">
    <thead>
        <tr>
            <th style="border: 0.5px solid #999; padding: 6px; text-align: left;">Item</th>
            <th style="border: 0.5px solid #999; padding: 6px; text-align: left;">Category</th>
            <th style="border: 0.5px solid #999; padding: 6px; text-align: left;">Supplier</th>
            <th style="border: 0.5px solid #999; padding: 6px; text-align: left;">Unit</th>
            <th style="border: 0.5px solid #999; padding: 6px; text-align: right;">Default Price</th>
            <th style="border: 0.5px solid #999; padding: 6px; text-align: left;">Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($items as $item)
        <tr>
            <td style="border: 0.5px solid #999; padding: 6px;">{{ $item->item_name }}</td>
            <td style="border: 0.5px solid #999; padding: 6px;">{{ $item->masterListCategory?->name ?? '-' }}</td>
            <td style="border: 0.5px solid #999; padding: 6px;">{{ $item->supplier?->name ?? '-' }}</td>
            <td style="border: 0.5px solid #999; padding: 6px;">{{ $item->unit ?? '-' }}</td>
            <td style="border: 0.5px solid #999; padding: 6px; text-align: right;">{{ number_format((float) ($item->default_unit_price ?? 0), 2) }}</td>
            <td style="border: 0.5px solid #999; padding: 6px;">{{ $item->is_phased_out ? 'Phased Out' : 'Active' }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="6" style="border: 0.5px solid #999; padding: 8px; text-align: center;">No master list items found for the selected filters.</td>
        </tr>
        @endforelse
    </tbody>
</table>