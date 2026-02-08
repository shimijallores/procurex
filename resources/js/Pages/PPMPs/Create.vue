<script setup>
import { ref } from "vue";
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
import { Checkbox } from "@/components/ui/checkbox";

defineOptions({
    layout: (h, page) =>
        h(
            Layout,
            {
                breadcrumbs: [
                    {
                        label: "Project Procurement Management Plan",
                        href: route("ppmps.index"),
                    },
                    { label: "Create" },
                ],
            },
            () => page,
        ),
});

const props = defineProps({
    offices: Array,
    projects: Array,
});

const form = useForm({
    office_id: "",
    project_id: "",
    account_code: "",
    project_code: "",
    fiscal_year: new Date().getFullYear(),
    is_addendum: false,
    remarks: "",
    csv_file: null,
});

const csvFileName = ref("");

const handleFileChange = (event) => {
    const file = event.target.files[0];
    form.csv_file = file;
    csvFileName.value = file ? file.name : "";
};

const submit = () => {
    const data = { ...form.data() };

    // Remove null file field if no file uploaded
    if (!data.csv_file) {
        delete data.csv_file;
    }

    form.transform(() => data).post(route("ppmps.store"));
};
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center gap-4">
            <Link :href="route('ppmps.index')">
                <Button variant="ghost" size="sm">
                    <Icon icon="lucide:arrow-left" class="mr-2 h-4 w-4" />
                    Back
                </Button>
            </Link>
            <div class="space-y-1">
                <h1 class="text-2xl font-bold tracking-tight md:text-3xl">
                    Create Project Procurement Management Plan
                </h1>
                <p class="text-muted-foreground">
                    Add a new PPMP to the system
                </p>
            </div>
        </div>

        <!-- Form Card -->
        <Card class="max-w-2xl">
            <CardHeader>
                <CardTitle>PPMP Details</CardTitle>
                <CardDescription>
                    Enter the information for the new procurement plan
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

                    <div class="space-y-2">
                        <Label for="project_id">Project</Label>
                        <select
                            id="project_id"
                            v-model="form.project_id"
                            :class="[
                                'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm',
                                'ring-offset-background focus-visible:outline-none focus-visible:ring-2',
                                'focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
                                form.errors.project_id
                                    ? 'border-destructive'
                                    : '',
                            ]"
                        >
                            <option value="">Select a project</option>
                            <option
                                v-for="project in projects"
                                :key="project.id"
                                :value="project.id"
                            >
                                {{ project.name }}
                            </option>
                        </select>
                        <p
                            v-if="form.errors.project_id"
                            class="text-sm text-destructive"
                        >
                            {{ form.errors.project_id }}
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="account_code">Account Code</Label>
                            <input
                                id="account_code"
                                v-model="form.account_code"
                                type="text"
                                placeholder="e.g., 5-02-02"
                                :class="[
                                    'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm',
                                    'ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2',
                                    'focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
                                    form.errors.account_code
                                        ? 'border-destructive'
                                        : '',
                                ]"
                            />
                            <p
                                v-if="form.errors.account_code"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.account_code }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="project_code">Project Code</Label>
                            <input
                                id="project_code"
                                v-model="form.project_code"
                                type="text"
                                placeholder="e.g., PROJ-2026"
                                :class="[
                                    'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm',
                                    'ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2',
                                    'focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
                                    form.errors.project_code
                                        ? 'border-destructive'
                                        : '',
                                ]"
                            />
                            <p
                                v-if="form.errors.project_code"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.project_code }}
                            </p>
                        </div>
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

                    <div class="flex items-center space-x-2">
                        <Checkbox
                            id="is_addendum"
                            :checked="form.is_addendum"
                            @update:checked="form.is_addendum = $event"
                        />
                        <Label
                            for="is_addendum"
                            class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                        >
                            This is an addendum
                        </Label>
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
                    <div class="space-y-4 rounded-lg border p-4 bg-muted/50">
                        <div class="flex items-center gap-2">
                            <Icon
                                icon="lucide:file-spreadsheet"
                                class="h-5 w-5 text-primary"
                            />
                            <h3 class="font-semibold">Import from CSV</h3>
                        </div>

                        <p class="text-sm text-muted-foreground">
                            Upload a CSV file to automatically populate
                            categories and items. You can also create an empty
                            PPMP and import the CSV later.
                        </p>

                        <div class="space-y-2">
                            <Label for="csv_file">CSV File (Optional)</Label>
                            <div class="flex items-center gap-2">
                                <input
                                    id="csv_file"
                                    type="file"
                                    accept=".csv,.txt,.xlsx,.xls"
                                    @change="handleFileChange"
                                    :class="[
                                        'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm',
                                        'ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium',
                                        'placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2',
                                        'focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
                                        form.errors.csv_file
                                            ? 'border-destructive'
                                            : '',
                                    ]"
                                />
                            </div>
                            <p
                                v-if="csvFileName"
                                class="text-sm text-muted-foreground flex items-center gap-1"
                            >
                                <Icon
                                    icon="lucide:file-check"
                                    class="h-3 w-3"
                                />
                                Selected: {{ csvFileName }}
                            </p>
                            <p
                                v-if="form.errors.csv_file"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.csv_file }}
                            </p>
                        </div>

                        <div
                            class="rounded-md bg-blue-50 dark:bg-blue-950/30 p-3"
                        >
                            <div class="flex gap-2">
                                <Icon
                                    icon="lucide:info"
                                    class="h-4 w-4 text-blue-600 dark:text-blue-400 mt-0.5"
                                />
                                <div
                                    class="text-sm text-blue-800 dark:text-blue-300"
                                >
                                    <p class="font-medium mb-1">
                                        CSV Format Tips:
                                    </p>
                                    <ul
                                        class="list-disc list-inside space-y-1 text-xs"
                                    >
                                        <li>
                                            Make sure to use the provided PPMP
                                            template
                                        </li>
                                        <li>
                                            Budget validation against APP
                                            categories will be performed
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
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
    </div>
</template>
