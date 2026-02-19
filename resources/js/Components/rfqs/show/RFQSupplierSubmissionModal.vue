<script setup>
import { computed, watch } from "vue";
import { useForm } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog";

const props = defineProps({
    open: Boolean,
    rfq: Object,
    supplierEntry: Object,
});

const emit = defineEmits(["update:open"]);

const form = useForm({
    submitted_at: "",
    remarks: "",
    unit_prices: {},
});

const supplierName = computed(
    () => props.supplierEntry?.supplier?.name || "Supplier",
);

const selectedItems = computed(() => props.supplierEntry?.supplier_items || []);

watch(
    () => props.supplierEntry,
    (entry) => {
        if (!entry) return;

        form.submitted_at = entry.submitted_at
            ? new Date(entry.submitted_at).toISOString().slice(0, 16)
            : new Date().toISOString().slice(0, 16);
        form.remarks = entry.remarks || "";

        const prices = {};
        (entry.supplier_items || []).forEach((item) => {
            prices[item.rfq_item_id] = item.unit_price ?? "";
        });
        form.unit_prices = prices;
    },
    { immediate: true },
);

const submit = () => {
    if (!props.supplierEntry) return;

    form.post(
        route("rfqs.suppliers.submit", [props.rfq.id, props.supplierEntry.id]),
        {
            onSuccess: () => {
                emit("update:open", false);
            },
        },
    );
};
</script>

<template>
    <Dialog :open="open" @update:open="$emit('update:open', $event)">
        <DialogContent class="max-w-3xl">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2">
                    <Icon icon="lucide:save" class="h-5 w-5 text-primary" />
                    Save Supplier Submission
                </DialogTitle>
                <DialogDescription>
                    Update submitted date, remarks, and quoted unit prices for
                    {{ supplierName }}.
                </DialogDescription>
            </DialogHeader>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="space-y-2">
                    <Label for="submitted_at">Submitted At</Label>
                    <input
                        id="submitted_at"
                        v-model="form.submitted_at"
                        type="datetime-local"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                    />
                    <p
                        v-if="form.errors?.submitted_at"
                        class="text-xs text-destructive"
                    >
                        {{ form.errors.submitted_at }}
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="remarks">Remarks</Label>
                    <input
                        id="remarks"
                        v-model="form.remarks"
                        type="text"
                        placeholder="Optional remarks"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                    />
                    <p
                        v-if="form.errors?.remarks"
                        class="text-xs text-destructive"
                    >
                        {{ form.errors.remarks }}
                    </p>
                </div>
            </div>

            <div class="max-h-[340px] overflow-auto rounded-md border">
                <table class="w-full text-sm">
                    <thead class="border-b bg-muted/30">
                        <tr>
                            <th
                                class="px-3 py-2 text-left font-medium text-muted-foreground"
                            >
                                Item
                            </th>
                            <th
                                class="px-3 py-2 text-center font-medium text-muted-foreground"
                            >
                                Qty
                            </th>
                            <th
                                class="px-3 py-2 text-center font-medium text-muted-foreground"
                            >
                                Unit
                            </th>
                            <th
                                class="px-3 py-2 text-right font-medium text-muted-foreground"
                            >
                                Unit Price
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="supplierItem in selectedItems"
                            :key="supplierItem.id"
                            class="border-b"
                        >
                            <td class="px-3 py-2">
                                {{
                                    supplierItem.rfq_item?.purchase_request_item
                                        ?.item_name ||
                                    supplierItem.rfq_item?.purchase_request_item
                                        ?.emanating_item?.ppmp_item?.name ||
                                    "—"
                                }}
                            </td>
                            <td class="px-3 py-2 text-center">
                                {{
                                    supplierItem.rfq_item?.purchase_request_item
                                        ?.quantity || 0
                                }}
                            </td>
                            <td class="px-3 py-2 text-center">
                                {{
                                    supplierItem.rfq_item?.purchase_request_item
                                        ?.unit ||
                                    supplierItem.rfq_item?.purchase_request_item
                                        ?.emanating_item?.unit ||
                                    "—"
                                }}
                            </td>
                            <td class="px-3 py-2">
                                <input
                                    v-model="
                                        form.unit_prices[
                                            supplierItem.rfq_item_id
                                        ]
                                    "
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    class="flex h-9 w-full rounded-md border border-input bg-background px-2 py-1 text-sm text-right"
                                />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <DialogFooter>
                <Button variant="outline" @click="$emit('update:open', false)">
                    Cancel
                </Button>
                <Button :disabled="form.processing" @click="submit">
                    <Icon
                        v-if="form.processing"
                        icon="lucide:loader-2"
                        class="mr-2 h-4 w-4 animate-spin"
                    />
                    Save Quotation
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
