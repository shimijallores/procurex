<script setup>
import { computed } from "vue";
import { Icon } from "@iconify/vue";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";

const props = defineProps({
    purchaseRequest: Object,
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
    }).format(value || 0);
};

const grandTotal = computed(() => {
    return (
        props.purchaseRequest?.items?.reduce(
            (sum, item) => sum + parseFloat(item.line_total || 0),
            0,
        ) || 0
    );
});
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle class="flex items-center gap-2 text-base">
                <Icon icon="lucide:package" class="h-4 w-4 text-primary" />
                Items
                <span class="ml-auto text-sm font-normal text-muted-foreground">
                    {{ purchaseRequest.items?.length || 0 }} item(s)
                </span>
            </CardTitle>
        </CardHeader>
        <CardContent>
            <div class="relative w-full overflow-auto">
                <table class="w-full caption-bottom text-sm">
                    <thead class="border-b">
                        <tr class="border-b">
                            <th
                                class="h-10 px-3 text-left align-middle font-medium text-muted-foreground"
                            >
                                #
                            </th>
                            <th
                                class="h-10 px-3 text-left align-middle font-medium text-muted-foreground"
                            >
                                Description
                            </th>
                            <th
                                class="h-10 px-3 text-center align-middle font-medium text-muted-foreground"
                            >
                                Unit
                            </th>
                            <th
                                class="h-10 px-3 text-center align-middle font-medium text-muted-foreground"
                            >
                                Qty
                            </th>
                            <th
                                class="h-10 px-3 text-right align-middle font-medium text-muted-foreground"
                            >
                                Unit Cost
                            </th>
                            <th
                                class="h-10 px-3 text-center align-middle font-medium text-muted-foreground"
                            >
                                VAT
                            </th>
                            <th
                                class="h-10 px-3 text-right align-middle font-medium text-muted-foreground"
                            >
                                Line Total
                            </th>
                        </tr>
                    </thead>
                    <tbody class="[&_tr:last-child]:border-0">
                        <tr v-if="!purchaseRequest.items?.length">
                            <td
                                colspan="7"
                                class="p-6 text-center text-muted-foreground"
                            >
                                No items found.
                            </td>
                        </tr>
                        <tr
                            v-for="(item, idx) in purchaseRequest.items"
                            :key="item.id"
                            class="border-b transition-colors hover:bg-muted/50"
                        >
                            <td
                                class="p-3 align-middle text-muted-foreground text-xs"
                            >
                                {{ idx + 1 }}
                            </td>
                            <td class="p-3 align-middle">
                                <div class="font-medium">
                                    {{
                                        item.item_name ||
                                        item.emanating_item?.ppmp_item?.name ||
                                        "—"
                                    }}
                                </div>
                                <div
                                    v-if="item.remarks"
                                    class="text-xs text-muted-foreground mt-0.5"
                                >
                                    {{ item.remarks }}
                                </div>
                            </td>
                            <td
                                class="p-3 align-middle text-center text-muted-foreground"
                            >
                                {{
                                    item.unit ||
                                    item.emanating_item?.unit ||
                                    "—"
                                }}
                            </td>
                            <td
                                class="p-3 align-middle text-center font-medium"
                            >
                                {{ item.quantity }}
                            </td>
                            <td class="p-3 align-middle text-right">
                                {{ formatCurrency(item.unit_cost) }}
                            </td>
                            <td class="p-3 align-middle text-center">
                                <span
                                    v-if="item.vat_applicable"
                                    class="inline-flex items-center gap-1 rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900 dark:text-blue-300"
                                >
                                    <Icon icon="lucide:check" class="h-3 w-3" />
                                    12%
                                </span>
                                <span
                                    v-else
                                    class="text-muted-foreground text-xs"
                                    >—</span
                                >
                            </td>
                            <td
                                class="p-3 align-middle text-right font-semibold"
                            >
                                {{ formatCurrency(item.line_total) }}
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="border-t bg-muted/30">
                            <td
                                colspan="6"
                                class="px-3 py-3 text-right font-semibold text-sm"
                            >
                                Grand Total
                            </td>
                            <td
                                class="px-3 py-3 text-right font-bold text-primary text-base"
                            >
                                {{ formatCurrency(grandTotal) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </CardContent>
    </Card>
</template>
