<script setup>
import { computed, onMounted, ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import axios from "axios";
import { route } from "ziggy-js";
import { toast } from "vue-sonner";
import { useDebounceFn } from "@vueuse/core";
import { useWorkingDayInputGuard } from "@/composables/useWorkingDayInputGuard";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";
import SvpSmartInput from "@/components/SvpSmartInput.vue";
import SupplierSmartInput from "@/components/SupplierSmartInput.vue";

const props = defineProps({
    form: Object,
    suppliers: Array,
    batches: Array,
    activeEarmarkBatch: Object,
    displayBatch: Object,
});

const emit = defineEmits(["submit", "batch-assigned"]);

const rfqData = ref(null);
const loadingRfq = ref(false);
const rfqError = ref("");

const earmarkFrom = ref("");
const earmarkTo = ref("");
const findingBatch = ref(false);
const assignedBatch = ref(props.activeEarmarkBatch || null);
const isNewBatch = ref(false);

// Request access to locked batch
const showRequestModal = ref(false);
const lockedBatches = ref([]);
const selectedLockedBatchId = ref("");
const requestReason = ref("");
const requestingAccess = ref(false);
const loadingLockedBatches = ref(false);

const openRequestModal = async () => {
    selectedLockedBatchId.value = "";
    requestReason.value = "";
    loadingLockedBatches.value = true;
    showRequestModal.value = true;
    try {
        const { data } = await axios.get(route("batch-aoq-requests.locked-batches"));
        lockedBatches.value = data;
    } catch {
        lockedBatches.value = [];
        toast.error("Failed to load locked batches.");
    } finally {
        loadingLockedBatches.value = false;
    }
};

const submitRequest = async () => {
    if (!selectedLockedBatchId.value) {
        toast.error("Please select a locked batch.");
        return;
    }

    if (!props.form.rfq_id) {
        toast.error("Please select an RFQ first.");
        return;
    }

    requestingAccess.value = true;
    try {
        await axios.post(route("batch-aoq-requests.store"), {
            batch_id: selectedLockedBatchId.value,
            reason: requestReason.value,
            request_data: {
                rfq_id: props.form.rfq_id,
                aoq_date: props.form.aoq_date,
                quotations: props.form.quotations,
            },
        });
        showRequestModal.value = false;
        router.get(route("batch-aoq-requests.my-requests"));
    } catch (err) {
        toast.error(err?.response?.data?.error || "Failed to submit request.");
    } finally {
        requestingAccess.value = false;
    }
};

if (props.activeEarmarkBatch) {
    props.form.batch_id = String(props.activeEarmarkBatch.id);
}

onMounted(async () => {
    if (assignedBatch.value) return;

    try {
        const { data } = await axios.get(route("aoqs.active-earmark"));
        if (data.batch) {
            assignedBatch.value = data.batch;
            props.form.batch_id = String(data.batch.id);
        }
    } catch {
        // silently fail — batch assignment can still happen via date input
    }
});

const batchNotice = computed(() => {
    if (!assignedBatch.value) return "";
    const label = isNewBatch.value
        ? "A new batch has been created"
        : "This AOQ will be assigned to";
    return `${label}: ${assignedBatch.value.batch_no}`;
});

const findOrCreateBatch = async () => {
    const from = earmarkFrom.value;
    const to = earmarkTo.value;
    if (!from || !to) return;

    findingBatch.value = true;
    assignedBatch.value = null;
    isNewBatch.value = false;
    props.form.batch_id = "";

    try {
        const { data } = await axios.post(
            route("aoqs.find-or-create-batch"),
            {
                earmark_date_from: from,
                earmark_date_to: to,
            },
        );
        assignedBatch.value = data.batch;
        isNewBatch.value = data.is_new;
        props.form.batch_id = String(data.batch.id);
        emit("batch-assigned", { isNew: data.is_new, batchNo: data.batch.batch_no, batchId: data.batch.id });
    } catch {
        //
    } finally {
        findingBatch.value = false;
    }
};

const { enforceWorkingDay, getDateNotice, getDateNoticeClass } =
    useWorkingDayInputGuard(props.form);

const selectedItems = computed(() => rfqData.value?.items || []);

const formatDateShort = (date) => {
    if (!date) return "—";
    return new Date(date).toLocaleDateString("en-US", {
        year: "numeric",
        month: "long",
        day: "numeric",
    });
};

const formatCurrency = (value) =>
    new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
    }).format(value || 0);

const selectedSupplierIds = computed(() => {
    return (props.form.quotations || [])
        .map((q) => String(q.supplier_id))
        .filter(Boolean);
});

const maxColumnsReached = computed(
    () => (props.form.quotations || []).length >= 3,
);

const createQuotation = () => ({
    supplier_id: "",
    submitted_at: "",
    remarks: "",
    unit_prices: {},
});

const normalizeQuotationUnitPrices = (quotation, itemIds) => {
    const unitPrices = {};

    for (const itemId of itemIds) {
        unitPrices[itemId] = quotation.unit_prices?.[itemId] ?? "";
    }

    return {
        ...quotation,
        unit_prices: unitPrices,
    };
};

const loadRfq = async (svpNo) => {
    if (!svpNo) {
        rfqData.value = null;
        props.form.rfq_id = "";
        props.form.quotations = [];
        return;
    }

    loadingRfq.value = true;
    rfqError.value = "";

    try {
        const [rfqResponse, earmarkResponse] = await Promise.all([
            axios.get(route("aoqs.find-rfq-by-svp", { svp_no: svpNo })),
            axios.get(route("aoqs.active-earmark")),
        ]);

        const rfqDataResponse = rfqResponse.data;
        const activeBatchResponse = earmarkResponse.data;

        if (activeBatchResponse.batch) {
            assignedBatch.value = activeBatchResponse.batch;
            props.form.batch_id = String(activeBatchResponse.batch.id);
        }

        rfqData.value = rfqDataResponse.rfq;
        props.form.rfq_id = String(rfqDataResponse.rfq.id);

        const itemIds = (rfqDataResponse.rfq.items || []).map((item) => item.id);
        props.form.quotations = [
            normalizeQuotationUnitPrices(createQuotation(), itemIds),
        ];
    } catch (err) {
        rfqData.value = null;
        assignedBatch.value = null;
        isNewBatch.value = false;
        props.form.rfq_id = "";
        props.form.batch_id = "";
        props.form.quotations = [];
        rfqError.value =
            err?.response?.data?.error || "Failed to load RFQ data.";

        // Re-check active batch on error so the next SVP attempt finds it
        try {
            const { data } = await axios.get(route("aoqs.active-earmark"));
            if (data.batch) {
                assignedBatch.value = data.batch;
                props.form.batch_id = String(data.batch.id);
            }
        } catch {
            // still nothing — user will need the date input card
        }
    } finally {
        loadingRfq.value = false;
    }
};

const onSvpSelect = (svp) => {
    loadRfq(svp.svp_no);
};

watch(
    () => props.form.aoq_date,
    async (date) => {
        await enforceWorkingDay({
            dateValue: date,
            errorKey: "aoq_date",
            statusKey: "aoq_date",
            clearDate: () => {
                props.form.aoq_date = "";
            },
        });
    },
);

watch(
    () =>
        (props.form.quotations || []).map(
            (quotation) => quotation.submitted_at,
        ),
    async () => {
        const quotations = props.form.quotations || [];

        for (const [index, quotation] of quotations.entries()) {
            const submittedAt = quotation.submitted_at;
            if (!submittedAt) continue;

            await enforceWorkingDay({
                dateValue: submittedAt,
                errorKey: `quotations.${index}.submitted_at`,
                statusKey: `quotations.${index}.submitted_at`,
                clearDate: () => {
                    quotation.submitted_at = "";
                },
            });
        }
    },
    { deep: false },
);

const addQuotation = () => {
    const itemIds = selectedItems.value.map((item) => item.id);
    const quotation = createQuotation();

    for (const itemId of itemIds) {
        quotation.unit_prices[itemId] = "";
    }

    props.form.quotations = [...(props.form.quotations || []), quotation];
};

const removeQuotation = (index) => {
    props.form.quotations = props.form.quotations.filter((_, i) => i !== index);
};

const supplierName = (supplierId) => {
    const supplier = props.suppliers?.find(
        (item) => String(item.id) === String(supplierId),
    );

    return supplier?.name || "Supplier";
};

const quotationTotal = (quotation) => {
    let total = 0;

    for (const item of selectedItems.value) {
        const unitPrice = Number(quotation.unit_prices?.[item.id] || 0);
        const quantity = Number(item.quantity || 0);
        total += unitPrice * quantity;
    }

    return total;
};

watch(
    [earmarkFrom, earmarkTo],
    () => {
        if (earmarkFrom.value && earmarkTo.value) {
            findOrCreateBatch();
        } else {
            assignedBatch.value = null;
            isNewBatch.value = false;
            props.form.batch_id = "";
        }
    },
);

const supplierCountWithPrices = computed(() => {
    return (props.form.quotations || []).filter((quotation) => {
        return selectedItems.value.some((item) => {
            const rawPrice = quotation.unit_prices?.[item.id];
            return (
                rawPrice !== null && rawPrice !== undefined && rawPrice !== ""
            );
        });
    }).length;
});

const calculationMessage = computed(() => {
    const count = supplierCountWithPrices.value;

    if (count >= 2) {
        return "lowest calculated";
    }

    if (count === 1) {
        return "single calculated";
    }

    return "";
});

const supplierRankings = ref({});

const updateRankings = useDebounceFn(() => {
    const totals = {};

    for (const quotation of props.form.quotations || []) {
        if (!quotation.supplier_id) continue;
        let total = 0;
        let hasPrice = false;

        for (const item of selectedItems.value) {
            const rawPrice = quotation.unit_prices?.[item.id];
            if (rawPrice === null || rawPrice === undefined || rawPrice === "") {
                continue;
            }
            total += Number(rawPrice) * Number(item.quantity || 0);
            hasPrice = true;
        }

        if (hasPrice) {
            totals[quotation.supplier_id] = total;
        }
    }

    const sorted = Object.entries(totals).sort(([, a], [, b]) => a - b);
    const rankings = {};

    sorted.forEach(([supplierId], index) => {
        if (index === 0) rankings[supplierId] = "winner";
        else if (index === 1) rankings[supplierId] = "2nd_lowest";
        else if (index === 2) rankings[supplierId] = "3rd_lowest";
    });

    supplierRankings.value = rankings;
}, 500);

watch(
    () => props.form.quotations,
    () => {
        if (props.form.quotations?.length) {
            updateRankings();
        }
    },
    { deep: true },
);

const rankingBadge = (supplierId) => {
    const rank = supplierRankings.value[supplierId];
    if (!rank) return null;

    if (rank === "winner") {
        return {
            label: "Winner",
            class: "bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300",
        };
    }

    if (rank === "2nd_lowest") {
        return {
            label: "2nd",
            class: "bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300",
        };
    }

    return {
        label: "3rd",
        class: "bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300",
    };
};

const fileInputRef = ref(null);
const importing = ref(false);
const importError = ref("");

const importMatrix = async (file) => {
    if (!file || !rfqData.value) return;

    const supplierCount = (props.form.quotations || []).length;
    if (!supplierCount) {
        importError.value = "Add at least one supplier column first.";
        return;
    }

    importError.value = "";
    importing.value = true;

    const formData = new FormData();
    formData.append("file", file);
    formData.append("rfq_id", rfqData.value.id);
    formData.append("supplier_count", String(supplierCount));

    try {
        const { data } = await axios.post(route("aoqs.import-matrix"), formData);
        const unitPrices = data.unit_prices || {};

        const supplierKeys = Object.keys(unitPrices);
        if (supplierKeys.length !== supplierCount) {
            importError.value = `Supplier count mismatch: Excel has ${supplierKeys.length} supplier(s) but the matrix expects ${supplierCount}.`;
            return;
        }

        for (let i = 0; i < supplierCount; i++) {
            const prices = unitPrices[i] || {};
            const quotation = props.form.quotations[i];
            if (!quotation) continue;

            for (const [itemId, price] of Object.entries(prices)) {
                quotation.unit_prices[itemId] = price;
            }
        }

        importError.value = "";
    } catch (err) {
        const msg = err?.response?.data?.error || err?.response?.data?.message || "Failed to import matrix.";
        importError.value = msg;
    } finally {
        importing.value = false;
        if (fileInputRef.value) fileInputRef.value.value = "";
    }
};

const onFileSelected = (e) => {
    const file = e.target.files?.[0];
    if (file) importMatrix(file);
};

const svpInputValue = ref("");
const onSvpInput = (val) => {
    svpInputValue.value = val;
    if (!val) {
        rfqData.value = null;
        props.form.rfq_id = "";
        props.form.quotations = [];
    }
};
</script>

<template>
    <form @submit.prevent="$emit('submit')" class="space-y-6">
        <a
            v-if="displayBatch"
            :href="route('batches.show', displayBatch.id)"
            target="_blank"
            class="flex items-center gap-3 rounded-md border border-primary/20 bg-primary/5 p-3 text-sm hover:bg-primary/10 transition-colors"
        >
            <Icon icon="lucide:layers" class="h-4 w-4 text-primary shrink-0" />
            <span>
                Active Batch:
                <span class="font-mono font-semibold underline underline-offset-2">{{ displayBatch.batch_no }}</span>
                <span class="text-muted-foreground ml-1">
                    ({{ formatDateShort(displayBatch.earmark_date_from) }} — {{ formatDateShort(displayBatch.earmark_date_to) }})
                </span>
            </span>
            <Icon icon="lucide:external-link" class="h-3.5 w-3.5 text-muted-foreground ml-auto shrink-0" />
        </a>

        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-base">
                    <Icon icon="lucide:link" class="h-4 w-4 text-primary" />
                    Source RFQ
                </CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="flex gap-2 items-end w-100 justify-center">
                    <div class="flex-1 space-y-2">
                        <Label for="svp_search">
                            SVP Number
                            <span class="text-destructive">*</span>
                        </Label>
                        <SvpSmartInput
                            :model-value="svpInputValue"
                            @update:model-value="onSvpInput"
                            @select="onSvpSelect"
                        />
                        <p v-if="rfqError" class="text-xs text-destructive">
                            {{ rfqError }}
                        </p>
                        <p
                            v-if="form.errors?.rfq_id"
                            class="text-xs text-destructive"
                        >
                            {{ form.errors.rfq_id }}
                        </p>
                    </div>
                    <Button
                        type="button"
                        variant="default"
                        class="mb-0.5"
                        :disabled="!svpInputValue.trim() || loadingRfq"
                        @click="loadRfq(svpInputValue)"
                    >
                        <Icon
                            v-if="loadingRfq"
                            icon="lucide:loader-2"
                            class="mr-1 h-4 w-4 animate-spin"
                        />
                        <Icon
                            v-else
                            icon="lucide:search"
                            class="mr-1 h-4 w-4"
                        />
                        Find
                    </Button>
                </div>

                <div
                    v-if="loadingRfq"
                    class="flex items-center gap-2 rounded-md border border-border bg-muted/40 p-3 text-sm"
                >
                    <Icon
                        icon="lucide:loader-2"
                        class="h-4 w-4 animate-spin text-muted-foreground"
                    />
                    Loading RFQ data...
                </div>

                <div
                    v-if="rfqData && !loadingRfq"
                    class="rounded-md border border-border bg-muted/40 p-3 text-sm space-y-1"
                >
                    <div>
                        <span class="text-muted-foreground">SVP No:</span>
                        <span class="font-medium font-mono ml-1">{{
                            rfqData.svp_no
                        }}</span>
                    </div>
                    <div>
                        <span class="text-muted-foreground">Project:</span>
                        <span class="font-medium ml-1">{{
                            rfqData.project_name
                        }}</span>
                    </div>
                    <div>
                        <span class="text-muted-foreground">Office:</span>
                        <span class="font-medium ml-1">{{
                            rfqData.purchase_request?.office?.name || "—"
                        }}</span>
                    </div>
                    <div>
                        <span class="text-muted-foreground">ABC:</span>
                        <span class="font-medium ml-1">{{
                            formatCurrency(rfqData.abc_amount)
                        }}</span>
                    </div>
                    <div>
                        <span class="text-muted-foreground">PR No:</span>
                        <span class="font-medium font-mono ml-1">{{
                            rfqData.purchase_request?.pr_no || "—"
                        }}</span>
                    </div>
                </div>
            </CardContent>
        </Card>

        <Card v-if="rfqData">
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-base">
                    <Icon
                        icon="lucide:calendar-days"
                        class="h-4 w-4 text-primary"
                    />
                    AOQ Date
                </CardTitle>
            </CardHeader>
            <CardContent class="space-y-2">
                <Label for="aoq_date">
                    AOQ Date
                    <span class="text-destructive">*</span>
                </Label>
                <input
                    id="aoq_date"
                    v-model="form.aoq_date"
                    type="date"
                    class="flex h-10 w-full max-w-xs rounded-md border border-input bg-background px-3 py-2 text-sm"
                />
                <p :class="getDateNoticeClass('aoq_date')">
                    {{ getDateNotice("aoq_date") }}
                </p>
                <p
                    v-if="form.errors?.aoq_date"
                    class="text-xs text-destructive"
                >
                    {{ form.errors.aoq_date }}
                </p>
            </CardContent>
        </Card>

        <div
            v-if="rfqData && assignedBatch"
            class="rounded-md border bg-primary/5 border-primary/20 p-3 text-sm flex items-center gap-2"
        >
            <Icon icon="lucide:layers" class="h-4 w-4 text-primary shrink-0" />
            <span class="font-medium">
                This AOQ automatically belongs to Batch
                <span class="font-mono">{{ assignedBatch.batch_no }}</span>
                (earmark: {{ formatDateShort(assignedBatch.earmark_date_from) }} —
                {{ formatDateShort(assignedBatch.earmark_date_to) }}).
            </span>
        </div>

        <Card v-if="rfqData && !assignedBatch">
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-base">
                    <Icon
                        icon="lucide:calendar-range"
                        class="h-4 w-4 text-primary"
                    />
                    Earmark Date
                    <span class="text-destructive">*</span>
                </CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Label for="earmark_from">
                            From
                            <span class="text-destructive">*</span>
                        </Label>
                        <input
                            id="earmark_from"
                            v-model="earmarkFrom"
                            type="date"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        />
                    </div>
                    <div class="space-y-2">
                        <Label for="earmark_to">
                            To
                            <span class="text-destructive">*</span>
                        </Label>
                        <input
                            id="earmark_to"
                            v-model="earmarkTo"
                            type="date"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        />
                    </div>
                </div>

                <div
                    v-if="findingBatch"
                    class="flex items-center gap-2 rounded-md border border-border bg-muted/40 p-3 text-sm"
                >
                    <Icon
                        icon="lucide:loader-2"
                        class="h-4 w-4 animate-spin text-muted-foreground"
                    />
                    Finding or creating batch...
                </div>

                <div
                    v-else-if="assignedBatch"
                    class="rounded-md border bg-primary/5 border-primary/20 p-3 text-sm space-y-1"
                >
                    <div class="flex items-center gap-2">
                        <Icon
                            icon="lucide:layers"
                            class="h-4 w-4 text-primary"
                        />
                        <span class="font-medium">{{ batchNotice }}</span>
                    </div>
                    <div
                        v-if="isNewBatch"
                        class="flex items-center gap-2 text-xs text-muted-foreground mt-1"
                    >
                        <Icon icon="lucide:info" class="h-3.5 w-3.5" />
                        After creating this AOQ, set the batch dates (BAC, NOA, PO) in the Batches page.
                    </div>
                </div>

                <p
                    v-if="form.errors?.batch_id"
                    class="text-xs text-destructive"
                >
                    {{ form.errors.batch_id }}
                </p>
            </CardContent>
        </Card>

        <Card v-if="rfqData">
            <CardHeader class="flex flex-row items-center justify-between">
                <CardTitle class="flex items-center gap-2 text-base">
                    <Icon
                        icon="lucide:building-2"
                        class="h-4 w-4 text-primary"
                    />
                    Supplier Matrix Setup
                    <span class="text-destructive">*</span>
                    <a
                        :href="route('suppliers.index')"
                        target="_blank"
                        class="ml-2"
                    >
                        <Button type="button" variant="ghost" size="sm" class="h-7 gap-1 px-2 text-xs">
                            <Icon icon="lucide:external-link" class="h-3 w-3" />
                            Manage Supplier
                        </Button>
                    </a>
                </CardTitle>
                <div class="flex items-center gap-2">
                    <span
                        v-if="form.quotations?.length"
                        class="text-xs text-muted-foreground"
                    >
                        {{ form.quotations.length }}/3
                    </span>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        :disabled="!rfqData || maxColumnsReached"
                        @click="addQuotation"
                    >
                        <Icon icon="lucide:plus" class="mr-1 h-3.5 w-3.5" />
                        Add Supplier Column
                    </Button>
                </div>
            </CardHeader>
            <CardContent class="space-y-4">
                <p v-if="!rfqData" class="text-sm text-muted-foreground">
                    Select an SVP first to build the quotation matrix.
                </p>

                <div v-if="form.quotations?.length" class="space-y-3">
                    <div
                        v-for="(quotation, quotationIndex) in form.quotations"
                        :key="quotationIndex"
                        class="grid gap-3 rounded-md border border-border p-3 lg:grid-cols-12"
                    >
                        <div class="space-y-2 lg:col-span-3">
                            <Label>
                                Supplier
                                <span class="text-destructive">*</span>
                            </Label>
                            <SupplierSmartInput
                                :model-value="quotation.supplier_id"
                                :suppliers="suppliers"
                                :selected-ids="
                                    selectedSupplierIds.filter(
                                        (id) =>
                                            id !==
                                            String(quotation.supplier_id),
                                    )
                                "
                                placeholder="Search supplier..."
                                @update:model-value="
                                    quotation.supplier_id = $event
                                "
                            />
                            <p
                                v-if="
                                    form.errors?.[
                                        `quotations.${quotationIndex}.supplier_id`
                                    ]
                                "
                                class="text-xs text-destructive"
                            >
                                {{
                                    form.errors[
                                        `quotations.${quotationIndex}.supplier_id`
                                    ]
                                }}
                            </p>
                        </div>

                        <div class="space-y-2 lg:col-span-3">
                            <Label>Submitted Date</Label>
                            <input
                                v-model="quotation.submitted_at"
                                type="date"
                                class="flex h-9 w-full rounded-md border border-input bg-background px-2 py-1 text-sm"
                            />
                            <p
                                :class="
                                    getDateNoticeClass(
                                        `quotations.${quotationIndex}.submitted_at`,
                                    )
                                "
                            >
                                {{
                                    getDateNotice(
                                        `quotations.${quotationIndex}.submitted_at`,
                                    )
                                }}
                            </p>
                        </div>

                        <div class="space-y-2 lg:col-span-5">
                            <Label>Remarks</Label>
                            <input
                                v-model="quotation.remarks"
                                type="text"
                                class="flex h-9 w-full rounded-md border border-input bg-background px-2 py-1 text-sm"
                            />
                        </div>

                        <div class="flex items-end justify-end lg:col-span-1">
                            <Button
                                type="button"
                                variant="destructive"
                                size="sm"
                                @click="removeQuotation(quotationIndex)"
                            >
                                <Icon
                                    icon="lucide:trash-2"
                                    class="h-3.5 w-3.5"
                                />
                            </Button>
                        </div>
                    </div>
                </div>

                <p
                    v-if="form.errors?.quotations"
                    class="text-xs text-destructive"
                >
                    {{ form.errors.quotations }}
                </p>
            </CardContent>
        </Card>

        <Card v-if="rfqData">
            <CardHeader>
                <div class="flex items-center justify-between">
                    <CardTitle class="flex items-center gap-2 text-base">
                        <Icon icon="lucide:grid-2x2" class="h-4 w-4 text-primary" />
                        Quotation Matrix
                    </CardTitle>
                    <div class="flex items-center gap-2">
                        <input
                            ref="fileInputRef"
                            type="file"
                            accept=".xlsx,.csv"
                            class="hidden"
                            @change="onFileSelected"
                        />
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            :disabled="importing || !form.quotations?.length"
                            @click="fileInputRef?.click()"
                        >
                            <Icon
                                v-if="importing"
                                icon="lucide:loader-2"
                                class="mr-1 h-3.5 w-3.5 animate-spin"
                            />
                            <Icon
                                v-else
                                icon="lucide:file-spreadsheet"
                                class="mr-1 h-3.5 w-3.5"
                            />
                            Import Quotation
                        </Button>
                        <a :href="route('templates.index')" target="_blank">
                            <Button type="button" variant="outline" size="sm">
                                <Icon icon="lucide:external-link" class="mr-1 h-3.5 w-3.5" />
                                Template
                            </Button>
                        </a>
                        <div class="text-sm">
                            <span class="text-muted-foreground">ABC:</span>
                            <span class="font-semibold ml-1">{{
                                formatCurrency(rfqData.abc_amount)
                            }}</span>
                        </div>
                    </div>
                </div>
                <p
                    v-if="importError"
                    class="mt-2 text-xs text-destructive"
                >
                    {{ importError }}
                </p>
            </CardHeader>
            <CardContent class="space-y-4">
                <div
                    v-if="form.quotations?.length"
                    class="relative w-full overflow-auto rounded-md border"
                >
                    <table class="w-full text-sm">
                        <thead class="border-b bg-muted/30">
                            <tr>
                                <th class="w-2/5 min-w-40 px-3 py-2 text-left">Item</th>
                                <th class="w-20 px-3 py-2 text-center">Qty</th>
                                <th class="w-20 px-3 py-2 text-center">Unit</th>
                                <th class="w-32 px-3 py-2 text-right">
                                    Expected Price
                                </th>
                                <th
                                    v-for="(
                                        quotation, quotationIndex
                                    ) in form.quotations"
                                    :key="`header-${quotationIndex}`"
                                    class="px-3 py-2 text-right"
                                >
                                    <div
                                        class="flex flex-col items-end gap-1 max-w-40"
                                    >
                                        <span class="truncate w-full text-right">{{
                                            supplierName(quotation.supplier_id)
                                        }}</span>
                                        <span
                                            v-if="
                                                rankingBadge(
                                                    quotation.supplier_id,
                                                )
                                            "
                                            class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-medium leading-none"
                                            :class="
                                                rankingBadge(
                                                    quotation.supplier_id,
                                                ).class
                                            "
                                        >
                                            <Icon
                                                v-if="
                                                    rankingBadge(
                                                        quotation.supplier_id,
                                                    ).label === 'Winner'
                                                "
                                                icon="lucide:trophy"
                                                class="mr-0.5 h-3 w-3"
                                            />
                                            {{
                                                rankingBadge(
                                                    quotation.supplier_id,
                                                ).label
                                            }}
                                        </span>
                                    </div>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="item in selectedItems"
                                :key="item.id"
                                class="border-b"
                            >
                                <td class="px-3 py-2">
                                    {{
                                        item.item_name ||
                                        item.purchase_request_item?.item_name ||
                                        "—"
                                    }}
                                </td>
                                <td class="px-3 py-2 text-center">
                                    {{ item.quantity || 0 }}
                                </td>
                                <td class="px-3 py-2 text-center">
                                    {{ item.unit || "—" }}
                                </td>
                                <td class="px-3 py-2 text-right">
                                    {{
                                        formatCurrency(
                                            Number(
                                                item.purchase_request_item
                                                    ?.unit_cost || 0,
                                            ),
                                        )
                                    }}
                                </td>
                                <td
                                    v-for="(
                                        quotation, quotationIndex
                                    ) in form.quotations"
                                    :key="`cell-${quotationIndex}-${item.id}`"
                                    class="px-3 py-2"
                                >
                                    <input
                                        v-model="quotation.unit_prices[item.id]"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        class="flex h-8 w-full rounded-md border border-input bg-background px-2 py-1 text-right"
                                        placeholder="Unit price"
                                    />
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="bg-muted/20 font-semibold">
                                <td colspan="4" class="px-3 py-2 text-right">
                                    Supplier Totals
                                </td>
                                <td
                                    v-for="(
                                        quotation, quotationIndex
                                    ) in form.quotations"
                                    :key="`total-${quotationIndex}`"
                                    class="px-3 py-2 text-right"
                                >
                                    {{
                                        formatCurrency(
                                            quotationTotal(quotation),
                                        )
                                    }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div
                    v-if="calculationMessage"
                    class="rounded-md border border-border bg-muted/40 p-3 text-sm"
                >
                    Calculation Message:
                    <span class="font-semibold">{{ calculationMessage }}</span>
                </div>
            </CardContent>
        </Card>

        <div class="flex justify-end gap-2">
            <Button type="button" variant="outline" @click="form.reset()"
                >Reset</Button
            >
            <Button
                type="button"
                variant="secondary"
                :disabled="!form.rfq_id || form.processing"
                @click="openRequestModal"
            >
                <Icon icon="lucide:mail-question" class="mr-2 h-4 w-4" />
                Request Batch Access
            </Button>
            <Button type="submit" :disabled="form.processing">
                <Icon
                    v-if="form.processing"
                    icon="lucide:loader-2"
                    class="mr-2 h-4 w-4 animate-spin"
                />
                Create AOQ
            </Button>
        </div>

        <!-- Request Access Modal -->
        <div
            v-if="showRequestModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
            @click.self="showRequestModal = false"
        >
            <div class="w-full max-w-lg rounded-lg bg-background p-6 shadow-lg">
                <h3 class="text-lg font-semibold mb-2">Request Batch Access</h3>
                <p class="text-sm text-muted-foreground mb-4">
                    The current batch is locked or unavailable. Select a locked batch to request
                    SuperAdmin approval for adding this AOQ.
                </p>

                <div class="space-y-4">
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium">Select Locked Batch</label>
                        <select
                            v-model="selectedLockedBatchId"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        >
                            <option value="" disabled>
                                {{ loadingLockedBatches ? "Loading..." : "— Select —" }}
                            </option>
                            <option
                                v-for="batch in lockedBatches"
                                :key="batch.id"
                                :value="String(batch.id)"
                            >
                                {{ batch.batch_no }}
                            </option>
                        </select>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-sm font-medium">Reason (optional)</label>
                        <textarea
                            v-model="requestReason"
                            rows="3"
                            class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            placeholder="Why do you need to add an AOQ to this locked batch?"
                        />
                    </div>
                </div>

                <div class="flex justify-end gap-2 mt-4">
                    <Button variant="outline" @click="showRequestModal = false">Cancel</Button>
                    <Button
                        :disabled="requestingAccess || !selectedLockedBatchId"
                        @click="submitRequest"
                    >
                        <Icon
                            v-if="requestingAccess"
                            icon="lucide:loader-2"
                            class="mr-2 h-4 w-4 animate-spin"
                        />
                        Submit Request
                    </Button>
                </div>
            </div>
        </div>
    </form>
</template>
