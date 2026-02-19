<script setup>
import { Icon } from "@iconify/vue";
import { Button } from "@/components/ui/button";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";

defineProps({
    canvas: Object,
    isPending: Boolean,
    activeRowId: Number,
    allRowsPriced: Boolean,
    formatCurrency: Function,
    onOpenRowEditor: Function,
});
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Emanating Items to Price</CardTitle>
            <CardDescription>
                {{
                    allRowsPriced
                        ? "✓ All rows priced."
                        : `${canvas.canvas_items?.filter((ci) => ci.computed_price === null).length} row(s) still need pricing.`
                }}
            </CardDescription>
        </CardHeader>
        <CardContent class="p-0">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b bg-muted/50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">#</th>
                            <th class="px-4 py-3 text-left font-medium">
                                Item / Description
                            </th>
                            <th class="px-4 py-3 text-left font-medium">Qty</th>
                            <th class="px-4 py-3 text-left font-medium">
                                Unit
                            </th>
                            <th class="px-4 py-3 text-right font-medium">
                                Set Price
                            </th>
                            <th
                                v-if="isPending"
                                class="px-4 py-3 text-right font-medium"
                            >
                                Action
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-if="!canvas.canvas_items?.length">
                            <td
                                :colspan="isPending ? 6 : 5"
                                class="px-4 py-8 text-center text-muted-foreground"
                            >
                                No items found in this canvas.
                            </td>
                        </tr>
                        <tr
                            v-for="(ci, index) in canvas.canvas_items"
                            :key="ci.id"
                            :class="[
                                'hover:bg-muted/50 transition-colors',
                                activeRowId === ci.id ? 'bg-primary/5' : '',
                            ]"
                        >
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ index + 1 }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-medium">
                                    {{
                                        ci.emanating_item?.ppmp_item?.name ??
                                        `Item #${ci.emanating_item_id}`
                                    }}
                                </div>
                            </td>
                            <td class="px-4 py-3 font-mono text-xs">
                                {{ ci.emanating_item?.quantity ?? "—" }}
                            </td>
                            <td class="px-4 py-3 text-muted-foreground text-xs">
                                {{ ci.emanating_item?.unit ?? "—" }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <span
                                    :class="[
                                        'font-mono text-sm font-medium',
                                        ci.computed_price !== null
                                            ? 'text-foreground'
                                            : 'text-muted-foreground',
                                    ]"
                                >
                                    {{ formatCurrency(ci.computed_price) }}
                                </span>
                            </td>
                            <td v-if="isPending" class="px-4 py-3 text-right">
                                <Button
                                    size="sm"
                                    :variant="
                                        ci.computed_price !== null
                                            ? 'outline'
                                            : 'default'
                                    "
                                    @click="onOpenRowEditor(ci)"
                                >
                                    <Icon
                                        :icon="
                                            ci.computed_price !== null
                                                ? 'lucide:pencil'
                                                : 'lucide:edit-3'
                                        "
                                        class="mr-1.5 h-3.5 w-3.5"
                                    />
                                    {{
                                        ci.computed_price !== null
                                            ? "Edit"
                                            : "Price"
                                    }}
                                </Button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </CardContent>
    </Card>
</template>
