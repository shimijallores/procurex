<script setup>
import { computed, watch } from "vue";
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
import PPMPCreateCSVUpload from "./PPMPCreateCSVUpload.vue";

const props = defineProps({
    form: Object,
    offices: Array,
    xlsxFileName: String,
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

const emit = defineEmits(["submit", "file-change"]);

const handleSubmit = () => {
    emit("submit");
};

const handleFileChange = (event) => {
    emit("file-change", event);
};
</script>

<template>
    <Card class="w-full">
        <CardHeader>
            <CardTitle>PPMP Details</CardTitle>
            <CardDescription>
                Enter the information for the new procurement plan
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

                <!-- CSV Upload Section -->
                <PPMPCreateCSVUpload
                    :xlsx-file-name="xlsxFileName"
                    :errors="form.errors"
                    @file-change="handleFileChange"
                />

                <div class="flex items-center gap-4">
                    <Button type="submit" :disabled="form.processing">
                        <Icon
                            v-if="form.processing"
                            icon="lucide:loader-2"
                            class="mr-2 h-4 w-4 animate-spin"
                        />
                        <Icon v-else icon="lucide:plus" class="mr-2 h-4 w-4" />
                        Create PPMP
                    </Button>
                    <Link :href="route('ppmps.index')">
                        <Button type="button" variant="outline">
                            Cancel
                        </Button>
                    </Link>
                </div>
            </form>
        </CardContent>
    </Card>
</template>
