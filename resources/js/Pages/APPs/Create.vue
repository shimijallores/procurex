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

defineOptions({
    layout: (h, page) =>
        h(
            Layout,
            {
                breadcrumbs: [
                    {
                        label: "Annual Procurement Plan",
                        href: route("apps.index"),
                    },
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
    fiscal_year: new Date().getFullYear(),
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

    form.transform(() => data).post(route("apps.store"));
};
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center gap-4">
            <Link :href="route('apps.index')">
                <Button variant="ghost" size="sm">
                    <Icon icon="lucide:arrow-left" class="mr-2 h-4 w-4" />
                    Back
                </Button>
            </Link>
            <div class="space-y-1">
                <h1 class="text-2xl font-bold tracking-tight md:text-3xl">
                    Create Annual Procurement Plan
                </h1>
                <p class="text-muted-foreground">Add a new APP to the system</p>
            </div>
        </div>

        <!-- Form Card -->
        <Card class="max-w-2xl">
            <CardHeader>
                <CardTitle>APP Details</CardTitle>
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
                            APP and import the CSV later.
                        </p>

                        <div class="space-y-2">
                            <Label for="csv_file">CSV File (Optional)</Label>
                            <div class="flex items-center gap-2">
                                <input
                                    id="csv_file"
                                    type="file"
                                    accept=".csv,.txt"
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
                                            Make sure to use the provided APP
                                            template
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
                            Create APP
                        </Button>
                        <Link :href="route('apps.index')">
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
