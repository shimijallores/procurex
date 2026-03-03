<script setup>
import { computed, ref, watch } from "vue";
import { Icon } from "@iconify/vue";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";
import { useCalendarCheck } from "@/composables/useCalendarCheck";

const props = defineProps({
    form: Object,
    eligibleAoqs: Array,
});

defineEmits(["submit"]);

const { checkDate } = useCalendarCheck();
const meetingDateStatus = ref(null);

watch(
    () => props.form.meeting_date,
    async (date) => {
        if (!date) {
            meetingDateStatus.value = null;
            return;
        }

        meetingDateStatus.value = await checkDate(date);
    },
    { immediate: true },
);

const selectedAoq = computed(() =>
    props.eligibleAoqs?.find(
        (aoq) => String(aoq.id) === String(props.form.aoq_id),
    ),
);

const formatCurrency = (value) =>
    new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
    }).format(value || 0);

watch(
    selectedAoq,
    (aoq) => {
        if (!aoq) {
            return;
        }

        const calculationLabel =
            aoq.calculation_label ||
            (Number(aoq.calculated_supplier_count || 0) <= 1
                ? "Single Calculated"
                : "Lowest Calculated");

        props.form.project_name = aoq.rfq?.project_name || "";
        props.form.winner_supplier_name = aoq.winner_supplier?.name || "N/A";
        props.form.winner_amount = String(aoq.winner_amount ?? 0);
        props.form.calculation_label = calculationLabel;
        props.form.justification = `for being the supplier with the ${calculationLabel} and Responsive Quotation which is advantageous to the Provincial Government of Batangas.`;
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
                    <Label for="aoq_id">AOQ</Label>
                    <select
                        id="aoq_id"
                        :value="form.aoq_id"
                        @change="form.aoq_id = $event.target.value"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                    >
                        <option value="">— Select AOQ —</option>
                        <option
                            v-for="aoq in eligibleAoqs"
                            :key="aoq.id"
                            :value="aoq.id"
                        >
                            {{ aoq.rfq?.svp_no }} — {{ aoq.rfq?.project_name }}
                        </option>
                    </select>
                    <p
                        v-if="form.errors?.aoq_id"
                        class="text-xs text-destructive"
                    >
                        {{ form.errors.aoq_id }}
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
                        <p
                            class="text-xs"
                            :class="
                                meetingDateStatus?.is_available === false
                                    ? 'text-destructive'
                                    : 'text-muted-foreground'
                            "
                        >
                            {{
                                meetingDateStatus?.message ||
                                "Meeting date must be a working day (not weekend/holiday)."
                            }}
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

        <Card v-if="selectedAoq">
            <CardHeader>
                <CardTitle class="text-base">AOQ Preview</CardTitle>
            </CardHeader>
            <CardContent class="grid gap-3 text-sm md:grid-cols-2">
                <div>
                    <p class="text-muted-foreground">SVP No.</p>
                    <p class="font-medium">
                        {{ selectedAoq.rfq?.svp_no || "—" }}
                    </p>
                </div>
                <div>
                    <p class="text-muted-foreground">Project</p>
                    <p class="font-medium">
                        {{ selectedAoq.rfq?.project_name || "—" }}
                    </p>
                </div>
                <div>
                    <p class="text-muted-foreground">Winner Supplier</p>
                    <p class="font-medium">
                        {{ selectedAoq.winner_supplier?.name || "—" }}
                    </p>
                </div>
                <div>
                    <p class="text-muted-foreground">AOQ Date</p>
                    <p class="font-medium">{{ selectedAoq.aoq_date || "—" }}</p>
                </div>
                <div>
                    <p class="text-muted-foreground">RFQ ABC</p>
                    <p class="font-medium">
                        {{ formatCurrency(selectedAoq.rfq?.abc_amount || 0) }}
                    </p>
                </div>
            </CardContent>
        </Card>

        <Card v-if="selectedAoq">
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
