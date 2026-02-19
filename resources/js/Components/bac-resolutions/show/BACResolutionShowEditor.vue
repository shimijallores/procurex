<script setup>
import { computed, ref, watch } from "vue";
import { Icon } from "@iconify/vue";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Label } from "@/components/ui/label";
import { useCalendarCheck } from "@/composables/useCalendarCheck";

const props = defineProps({
    resolution: Object,
    form: Object,
});

const canEdit = computed(() => !props.resolution?.finalized_at);

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
</script>

<template>
    <div class="space-y-4">
        <div
            v-if="resolution.finalized_at"
            class="rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-900 dark:bg-green-950/30 dark:text-green-300"
        >
            This BAC Resolution is finalized and locked for editing.
        </div>

        <Card>
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
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="resolution_date">Resolution Date</Label>
                        <input
                            id="resolution_date"
                            v-model="form.resolution_date"
                            type="date"
                            :disabled="!canEdit"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
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
                            :disabled="!canEdit"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
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

                <div class="space-y-2">
                    <Label for="project_name">Project Name</Label>
                    <input
                        id="project_name"
                        v-model="form.project_name"
                        :disabled="!canEdit"
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
                            :disabled="!canEdit"
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
                            step="0.01"
                            min="0"
                            :disabled="!canEdit"
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
                        :disabled="!canEdit"
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
                        :disabled="!canEdit"
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
                            :disabled="!canEdit"
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
                            :disabled="!canEdit"
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
                            :disabled="!canEdit"
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
                            :disabled="!canEdit"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        />
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
