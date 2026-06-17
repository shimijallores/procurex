<script setup>
import { useForm, router } from "@inertiajs/vue3";
import { ref } from "vue";
import axios from "axios";
import SvpSmartInput from "@/components/SvpSmartInput.vue";
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

const svpSearchNo = ref("");
const searchingSvp = ref(false);
const svpError = ref("");

const findBatchBySvp = async (svp) => {
    if (!svp || searchingSvp.value) return;
    searchingSvp.value = true;
    svpError.value = "";

    try {
        const res = await axios.get(route("noas.find-batch-by-svp"), {
            params: { svp_no: svp },
        });
        router.get(
            route("purchase-orders.create"),
            { batch_id: res.data.batch.id },
            { preserveState: false, preserveScroll: true, replace: true },
        );
    } catch (err) {
        svpError.value = err?.response?.data?.error || "SVP not found. Make sure the AOQ has a batch assigned.";
    } finally {
        searchingSvp.value = false;
    }
};
</script>

<template>
    <div class="space-y-6">
        <PurchaseOrderCreateHeader />

        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-base">
                    <Icon icon="lucide:search" class="h-4 w-4 text-primary" />
                    Find by SVP Number
                </CardTitle>
            </CardHeader>
            <CardContent>
                <div class="max-w-md space-y-3">
                    <div class="space-y-2">
                        <Label for="svp_search">SVP Number</Label>
                        <SvpSmartInput
                            v-model="svpSearchNo"
                            :disabled="searchingSvp"
                            @select="findBatchBySvp"
                        />
                    </div>

                    <Button
                        type="button"
                        variant="default"
                        size="sm"
                        :disabled="!svpSearchNo.trim() || searchingSvp"
                        @click="findBatchBySvp(svpSearchNo)"
                    >
                        <Icon
                            v-if="searchingSvp"
                            icon="lucide:loader-2"
                            class="mr-1 h-3.5 w-3.5 animate-spin"
                        />
                        Find NOAs
                    </Button>

                    <p v-if="svpError" class="text-xs text-destructive">{{ svpError }}</p>

                    <p class="text-xs text-muted-foreground">
                        Enter an SVP number to find the batch and load eligible NOAs for Purchase Order creation.
                    </p>
                </div>
            </CardContent>
        </Card>

        <PurchaseOrderCreateForm
            v-if="selectedBatchId"
            :form="form"
            :eligible-noas="eligibleNoas"
            :defaults="defaults"
            @submit="submit"
        />
    </div>
</template>
