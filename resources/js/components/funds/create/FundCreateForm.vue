<script setup>
import { computed, watch } from "vue";
import { Link } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";

const props = defineProps({
    form: Object,
    offices: Array,
    fundType: String,
    showProjectFields: Boolean,
});

const availableProjectCodes = computed(() => {
    const officeId = Number(props.form.office_id);

    if (!officeId) {
        return [];
    }

    const selectedOffice = props.offices.find(
        (office) => Number(office.id) === officeId,
    );

    return selectedOffice?.project_codes ?? [];
});

watch(
    () => props.form.office_id,
    () => {
        const exists = availableProjectCodes.value.some(
            (projectCode) =>
                String(projectCode.id) === String(props.form.project_code_id),
        );

        if (!exists) {
            props.form.project_code_id = "";
        }
    },
);

defineEmits(["update:fundType", "submit"]);
</script>

<template>
    <Card class="max-w-2xl">
        <CardHeader>
            <CardTitle>Fund Details</CardTitle>
            <CardDescription>
                Enter the information for the new fund
            </CardDescription>
        </CardHeader>
        <CardContent>
            <form @submit.prevent="$emit('submit')" class="space-y-6">
                <div class="space-y-2">
                    <Label for="office_id">Office</Label>
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
                    <Label for="project_code_id">Project Code</Label>
                    <select
                        id="project_code_id"
                        v-model="form.project_code_id"
                        :disabled="!form.office_id"
                        :class="[
                            'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm',
                            'ring-offset-background focus-visible:outline-none focus-visible:ring-2',
                            'focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
                            form.errors.project_code_id
                                ? 'border-destructive'
                                : '',
                        ]"
                    >
                        <option value="">Select a project code</option>
                        <option
                            v-for="projectCode in availableProjectCodes"
                            :key="projectCode.id"
                            :value="projectCode.id"
                        >
                            {{ projectCode.code }} - {{ projectCode.name }}
                        </option>
                    </select>
                    <p
                        v-if="form.errors.project_code_id"
                        class="text-sm text-destructive"
                    >
                        {{ form.errors.project_code_id }}
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Label for="code">Fund Code</Label>
                        <input
                            id="code"
                            v-model="form.code"
                            type="text"
                            placeholder="Enter fund code"
                            :class="[
                                'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm',
                                'ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2',
                                'focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
                                form.errors.code ? 'border-destructive' : '',
                            ]"
                        />
                        <p
                            v-if="form.errors.code"
                            class="text-sm text-destructive"
                        >
                            {{ form.errors.code }}
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
                                form.errors.fiscal_year
                                    ? 'border-destructive'
                                    : '',
                            ]"
                        />
                        <p
                            v-if="form.errors.fiscal_year"
                            class="text-sm text-destructive"
                        >
                            {{ form.errors.fiscal_year }}
                        </p>
                    </div>
                </div>

                <div class="space-y-2">
                    <Label for="name">Fund Name</Label>
                    <input
                        id="name"
                        v-model="form.name"
                        type="text"
                        placeholder="Enter fund name"
                        :class="[
                            'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm',
                            'ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2',
                            'focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
                            form.errors.name ? 'border-destructive' : '',
                        ]"
                    />
                    <p v-if="form.errors.name" class="text-sm text-destructive">
                        {{ form.errors.name }}
                    </p>
                </div>

                <div v-if="showProjectFields" class="space-y-2">
                    <Label for="project_name">Project Name</Label>
                    <input
                        id="project_name"
                        v-model="form.project_name"
                        type="text"
                        placeholder="Enter project name (defaults to fund name if empty)"
                        :class="[
                            'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm',
                            'ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2',
                            'focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
                            form.errors.project_name
                                ? 'border-destructive'
                                : '',
                        ]"
                    />
                    <p
                        v-if="form.errors.project_name"
                        class="text-sm text-destructive"
                    >
                        {{ form.errors.project_name }}
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="type">Fund Type</Label>
                    <select
                        id="type"
                        :value="fundType"
                        @input="$emit('update:fundType', $event.target.value)"
                        :class="[
                            'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm',
                            'ring-offset-background focus-visible:outline-none focus-visible:ring-2',
                            'focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
                            form.errors.type ? 'border-destructive' : '',
                        ]"
                    >
                        <option value="general">General</option>
                        <option value="project">Project</option>
                    </select>
                    <p v-if="form.errors.type" class="text-sm text-destructive">
                        {{ form.errors.type }}
                    </p>
                </div>

                <!-- Project Documents Section (only for project type) -->
                <div
                    v-if="showProjectFields"
                    class="space-y-4 rounded-lg border p-4 bg-muted/50"
                >
                    <div class="flex items-center gap-2">
                        <Icon
                            icon="lucide:folder-kanban"
                            class="h-5 w-5 text-primary"
                        />
                        <h3 class="font-semibold">Project Documents</h3>
                    </div>
                    <p class="text-xs text-muted-foreground">
                        Upload all three project documents together.
                    </p>

                    <div class="space-y-2">
                        <Label for="work_program">Work Program (DOCX)</Label>
                        <input
                            id="work_program"
                            type="file"
                            accept=".docx"
                            @input="form.work_program = $event.target.files[0]"
                            :class="[
                                'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm',
                                'ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium',
                                'placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2',
                                'focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
                                form.errors.work_program
                                    ? 'border-destructive'
                                    : '',
                            ]"
                        />
                        <p
                            v-if="form.errors.work_program"
                            class="text-sm text-destructive"
                        >
                            {{ form.errors.work_program }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="project_brief">Project Brief (DOCX)</Label>
                        <input
                            id="project_brief"
                            type="file"
                            accept=".docx"
                            @input="form.project_brief = $event.target.files[0]"
                            :class="[
                                'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm',
                                'ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium',
                                'placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2',
                                'focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
                                form.errors.project_brief
                                    ? 'border-destructive'
                                    : '',
                            ]"
                        />
                        <p
                            v-if="form.errors.project_brief"
                            class="text-sm text-destructive"
                        >
                            {{ form.errors.project_brief }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="project_proposal"
                            >Project Proposal (DOCX)</Label
                        >
                        <input
                            id="project_proposal"
                            type="file"
                            accept=".docx"
                            @input="
                                form.project_proposal = $event.target.files[0]
                            "
                            :class="[
                                'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm',
                                'ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium',
                                'placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2',
                                'focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
                                form.errors.project_proposal
                                    ? 'border-destructive'
                                    : '',
                            ]"
                        />
                        <p
                            v-if="form.errors.project_proposal"
                            class="text-sm text-destructive"
                        >
                            {{ form.errors.project_proposal }}
                        </p>
                    </div>
                </div>

                <div class="space-y-2">
                    <Label for="remarks">Remarks</Label>
                    <textarea
                        id="remarks"
                        v-model="form.remarks"
                        rows="3"
                        placeholder="Enter any additional remarks"
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
                        <Icon v-else icon="lucide:plus" class="mr-2 h-4 w-4" />
                        Create Fund
                    </Button>
                    <Link :href="route('funds.index')">
                        <Button type="button" variant="outline">
                            Cancel
                        </Button>
                    </Link>
                </div>
            </form>
        </CardContent>
    </Card>
</template>
