<script setup>
import { computed, ref } from "vue";
import { Icon } from "@iconify/vue";
import { router } from "@inertiajs/vue3";
import axios from "axios";
import BatchSmartInput from "@/components/BatchSmartInput.vue";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";

const defaultCoaHeader =
    "MARIA VANESSA C. BRIONES - VEGAS\nOIC – SUPERVISING AUDITOR\nCOMMISSION ON AUDIT\nCapitol Site, Batangas City\n\nMa'am,";
const defaultOpgHeader =
    "HON. VILMA SANTOS - RECTO\nGovernor\nProvince of Batangas\nCapitol Site, Batangas City\n\nMa'am,";
const defaultSignatory = "NOEL R. ROCAFORT";
const defaultTitle = "PGDH – GSO";

const selectedBatchId = ref("");
const purchaseOrders = ref([]);
const loadingPos = ref(false);
const submitting = ref(false);
const selectedPoIds = ref([]);

const fetchPurchaseOrders = async (batchId) => {
    if (!batchId) {
        purchaseOrders.value = [];
        selectedPoIds.value = [];
        return;
    }

    loadingPos.value = true;
    try {
        const res = await axios.get(
            route("po-transmittals.batch-purchase-orders", batchId),
        );
        purchaseOrders.value = res.data.purchaseOrders || [];
        const poList = res.data.purchaseOrders || [];
        selectedPoIds.value = poList.map((po) => String(po.id));
    } catch {
        purchaseOrders.value = [];
        selectedPoIds.value = [];
    } finally {
        loadingPos.value = false;
    }
};

const onBatchSelect = (batch) => {
    selectedBatchId.value = String(batch.id);
    fetchPurchaseOrders(batch.id);
};

const selectedCount = computed(() => selectedPoIds.value.length);

const togglePo = (id) => {
    const idx = selectedPoIds.value.indexOf(id);
    if (idx === -1) {
        selectedPoIds.value = [...selectedPoIds.value, id];
    } else {
        selectedPoIds.value = selectedPoIds.value.filter((v) => v !== id);
    }
};

const selectAllPos = () => {
    selectedPoIds.value = purchaseOrders.value.map((po) => String(po.id));
};

const deselectAllPos = () => {
    selectedPoIds.value = [];
};

const submitAll = () => {
    if (submitting.value) return;
    submitting.value = true;

    const selected = purchaseOrders.value.filter((po) =>
        selectedPoIds.value.includes(String(po.id)),
    );

    const payload = {
        purchase_orders: selected.map((po) => ({
            id: po.id,
            coa: po._coa || {
                transmittal_no: po._coa_transmittal_no || "",
                header_text: defaultCoaHeader,
                signatory_name: defaultSignatory,
                signatory_title: defaultTitle,
            },
            opg: po._opg || {
                transmittal_no: po._opg_transmittal_no || "",
                header_text: defaultOpgHeader,
                signatory_name: defaultSignatory,
                signatory_title: defaultTitle,
            },
        })),
    };

    router.post(route("po-transmittals.store"), payload, {
        preserveScroll: true,
        onStart: () => {
            submitting.value = true;
        },
        onSuccess: () => {
            submitting.value = false;
        },
        onError: () => {
            submitting.value = false;
        },
        onFinish: () => {
            submitting.value = false;
        },
    });
};
</script>

<template>
    <div class="space-y-6">
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-base">
                    <Icon icon="lucide:search" class="h-4 w-4 text-primary" />
                    Find Batch
                </CardTitle>
            </CardHeader>
            <CardContent>
                <div class="max-w-md space-y-2">
                    <Label for="batch_search">Batch Number</Label>
                    <BatchSmartInput
                        context="po-transmittal"
                        @select="onBatchSelect"
                    />
                </div>
            </CardContent>
        </Card>

        <Card v-if="selectedBatchId">
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-base">
                    <Icon
                        icon="lucide:file-text"
                        class="h-4 w-4 text-primary"
                    />
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

                <div
                    v-if="purchaseOrders.length"
                    class="flex items-center justify-between"
                >
                    <p class="text-sm text-muted-foreground">
                        {{ selectedCount }} of {{ purchaseOrders.length }} PO(s) selected
                    </p>
                    <div class="flex gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            @click="selectAllPos"
                        >
                            Select All
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            @click="deselectAllPos"
                        >
                            Deselect All
                        </Button>
                    </div>
                </div>

                <div
                    v-for="(po, i) in purchaseOrders"
                    :key="po.id"
                    class="rounded-lg border p-4 space-y-4 transition-colors"
                    :class="selectedPoIds.includes(String(po.id)) ? 'border-l-primary border-l-4' : 'opacity-60'"
                >
                    <div class="grid gap-4 md:grid-cols-[auto_1fr_1fr]">
                        <div class="flex items-start pt-1">
                            <input
                                type="checkbox"
                                :checked="selectedPoIds.includes(String(po.id))"
                                @change="togglePo(String(po.id))"
                                class="h-5 w-5 rounded border-gray-300 text-primary"
                            />
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground">
                                P.O. Number
                            </p>
                            <p class="font-mono text-base font-semibold">
                                {{ po.po_no }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground">
                                SVP No.
                            </p>
                            <p class="font-mono text-base font-semibold">
                                {{ po._svp_no || "—" }}
                            </p>
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <p class="text-xs text-muted-foreground">Project</p>
                            <p class="font-medium">
                                {{ po.noa?.aoq?.rfq?.project_name || po.noa?.bac_resolution?.aoq?.rfq?.project_name || "—" }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground">Supplier</p>
                            <p class="font-medium">
                                {{ po.noa?.aoq?.winner_supplier?.name || po.noa?.bac_resolution?.aoq?.winner_supplier?.name || "—" }}
                            </p>
                        </div>
                    </div>

                    <div
                        v-if="selectedPoIds.includes(String(po.id))"
                        class="grid gap-6 lg:grid-cols-2 pt-2"
                    >
                        <div class="rounded-md border p-4 space-y-3">
                            <p class="text-xs font-semibold uppercase text-muted-foreground">
                                COA Transmittal
                            </p>
                            <div class="space-y-2">
                                <Label :for="'coa_transmittal_no_' + i">Transmittal No.</Label>
                                <input
                                    :id="'coa_transmittal_no_' + i"
                                    :value="po._coa?.transmittal_no ?? po._coa_transmittal_no ?? ''"
                                    @input="
                                        po._coa = { ...(po._coa || {}), transmittal_no: $event.target.value }
                                    "
                                    class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                />
                            </div>
                            <div class="space-y-2">
                                <Label :for="'coa_header_text_' + i">Header / Addressee</Label>
                                <textarea
                                    :id="'coa_header_text_' + i"
                                    :value="po._coa?.header_text ?? defaultCoaHeader"
                                    @input="
                                        po._coa = { ...(po._coa || {}), header_text: $event.target.value }
                                    "
                                    rows="4"
                                    class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                />
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <div class="space-y-2">
                                    <Label :for="'coa_signatory_name_' + i">Signatory Name</Label>
                                    <input
                                        :id="'coa_signatory_name_' + i"
                                        :value="po._coa?.signatory_name ?? defaultSignatory"
                                        @input="
                                            po._coa = { ...(po._coa || {}), signatory_name: $event.target.value }
                                        "
                                        class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                    />
                                </div>
                                <div class="space-y-2">
                                    <Label :for="'coa_signatory_title_' + i">Title</Label>
                                    <input
                                        :id="'coa_signatory_title_' + i"
                                        :value="po._coa?.signatory_title ?? defaultTitle"
                                        @input="
                                            po._coa = { ...(po._coa || {}), signatory_title: $event.target.value }
                                        "
                                        class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                    />
                                </div>
                            </div>
                        </div>

                        <div class="rounded-md border p-4 space-y-3">
                            <p class="text-xs font-semibold uppercase text-muted-foreground">
                                OPG Transmittal
                            </p>
                            <div class="space-y-2">
                                <Label :for="'opg_transmittal_no_' + i">Transmittal No.</Label>
                                <input
                                    :id="'opg_transmittal_no_' + i"
                                    :value="po._opg?.transmittal_no ?? po._opg_transmittal_no ?? ''"
                                    @input="
                                        po._opg = { ...(po._opg || {}), transmittal_no: $event.target.value }
                                    "
                                    class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                />
                            </div>
                            <div class="space-y-2">
                                <Label :for="'opg_header_text_' + i">Header / Addressee</Label>
                                <textarea
                                    :id="'opg_header_text_' + i"
                                    :value="po._opg?.header_text ?? defaultOpgHeader"
                                    @input="
                                        po._opg = { ...(po._opg || {}), header_text: $event.target.value }
                                    "
                                    rows="4"
                                    class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                />
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <div class="space-y-2">
                                    <Label :for="'opg_signatory_name_' + i">Signatory Name</Label>
                                    <input
                                        :id="'opg_signatory_name_' + i"
                                        :value="po._opg?.signatory_name ?? defaultSignatory"
                                        @input="
                                            po._opg = { ...(po._opg || {}), signatory_name: $event.target.value }
                                        "
                                        class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                    />
                                </div>
                                <div class="space-y-2">
                                    <Label :for="'opg_signatory_title_' + i">Title</Label>
                                    <input
                                        :id="'opg_signatory_title_' + i"
                                        :value="po._opg?.signatory_title ?? defaultTitle"
                                        @input="
                                            po._opg = { ...(po._opg || {}), signatory_title: $event.target.value }
                                        "
                                        class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="purchaseOrders.length" class="flex justify-end gap-3 pt-2">
                    <Button
                        type="button"
                        variant="outline"
                        @click="selectedBatchId = ''"
                    >
                        Cancel
                    </Button>
                    <Button
                        type="button"
                        :disabled="submitting || loadingPos || !selectedCount"
                        @click="submitAll"
                    >
                        <Icon
                            v-if="submitting"
                            icon="lucide:loader-2"
                            class="mr-2 h-4 w-4 animate-spin"
                        />
                        Create {{ selectedCount }} PO Transmittal{{ selectedCount !== 1 ? "s" : "" }}
                    </Button>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
