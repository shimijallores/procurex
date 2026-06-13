<script setup>
import { computed, ref, watch } from "vue";
import { Icon } from "@iconify/vue";
import axios from "axios";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";

const props = defineProps({
    form: Object,
    batches: Array,
});

defineEmits(["submit"]);

const selectedBatchId = ref("");
const purchaseOrders = ref([]);
const loadingPos = ref(false);

const headerTouched = ref(false);
const signatoryTouched = ref(false);
const titleTouched = ref(false);
const opgHeaderTouched = ref(false);
const opgSignatoryTouched = ref(false);
const opgTitleTouched = ref(false);

const selectedPurchaseOrder = computed(() =>
    purchaseOrders.value?.find(
        (po) => String(po.id) === String(props.form.purchase_order_id),
    ),
);

const fetchPurchaseOrders = async (batchId) => {
    if (!batchId) {
        purchaseOrders.value = [];
        props.form.purchase_order_id = "";
        return;
    }

    loadingPos.value = true;
    try {
        const res = await axios.get(
            route("po-transmittals.batch-purchase-orders", batchId),
        );
        purchaseOrders.value = res.data.purchaseOrders || [];
        props.form.purchase_order_id = "";
    } catch {
        purchaseOrders.value = [];
    } finally {
        loadingPos.value = false;
    }
};

watch(selectedBatchId, (id) => {
    fetchPurchaseOrders(id);
});

watch(
    () => props.form.purchase_order_id,
    (poId) => {
        if (!poId) return;
        const po = purchaseOrders.value?.find(
            (p) => String(p.id) === String(poId),
        );
        if (!po) return;

        if (!headerTouched.value) {
            props.form.coa.header_text =
                "MARIA VANESSA C. BRIONES - VEGAS\nOIC – SUPERVISING AUDITOR\nCOMMISSION ON AUDIT\nCapitol Site, Batangas City\n\nMa'am,";
        }
        if (!signatoryTouched.value) {
            props.form.coa.signatory_name = "NOEL R. ROCAFORT";
        }
        if (!titleTouched.value) {
            props.form.coa.signatory_title = "PGDH – GSO";
        }
        if (!opgHeaderTouched.value) {
            props.form.opg.header_text =
                "HON. VILMA SANTOS - RECTO\nGovernor\nProvince of Batangas\nCapitol Site, Batangas City\n\nMa'am,";
        }
        if (!opgSignatoryTouched.value) {
            props.form.opg.signatory_name = "NOEL R. ROCAFORT";
        }
        if (!opgTitleTouched.value) {
            props.form.opg.signatory_title = "PGDH – GSO";
        }

        props.form.coa.transmittal_no = po._coa_transmittal_no || "";
        props.form.opg.transmittal_no = po._opg_transmittal_no || "";
    },
);
</script>

<template>
    <form @submit.prevent="$emit('submit')" class="space-y-6">
        <Card>
            <CardHeader>
                <CardTitle class="text-base">Select Batch</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="space-y-2">
                    <Label for="batch_id">Batch</Label>
                    <select
                        id="batch_id"
                        v-model="selectedBatchId"
                        class="flex h-10 w-full max-w-md rounded-md border border-input bg-background px-3 py-2 text-sm"
                    >
                        <option value="">— Select Batch —</option>
                        <option
                            v-for="batch in batches"
                            :key="batch.id"
                            :value="String(batch.id)"
                        >
                            {{ batch.batch_no }}
                            ({{ batch.aoqs_count }} PO{{ batch.aoqs_count !== 1 ? "s" : "" }})
                        </option>
                    </select>
                </div>
            </CardContent>
        </Card>

        <Card v-if="selectedBatchId">
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-base">
                    <Icon icon="lucide:file-text" class="h-4 w-4 text-primary" />
                    Purchase Orders in Batch
                    <span v-if="loadingPos" class="ml-2">
                        <Icon
                            icon="lucide:loader-2"
                            class="h-4 w-4 animate-spin text-muted-foreground"
                        />
                    </span>
                </CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <div
                    v-if="!purchaseOrders.length && !loadingPos"
                    class="py-6 text-center text-sm text-muted-foreground"
                >
                    All POs in this batch already have transmittals.
                </div>

                <div v-if="purchaseOrders.length" class="space-y-2">
                    <Label for="purchase_order_id">Purchase Order</Label>
                    <select
                        id="purchase_order_id"
                        :value="form.purchase_order_id"
                        @change="form.purchase_order_id = $event.target.value"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    >
                        <option value="">— Select Purchase Order —</option>
                        <option
                            v-for="po in purchaseOrders"
                            :key="po.id"
                            :value="po.id"
                        >
                            {{ po.po_no }} —
                            {{ po.noa?.aoq?.rfq?.project_name || po.noa?.bac_resolution?.project_name || "—" }}
                        </option>
                    </select>
                    <p
                        v-if="form.errors?.purchase_order_id"
                        class="text-xs text-destructive"
                    >
                        {{ form.errors.purchase_order_id }}
                    </p>
                </div>

                <div
                    v-if="selectedPurchaseOrder"
                    class="grid gap-3 text-sm md:grid-cols-2"
                >
                    <div>
                        <p class="text-muted-foreground">Supplier</p>
                        <p class="font-medium">
                            {{
                                selectedPurchaseOrder.noa?.aoq?.winner_supplier
                                    ?.name
                                    || selectedPurchaseOrder.noa?.bac_resolution
                                        ?.aoq?.winner_supplier?.name
                                    || "—"
                            }}
                        </p>
                    </div>
                    <div>
                        <p class="text-muted-foreground">SVP No.</p>
                        <p class="font-mono font-medium">
                            {{ selectedPurchaseOrder._svp_no || "—" }}
                        </p>
                    </div>
                </div>
            </CardContent>
        </Card>

        <div
            v-if="selectedPurchaseOrder"
            class="grid gap-6 lg:grid-cols-2"
        >
            <Card>
                <CardHeader>
                    <CardTitle class="text-base">COA Transmittal</CardTitle>
                </CardHeader>
                <CardContent class="grid gap-4 md:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="coa_transmittal_no">Transmittal No.</Label>
                        <input
                            id="coa_transmittal_no"
                            v-model="form.coa.transmittal_no"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        />
                        <p
                            v-if="form.errors?.['coa.transmittal_no']"
                            class="text-xs text-destructive"
                        >
                            {{ form.errors["coa.transmittal_no"] }}
                        </p>
                    </div>

                    <div class="space-y-2 md:col-span-2">
                        <Label for="coa_header_text">Header / Addressee</Label>
                        <textarea
                            id="coa_header_text"
                            v-model="form.coa.header_text"
                            rows="6"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            @input="headerTouched = true"
                        />
                        <p
                            v-if="form.errors?.['coa.header_text']"
                            class="text-xs text-destructive"
                        >
                            {{ form.errors["coa.header_text"] }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="coa_signatory_name">Signatory Name</Label>
                        <input
                            id="coa_signatory_name"
                            v-model="form.coa.signatory_name"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            @input="signatoryTouched = true"
                        />
                        <p
                            v-if="form.errors?.['coa.signatory_name']"
                            class="text-xs text-destructive"
                        >
                            {{ form.errors["coa.signatory_name"] }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="coa_signatory_title">Signatory Title</Label>
                        <input
                            id="coa_signatory_title"
                            v-model="form.coa.signatory_title"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            @input="titleTouched = true"
                        />
                        <p
                            v-if="form.errors?.['coa.signatory_title']"
                            class="text-xs text-destructive"
                        >
                            {{ form.errors["coa.signatory_title"] }}
                        </p>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle class="text-base">OPG Transmittal</CardTitle>
                </CardHeader>
                <CardContent class="grid gap-4 md:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="opg_transmittal_no">Transmittal No.</Label>
                        <input
                            id="opg_transmittal_no"
                            v-model="form.opg.transmittal_no"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        />
                        <p
                            v-if="form.errors?.['opg.transmittal_no']"
                            class="text-xs text-destructive"
                        >
                            {{ form.errors["opg.transmittal_no"] }}
                        </p>
                    </div>

                    <div class="space-y-2 md:col-span-2">
                        <Label for="opg_header_text">Header / Addressee</Label>
                        <textarea
                            id="opg_header_text"
                            v-model="form.opg.header_text"
                            rows="6"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            @input="opgHeaderTouched = true"
                        />
                        <p
                            v-if="form.errors?.['opg.header_text']"
                            class="text-xs text-destructive"
                        >
                            {{ form.errors["opg.header_text"] }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="opg_signatory_name">Signatory Name</Label>
                        <input
                            id="opg_signatory_name"
                            v-model="form.opg.signatory_name"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            @input="opgSignatoryTouched = true"
                        />
                        <p
                            v-if="form.errors?.['opg.signatory_name']"
                            class="text-xs text-destructive"
                        >
                            {{ form.errors["opg.signatory_name"] }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="opg_signatory_title">Signatory Title</Label>
                        <input
                            id="opg_signatory_title"
                            v-model="form.opg.signatory_title"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            @input="opgTitleTouched = true"
                        />
                        <p
                            v-if="form.errors?.['opg.signatory_title']"
                            class="text-xs text-destructive"
                        >
                            {{ form.errors["opg.signatory_title"] }}
                        </p>
                    </div>
                </CardContent>
            </Card>
        </div>

        <div
            v-if="selectedPurchaseOrder"
            class="flex justify-end gap-2"
        >
            <Button type="button" variant="outline" @click="form.reset()">
                Reset
            </Button>
            <Button type="submit" :disabled="form.processing">
                <Icon
                    v-if="form.processing"
                    icon="lucide:loader-2"
                    class="mr-2 h-4 w-4 animate-spin"
                />
                Create COA + OPG Transmittals
            </Button>
        </div>
    </form>
</template>
