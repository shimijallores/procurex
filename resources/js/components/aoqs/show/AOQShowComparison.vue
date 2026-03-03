<script setup>
const props = defineProps({
    aoq: Object,
    calculation: Object,
});

const formatCurrency = (value) =>
    new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
    }).format(value || 0);

const resolvedSuppliers = () => {
    const totals = props.calculation?.supplier_totals || [];
    return totals
        .map((total) => {
            const entry = (props.aoq?.rfq?.suppliers || []).find(
                (s) => s.supplier_id === total.supplier_id,
            );
            return {
                ...total,
                supplier_entry: entry,
            };
        })
        .slice(0, 3);
};

const lineTotal = (supplierEntry, rfqItem) => {
    if (!supplierEntry) return null;
    const supplierItem = (supplierEntry.supplier_items || []).find(
        (item) => item.rfq_item_id === rfqItem.id,
    );
    if (
        !supplierItem ||
        supplierItem.unit_price === null ||
        supplierItem.unit_price === undefined
    )
        return null;
    const quantity = Number(rfqItem.quantity || 0);
    return Number(supplierItem.unit_price) * quantity;
};

const unitPrice = (supplierEntry, rfqItem) => {
    if (!supplierEntry) return null;
    const supplierItem = (supplierEntry.supplier_items || []).find(
        (item) => item.rfq_item_id === rfqItem.id,
    );
    return supplierItem?.unit_price ?? null;
};

const calculationMessage = () => {
    const count = Number(props.calculation?.calculated_supplier_count || 0);

    if (count >= 2) {
        return "lowest calculated";
    }

    if (count === 1) {
        return "single calculated";
    }

    return "";
};
</script>

<template>
    <div class="rounded-lg border bg-card p-4 space-y-4">
        <div>
            <h2 class="text-lg font-semibold">Abstract Comparison</h2>
            <p
                v-if="calculationMessage()"
                class="text-sm text-muted-foreground"
            >
                {{ calculationMessage() }}
            </p>
        </div>

        <div class="relative w-full overflow-auto">
            <table class="w-full text-sm">
                <thead class="border-b">
                    <tr>
                        <th
                            class="px-3 py-2 text-left font-medium text-muted-foreground"
                        >
                            QTY
                        </th>
                        <th
                            class="px-3 py-2 text-left font-medium text-muted-foreground"
                        >
                            UNIT
                        </th>
                        <th
                            class="px-3 py-2 text-left font-medium text-muted-foreground"
                        >
                            PARTICULARS
                        </th>
                        <th
                            class="px-3 py-2 text-right font-medium text-muted-foreground"
                        >
                            ABC
                        </th>
                        <template
                            v-for="supplier in resolvedSuppliers()"
                            :key="supplier.supplier_id"
                        >
                            <th
                                class="px-3 py-2 text-right font-medium text-muted-foreground"
                            >
                                {{ supplier.supplier_name }} (Unit)
                            </th>
                            <th
                                class="px-3 py-2 text-right font-medium text-muted-foreground"
                            >
                                {{ supplier.supplier_name }} (Total)
                            </th>
                        </template>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="rfqItem in aoq.rfq?.items || []"
                        :key="rfqItem.id"
                        class="border-b"
                    >
                        <td class="px-3 py-2">
                            {{ rfqItem.quantity || 0 }}
                        </td>
                        <td class="px-3 py-2">
                            {{ rfqItem.unit || "—" }}
                        </td>
                        <td class="px-3 py-2">
                            {{ rfqItem.item_name || "—" }}
                        </td>
                        <td class="px-3 py-2 text-right">
                            {{
                                formatCurrency(
                                    Number(rfqItem.quantity || 0) *
                                        Number(
                                            rfqItem.purchase_request_item
                                                ?.unit_cost || 0,
                                        ),
                                )
                            }}
                        </td>
                        <template
                            v-for="supplier in resolvedSuppliers()"
                            :key="supplier.supplier_id + '-' + rfqItem.id"
                        >
                            <td class="px-3 py-2 text-right">
                                {{
                                    unitPrice(
                                        supplier.supplier_entry,
                                        rfqItem,
                                    ) !== null
                                        ? formatCurrency(
                                              unitPrice(
                                                  supplier.supplier_entry,
                                                  rfqItem,
                                              ),
                                          )
                                        : "—"
                                }}
                            </td>
                            <td class="px-3 py-2 text-right">
                                {{
                                    lineTotal(
                                        supplier.supplier_entry,
                                        rfqItem,
                                    ) !== null
                                        ? formatCurrency(
                                              lineTotal(
                                                  supplier.supplier_entry,
                                                  rfqItem,
                                              ),
                                          )
                                        : "—"
                                }}
                            </td>
                        </template>
                    </tr>
                </tbody>
                <tfoot>
                    <tr class="font-semibold">
                        <td colspan="3" class="px-3 py-2 text-right">
                            GRAND TOTAL
                        </td>
                        <td class="px-3 py-2 text-right">
                            {{ formatCurrency(aoq.rfq?.abc_amount || 0) }}
                        </td>
                        <template
                            v-for="supplier in resolvedSuppliers()"
                            :key="supplier.supplier_id + '-total'"
                        >
                            <td class="px-3 py-2 text-right">—</td>
                            <td class="px-3 py-2 text-right">
                                {{ formatCurrency(supplier.total_amount) }}
                            </td>
                        </template>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="rounded-md border border-border bg-muted/40 p-3 text-sm">
            <p>
                Winner Supplier:
                <span class="font-semibold">{{
                    aoq.winner_supplier?.name || "—"
                }}</span>
            </p>
        </div>
    </div>
</template>
