<script setup>
import { computed, ref, watch } from "vue";
import { Icon } from "@iconify/vue";
import { router, usePage } from "@inertiajs/vue3";
import axios from "axios";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";

const props = defineProps({
    batches: Array,
    suppliers: Array,
    defaultNoaDate: String,
});

const selectedBatchId = ref("");
const aoqs = ref([]);
const noaRows = ref([]);
const loadingAoqs = ref(false);
const submitting = ref(false);

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
        noaRows.value = (res.data.aoqs || []).map((aoq) => ({
            aoq_id: String(aoq.id),
            noa_date: formatDate(props.defaultNoaDate || ""),
            recipient_name: aoq.winner_supplier?.proprietor
                || aoq.winner_supplier?.authorized_representative
                || aoq.winner_supplier?.owner
                || "",
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
    } catch {
        aoqs.value = [];
        noaRows.value = [];
    } finally {
        loadingAoqs.value = false;
    }
};

const findSupplier = (row) => {
    const name = row._supplier?.name || "";
    if (!name) return null;
    return props.suppliers?.find(
        (s) => s.name?.toLowerCase() === name.toLowerCase(),
    ) || null;
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
    else if (name === supplier.authorized_representative) row.recipient_title = "Authorized Representative";
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
        noas: noaRows.value.map((row) => ({
            aoq_id: row.aoq_id,
            noa_date: row.noa_date,
            recipient_name: row.recipient_name,
            recipient_title: row.recipient_title,
        })),
    };

    router.post(route("noas.store"), payload, {
        preserveScroll: true,
        onStart: () => { submitting.value = true; },
        onSuccess: () => { submitting.value = false; },
        onError: () => { submitting.value = false; },
        onFinish: () => { submitting.value = false; },
    });
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
                    <Icon icon="lucide:layers" class="h-4 w-4 text-primary" />
                    Select Batch
                </CardTitle>
            </CardHeader>
            <CardContent class="space-y-3">
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
                            ({{ batch.aoqs_count }} AOQ{{ batch.aoqs_count !== 1 ? "s" : "" }})
                        </option>
                    </select>
                </div>
                <p class="text-xs text-muted-foreground">
                    Select a batch to load AOQs that need Notices of Award.
                </p>
            </CardContent>
        </Card>

        <Card v-if="selectedBatchId">
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-base">
                    <Icon icon="lucide:file-text" class="h-4 w-4 text-primary" />
                    AOQs in Batch
                    <span v-if="loadingAoqs" class="ml-2">
                        <Icon icon="lucide:loader-2" class="h-4 w-4 animate-spin text-muted-foreground" />
                    </span>
                </CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <div v-if="!noaRows.length && !loadingAoqs" class="py-6 text-center text-sm text-muted-foreground">
                    All AOQs in this batch already have NOAs.
                </div>

                <div
                    v-for="(row, i) in noaRows"
                    :key="row.aoq_id"
                    class="rounded-lg border p-4 space-y-3"
                >
                    <div class="grid gap-2 text-sm md:grid-cols-3">
                        <div>
                            <p class="text-xs text-muted-foreground">SVP No.</p>
                            <p class="font-medium">{{ row._aoq.rfq?.svp_no || "—" }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground">Project</p>
                            <p class="font-medium truncate">{{ row._aoq.rfq?.project_name || "—" }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground">Winner Supplier</p>
                            <p class="font-medium">{{ row._supplier?.name || "—" }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground">Office</p>
                            <p class="font-medium">{{ row._aoq.rfq?.purchase_request?.office?.name || "—" }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground">Winner Amount</p>
                            <p class="font-medium">{{ row._aoq.winner_amount ?? "—" }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground">NOA Number</p>
                            <p class="font-mono font-medium">{{ row._aoq.rfq?.svp_no || "—" }}</p>
                        </div>
                    </div>

                    <hr class="border-border" />

                    <div class="grid gap-4 md:grid-cols-3">
                        <div class="space-y-1.5">
                            <Label :for="'noa_date_' + i">NOA Date</Label>
                            <input
                                :id="'noa_date_' + i"
                                v-model="row.noa_date"
                                type="date"
                                class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            />
                            <p v-if="usePage().props.errors?.['noas.' + i + '.noa_date']" class="text-xs text-destructive">
                                {{ usePage().props.errors['noas.' + i + '.noa_date'] }}
                            </p>
                        </div>

                        <div class="space-y-1.5">
                            <Label :for="'recipient_name_' + i">Recipient Name</Label>
                            <div class="relative">
                                <input
                                    :id="'recipient_name_' + i"
                                    v-model="row.recipient_name"
                                    class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                    @focus="showRecipientSuggestions = i"
                                    @blur="setTimeout(() => showRecipientSuggestions === i && (showRecipientSuggestions = null), 150)"
                                />
                                <div
                                    v-if="showRecipientSuggestions === i && repSuggestions(row).length"
                                    class="absolute z-20 mt-1 max-h-36 w-full overflow-auto rounded-md border border-input bg-background shadow-sm"
                                >
                                    <button
                                        v-for="person in repSuggestions(row)"
                                        :key="person"
                                        type="button"
                                        class="block w-full px-3 py-1.5 text-left text-sm hover:bg-muted"
                                        @mousedown.prevent="selectRecipient(row, person)"
                                    >
                                        {{ person }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-1.5">
                            <Label :for="'recipient_title_' + i">Recipient Title</Label>
                            <div class="relative">
                                <input
                                    :id="'recipient_title_' + i"
                                    v-model="row.recipient_title"
                                    class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                    @focus="showTitleSuggestions = i"
                                    @blur="setTimeout(() => showTitleSuggestions === i && (showTitleSuggestions = null), 150)"
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
                                        @mousedown.prevent="selectTitle(row, title)"
                                    >
                                        {{ title }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <p v-if="usePage().props.errors?.['noas.' + i + '.aoq_id']" class="text-xs text-destructive">
                        {{ usePage().props.errors['noas.' + i + '.aoq_id'] }}
                    </p>
                </div>

                <div v-if="noaRows.length" class="flex justify-end gap-3 pt-2">
                    <Button type="button" variant="outline" @click="selectedBatchId = ''">
                        Cancel
                    </Button>
                    <Button
                        type="button"
                        :disabled="submitting || loadingAoqs"
                        @click="submitAll"
                    >
                        <Icon
                            v-if="submitting"
                            icon="lucide:loader-2"
                            class="mr-2 h-4 w-4 animate-spin"
                        />
                        Create {{ noaRows.length }} NOA{{ noaRows.length !== 1 ? "s" : "" }}
                    </Button>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
