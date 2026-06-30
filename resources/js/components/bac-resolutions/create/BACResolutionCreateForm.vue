<script setup>
import { computed, onMounted, ref, watch } from "vue";
import { Icon } from "@iconify/vue";
import axios from "axios";
import BatchSmartInput from "@/components/BatchSmartInput.vue";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";
import { useWorkingDayInputGuard } from "@/composables/useWorkingDayInputGuard";

const props = defineProps({
    form: Object,
});

defineEmits(["submit", "save-draft"]);

const selectedBatchId = ref(String(props.form.batch_id || ""));
const aoqs = ref([]);
const selectedBatchInfo = ref(null);
const loadingAoqs = ref(false);
const fetchError = ref("");

const fetchBatchAoqs = async (batchId) => {
    if (!batchId) {
        aoqs.value = [];
        selectedBatchInfo.value = null;
        fetchError.value = "";
        return;
    }

    loadingAoqs.value = true;
    fetchError.value = "";
    try {
        const res = await axios.get(
            route("bac-resolutions.batch-aoqs", batchId),
        );
        if (!res.data.aoqs?.length) {
            fetchError.value = "This batch has no AOQs available for a BAC Resolution.";
            aoqs.value = [];
            selectedBatchInfo.value = null;
            return;
        }
        aoqs.value = res.data.aoqs || [];
        selectedBatchInfo.value = res.data.batch || null;
        populateForm(res.data.aoqs || [], res.data.batch || null);
    } catch (err) {
        fetchError.value = err?.response?.data?.error || "Batch not found.";
        aoqs.value = [];
        selectedBatchInfo.value = null;
        selectedBatchId.value = "";
        props.form.batch_id = "";
    } finally {
        loadingAoqs.value = false;
    }
};

const populateForm = (aoqList, batch) => {
    if (!aoqList.length) return;

    const totalWinnerAmount = aoqList.reduce(
        (sum, aoq) => sum + Number(aoq.winner_amount || 0),
        0,
    );

    const supplierNames = [
        ...new Set(
            aoqList
                .map((aoq) => aoq.winner_supplier?.name)
                .filter((name) => Boolean(name)),
        ),
    ];

    props.form.project_name =
        aoqList.length === 1
            ? aoqList[0]?.rfq?.project_name || ""
            : `Batch of ${aoqList.length} projects`;
    props.form.winner_supplier_name =
        supplierNames.length === 1
            ? supplierNames[0]
            : `Multiple suppliers (${supplierNames.length})`;
    props.form.winner_amount = String(totalWinnerAmount.toFixed(2));
    props.form.calculation_label = "Lowest/Single Calculated";

    if (batch?.bac_date) {
        const d = new Date(batch.bac_date);
        if (!isNaN(d.getTime())) {
            const mm = String(d.getMonth() + 1).padStart(2, "0");
            const dd = String(d.getDate()).padStart(2, "0");
            const val = `${d.getFullYear()}-${mm}-${dd}`;
            props.form.resolution_date = val;
            props.form.meeting_date = val;
        }
    }

    props.form.justification =
        "for being the suppliers with the Lowest/Single Calculated and Responsive Quotations which are advantageous to the Provincial Government of Batangas.";
    props.form.signatory_chairperson =
        props.form.signatory_chairperson || "BAC Chairperson";
    props.form.signatory_member_one =
        props.form.signatory_member_one || "BAC Member";
    props.form.signatory_member_two =
        props.form.signatory_member_two || "BAC Member";
    props.form.signatory_member_three =
        props.form.signatory_member_three || "BAC Member";
};

const onBatchSelect = (batch) => {
    selectedBatchId.value = String(batch.id);
    props.form.batch_id = String(batch.id);
    fetchBatchAoqs(batch.id);
};

const { enforceWorkingDay, getDateNotice, getDateNoticeClass } =
    useWorkingDayInputGuard(props.form);

const selectedAoqs = computed(() => aoqs.value || []);

const formatCurrency = (value) =>
    new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
    }).format(value || 0);

onMounted(() => {
    if (props.form.batch_id) {
        fetchBatchAoqs(props.form.batch_id);
    }
});

watch(
    () => props.form.resolution_date,
    async (date) => {
        if (!date) return;

        await enforceWorkingDay({
            dateValue: date,
            errorKey: "resolution_date",
            statusKey: "resolution_date",
            clearDate: () => {
                props.form.resolution_date = "";
            },
        });
    },
    { immediate: true },
);

watch(
    () => props.form.meeting_date,
    async (date) => {
        await enforceWorkingDay({
            dateValue: date,
            errorKey: "meeting_date",
            statusKey: "meeting_date",
            clearDate: () => {
                props.form.meeting_date = "";
            },
        });
    },
    { immediate: true },
);
</script>

<template>
    <form @submit.prevent="$emit('submit')" class="space-y-6">
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-base">
                    <Icon icon="lucide:search" class="h-4 w-4 text-primary" />
                    Select Batch
                </CardTitle>
            </CardHeader>
            <CardContent class="space-y-3">
                <div class="space-y-2">
                    <Label for="batch_search">Batch Number</Label>
                    <BatchSmartInput
                        context="bac-resolution"
                        :model-value="selectedBatchId ? selectedBatchInfo?.batch_no || '' : ''"
                        @select="onBatchSelect"
                    />
                    <p
                        v-if="form.errors?.batch_id"
                        class="text-xs text-destructive"
                    >
                        {{ form.errors.batch_id }}
                    </p>
                </div>

                <div class="grid gap-3 md:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="resolution_date">Resolution Date</Label>
                        <input
                            id="resolution_date"
                            v-model="form.resolution_date"
                            type="date"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                        />
                        <p :class="getDateNoticeClass('resolution_date')">
                            {{ getDateNotice("resolution_date") }}
                        </p>
                        <p
                            v-if="form.errors?.resolution_date"
                            class="text-xs text-destructive"
                        >
                            {{ form.errors.resolution_date }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="meeting_date"
                            >Meeting Date (Page 2 Date)</Label
                        >
                        <input
                            id="meeting_date"
                            v-model="form.meeting_date"
                            type="date"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                        />
                        <p :class="getDateNoticeClass('meeting_date')">
                            {{ getDateNotice("meeting_date") }}
                        </p>
                        <p
                            v-if="form.errors?.meeting_date"
                            class="text-xs text-destructive"
                        >
                            {{ form.errors.meeting_date }}
                        </p>
                    </div>
                </div>
            </CardContent>
        </Card>

        <div
            v-if="fetchError"
            class="rounded-md border border-destructive/50 bg-destructive/10 p-4 text-sm text-destructive"
        >
            <div class="flex items-center gap-2">
                <Icon icon="lucide:alert-triangle" class="h-4 w-4 shrink-0" />
                <span>{{ fetchError }}</span>
            </div>
        </div>

        <Card v-if="selectedBatchId && !fetchError && (selectedAoqs.length || loadingAoqs)">
            <CardHeader>
                <CardTitle class="text-base flex items-center gap-2">
                    <Icon icon="lucide:list" class="h-4 w-4 text-primary" />
                    Batch AOQs — {{ selectedBatchInfo?.batch_no || "..." }}
                    <span v-if="loadingAoqs" class="ml-2">
                        <Icon
                            icon="lucide:loader-2"
                            class="h-4 w-4 animate-spin text-muted-foreground"
                        />
                    </span>
                </CardTitle>
            </CardHeader>
            <CardContent class="space-y-3 text-sm">
                <div class="text-muted-foreground">
                    {{ selectedAoqs.length }} AOQ(s) in this batch.
                </div>
                <div class="relative w-full overflow-auto">
                    <table class="w-full text-sm">
                        <thead class="border-b">
                            <tr>
                                <th class="px-2 py-2 text-left">SVP No.</th>
                                <th class="px-2 py-2 text-left">Office</th>
                                <th class="px-2 py-2 text-left">Project</th>
                                <th class="px-2 py-2 text-left">Winner</th>
                                <th class="px-2 py-2 text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="aoq in selectedAoqs"
                                :key="aoq.id"
                                class="border-b"
                            >
                                <td class="px-2 py-2">
                                    {{ aoq.rfq?.svp_no || "—" }}
                                </td>
                                <td class="px-2 py-2">
                                    {{
                                        aoq.rfq?.purchase_request?.office
                                            ?.name || "—"
                                    }}
                                </td>
                                <td class="px-2 py-2">
                                    {{ aoq.rfq?.project_name || "—" }}
                                </td>
                                <td class="px-2 py-2">
                                    {{ aoq.winner_supplier?.name || "—" }}
                                </td>
                                <td class="px-2 py-2 text-right">
                                    {{ formatCurrency(aoq.winner_amount || 0) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>

        <Card v-if="selectedBatchId && !fetchError">
            <CardHeader>
                <CardTitle class="text-base flex items-center gap-2">
                    <Icon
                        icon="lucide:file-pen-line"
                        class="h-4 w-4 text-primary"
                    />
                    Editable Resolution Content
                </CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="space-y-2">
                    <Label for="project_name">Project Name</Label>
                    <input
                        id="project_name"
                        v-model="form.project_name"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    />
                    <p
                        v-if="form.errors?.project_name"
                        class="text-xs text-destructive"
                    >
                        {{ form.errors.project_name }}
                    </p>
                </div>

                <div class="grid gap-4 md:grid-cols-3">
                    <div class="space-y-2 md:col-span-2">
                        <Label for="winner_supplier_name"
                            >Winner Supplier</Label
                        >
                        <input
                            id="winner_supplier_name"
                            v-model="form.winner_supplier_name"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        />
                        <p
                            v-if="form.errors?.winner_supplier_name"
                            class="text-xs text-destructive"
                        >
                            {{ form.errors.winner_supplier_name }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="winner_amount">Winner Amount</Label>
                        <input
                            id="winner_amount"
                            v-model="form.winner_amount"
                            type="number"
                            min="0"
                            step="0.01"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        />
                        <p
                            v-if="form.errors?.winner_amount"
                            class="text-xs text-destructive"
                        >
                            {{ form.errors.winner_amount }}
                        </p>
                    </div>
                </div>

                <div class="space-y-2">
                    <Label for="calculation_label">Calculation Label</Label>
                    <input
                        id="calculation_label"
                        v-model="form.calculation_label"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    />
                    <p
                        v-if="form.errors?.calculation_label"
                        class="text-xs text-destructive"
                    >
                        {{ form.errors.calculation_label }}
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="justification">Justification</Label>
                    <textarea
                        id="justification"
                        v-model="form.justification"
                        rows="4"
                        class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    />
                    <p
                        v-if="form.errors?.justification"
                        class="text-xs text-destructive"
                    >
                        {{ form.errors.justification }}
                    </p>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="signatory_chairperson"
                            >Signatory: Chairperson</Label
                        >
                        <input
                            id="signatory_chairperson"
                            v-model="form.signatory_chairperson"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="signatory_member_one"
                            >Signatory: Member 1</Label
                        >
                        <input
                            id="signatory_member_one"
                            v-model="form.signatory_member_one"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="signatory_member_two"
                            >Signatory: Member 2</Label
                        >
                        <input
                            id="signatory_member_two"
                            v-model="form.signatory_member_two"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="signatory_member_three"
                            >Signatory: Member 3</Label
                        >
                        <input
                            id="signatory_member_three"
                            v-model="form.signatory_member_three"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        />
                    </div>
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
                :disabled="form.processing"
                @click="$emit('save-draft')"
            >
                <Icon
                    v-if="form.processing"
                    icon="lucide:loader-2"
                    class="mr-2 h-4 w-4 animate-spin"
                />
                Save as Draft
            </Button>
            <Button
                type="submit"
                :disabled="
                    form.processing || meetingDateStatus?.is_available === false
                "
            >
                <Icon
                    v-if="form.processing"
                    icon="lucide:loader-2"
                    class="mr-2 h-4 w-4 animate-spin"
                />
                Create BAC Resolution (Approved)
            </Button>
        </div>
    </form>
</template>
