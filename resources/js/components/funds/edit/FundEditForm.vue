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
    fund: Object,
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

watch(
    () => props.fundType,
    (newType) => {
        if (newType !== "project") {
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
                Modify the fund information below
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

                <div v-if="showProjectFields" class="space-y-2">
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
                        Project funds require work program, project brief, and
                        project proposal.
                    </p>

                    <div class="space-y-2">
                        <Label for="work_program">Work Program (DOCX)</Label>
                        <div
                            v-if="fund.project?.work_program"
                            class="text-sm text-muted-foreground mb-2"
                        >
                            Current file:
                            <a
                                :href="`/storage/${fund.project.work_program.file_url}`"
                                target="_blank"
                                class="text-cyan-600 underline hover:no-underline"
                            >
                                View current work program
                            </a>
                        </div>
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
                        <p class="text-xs text-muted-foreground">
                            Upload a new file to replace the current one
                        </p>
                        <p
                            v-if="form.errors.work_program"
                            class="text-sm text-destructive"
                        >
                            {{ form.errors.work_program }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="project_brief">Project Brief (DOCX)</Label>
                        <div
                            v-if="fund.project?.project_brief"
                            class="text-sm text-muted-foreground mb-2"
                        >
                            Current file:
                            <a
                                :href="`/storage/${fund.project.project_brief.file_url}`"
                                target="_blank"
                                class="text-cyan-600 underline hover:no-underline"
                            >
                                View current project brief
                            </a>
                        </div>
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
                        <p class="text-xs text-muted-foreground">
                            Upload a new file to replace the current one
                        </p>
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
                        <div
                            v-if="fund.project?.project_proposal"
                            class="text-sm text-muted-foreground mb-2"
                        >
                            Current file:
                            <a
                                :href="`/storage/${fund.project.project_proposal.file_url}`"
                                target="_blank"
                                class="text-cyan-600 underline hover:no-underline"
                            >
                                View current project proposal
                            </a>
                        </div>
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
                        <p class="text-xs text-muted-foreground">
                            Upload a new file to replace the current one
                        </p>
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
                        <Icon v-else icon="lucide:save" class="mr-2 h-4 w-4" />
                        Save Changes
                    </Button>
                    <Link :href="route('funds.index')">
                        <Button type="button" variant="outline">
                            Cancel
                        </Button>
                    </Link>
                </div>
            </form>

            <div
                v-if="
                    showProjectFields &&
                    form.processing &&
                    (form.progress ||
                        form.work_program ||
                        form.project_brief ||
                        form.project_proposal)
                "
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/45 px-4 backdrop-blur-[1px]"
            >
                <div
                    class="w-full max-w-md rounded-xl border border-border bg-background p-5 shadow-xl"
                >
                    <div class="mb-1 text-base font-semibold">
                        Uploading project documents
                    </div>
                    <p class="mb-4 text-sm text-muted-foreground">
                        Please keep this page open while files are uploading.
                    </p>

                    <div class="mb-2 flex items-center justify-between text-sm">
                        <span>Progress</span>
                        <span class="font-medium"
                            >{{ form.progress?.percentage ?? 0 }}%</span
                        >
                    </div>
                    <div
                        class="h-2 w-full overflow-hidden rounded-full bg-muted"
                    >
                        <div
                            class="h-full rounded-full bg-primary transition-all duration-300"
                            :style="{
                                width: `${form.progress?.percentage ?? 0}%`,
                            }"
                        />
                    </div>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
