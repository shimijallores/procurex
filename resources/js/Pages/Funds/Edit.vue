<script setup>
import { ref, watch } from "vue";
import { Link, useForm } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import Layout from "@/Layout/Layout.vue";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";

defineOptions({
    layout: (h, page) =>
        h(
            Layout,
            {
                breadcrumbs: [
                    { label: "Funds", href: route("funds.index") },
                    { label: "Edit" },
                ],
            },
            () => page,
        ),
});

const props = defineProps({
    fund: Object,
    offices: Array,
});

const form = useForm({
    office_id: props.fund.office_id,
    code: props.fund.code,
    name: props.fund.name,
    type: props.fund.type,
    fiscal_year: props.fund.fiscal_year,
    remarks: props.fund.remarks,
    project_name: props.fund.project?.name || "",
    work_program: null,
    project_brief: null,
    project_proposal: null,
});

const fundType = ref(props.fund.type);
const showProjectFields = ref(props.fund.type === "project");

watch(fundType, (newType) => {
    showProjectFields.value = newType === "project";
    form.type = newType;
});

const submit = () => {
    const data = { ...form.data() };

    // Remove null file fields to allow JSON requests when no files are uploaded
    if (!data.work_program) delete data.work_program;
    if (!data.project_brief) delete data.project_brief;
    if (!data.project_proposal) delete data.project_proposal;

    // If files exist, add _method for Laravel
    const hasFiles =
        data.work_program || data.project_brief || data.project_proposal;
    if (hasFiles) {
        data._method = "PUT";
    }

    form.transform(() => data)[hasFiles ? "post" : "put"](
        route("funds.update", props.fund.id),
    );
};
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center gap-4">
            <Link :href="route('funds.index')">
                <Button variant="ghost" size="sm">
                    <Icon icon="lucide:arrow-left" class="mr-2 h-4 w-4" />
                    Back
                </Button>
            </Link>
            <div class="space-y-1">
                <h1 class="text-2xl font-bold tracking-tight md:text-3xl">
                    Edit Fund
                </h1>
                <p class="text-muted-foreground">Update fund information</p>
            </div>
        </div>

        <!-- Form Card -->
        <Card class="max-w-2xl">
            <CardHeader>
                <CardTitle>Fund Details</CardTitle>
                <CardDescription>
                    Modify the fund information below
                </CardDescription>
            </CardHeader>
            <CardContent>
                <form @submit.prevent="submit" class="space-y-6">
                    <div class="space-y-2">
                        <Label for="office_id">Office</Label>
                        <select
                            id="office_id"
                            v-model="form.office_id"
                            :class="[
                                'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm',
                                'ring-offset-background focus-visible:outline-none focus-visible:ring-2',
                                'focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
                                form.errors.office_id
                                    ? 'border-destructive'
                                    : '',
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
                                    form.errors.code
                                        ? 'border-destructive'
                                        : '',
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
                        <p
                            v-if="form.errors.name"
                            class="text-sm text-destructive"
                        >
                            {{ form.errors.name }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="type">Fund Type</Label>
                        <select
                            id="type"
                            v-model="fundType"
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
                        <p
                            v-if="form.errors.type"
                            class="text-sm text-destructive"
                        >
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

                        <div class="space-y-2">
                            <Label for="work_program">Work Program (PDF)</Label>
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
                                accept=".pdf"
                                @input="
                                    form.work_program = $event.target.files[0]
                                "
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
                            <Label for="project_brief"
                                >Project Brief (PDF)</Label
                            >
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
                                accept=".pdf"
                                @input="
                                    form.project_brief = $event.target.files[0]
                                "
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
                                >Project Proposal (PDF)</Label
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
                                accept=".pdf"
                                @input="
                                    form.project_proposal =
                                        $event.target.files[0]
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
                            <Icon
                                v-else
                                icon="lucide:save"
                                class="mr-2 h-4 w-4"
                            />
                            Save Changes
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
    </div>
</template>
