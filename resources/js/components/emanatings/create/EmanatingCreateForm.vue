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
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";

const props = defineProps({
    form: Object,
    funds: Array,
    xlsxFileName: String,
});

const officeOptions = computed(() => {
    const seen = new Set();

    return (props.funds || [])
        .map((fund) => fund.office)
        .filter((office) => office && office.id)
        .filter((office) => {
            const key = String(office.id);
            if (seen.has(key)) {
                return false;
            }

            seen.add(key);
            return true;
        })
        .sort((a, b) =>
            String(a.name || "").localeCompare(String(b.name || "")),
        );
});

const filteredFunds = computed(() => {
    if (!props.form.office_id) {
        return [];
    }

    return (props.funds || []).filter(
        (fund) => String(fund.office?.id) === String(props.form.office_id),
    );
});

const selectedFund = computed(() => {
    if (!props.form.fund_id) {
        return null;
    }

    return (
        filteredFunds.value.find(
            (fund) => String(fund.id) === String(props.form.fund_id),
        ) || null
    );
});

const filteredCategories = computed(() => {
    if (!selectedFund.value) {
        return [];
    }

    return selectedFund.value.ppmp_categories || [];
});

watch(
    () => props.form.office_id,
    () => {
        props.form.fund_id = "";
        props.form.ppmp_category_id = "";
    },
);

watch(
    () => props.form.fund_id,
    () => {
        props.form.ppmp_category_id = "";
    },
);

const emit = defineEmits(["submit", "file-change"]);

const handleSubmit = () => {
    emit("submit");
};

const handleFileChange = (event) => {
    emit("file-change", event);
};

const formatFundLabel = (fund) => {
    const codeLabel =
        fund.type === "project"
            ? `Project Code: ${fund.project_code?.code || "N/A"}`
            : `General Fund`;

    return `${fund.office?.name || "Unknown Office"} - ${fund.name} (${codeLabel}, FY ${fund.fiscal_year})`;
};
</script>

<template>
    <Card class="w-full">
        <CardHeader>
            <CardTitle>Upload Emanating Request XLSX</CardTitle>
            <CardDescription>
                Upload an XLSX file to create an emanating request. All required
                data will be automatically extracted from the file.
            </CardDescription>
        </CardHeader>
        <CardContent>
            <form @submit.prevent="handleSubmit" class="space-y-6">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="office_id">Office *</Label>
                        <select
                            id="office_id"
                            v-model="form.office_id"
                            :class="[
                                'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm',
                                'ring-offset-background focus-visible:outline-none focus-visible:ring-2',
                                'focus-visible:ring-ring focus-visible:ring-offset-2',
                                form.errors.office_id
                                    ? 'border-destructive'
                                    : '',
                            ]"
                        >
                            <option value="">Select an office</option>
                            <option
                                v-for="office in officeOptions"
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
                        <Label for="fund_id">Fund *</Label>
                        <select
                            id="fund_id"
                            v-model="form.fund_id"
                            :disabled="!form.office_id"
                            :class="[
                                'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm',
                                'ring-offset-background focus-visible:outline-none focus-visible:ring-2',
                                'focus-visible:ring-ring focus-visible:ring-offset-2',
                                form.errors.fund_id ? 'border-destructive' : '',
                            ]"
                        >
                            <option value="">Select a fund</option>
                            <option
                                v-for="fund in filteredFunds"
                                :key="fund.id"
                                :value="fund.id"
                            >
                                {{ formatFundLabel(fund) }}
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
                        <Label for="ppmp_category_id"
                            >Account / PPMP Category *</Label
                        >
                        <select
                            id="ppmp_category_id"
                            v-model="form.ppmp_category_id"
                            :disabled="!form.office_id || !form.fund_id"
                            :class="[
                                'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm',
                                'ring-offset-background focus-visible:outline-none focus-visible:ring-2',
                                'focus-visible:ring-ring focus-visible:ring-offset-2',
                                form.errors.ppmp_category_id
                                    ? 'border-destructive'
                                    : '',
                            ]"
                        >
                            <option value="">
                                Select an account / category
                            </option>
                            <option
                                v-for="category in filteredCategories"
                                :key="category.id"
                                :value="category.id"
                            >
                                {{ category.code }} - {{ category.name }}
                            </option>
                        </select>
                        <p
                            v-if="form.errors.ppmp_category_id"
                            class="text-sm text-destructive"
                        >
                            {{ form.errors.ppmp_category_id }}
                        </p>
                    </div>
                </div>

                <!-- PR No (Optional) -->
                <div class="space-y-2">
                    <Label for="pr_no">PR No. (Optional)</Label>
                    <Input
                        id="pr_no"
                        v-model="form.pr_no"
                        type="text"
                        placeholder="e.g., PR-2025-001"
                    />
                    <p
                        v-if="form.errors.pr_no"
                        class="text-sm text-destructive"
                    >
                        {{ form.errors.pr_no }}
                    </p>
                </div>

                <!-- XLSX Upload (Required) -->
                <div class="space-y-2">
                    <Label for="xlsx_file">XLSX File *</Label>
                    <div
                        class="rounded-lg border-2 border-dashed border-primary/30 bg-background p-4"
                    >
                        <Input
                            id="xlsx_file"
                            type="file"
                            accept=".xlsx"
                            @change="handleFileChange"
                        />
                        <p class="mt-2 text-xs text-muted-foreground">
                            Supported format: <strong>.xlsx</strong>
                        </p>
                    </div>
                    <p
                        v-if="xlsxFileName"
                        class="text-sm text-muted-foreground"
                    >
                        Selected: {{ xlsxFileName }}
                    </p>
                    <p
                        v-if="form.errors.xlsx_file"
                        class="text-sm text-destructive"
                    >
                        {{ form.errors.xlsx_file }}
                    </p>
                </div>

                <!-- Remarks -->
                <div class="space-y-2">
                    <Label for="remarks">Remarks (Optional)</Label>
                    <Textarea
                        id="remarks"
                        v-model="form.remarks"
                        rows="2"
                        placeholder="Any additional notes..."
                    />
                </div>

                <!-- Checkboxes -->
                <div class="flex gap-6">
                    <div class="flex items-center space-x-2">
                        <input
                            id="reimbursement"
                            v-model="form.reimbursement"
                            type="checkbox"
                            class="h-4 w-4 rounded border border-input"
                        />
                        <Label for="reimbursement">Reimbursement</Label>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex justify-end gap-4 pt-4">
                    <Link :href="route('emanatings.index')">
                        <Button type="button" variant="outline">
                            Cancel
                        </Button>
                    </Link>
                    <Button type="submit" :disabled="form.processing">
                        <Icon
                            v-if="form.processing"
                            icon="lucide:loader-2"
                            class="mr-2 h-4 w-4 animate-spin"
                        />
                        <Icon v-else icon="lucide:save" class="mr-2 h-4 w-4" />
                        {{
                            form.processing ? "Creating..." : "Create Emanating"
                        }}
                    </Button>
                </div>
            </form>
        </CardContent>
    </Card>
</template>
