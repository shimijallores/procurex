<script setup>
import { useForm, router } from "@inertiajs/vue3";
import { ref, watch } from "vue";
import axios from "axios";
import Layout from "@/Layout/Layout.vue";
import PurchaseOrderCreateHeader from "@/components/purchase-orders/create/PurchaseOrderCreateHeader.vue";
import PurchaseOrderCreateForm from "@/components/purchase-orders/create/PurchaseOrderCreateForm.vue";
import { Icon } from "@iconify/vue";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";

defineOptions({
    layout: (h, page) =>
        h(
            Layout,
            {
                breadcrumbs: [
                    {
                        label: "Purchase Order",
                        href: route("purchase-orders.index"),
                    },
                    { label: "Create" },
                ],
            },
            () => page,
        ),
});

const props = defineProps({
    batches: Array,
    eligibleNoas: Array,
    selectedBatchId: String,
    selectedNoaId: String,
    defaults: Object,
});

const form = useForm({
    noa_id: props.selectedNoaId || "",
    po_no: props.defaults?.po_no || "",
    po_date: props.defaults?.po_date || "",
    mode_of_procurement: props.defaults?.mode_of_procurement || "Small Value",
    place_of_delivery: "",
    delivery_term_days: props.defaults?.delivery_term_days || 15,
    payment_term:
        props.defaults?.payment_term || "upon 100% completion /delivery",
    total_amount: 0,
    total_amount_words: "",
    remarks: "",
    items: [],
});

const submit = () => {
    form.post(route("purchase-orders.store"));
};

const selectedBatchDisplay = ref(props.selectedBatchId || "");
const manualBatchNo = ref("");
const creatingManualBatch = ref(false);

const useManualBatch = async () => {
    const no = (manualBatchNo.value || "").trim();
    if (!no || creatingManualBatch.value) return;
    creatingManualBatch.value = true;

    try {
        const { data } = await axios.post(route("aoqs.store-batch"), { batch_no: no });
        selectedBatchDisplay.value = String(data.id);
        manualBatchNo.value = "";
    } catch (err) {
        if (err?.response?.status === 409) {
            const existing = err?.response?.data;
            if (existing?.id) selectedBatchDisplay.value = String(existing.id);
        }
    } finally {
        creatingManualBatch.value = false;
    }
};

watch(selectedBatchDisplay, (id) => {
    if (id) {
        router.get(
            route("purchase-orders.create"),
            { batch_id: id },
            { preserveState: false, preserveScroll: true, replace: true },
        );
    }
});
</script>

<template>
    <div class="space-y-6">
        <PurchaseOrderCreateHeader />

        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-base">
                    <Icon icon="lucide:layers" class="h-4 w-4 text-primary" />
                    Select Batch
                </CardTitle>
            </CardHeader>
            <CardContent class="space-y-3">
                <div class="space-y-2">
                    <Label for="batch_id">Batch</Label>
                    <select
                        id="batch_id"
                        v-model="selectedBatchDisplay"
                        class="flex h-10 w-full max-w-md rounded-md border border-input bg-background px-3 py-2 text-sm"
                    >
                        <option value="">— Select Batch —</option>
                        <option
                            v-for="batch in batches"
                            :key="batch.id"
                            :value="String(batch.id)"
                        >
                            {{ batch.batch_no }}
                            ({{ batch.aoqs_count }} NOA{{ batch.aoqs_count !== 1 ? "s" : "" }})
                        </option>
                    </select>
                </div>

                <div class="flex items-end gap-2">
                    <div class="space-y-1.5 flex-1">
                        <Label for="manual_batch_no">Or type a Batch No.</Label>
                        <input
                            id="manual_batch_no"
                            v-model="manualBatchNo"
                            type="text"
                            placeholder="e.g. 260088"
                            class="flex h-9 w-full max-w-md rounded-md border border-input bg-background px-3 py-2 text-sm font-mono"
                            @keyup.enter="useManualBatch"
                        />
                    </div>
                    <Button
                        type="button"
                        variant="default"
                        size="sm"
                        :disabled="!manualBatchNo.trim() || creatingManualBatch"
                        @click="useManualBatch"
                    >
                        <Icon
                            v-if="creatingManualBatch"
                            icon="lucide:loader-2"
                            class="mr-1 h-3.5 w-3.5 animate-spin"
                        />
                        Use
                    </Button>
                </div>

                <p class="text-xs text-muted-foreground">
                    Select a batch to show eligible NOAs for Purchase Order creation.
                </p>
            </CardContent>
        </Card>

        <PurchaseOrderCreateForm
            v-if="selectedBatchDisplay"
            :form="form"
            :eligible-noas="eligibleNoas"
            :defaults="defaults"
            @submit="submit"
        />
    </div>
</template>
