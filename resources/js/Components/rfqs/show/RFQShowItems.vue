<script setup>
import { Icon } from "@iconify/vue";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";

defineProps({
    rfq: Object,
});
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle class="flex items-center gap-2 text-base">
                <Icon icon="lucide:list" class="h-4 w-4 text-primary" />
                RFQ Items
            </CardTitle>
        </CardHeader>
        <CardContent>
            <div class="relative w-full overflow-auto">
                <table class="w-full caption-bottom text-sm">
                    <thead class="border-b">
                        <tr
                            class="border-b transition-colors hover:bg-muted/50"
                        >
                            <th
                                class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                            >
                                Item Description
                            </th>
                            <th
                                class="h-12 px-4 text-center align-middle font-medium text-muted-foreground"
                            >
                                Quantity
                            </th>
                            <th
                                class="h-12 px-4 text-center align-middle font-medium text-muted-foreground"
                            >
                                Unit
                            </th>
                            <th
                                class="h-12 px-4 text-right align-middle font-medium text-muted-foreground"
                            >
                                Estimated Unit Cost
                            </th>
                            <th
                                class="h-12 px-4 text-right align-middle font-medium text-muted-foreground"
                            >
                                Line Total
                            </th>
                        </tr>
                    </thead>
                    <tbody class="[&_tr:last-child]:border-0">
                        <tr
                            v-for="item in rfq.items"
                            :key="item.id"
                            class="border-b transition-colors hover:bg-muted/50"
                        >
                            <td class="p-4 align-middle">
                                {{
                                    item.purchase_request_item?.item_name ||
                                    item.purchase_request_item?.emanating_item
                                        ?.ppmp_item?.name ||
                                    "—"
                                }}
                            </td>
                            <td class="p-4 align-middle text-center">
                                {{ item.purchase_request_item?.quantity || 0 }}
                            </td>
                            <td class="p-4 align-middle text-center">
                                {{
                                    item.purchase_request_item?.unit ||
                                    item.purchase_request_item?.emanating_item
                                        ?.unit ||
                                    "—"
                                }}
                            </td>
                            <td class="p-4 align-middle text-right">
                                {{
                                    new Intl.NumberFormat("en-PH", {
                                        style: "currency",
                                        currency: "PHP",
                                    }).format(
                                        item.purchase_request_item?.unit_cost ||
                                            0,
                                    )
                                }}
                            </td>
                            <td class="p-4 align-middle text-right font-medium">
                                {{
                                    new Intl.NumberFormat("en-PH", {
                                        style: "currency",
                                        currency: "PHP",
                                    }).format(
                                        item.purchase_request_item
                                            ?.line_total || 0,
                                    )
                                }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </CardContent>
    </Card>
</template>
