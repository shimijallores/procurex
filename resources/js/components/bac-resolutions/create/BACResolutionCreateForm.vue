<script setup>
import { computed, ref, watch } from "vue";
import { Icon } from "@iconify/vue";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";
import { useWorkingDayInputGuard } from "@/composables/useWorkingDayInputGuard";

const props = defineProps({
    form: Object,
    eligibleAoqs: Array,
});

defineEmits(["submit"]);

const { enforceWorkingDay, getDateNotice, getDateNoticeClass } =
    useWorkingDayInputGuard(props.form);

watch(
    () => props.form.resolution_date,
    async (date) => {
        if (!date) {
            return;
        }

        const isValid = await enforceWorkingDay({
            dateValue: date,
            errorKey: "resolution_date",
            statusKey: "resolution_date",
            clearDate: () => {
                props.form.resolution_date = "";
            },
        });

        if (!isValid) {
            return;
        }
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

const selectedAoqIds = computed(() =>
    (props.form.aoq_ids || []).map((id) => String(id)),
);

const selectedAoqs = computed(() =>
    (props.eligibleAoqs || []).filter((aoq) =>
        selectedAoqIds.value.includes(String(aoq.id)),
    ),
);

const formatCurrency = (value) =>
    new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
    }).format(value || 0);

watch(
    selectedAoqs,
    (aoqs) => {
        if (!aoqs?.length) {
            return;
        }

        const totalWinnerAmount = aoqs.reduce(
            (sum, aoq) => sum + Number(aoq.winner_amount || 0),
            0,
        );

        const supplierNames = [
            ...new Set(
                aoqs
                    .map((aoq) => aoq.winner_supplier?.name)
                    .filter((name) => Boolean(name)),
            ),
        ];

        props.form.project_name =
            aoqs.length === 1
                ? aoqs[0]?.rfq?.project_name || ""
                : `Batch of ${aoqs.length} projects`;
        props.form.winner_supplier_name =
            supplierNames.length === 1
                ? supplierNames[0]
                : `Multiple suppliers (${supplierNames.length})`;
        props.form.winner_amount = String(totalWinnerAmount.toFixed(2));
        props.form.calculation_label = "Lowest/Single Calculated";
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
    },
    { immediate: true },
);

const toggleAoqSelection = (aoqId) => {
    const value = String(aoqId);
    const current = [...selectedAoqIds.value];
    const exists = current.includes(value);

    props.form.aoq_ids = exists
        ? current.filter((id) => id !== value)
        : [...current, value];
};
</script>

<template>
    <form @submit.prevent="$emit('submit')" class="space-y-6">
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-base">
                    <Icon icon="lucide:link" class="h-4 w-4 text-primary" />
                    Source AOQ
                </CardTitle>
            </CardHeader>
            <CardContent class="space-y-3">
                <div class="space-y-2">
                    <Label>AOQs (Batch Selection)</Label>
                    <div
                        class="max-h-64 space-y-2 overflow-auto rounded-md border border-input p-2"
                    >
                        <label
                            v-for="aoq in eligibleAoqs"
                            :key="aoq.id"
                            class="flex cursor-pointer items-start gap-3 rounded-md border border-border p-2 hover:bg-muted/40"
                        >
                            <input
                                type="checkbox"
                                :checked="
                                    selectedAoqIds.includes(String(aoq.id))
                                "
                                @change="toggleAoqSelection(aoq.id)"
                                class="mt-1"
                            />
                            <div class="min-w-0 text-sm">
                                <p class="font-medium truncate">
                                    {{ aoq.rfq?.svp_no }} —
                                    {{ aoq.rfq?.project_name }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{
                                        aoq.rfq?.purchase_request?.office
                                            ?.name || "No Office"
                                    }}
                                    • AOQ Date: {{ aoq.aoq_date || "—" }}
                                    • Winner:
                                    {{ aoq.winner_supplier?.name || "N/A" }}
                                </p>
                            </div>
                        </label>
                    </div>
                    <p
                        v-if="form.errors?.aoq_ids"
                        class="text-xs text-destructive"
                    >
                        {{ form.errors.aoq_ids }}
                    </p>
                    <p
                        v-if="form.errors?.['aoq_ids.0']"
                        class="text-xs text-destructive"
                    >
                        {{ form.errors["aoq_ids.0"] }}
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

        <Card v-if="selectedAoqs.length">
            <CardHeader>
                <CardTitle class="text-base">Selected AOQ Batch</CardTitle>
            </CardHeader>
            <CardContent class="space-y-3 text-sm">
                <div class="text-muted-foreground">
                    {{ selectedAoqs.length }} AOQ(s) selected for this
                    resolution.
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

        <Card v-if="selectedAoqs.length">
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
