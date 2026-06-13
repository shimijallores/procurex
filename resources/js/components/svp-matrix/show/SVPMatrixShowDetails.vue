<script setup>
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";

const props = defineProps({
    matrixRow: Object,
});

const formatCurrency = (value) => {
    if (value === null || value === undefined || value === "") return "-";
    return new Intl.NumberFormat("en-PH", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(value);
};

const entries = (row) => [
    ["OFFICE", row.office],
    ["BATCH", row.batch],
    ["PO NO.", row.po_no],
    ["MODE OF PROCUREMENT", row.mode_of_procurement],
    ["PR NO.", row.pr_no],
    ["ABC", row.abc != null ? formatCurrency(row.abc) : "-"],
    ["SUPPLIER", row.supplier],
    ["PARTICULARS", row.particulars],
    ["AMOUNT", row.amount != null ? formatCurrency(row.amount) : "-"],
    ["RFQ", row.rfq],
    ["ABSTRACT", row.abstract],
    ["RESOLUTION", row.resolution],
    ["NOA & PO", row.noa_po],
    ["TRANSMITTAL FORM", row.transmittal_form],
    ["BAC MEMBERS/GOV", row.bac_members_gov],
    ["FRONTDESK", row.frontdesk],
    ["REMARKS", row.remarks],
];
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Matrix Details</CardTitle>
        </CardHeader>
        <CardContent>
            <div class="grid gap-3 md:grid-cols-2">
                <div
                    v-for="entry in entries(matrixRow)"
                    :key="entry[0]"
                    class="rounded-md border p-3"
                >
                    <div class="text-xs text-muted-foreground">
                        {{ entry[0] }}
                    </div>
                    <div class="font-medium break-words">
                        {{ entry[1] || "-" }}
                    </div>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
