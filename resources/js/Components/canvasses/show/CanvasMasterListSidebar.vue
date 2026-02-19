<script setup>
import { Icon } from "@iconify/vue";
import { watch, computed } from "vue";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";

const props = defineProps({
    activeRowId: Number,
    canvas: Object,
    filteredCategories: Array,
    masterListCategories: Array,
    itemSearch: String,
    categoryFilter: String,
    localSelections: Object,
    localComputedPrice: [Number, String, null],
    itemsSubtotal: Number,
    savingRow: Boolean,
    formatCurrency: Function,
});

const emit = defineEmits([
    "update:item-search",
    "update:category-filter",
    "toggle-item",
    "update-selection",
    "update:local-computed-price",
    "save-row-price",
    "close-row-editor",
]);

const isItemSelected = (itemId) => {
    return props.localSelections && props.localSelections[itemId];
};

// Get the current canvas item being edited
const currentCanvasItem = computed(() => {
    if (!props.activeRowId || !props.canvas?.canvas_items) return null;
    return props.canvas.canvas_items.find((ci) => ci.id === props.activeRowId);
});

// Get expected price for the current row
const currentExpectedPrice = computed(() => {
    return currentCanvasItem.value?.emanating_item?.total_price ?? 0;
});

// Check if computed price exceeds expected
const exceedsExpectedPrice = computed(() => {
    const computed = parseFloat(props.localComputedPrice) || 0;
    return computed > 0 && computed > currentExpectedPrice.value;
});

// Auto-update localComputedPrice when itemsSubtotal changes (always override)
watch(
    () => props.itemsSubtotal,
    (newSubtotal) => {
        // Always update with the current subtotal
        if (newSubtotal > 0) {
            emit("update:local-computed-price", newSubtotal);
        }
    },
);
</script>

<template>
    <div>
        <Card
            v-if="activeRowId !== null"
            class="sticky top-4 flex flex-col h-fit max-h-[calc(100vh-8rem)]"
        >
            <CardHeader class="pb-3">
                <div class="space-y-1">
                    <CardTitle class="text-base">Master List Items</CardTitle>
                    <CardDescription class="text-xs">
                        Select items for this row
                    </CardDescription>
                </div>
            </CardHeader>

            <CardContent class="flex-1 flex flex-col min-h-0 p-3 space-y-3">
                <!-- Search & Filter -->
                <div class="space-y-2 shrink-0">
                    <div class="relative">
                        <Icon
                            icon="lucide:search"
                            class="absolute left-2 top-2.5 h-3.5 w-3.5 text-muted-foreground"
                        />
                        <input
                            :value="itemSearch"
                            type="text"
                            placeholder="Search..."
                            class="flex h-8 w-full rounded-md border border-input bg-background pl-7 pr-3 text-xs focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                            @input="
                                emit('update:item-search', $event.target.value)
                            "
                        />
                    </div>
                    <select
                        :value="categoryFilter"
                        class="h-8 w-full rounded-md border border-input bg-background px-2 text-xs focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                        @change="
                            emit('update:category-filter', $event.target.value)
                        "
                    >
                        <option value="">All Categories</option>
                        <option
                            v-for="cat in masterListCategories"
                            :key="cat.id"
                            :value="cat.id"
                        >
                            {{ cat.name }}
                        </option>
                    </select>
                </div>

                <!-- Items List -->
                <div
                    class="flex-1 overflow-y-auto border rounded divide-y min-h-0"
                >
                    <div
                        v-if="filteredCategories.length === 0"
                        class="px-3 py-4 text-center text-xs text-muted-foreground"
                    >
                        No items found
                    </div>
                    <template v-for="cat in filteredCategories" :key="cat.id">
                        <div
                            class="bg-muted/50 px-2.5 py-1.5 text-xs font-semibold text-muted-foreground uppercase tracking-wide sticky top-0"
                        >
                            {{ cat.name }}
                        </div>
                        <div
                            v-for="item in cat.masterListItems"
                            :key="item.id"
                            :class="[
                                'px-2.5 py-2 space-y-1 border-b last:border-0 transition-colors',
                                item.is_phased_out ? 'opacity-50' : '',
                                isItemSelected(item.id)
                                    ? 'bg-primary/5'
                                    : 'hover:bg-muted/50',
                            ]"
                        >
                            <label
                                class="flex items-start gap-2 cursor-pointer"
                            >
                                <input
                                    type="checkbox"
                                    :checked="isItemSelected(item.id)"
                                    :disabled="item.is_phased_out"
                                    class="h-3.5 w-3.5 rounded border-input shrink-0 mt-0.5"
                                    @change="emit('toggle-item', item)"
                                />
                                <div class="min-w-0 flex-1">
                                    <div class="text-xs font-medium truncate">
                                        {{ item.item_name }}
                                    </div>
                                    <div
                                        class="text-xs text-muted-foreground line-clamp-1"
                                    >
                                        {{ item.supplier?.name }}
                                    </div>
                                </div>
                            </label>

                            <!-- Qty + Price inputs -->
                            <div
                                v-if="isItemSelected(item.id)"
                                class="flex gap-1.5 text-xs"
                            >
                                <div class="flex-1">
                                    <div class="text-muted-foreground mb-0.5">
                                        Qty
                                    </div>
                                    <input
                                        type="number"
                                        min="0.01"
                                        step="0.01"
                                        :value="
                                            localSelections[item.id].quantity
                                        "
                                        class="h-7 w-full rounded border border-input bg-background px-1.5 text-right text-xs focus:outline-none focus:ring-1 focus:ring-ring"
                                        @change="
                                            emit('update-selection', {
                                                itemId: item.id,
                                                field: 'quantity',
                                                value: $event.target.value,
                                            })
                                        "
                                    />
                                </div>
                                <div class="flex-1">
                                    <div class="text-muted-foreground mb-0.5">
                                        ₱
                                    </div>
                                    <input
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        :value="
                                            localSelections[item.id].unit_price
                                        "
                                        class="h-7 w-full rounded border border-input bg-background px-1.5 text-right text-xs focus:outline-none focus:ring-1 focus:ring-ring"
                                        @change="
                                            emit('update-selection', {
                                                itemId: item.id,
                                                field: 'unit_price',
                                                value: $event.target.value,
                                            })
                                        "
                                    />
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Set Price Input -->
                <div class="space-y-2 border-t pt-3 shrink-0">
                    <div class="text-xs font-medium">Items Subtotal</div>
                    <div class="text-sm font-mono font-bold text-primary">
                        {{ formatCurrency(itemsSubtotal) }}
                    </div>
                    <div class="text-xs text-muted-foreground">
                        ({{ Object.keys(localSelections).length }} selected)
                    </div>

                    <Label class="text-xs font-medium mt-3 block"
                        >Total Price for This Set
                        <span class="text-destructive">*</span>
                    </Label>
                    <input
                        :value="localComputedPrice"
                        type="number"
                        min="0"
                        step="0.01"
                        placeholder="Enter total price"
                        :class="[
                            'h-9 w-full rounded-md border px-3 text-sm font-mono text-right focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring',
                            exceedsExpectedPrice
                                ? 'border-destructive bg-destructive/5'
                                : 'border-input bg-background',
                        ]"
                        @input="
                            emit(
                                'update:local-computed-price',
                                $event.target.value,
                            )
                        "
                    />
                    <div
                        v-if="exceedsExpectedPrice"
                        class="flex gap-2 p-2 rounded bg-destructive/5 border border-destructive/20"
                    >
                        <Icon
                            icon="lucide:alert-circle"
                            class="h-4 w-4 text-destructive shrink-0 mt-0.5"
                        />
                        <div class="text-xs text-destructive">
                            <p class="font-semibold">Exceeds Expected Price</p>
                            <p class="text-destructive/80">
                                Expected:
                                {{ formatCurrency(currentExpectedPrice) }}
                            </p>
                        </div>
                    </div>
                </div>
            </CardContent>

            <div class="border-t p-3 space-y-2 shrink-0">
                <Button
                    class="w-full"
                    size="sm"
                    :disabled="
                        !localComputedPrice ||
                        parseFloat(localComputedPrice) === 0 ||
                        savingRow
                    "
                    @click="emit('save-row-price')"
                >
                    <Icon icon="lucide:save" class="mr-2 h-3.5 w-3.5" />
                    Save Price
                </Button>
                <Button
                    variant="outline"
                    class="w-full"
                    size="sm"
                    @click="emit('close-row-editor')"
                >
                    Cancel
                </Button>
            </div>
        </Card>

        <div
            v-else
            class="rounded-lg border border-dashed p-6 text-center text-muted-foreground"
        >
            <Icon icon="lucide:info" class="mx-auto mb-2 h-8 w-8 opacity-30" />
            <p class="text-sm">Select a row from the left to price it</p>
        </div>
    </div>
</template>
