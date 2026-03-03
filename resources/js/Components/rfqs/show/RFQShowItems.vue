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
                                {{ item.item_name || "—" }}
                            </td>
                            <td class="p-4 align-middle text-center">
                                {{ item.quantity || 0 }}
                            </td>
                            <td class="p-4 align-middle text-center">
                                {{ item.unit || "—" }}
                            </td>
                            <td
                                class="p-4 align-middle text-right text-muted-foreground"
                            >
                                (to be filled by supplier)
                            </td>
                            <td
                                class="p-4 align-middle text-right font-medium text-muted-foreground"
                            >
                                (to be filled by supplier)
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </CardContent>
    </Card>
</template>
