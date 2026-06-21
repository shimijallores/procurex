<script setup>
import { computed, ref, watch } from "vue";
import { Icon } from "@iconify/vue";
import axios from "axios";
import { route } from "ziggy-js";
import { useDebounceFn } from "@vueuse/core";
import { useWorkingDayInputGuard } from "@/composables/useWorkingDayInputGuard";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";
import SupplierSmartInput from "@/components/SupplierSmartInput.vue";

const props = defineProps({
    form: Object,
    aoq: Object,
    suppliers: Array,
    batches: Array,
});

const emit = defineEmits(["submit"]);

const rfqData = ref(null);
const loadingRfq = ref(false);

const creatingBatch = ref(false);
const batchList = ref([...(props.batches || [])]);
const selectedBatchYear = ref("");

const batchYears = computed(() => {
    const years = new Set();
    for (const batch of batchList.value) {
        const year = batch.created_at?.slice(0, 4);
        if (year) years.add(year);
    }
    return [...years].sort().reverse();
});

const displayBatches = computed(() => {
    let list = batchList.value;
    if (selectedBatchYear.value) {
        list = list.filter(
            (b) => b.created_at?.slice(0, 4) === selectedBatchYear.value,
        );
    }
    return [...list]
        .sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
        .slice(0, 5);
});

const deletingBatchId = ref(null);

const deleteBatch = async (batch) => {
    if (batch.aoqs_count > 0 || deletingBatchId.value) return;
    deletingBatchId.value = batch.id;

    try {
        await axios.delete(route("batches.destroy", batch.id));
        batchList.value = batchList.value.filter((b) => b.id !== batch.id);
        if (String(form.batch_id) === String(batch.id)) {
            form.batch_id = "";
        }
    } catch {
        //
    } finally {
        deletingBatchId.value = null;
    }
};

const isSelected = (batch) => String(batch.id) === String(props.form.batch_id);

const formatDate = (date) => {
    if (!date) return "—";
    return new Date(date).toLocaleDateString("en-US", {
        year: "numeric",
        month: "short",
        day: "numeric",
    });
};

const { enforceWorkingDay, getDateNotice, getDateNoticeClass } =
    useWorkingDayInputGuard(props.form);

const selectedItems = computed(() => rfqData.value?.items || []);

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

const initFromAoq = () => {
    const aoq = props.aoq;
    const rfq = aoq.rfq;

    rfqData.value = rfq;
    props.form.rfq_id = String(rfq.id);

    const itemIds = (rfq.items || []).map((item) => item.id);
    const quotations = [];

    for (const rfqSupplier of rfq.suppliers || []) {
        const unitPrices = {};

        for (const itemId of itemIds) {
            const match = (rfqSupplier.supplier_items || rfqSupplier.supplierItems || []).find(
                (si) => String(si.rfq_item_id) === String(itemId),
            );
            unitPrices[itemId] = match?.unit_price ?? "";
        }

        quotations.push({
            supplier_id: String(rfqSupplier.supplier_id),
            submitted_at: rfqSupplier.submitted_at?.slice(0, 10) || "",
            remarks: rfqSupplier.remarks || "",
            unit_prices: unitPrices,
        });
    }

    if (quotations.length === 0) {
        quotations.push(normalizeQuotationUnitPrices(createQuotation(), itemIds));
    }

    props.form.quotations = quotations;
};

initFromAoq();

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

const manualBatchNo = ref("");
const creatingManualBatch = ref(false);

const useManualBatch = async () => {
    const no = (manualBatchNo.value || "").trim();
    if (!no || creatingManualBatch.value) return;
    creatingManualBatch.value = true;

    try {
        const { data } = await axios.post(route("aoqs.store-batch"), {
            batch_no: no,
        });
        props.form.batch_id = String(data.id);
        batchList.value.push({ ...data, aoqs_count: 0 });
        manualBatchNo.value = "";
    } catch (err) {
        if (err?.response?.status === 409) {
            const existing = batchList.value.find((b) => b.batch_no === no);
            if (existing) props.form.batch_id = String(existing.id);
        }
    } finally {
        creatingManualBatch.value = false;
    }
};

const createNewBatch = async () => {
    if (creatingBatch.value) return;
    creatingBatch.value = true;

    try {
        const { data } = await axios.post(route("aoqs.store-batch"));
        props.form.batch_id = String(data.id);
        batchList.value.push({ ...data, aoqs_count: 0 });
    } catch {
        //
    } finally {
        creatingBatch.value = false;
    }
};

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
    if (!rank) return "";

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
</script>

<template>
    <form @submit.prevent="$emit('submit')" class="space-y-6">
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-base">
                    <Icon icon="lucide:link" class="h-4 w-4 text-primary" />
                    Source RFQ
                </CardTitle>
            </CardHeader>
            <CardContent>
                <div
                    v-if="rfqData"
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

        <Card>
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

        <Card>
            <CardHeader>
                <div class="flex items-center justify-between">
                    <CardTitle class="flex items-center gap-2 text-base">
                        <Icon
                            icon="lucide:layers"
                            class="h-4 w-4 text-primary"
                        />
                        Batch Assignment
                        <span class="text-destructive">*</span>
                    </CardTitle>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        :disabled="creatingBatch"
                        @click="createNewBatch"
                    >
                        <Icon
                            v-if="creatingBatch"
                            icon="lucide:loader-2"
                            class="mr-1 h-3.5 w-3.5 animate-spin"
                        />
                        <Icon
                            v-else
                            icon="lucide:plus"
                            class="mr-1 h-3.5 w-3.5"
                        />
                        New Batch
                    </Button>
                </div>
            </CardHeader>
            <CardContent class="space-y-3">
                <div class="flex items-end gap-2">
                    <div class="space-y-1.5 flex-1">
                        <Label for="manual_batch_no">Batch No. (manual)</Label>
                        <input
                            id="manual_batch_no"
                            v-model="manualBatchNo"
                            type="text"
                            placeholder="e.g. 260088"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm font-mono"
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
                        Use Batch No
                    </Button>
                </div>

                <div class="flex items-center gap-2">
                    <Icon
                        icon="lucide:calendar"
                        class="h-4 w-4 text-muted-foreground shrink-0"
                    />
                    <select
                        v-model="selectedBatchYear"
                        class="flex h-9 w-40 rounded-md border border-input bg-background px-3 py-2 text-sm"
                    >
                        <option value="">All Years</option>
                        <option
                            v-for="year in batchYears"
                            :key="year"
                            :value="year"
                        >
                            {{ year }}
                        </option>
                    </select>
                </div>

                <div class="overflow-hidden rounded-md border">
                    <table class="w-full text-sm">
                        <thead class="bg-muted/40">
                            <tr>
                                <th class="w-8 px-2 py-2"></th>
                                <th
                                    class="px-2 py-2 text-left font-medium text-muted-foreground"
                                >
                                    Batch No.
                                </th>
                                <th
                                    class="px-2 py-2 text-center font-medium text-muted-foreground"
                                >
                                    AOQs
                                </th>
                                <th
                                    class="px-2 py-2 text-left font-medium text-muted-foreground"
                                >
                                    Created
                                </th>
                                <th
                                    class="px-2 py-2 text-right font-medium text-muted-foreground"
                                ></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="!displayBatches.length">
                                <td
                                    colspan="5"
                                    class="px-3 py-6 text-center text-sm text-muted-foreground"
                                >
                                    No batches yet. Click "New Batch" to create
                                    one.
                                </td>
                            </tr>
                            <tr
                                v-for="batch in displayBatches"
                                :key="batch.id"
                                class="cursor-pointer border-b transition-colors hover:bg-primary/5"
                                :class="
                                    isSelected(batch) ? 'bg-primary/10' : ''
                                "
                                @click="form.batch_id = String(batch.id)"
                            >
                                <td class="w-8 px-2 py-2.5 text-center">
                                    <Icon
                                        v-if="isSelected(batch)"
                                        icon="lucide:circle-check"
                                        class="h-4 w-4 text-primary"
                                    />
                                    <Icon
                                        v-else
                                        icon="lucide:circle"
                                        class="h-4 w-4 text-muted-foreground/30"
                                    />
                                </td>
                                <td class="px-2 py-2.5">
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono font-medium">{{
                                            batch.batch_no
                                        }}</span>
                                        <span
                                            v-if="isSelected(batch)"
                                            class="inline-flex items-center rounded-full border border-primary/30 bg-primary/10 px-1.5 py-0.5 text-[10px] font-medium leading-none text-primary"
                                        >
                                            Selected
                                        </span>
                                    </div>
                                </td>
                                <td
                                    class="px-2 py-2.5 text-center text-muted-foreground"
                                >
                                    {{ batch.aoqs_count || 0 }}
                                </td>
                                <td class="px-2 py-2.5 text-muted-foreground">
                                    {{ formatDate(batch.created_at) }}
                                </td>
                                <td class="px-2 py-2.5 text-right">
                                    <button
                                        v-if="!batch.aoqs_count"
                                        type="button"
                                        :disabled="deletingBatchId === batch.id"
                                        class="inline-flex items-center justify-center rounded-md p-1 text-muted-foreground opacity-0 transition-all hover:bg-destructive/10 hover:text-destructive"
                                        :class="[
                                            deletingBatchId === batch.id
                                                ? 'opacity-50'
                                                : '',
                                            isSelected(batch)
                                                ? 'opacity-100'
                                                : 'hover:opacity-100',
                                        ]"
                                        @click.stop="deleteBatch(batch)"
                                        :title="
                                            'Delete batch ' + batch.batch_no
                                        "
                                    >
                                        <Icon
                                            v-if="deletingBatchId === batch.id"
                                            icon="lucide:loader-2"
                                            class="h-4 w-4 animate-spin"
                                        />
                                        <Icon
                                            v-else
                                            icon="lucide:trash-2"
                                            class="h-4 w-4"
                                        />
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <p class="text-xs text-muted-foreground">
                    Batches group AOQs for later BAC Resolution batching. Click
                    a row to select. Batch number is auto-generated.
                </p>
                <p
                    v-if="form.errors?.batch_id"
                    class="text-xs text-destructive"
                >
                    {{ form.errors.batch_id }}
                </p>
            </CardContent>
        </Card>

        <Card>
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

        <Card>
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
                                formatCurrency(rfqData?.abc_amount)
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
            <Button type="submit" :disabled="form.processing">
                <Icon
                    v-if="form.processing"
                    icon="lucide:loader-2"
                    class="mr-2 h-4 w-4 animate-spin"
                />
                Update AOQ
            </Button>
        </div>
    </form>
</template>
