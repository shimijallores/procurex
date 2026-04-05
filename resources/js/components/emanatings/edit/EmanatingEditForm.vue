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
    emanating: Object,
    offices: Array,
    ppmps: Array,
    ppmpCategories: Array,
});

const filteredPpmps = computed(() => {
    if (!props.form.office_id) {
        return [];
    }

    return props.ppmps.filter(
        (ppmp) => String(ppmp.office_id) === String(props.form.office_id),
    );
});

const filteredCategories = computed(() => {
    if (!props.form.ppmp_id) {
        return [];
    }

    return props.ppmpCategories.filter(
        (category) => String(category.ppmp_id) === String(props.form.ppmp_id),
    );
});

watch(
    () => props.form.office_id,
    () => {
        props.form.ppmp_id = "";
        props.form.ppmp_category_id = "";
    },
);

watch(
    () => props.form.ppmp_id,
    () => {
        props.form.ppmp_category_id = "";
    },
);

const emit = defineEmits(["submit"]);

const handleSubmit = () => {
    emit("submit");
};

const formatPpmpLabel = (ppmp) => {
    const projectName = ppmp.project_code?.name || "No Project Name";
    const projectCode = ppmp.project_code?.code
        ? ` (${ppmp.project_code.code})`
        : "";

    return `${ppmp.office?.name} - ${projectName}${projectCode} (FY ${ppmp.fiscal_year})`;
};
</script>

<template>
    <Card class="w-full">
        <CardHeader>
            <CardTitle>Edit Emanating Request</CardTitle>
            <CardDescription>
                Update the information for this emanating request
            </CardDescription>
        </CardHeader>
        <CardContent>
            <form @submit.prevent="handleSubmit" class="space-y-6">
                <!-- Same fields as Create form -->
                <div class="space-y-2">
                    <Label for="office_id">Office *</Label>
                    <select
                        id="office_id"
                        v-model="form.office_id"
                        :class="[
                            'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm',
                            'ring-offset-background focus-visible:outline-none focus-visible:ring-2',
                            'focus-visible:ring-ring focus-visible:ring-offset-2',
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

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Label for="ppmp_id">PPMP *</Label>
                        <select
                            id="ppmp_id"
                            v-model="form.ppmp_id"
                            :disabled="!form.office_id"
                            :class="[
                                'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm',
                                'ring-offset-background focus-visible:outline-none focus-visible:ring-2',
                                'focus-visible:ring-ring focus-visible:ring-offset-2',
                                form.errors.ppmp_id ? 'border-destructive' : '',
                            ]"
                        >
                            <option value="">Select a PPMP</option>
                            <option
                                v-for="ppmp in filteredPpmps"
                                :key="ppmp.id"
                                :value="ppmp.id"
                            >
                                {{ formatPpmpLabel(ppmp) }}
                            </option>
                        </select>
                        <p
                            v-if="form.errors.ppmp_id"
                            class="text-sm text-destructive"
                        >
                            {{ form.errors.ppmp_id }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="ppmp_category_id">PPMP Category *</Label>
                        <select
                            id="ppmp_category_id"
                            v-model="form.ppmp_category_id"
                            :disabled="!form.ppmp_id"
                            :class="[
                                'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm',
                                'ring-offset-background focus-visible:outline-none focus-visible:ring-2',
                                'focus-visible:ring-ring focus-visible:ring-offset-2',
                                form.errors.ppmp_category_id
                                    ? 'border-destructive'
                                    : '',
                            ]"
                        >
                            <option value="">Select a category</option>
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

                <!-- Info Note -->
                <div class="rounded-md bg-muted p-4">
                    <p class="text-sm text-muted-foreground">
                        <strong>Note:</strong> Fields like End User/Unit,
                        Charged To Code, Fiscal Year, Quarter, Month, and
                        signatories were imported from the XLSX and cannot be
                        edited. You can only update the PPMP category, remarks,
                        and flags below.
                    </p>
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

                <div class="flex justify-end gap-4 pt-4">
                    <Link :href="route('emanatings.show', emanating.id)">
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
                            form.processing ? "Updating..." : "Update Emanating"
                        }}
                    </Button>
                </div>
            </form>
        </CardContent>
    </Card>
</template>
