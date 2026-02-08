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
                    { label: "Create" },
                ],
            },
            () => page,
        ),
});

const props = defineProps({
    offices: Array,
});

const form = useForm({
    office_id: "",
    code: "",
    name: "",
    type: "general",
    fiscal_year: new Date().getFullYear(),
    remarks: "",
    project_name: "",
    work_program: null,
    project_brief: null,
    project_proposal: null,
});

const fundType = ref("general");
const showProjectFields = ref(false);

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

    form.transform(() => data).post(route("funds.store"));
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
                    Create Fund
                </h1>
                <p class="text-muted-foreground">
                    Add a new fund to the system
                </p>
            </div>
        </div>

        <!-- Form Card -->
        <Card class="max-w-2xl">
            <CardHeader>
                <CardTitle>Fund Details</CardTitle>
                <CardDescription>
                    Enter the information for the new fund
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
                                p
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
                                icon="lucide:plus"
                                class="mr-2 h-4 w-4"
                            />
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
    </div>
</template>
