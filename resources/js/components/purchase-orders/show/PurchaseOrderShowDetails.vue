<script setup>
defineProps({
    purchaseOrder: Object,
});

const formatDate = (date) => {
    if (!date) return "—";
    return new Date(date).toLocaleDateString("en-US", {
        year: "numeric",
        month: "long",
        day: "numeric",
    });
};

const formatCurrency = (value) =>
    new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
    }).format(value || 0);
</script>

<template>
    <div class="space-y-4">
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-lg border bg-card p-4 text-sm">
                <p class="text-muted-foreground">PO Date</p>
                <p class="font-medium">
                    {{ formatDate(purchaseOrder.po_date) }}
                </p>
            </div>
            <div class="rounded-lg border bg-card p-4 text-sm">
                <p class="text-muted-foreground">NOA No.</p>
                <p class="font-medium">
                    {{ purchaseOrder.noa?.noa_no || "—" }}
                </p>
            </div>
            <div class="rounded-lg border bg-card p-4 text-sm">
                <p class="text-muted-foreground">Delivery Term</p>
                <p class="font-medium">
                    {{ purchaseOrder.delivery_term_days || "—" }}
                    <span v-if="purchaseOrder.delivery_term_days">days</span>
                </p>
            </div>
            <div class="rounded-lg border bg-card p-4 text-sm">
                <p class="text-muted-foreground">Total Amount</p>
                <p class="font-medium">
                    {{ formatCurrency(purchaseOrder.total_amount) }}
                </p>
            </div>

            <div class="rounded-lg border bg-card p-4 text-sm md:col-span-2">
                <p class="text-muted-foreground">Supplier</p>
                <p class="font-medium">
                    {{
                        purchaseOrder.noa?.aoq?.winner_supplier?.name ||
                        purchaseOrder.noa?.bac_resolution?.aoq?.winner_supplier
                            ?.name || "—"
                    }}
                </p>
            </div>
            <div class="rounded-lg border bg-card p-4 text-sm md:col-span-2">
                <p class="text-muted-foreground">Place of Delivery</p>
                <p class="font-medium">
                    {{ purchaseOrder.place_of_delivery || "—" }}
                </p>
            </div>
            <div class="rounded-lg border bg-card p-4 text-sm md:col-span-2">
                <p class="text-muted-foreground">Mode of Procurement</p>
                <p class="font-medium">
                    {{ purchaseOrder.mode_of_procurement || "—" }}
                </p>
            </div>
            <div class="rounded-lg border bg-card p-4 text-sm md:col-span-2">
                <p class="text-muted-foreground">Payment Term</p>
                <p class="font-medium">
                    {{ purchaseOrder.payment_term || "—" }}
                </p>
            </div>
        </div>

        <div class="rounded-lg border bg-card">
            <div class="border-b p-4">
                <p class="font-medium">PO Items</p>
            </div>
            <div class="overflow-auto">
                <table class="w-full text-sm">
                    <thead class="border-b bg-muted/40">
                        <tr>
                            <th class="px-4 py-2 text-left font-medium">
                                Item
                            </th>
                            <th class="px-4 py-2 text-center font-medium">
                                Qty
                            </th>
                            <th class="px-4 py-2 text-left font-medium">
                                Unit
                            </th>
                            <th class="px-4 py-2 text-right font-medium">
                                Unit Cost
                            </th>
                            <th class="px-4 py-2 text-right font-medium">
                                Amount
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="item in purchaseOrder.items"
                            :key="item.id"
                            class="border-b"
                        >
                            <td class="px-4 py-2">
                                {{
                                    item.rfq_item?.purchase_request_item
                                        ?.item_name || "—"
                                }}
                            </td>
                            <td class="px-4 py-2 text-center">
                                {{ item.quantity_snapshot }}
                            </td>
                            <td class="px-4 py-2">
                                {{
                                    item.rfq_item?.purchase_request_item
                                        ?.unit || "—"
                                }}
                            </td>
                            <td class="px-4 py-2 text-right">
                                {{ formatCurrency(item.unit_cost_snapshot) }}
                            </td>
                            <td class="px-4 py-2 text-right">
                                {{ formatCurrency(item.amount_snapshot) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-lg border bg-card p-4 text-sm">
            <p class="text-muted-foreground">Amount in Words</p>
            <p class="font-medium">
                {{ purchaseOrder.total_amount_words || "—" }}
            </p>
        </div>
    </div>
</template>
