<script setup>
import { computed, watch, ref } from "vue";
import { Icon } from "@iconify/vue";
import { Link } from "@inertiajs/vue3";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";

const props = defineProps({
    form: Object,
    ppmp: Object,
    offices: Array,
    existingPpmps: Array,
});

const availableFunds = computed(() => {
    const officeId = Number(props.form.office_id);

    if (!officeId) {
        return [];
    }

    const selectedOffice = props.offices.find(
        (office) => Number(office.id) === officeId,
    );

    return selectedOffice?.funds ?? [];
});

const selectedFund = computed(() => {
    if (!props.form.fund_id) return null;
    return availableFunds.value.find(
        (fund) => String(fund.id) === String(props.form.fund_id),
    ) || null;
});

const hasExistingPpmp = computed(() => {
    if (!selectedFund.value || !props.form.office_id) return false;
    const projectCodeId = selectedFund.value.type === "project"
        ? selectedFund.value.project_code_id
        : null;
    return (props.existingPpmps || []).some(
        (ppmp) =>
            String(ppmp.office_id) === String(props.form.office_id) &&
            String(ppmp.project_code_id) === String(projectCodeId),
    );
});

const showWarning = ref(false);

watch(
    () => props.form.office_id,
    () => {
        const exists = availableFunds.value.some(
            (fund) => String(fund.id) === String(props.form.fund_id),
        );

        if (!exists) {
            props.form.fund_id = "";
        }
    },
);

const emit = defineEmits(["submit"]);

const handleSubmit = () => {
    if (hasExistingPpmp.value) {
        showWarning.value = true;
    } else {
        emit("submit");
    }
};

const confirmSubmit = () => {
    showWarning.value = false;
    emit("submit");
};
</script>

<template>
    <Card class="w-full">
        <CardHeader>
            <CardTitle>PPMP Details</CardTitle>
            <CardDescription>
                Update the information for this procurement plan
            </CardDescription>
        </CardHeader>
        <CardContent>
            <form @submit.prevent="handleSubmit" class="space-y-6">
                <div class="space-y-2">
                    <Label for="office_id">END USER/UNIT</Label>
                    <select
                        id="office_id"
                        v-model="form.office_id"
                        :class="[
                            'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm',
                            'ring-offset-background focus-visible:outline-none focus-visible:ring-2',
                            'focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
                            form.errors.office_id ? 'border-destructive' : '',
                        ]"
                    >
                        <option value="">Select an office</option>
                        <option
                            v-for="office in offices"
                            :key="office.id"
                            :value="office.id"
                        >
                            {{ office.name }}
                        </option>
                    </select>
                    <p
                        v-if="form.errors.office_id"
                        class="text-sm text-destructive"
                    >
                        {{ form.errors.office_id }}
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="fiscal_year">Fiscal Year</Label>
                    <input
                        id="fiscal_year"
                        v-model="form.fiscal_year"
                        type="number"
                        min="2000"
                        max="2100"
                        placeholder="2026"
                        :class="[
                            'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm',
                            'ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2',
                            'focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
                            form.errors.fiscal_year ? 'border-destructive' : '',
                        ]"
                    />
                    <p
                        v-if="form.errors.fiscal_year"
                        class="text-sm text-destructive"
                    >
                        {{ form.errors.fiscal_year }}
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="fund_id">Fund</Label>
                    <select
                        id="fund_id"
                        v-model="form.fund_id"
                        :disabled="!form.office_id"
                        :class="[
                            'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm',
                            'ring-offset-background focus-visible:outline-none focus-visible:ring-2',
                            'focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
                            form.errors.fund_id ? 'border-destructive' : '',
                        ]"
                    >
                        <option value="">Select a fund</option>
                        <option
                            v-for="fund in availableFunds"
                            :key="fund.id"
                            :value="fund.id"
                        >
                            {{ fund.name }}
                            ({{
                                fund.type === "project" ? "Project" : "General"
                            }}) -
                            {{
                                fund.type === "project"
                                    ? `Project Code: ${fund.project_code?.code ?? "N/A"}`
                                    : `General Code: ${
                                          offices.find(
                                              (office) =>
                                                  Number(office.id) ===
                                                  Number(form.office_id),
                                          )?.code ?? "N/A"
                                      }`
                            }}
                            - FY {{ fund.fiscal_year }}
                        </option>
                    </select>
                    <p
                        v-if="form.errors.fund_id"
                        class="text-sm text-destructive"
                    >
                        {{ form.errors.fund_id }}
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="remarks">Remarks (Optional)</Label>
                    <textarea
                        id="remarks"
                        v-model="form.remarks"
                        rows="3"
                        placeholder="Any additional notes or remarks..."
                        :class="[
                            'flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm',
                            'ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2',
                            'focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
                            form.errors.remarks ? 'border-destructive' : '',
                        ]"
                    />
                    <p
                        v-if="form.errors.remarks"
                        class="text-sm text-destructive"
                    >
                        {{ form.errors.remarks }}
                    </p>
                </div>

                <div class="flex items-center gap-4">
                    <Button type="submit" :disabled="form.processing">
                        <Icon
                            v-if="form.processing"
                            icon="lucide:loader-2"
                            class="mr-2 h-4 w-4 animate-spin"
                        />
                        <Icon v-else icon="lucide:save" class="mr-2 h-4 w-4" />
                        Update PPMP
                    </Button>
                    <Link :href="route('ppmps.index')">
                        <Button type="button" variant="outline">
                            Cancel
                        </Button>
                    </Link>
                </div>
            </form>
        </CardContent>

        <!-- Warning Modal -->
        <div
            v-if="showWarning"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/45 px-4 backdrop-blur-[1px]"
            @click.self="showWarning = false"
        >
            <div
                class="w-full max-w-md rounded-xl border border-border bg-background p-6 shadow-xl"
            >
                <div class="flex items-center gap-3 mb-4">
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-900/30"
                    >
                        <Icon
                            icon="lucide:alert-triangle"
                            class="h-5 w-5 text-amber-600 dark:text-amber-400"
                        />
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold">
                            Existing PPMP Found
                        </h3>
                        <p class="text-sm text-muted-foreground">
                            This office and fund combination already has a PPMP.
                        </p>
                    </div>
                </div>
                <div class="border-t my-4" />
                <p class="text-sm text-muted-foreground mb-6 text-justify">
                    This office and fund combination already has a PPMP.
                    Saving will <strong>create an addendum</strong> — the new
                    items will be appended to the existing PPMP.
                </p>
                <div class="flex justify-end gap-3">
                    <Button variant="outline" @click="showWarning = false">
                        Cancel
                    </Button>
                    <Button variant="default" @click="confirmSubmit">
                        Create Addendum
                    </Button>
                </div>
            </div>
        </div>
    </Card>
</template>
