<script setup>
import { computed, ref, watch } from "vue";
import { Icon } from "@iconify/vue";
import { router, usePage } from "@inertiajs/vue3";
import axios from "axios";
import SvpSmartInput from "@/components/SvpSmartInput.vue";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";

const svpSearchNo = ref("");
const searchingSvp = ref(false);
const svpError = ref("");

const props = defineProps({
    suppliers: Array,
    defaultNoaDate: String,
    defaultNoaNo: String,
});

const selectedBatchId = ref("");
const aoqs = ref([]);
const noaRows = ref([]);
const loadingAoqs = ref(false);
const submitting = ref(false);
const selectedBatchInfo = ref(null);
const selectedBacResolution = ref(null);
const selectedAoqIds = ref([]);

const showRecipientSuggestions = ref(null);
const showTitleSuggestions = ref(null);

const recipientTitleOptions = [
    "Proprietor",
    "Authorized Representative",
    "Owner",
];

const formatDate = (val) => {
    if (!val) return "";
    const d = val.match(/^(\d{4}-\d{2}-\d{2})/);
    return d ? d[1] : "";
};

const findBatchBySvp = async (svp) => {
    if (!svp || searchingSvp.value) return;
    searchingSvp.value = true;
    svpError.value = "";

    try {
        const res = await axios.get(route("noas.find-batch-by-svp"), {
            params: { svp_no: svp },
        });
        selectedBatchId.value = String(res.data.batch.id);
    } catch (err) {
        svpError.value =
            err?.response?.data?.error ||
            "SVP not found. Make sure the AOQ has a batch assigned.";
        selectedBatchId.value = "";
    } finally {
        searchingSvp.value = false;
    }
};

const fetchAoqs = async (batchId) => {
    if (!batchId) {
        aoqs.value = [];
        noaRows.value = [];
        return;
    }

    loadingAoqs.value = true;
    try {
        const res = await axios.get(route("noas.batch-aoqs", batchId));
        aoqs.value = res.data.aoqs || [];
        selectedBatchInfo.value = res.data.batch || null;
        selectedBacResolution.value = res.data.bac_resolution || null;
        const aoqList = res.data.aoqs || [];
        selectedAoqIds.value = aoqList.map((aoq) => String(aoq.id));
        const generateNoaNumbers = (count) => {
            const defaultNoa = props.defaultNoaNo || "";
            const match = defaultNoa.match(/^(\d{4})-(\d{4})$/);
            const year = match?.[1] ?? String(new Date().getFullYear());
            const baseSeq = match ? parseInt(match[2], 10) : 0;
            return Array.from({ length: count }, (_, i) => {
                const seq = String(baseSeq + i + (match ? 0 : 1)).padStart(4, "0");
                return `${year}-${seq}`;
            });
        };

        const noaNumbers = generateNoaNumbers(aoqList.length);
        noaRows.value = aoqList.map((aoq, idx) => ({
            aoq_id: String(aoq.id),
            noa_no: noaNumbers[idx],
            noa_date: formatDate(
                res.data.batch?.noa_date || props.defaultNoaDate || "",
            ),
            recipient_name:
                aoq.winner_supplier?.proprietor ||
                aoq.winner_supplier?.authorized_representative ||
                aoq.winner_supplier?.owner ||
                "",
            recipient_title: aoq.winner_supplier?.proprietor
                ? "Proprietor"
                : aoq.winner_supplier?.authorized_representative
                  ? "Authorized Representative"
                  : aoq.winner_supplier?.owner
                    ? "Owner"
                    : "",
            _supplier: aoq.winner_supplier || null,
            _aoq: aoq,
        }));
    } catch (err) {
        aoqs.value = [];
        noaRows.value = [];
        selectedBatchInfo.value = null;
        selectedBacResolution.value = null;
    } finally {
        loadingAoqs.value = false;
    }
};

const findSupplier = (row) => {
    const name = row._supplier?.name || "";
    if (!name) return null;
    return (
        props.suppliers?.find(
            (s) => s.name?.toLowerCase() === name.toLowerCase(),
        ) || null
    );
};

const repSuggestions = (row) => {
    const supplier = findSupplier(row);
    if (!supplier) return [];
    return [
        supplier.proprietor,
        supplier.authorized_representative,
        supplier.owner,
    ]
        .map((v) => String(v || "").trim())
        .filter((v, i, a) => v !== "" && a.indexOf(v) === i);
};

const selectRecipient = (row, name) => {
    row.recipient_name = name;
    showRecipientSuggestions.value = null;

    const supplier = findSupplier(row);
    if (!supplier) return;

    if (name === supplier.proprietor) row.recipient_title = "Proprietor";
    else if (name === supplier.authorized_representative)
        row.recipient_title = "Authorized Representative";
    else if (name === supplier.owner) row.recipient_title = "Owner";
};

const selectTitle = (row, title) => {
    row.recipient_title = title;
    showTitleSuggestions.value = null;
};

const submitAll = async () => {
    if (submitting.value) return;
    submitting.value = true;

    const payload = {
        batch_id: selectedBatchId.value,
        noas: noaRows.value
            .filter((row) => selectedAoqIds.value.includes(row.aoq_id))
            .map((row) => ({
                aoq_id: row.aoq_id,
                noa_no: row.noa_no,
                noa_date: row.noa_date,
                recipient_name: row.recipient_name,
                recipient_title: row.recipient_title,
            })),
    };

    router.post(route("noas.store"), payload, {
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

const selectedCount = computed(() => selectedAoqIds.value.length);

const toggleAoq = (id) => {
    const idx = selectedAoqIds.value.indexOf(id);
    if (idx === -1) {
        selectedAoqIds.value = [...selectedAoqIds.value, id];
    } else {
        selectedAoqIds.value = selectedAoqIds.value.filter((v) => v !== id);
    }
};

const selectAllAoqs = () => {
    selectedAoqIds.value = noaRows.value.map((r) => r.aoq_id);
};

const deselectAllAoqs = () => {
    selectedAoqIds.value = [];
};

watch(selectedBatchId, (id) => {
    fetchAoqs(id);
});
</script>

<template>
    <div class="space-y-6">
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
                            context="noa"
                            :disabled="searchingSvp"
                            @select="(svp) => findBatchBySvp(svp.svp_no)"
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
                        Find Batch
                    </Button>

                    <p v-if="svpError" class="text-xs text-destructive">
                        {{ svpError }}
                    </p>
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
                    AOQs in Batch
                    <span v-if="loadingAoqs" class="ml-2">
                        <Icon
                            icon="lucide:loader-2"
                            class="h-4 w-4 animate-spin text-muted-foreground"
                        />
                    </span>
                </CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <div
                    v-if="!noaRows.length && !loadingAoqs"
                    class="py-6 text-center text-sm text-muted-foreground"
                >
                    All AOQs in this batch already have NOAs.
                </div>

                <div
                    v-if="noaRows.length"
                    class="flex items-center justify-between"
                >
                    <p class="text-sm text-muted-foreground">
                        {{ selectedCount }} of {{ noaRows.length }} AOQ(s) selected
                    </p>
                    <div class="flex gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            @click="selectAllAoqs"
                        >
                            Select All
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            @click="deselectAllAoqs"
                        >
                            Deselect All
                        </Button>
                    </div>
                </div>

                <!-- AOQ Info -->
                <div
                    v-for="(row, i) in noaRows"
                    :key="row.aoq_id"
                    class="rounded-lg border p-4 space-y-4 transition-colors"
                    :class="selectedAoqIds.includes(row.aoq_id) ? 'border-l-primary border-l-4' : 'opacity-60'"
                >
                    <!-- Row 1: Checkbox, NOA Number, NOA Date, Batch/BAC info -->
                    <div class="grid gap-6 md:grid-cols-[auto_1fr_1fr_1fr]">
                        <div class="flex items-start pt-1">
                            <input
                                type="checkbox"
                                :checked="selectedAoqIds.includes(row.aoq_id)"
                                @change="toggleAoq(row.aoq_id)"
                                class="h-5 w-5 rounded border-gray-300 text-primary"
                            />
                        </div>
                        <div class="space-y-1.5">
                            <Label :for="'noa_no_' + i">NOA Number</Label>
                            <input
                                :id="'noa_no_' + i"
                                v-model="row.noa_no"
                                type="text"
                                placeholder="YYYY-NNNN"
                                class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm font-mono"
                            />
                            <p class="text-xs text-muted-foreground">
                                Auto-generated. You may edit it.
                            </p>
                            <p
                                v-if="
                                    usePage().props.errors?.[
                                        'noas.' + i + '.noa_no'
                                    ]
                                "
                                class="text-xs text-destructive"
                            >
                                {{
                                    usePage().props.errors[
                                        "noas." + i + ".noa_no"
                                    ]
                                }}
                            </p>
                        </div>
                        <div class="space-y-1.5">
                            <Label :for="'noa_date_' + i">NOA Date</Label>
                            <input
                                :id="'noa_date_' + i"
                                v-model="row.noa_date"
                                type="date"
                                class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            />
                            <p
                                v-if="
                                    usePage().props.errors?.[
                                        'noas.' + i + '.noa_date'
                                    ]
                                "
                                class="text-xs text-destructive"
                            >
                                {{
                                    usePage().props.errors[
                                        "noas." + i + ".noa_date"
                                    ]
                                }}
                            </p>
                        </div>
                        <div
                            v-if="selectedBatchInfo"
                            class="flex flex-wrap items-start gap-x-6 gap-y-1"
                        >
                            <div>
                                <p class="text-xs text-muted-foreground">
                                    Batch
                                </p>
                                <p class="font-mono text-base font-semibold">
                                    {{ selectedBatchInfo.batch_no }}
                                </p>
                            </div>
                            <div v-if="selectedBacResolution">
                                <p class="text-xs text-muted-foreground">
                                    BAC Resolution
                                </p>
                                <p class="font-mono text-base font-semibold">
                                    {{ selectedBacResolution.resolution_no }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Row 2: Project, Office -->
                    <div class="grid gap-6 mb-8 md:grid-cols-3">
                        <div class="md:col-span-2">
                            <p class="text-xs text-muted-foreground">Project</p>
                            <p class="font-medium">
                                {{ row._aoq.rfq?.project_name || "—" }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground">Office</p>
                            <p class="font-medium">
                                {{
                                    row._aoq.rfq?.purchase_request?.office
                                        ?.name || "—"
                                }}
                            </p>
                        </div>
                    </div>

                    <!-- Row 3: Winner Supplier, Recipient Name, Recipient Title, Amount -->
                    <div class="grid gap-4 sm:grid-cols-2 md:grid-cols-4">
                        <div>
                            <p class="text-xs text-muted-foreground">
                                Winner Supplier
                            </p>
                            <p class="font-medium">
                                {{ row._supplier?.name || "—" }}
                            </p>
                        </div>
                        <div class="space-y-1.5">
                            <Label :for="'recipient_name_' + i"
                                >Recipient Name</Label
                            >
                            <div class="relative">
                                <input
                                    :id="'recipient_name_' + i"
                                    v-model="row.recipient_name"
                                    class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                    @focus="showRecipientSuggestions = i"
                                    @blur="
                                        setTimeout(
                                            () =>
                                                showRecipientSuggestions ===
                                                    i &&
                                                (showRecipientSuggestions =
                                                    null),
                                            150,
                                        )
                                    "
                                />
                                <div
                                    v-if="
                                        showRecipientSuggestions === i &&
                                        repSuggestions(row).length
                                    "
                                    class="absolute z-20 mt-1 max-h-36 w-full overflow-auto rounded-md border border-input bg-background shadow-sm"
                                >
                                    <button
                                        v-for="person in repSuggestions(row)"
                                        :key="person"
                                        type="button"
                                        class="block w-full px-3 py-1.5 text-left text-sm hover:bg-muted"
                                        @mousedown.prevent="
                                            selectRecipient(row, person)
                                        "
                                    >
                                        {{ person }}
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="space-y-1.5">
                            <Label :for="'recipient_title_' + i"
                                >Recipient Title</Label
                            >
                            <div class="relative">
                                <input
                                    :id="'recipient_title_' + i"
                                    v-model="row.recipient_title"
                                    class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                    @focus="showTitleSuggestions = i"
                                    @blur="
                                        setTimeout(
                                            () =>
                                                showTitleSuggestions === i &&
                                                (showTitleSuggestions = null),
                                            150,
                                        )
                                    "
                                />
                                <div
                                    v-if="showTitleSuggestions === i"
                                    class="absolute z-20 mt-1 max-h-36 w-full overflow-auto rounded-md border border-input bg-background shadow-sm"
                                >
                                    <button
                                        v-for="title in recipientTitleOptions"
                                        :key="title"
                                        type="button"
                                        class="block w-full px-3 py-1.5 text-left text-sm hover:bg-muted"
                                        @mousedown.prevent="
                                            selectTitle(row, title)
                                        "
                                    >
                                        {{ title }}
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground">Amount</p>
                            <p class="font-semibold">
                                ₱{{
                                    Number(
                                        row._aoq.winner_amount ?? 0,
                                    ).toLocaleString("en-PH", {
                                        minimumFractionDigits: 2,
                                        maximumFractionDigits: 2,
                                    })
                                }}
                            </p>
                        </div>
                    </div>

                    <p
                        v-if="usePage().props.errors?.['noas.' + i + '.aoq_id']"
                        class="text-xs text-destructive"
                    >
                        {{ usePage().props.errors["noas." + i + ".aoq_id"] }}
                    </p>
                </div>

                <div v-if="noaRows.length" class="flex justify-end gap-3 pt-2">
                    <Button
                        type="button"
                        variant="outline"
                        @click="selectedBatchId = ''"
                    >
                        Cancel
                    </Button>
                    <Button
                        type="button"
                        :disabled="submitting || loadingAoqs || !selectedCount"
                        @click="submitAll"
                    >
                        <Icon
                            v-if="submitting"
                            icon="lucide:loader-2"
                            class="mr-2 h-4 w-4 animate-spin"
                        />
                        Create {{ selectedCount }} NOA{{
                            selectedCount !== 1 ? "s" : ""
                        }}
                    </Button>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
