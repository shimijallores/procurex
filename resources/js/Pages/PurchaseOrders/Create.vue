<script setup>
import { useForm, router } from "@inertiajs/vue3";
import { ref, watch } from "vue";
import axios from "axios";
import { route } from "ziggy-js";
import NoaSmartInput from "@/components/NoaSmartInput.vue";
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
    batchNoas: Array,
    batchId: String,
    defaults: Object,
});

function buildInitialNoas(batchNoas, defaults) {
    if (!batchNoas?.length) return [];
    return batchNoas.map((noa) => ({
        noa_id: noa.id,
        selected: true,
        po_no: noa.suggested_po_no || "",
        po_date: defaults?.po_date || "",
        mode_of_procurement: defaults?.mode_of_procurement || "Small Value",
        delivery_term_days: noa.suggested_delivery_days ?? ((noa.winner_amount || 0) >= 200000 ? 30 : 15),
        payment_term: defaults?.payment_term || "upon 100% completion /delivery",
        place_of_delivery: noa.office_name || "",
        remarks: "",
    }));
}

const form = useForm({
    noas: buildInitialNoas(props.batchNoas, props.defaults),
});

const submit = () => {
    form.transform((data) => ({
        noas: data.noas.filter((n) => n.selected),
    })).post(route("purchase-orders.store"));
};

const toggleNoa = (id) => {
    const noa = form.noas.find((n) => n.noa_id === id);
    if (noa) noa.selected = !noa.selected;
};

const selectAll = () => {
    form.noas.forEach((n) => (n.selected = true));
};

const deselectAll = () => {
    form.noas.forEach((n) => (n.selected = false));
};

const resetForm = () => {
    form.reset();
};

const noaSearchNo = ref("");
const searchingNoa = ref(false);
const noaError = ref("");

const autoCompleteNoa = (raw) => {
    const val = (raw || "").trim();
    if (!val) return val;

    if (/^\d{4}-\d{4}$/.test(val)) return val;

    if (/^\d{1,4}$/.test(val)) {
        const padded = val.padStart(4, "0");
        return String(new Date().getFullYear()) + "-" + padded;
    }

    return val;
};

const findBatchByNoa = async (noa) => {
    const fullNoa = autoCompleteNoa(noa);
    if (!fullNoa || searchingNoa.value) return;
    searchingNoa.value = true;
    noaError.value = "";

    try {
        const res = await axios.get(route("noas.find-batch-by-noa"), {
            params: { noa_no: fullNoa },
        });
        router.get(
            route("purchase-orders.create"),
            { batch_id: res.data.batch.id },
            { preserveState: false, preserveScroll: true, replace: true },
        );
    } catch (err) {
        noaError.value = err?.response?.data?.error || `NOA "${fullNoa}" not found or has no batch.`;
    } finally {
        searchingNoa.value = false;
    }
};

const formatCurrency = (value) =>
    new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
    }).format(value || 0);
</script>

<template>
    <div class="space-y-6">
        <PurchaseOrderCreateHeader />

        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-base">
                    <Icon icon="lucide:search" class="h-4 w-4 text-primary" />
                    Find by NOA Number
                </CardTitle>
            </CardHeader>
            <CardContent>
                <div class="max-w-md space-y-3">
                    <div class="space-y-2">
                        <Label for="noa_search">NOA Number</Label>
                        <NoaSmartInput
                            :model-value="noaSearchNo"
                            context="po"
                            :disabled="searchingNoa"
                            @update:model-value="noaSearchNo = String($event)"
                            @select="findBatchByNoa"
                        />
                    </div>

                    <Button
                        type="button"
                        variant="default"
                        size="sm"
                        :disabled="!String(noaSearchNo ?? '').trim() || searchingNoa"
                        @click="findBatchByNoa(String(noaSearchNo ?? ''))"
                    >
                        <Icon
                            v-if="searchingNoa"
                            icon="lucide:loader-2"
                            class="mr-1 h-3.5 w-3.5 animate-spin"
                        />
                        Find NOAs
                    </Button>

                    <p v-if="noaError" class="text-xs text-destructive">{{ noaError }}</p>

                    <p class="text-xs text-muted-foreground">
                        Enter a NOA number (last 4 digits) to find its batch and load all NOAs for PO creation.
                    </p>
                </div>
            </CardContent>
        </Card>

        <PurchaseOrderCreateForm
            v-if="batchId"
            :form="form"
            :batch-noas="batchNoas"
            :defaults="defaults"
            :format-currency="formatCurrency"
            @submit="submit"
            @toggle-noa="toggleNoa"
            @select-all="selectAll"
            @deselect-all="deselectAll"
            @reset="resetForm"
        />
    </div>
</template>
