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
                Create BAC Resolution
            </Button>
        </div>
    </form>
</template>
