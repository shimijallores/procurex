<script setup>
import { ref, computed } from "vue";
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
import { Input } from "@/components/ui/input";
import DeleteModal from "@/components/DeleteModal.vue";

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
                    { label: "Details" },
                ],
            },
            () => page,
        ),
});

const props = defineProps({
    app: Object,
});

const formatDate = (date) => {
    return new Date(date).toLocaleDateString("en-US", {
        year: "numeric",
        month: "long",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
};

const formatCurrency = (amount) => {
    return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
    }).format(amount);
};

const monthNames = [
    "Jan",
    "Feb",
    "Mar",
    "Apr",
    "May",
    "Jun",
    "Jul",
    "Aug",
    "Sep",
    "Oct",
    "Nov",
    "Dec",
];

const getMonthName = (monthNum) => {
    return monthNum ? monthNames[monthNum - 1] : "-";
};

const showDeleteModal = ref(false);
const showImportModal = ref(false);
const searchQuery = ref("");

const importForm = useForm({
    csv_file: null,
});

const csvFileName = ref("");

// Filtered categories based on search
const filteredCategories = computed(() => {
    if (!searchQuery.value.trim()) {
        return props.app.categories || [];
    }

    const query = searchQuery.value.toLowerCase();
    return (props.app.categories || []).filter((category) => {
        // Search in category fields
        const categoryMatch =
            category.pap_code?.toLowerCase().includes(query) ||
            category.name?.toLowerCase().includes(query) ||
            category.mode_of_procurement?.toLowerCase().includes(query) ||
            category.source_of_fund?.toLowerCase().includes(query);

        // Search in items
        const itemsMatch = category.items?.some(
            (item) =>
                item.name?.toLowerCase().includes(query) ||
                item.remarks?.toLowerCase().includes(query),
        );

        return categoryMatch || itemsMatch;
    });
});

// Calculate total budget (filtered categories + items)
const totalBudget = computed(() => {
    return filteredCategories.value.reduce((total, category) => {
        const categoryBudget = parseFloat(category.estimated_budget || 0);
        const itemsTotal =
            category.items?.reduce(
                (sum, item) => sum + parseFloat(item.estimated_budget || 0),
                0,
            ) || 0;
        return total + categoryBudget + itemsTotal;
    }, 0);
});

const handleFileChange = (event) => {
    const file = event.target.files[0];
    importForm.csv_file = file;
    csvFileName.value = file ? file.name : "";
};

const submitImport = () => {
    importForm.post(route("apps.import", props.app.id), {
        onSuccess: () => {
            showImportModal.value = false;
            csvFileName.value = "";
            importForm.reset();
        },
    });
};
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <Link :href="route('apps.index')">
                    <Button variant="ghost" size="sm">
                        <Icon icon="lucide:arrow-left" class="mr-2 h-4 w-4" />
                        Back
                    </Button>
                </Link>
                <div class="space-y-1">
                    <h1 class="text-2xl font-bold tracking-tight md:text-3xl">
                        {{ app.office?.name || "Unknown Office" }} -
                        {{ app.fiscal_year }}
                    </h1>
                    <p class="text-muted-foreground">
                        Annual Procurement Plan details
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a
                    v-if="app.uploaded_file"
                    :href="route('apps.download', app.id)"
                    target="_blank"
                >
                    <Button variant="outline">
                        <Icon icon="lucide:download" class="mr-2 h-4 w-4" />
                        Download File
                    </Button>
                </a>
                <Button variant="outline" @click="showImportModal = true">
                    <Icon icon="lucide:upload" class="mr-2 h-4 w-4" />
                    Import CSV
                </Button>
                <Link :href="route('apps.edit', app.id)">
                    <Button variant="outline">
                        <Icon icon="lucide:pencil" class="mr-2 h-4 w-4" />
                        Edit
                    </Button>
                </Link>
                <Button variant="destructive" @click="showDeleteModal = true">
                    <Icon icon="lucide:trash-2" class="mr-2 h-4 w-4" />
                    Delete
                </Button>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid gap-6 md:grid-cols-3">
            <!-- Details Card -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Icon icon="lucide:info" class="h-5 w-5" />
                        APP Information
                    </CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="grid gap-1">
                        <p class="text-sm font-medium text-muted-foreground">
                            Office
                        </p>
                        <p class="font-medium">
                            {{ app.office?.name || "Unknown Office" }}
                        </p>
                    </div>
                    <div class="grid gap-1">
                        <p class="text-sm font-medium text-muted-foreground">
                            Fiscal Year
                        </p>
                        <p class="font-medium">{{ app.fiscal_year }}</p>
                    </div>
                    <div class="grid gap-1">
                        <p class="text-sm font-medium text-muted-foreground">
                            Created At
                        </p>
                        <p class="text-sm">{{ formatDate(app.created_at) }}</p>
                    </div>
                </CardContent>
            </Card>

            <!-- Stats Card -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Icon icon="lucide:bar-chart-3" class="h-5 w-5" />
                        Statistics
                    </CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="grid gap-1">
                        <p class="text-sm font-medium text-muted-foreground">
                            Total Categories
                        </p>
                        <p class="text-2xl font-bold">
                            {{ app.categories?.length || 0 }}
                        </p>
                    </div>
                    <div class="grid gap-1">
                        <p class="text-sm font-medium text-muted-foreground">
                            Total Items
                        </p>
                        <p class="text-2xl font-bold">
                            {{
                                app.categories?.reduce(
                                    (sum, cat) =>
                                        sum + (cat.items?.length || 0),
                                    0,
                                ) || 0
                            }}
                        </p>
                    </div>
                </CardContent>
            </Card>

            <!-- Budget Card -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Icon icon="lucide:wallet" class="h-5 w-5" />
                        Total Budget
                    </CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="grid gap-1">
                        <p class="text-sm font-medium text-muted-foreground">
                            Estimated Budget
                        </p>
                        <p class="text-2xl font-bold">
                            {{ formatCurrency(totalBudget) }}
                        </p>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Categories and Items -->
        <Card v-if="app.categories && app.categories.length > 0">
            <CardHeader>
                <div class="flex items-center justify-between">
                    <div>
                        <CardTitle class="flex items-center gap-2">
                            <Icon icon="lucide:list" class="h-5 w-5" />
                            Procurement Categories & Items
                        </CardTitle>
                        <CardDescription>
                            {{ filteredCategories.length }} of
                            {{ app.categories.length }} categories ({{
                                filteredCategories.reduce(
                                    (sum, cat) =>
                                        sum + (cat.items?.length || 0),
                                    0,
                                )
                            }}
                            items)
                        </CardDescription>
                    </div>
                    <div class="w-80">
                        <div class="relative">
                            <Icon
                                icon="lucide:search"
                                class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground"
                            />
                            <Input
                                v-model="searchQuery"
                                placeholder="Search categories and items..."
                                class="pl-9"
                            />
                        </div>
                    </div>
                </div>
            </CardHeader>
            <CardContent>
                <div v-if="filteredCategories.length > 0" class="space-y-6">
                    <div
                        v-for="category in filteredCategories"
                        :key="category.id"
                        class="rounded-lg border p-4 space-y-4"
                    >
                        <!-- Category Header -->
                        <div class="flex items-start justify-between">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="font-mono text-sm bg-primary/10 px-2 py-1 rounded"
                                    >
                                        {{ category.pap_code }}
                                    </span>
                                    <h3 class="font-semibold">
                                        {{ category.name }}
                                    </h3>
                                </div>
                                <div
                                    class="flex items-center gap-4 text-sm text-muted-foreground"
                                >
                                    <div class="flex items-center gap-1">
                                        <Icon
                                            icon="lucide:shopping-cart"
                                            class="h-3 w-3"
                                        />
                                        {{ category.mode_of_procurement }}
                                    </div>
                                    <div
                                        v-if="category.schedule_from_month"
                                        class="flex items-center gap-1"
                                    >
                                        <Icon
                                            icon="lucide:calendar"
                                            class="h-3 w-3"
                                        />
                                        {{
                                            getMonthName(
                                                category.schedule_from_month,
                                            )
                                        }}
                                        -
                                        {{
                                            getMonthName(
                                                category.schedule_to_month,
                                            )
                                        }}
                                    </div>
                                    <div
                                        v-if="category.source_of_fund"
                                        class="flex items-center gap-1"
                                    >
                                        <Icon
                                            icon="lucide:piggy-bank"
                                            class="h-3 w-3"
                                        />
                                        {{ category.source_of_fund }}
                                    </div>
                                    <span
                                        v-if="category.early_procurement"
                                        class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300"
                                    >
                                        Early Procurement
                                    </span>
                                </div>
                            </div>
                            <div class="text-right space-y-2">
                                <div
                                    v-if="
                                        category.estimated_budget ||
                                        category.mooe_amount ||
                                        category.co_amount
                                    "
                                    class="text-sm"
                                >
                                    <p class="text-muted-foreground">
                                        Category Budget
                                    </p>
                                    <p
                                        v-if="category.estimated_budget"
                                        class="font-semibold"
                                    >
                                        {{
                                            formatCurrency(
                                                category.estimated_budget,
                                            )
                                        }}
                                    </p>
                                    <div
                                        v-if="
                                            category.mooe_amount ||
                                            category.co_amount
                                        "
                                        class="text-xs text-muted-foreground mt-1"
                                    >
                                        <p v-if="category.mooe_amount">
                                            MOOE:
                                            {{
                                                formatCurrency(
                                                    category.mooe_amount,
                                                )
                                            }}
                                        </p>
                                        <p v-if="category.co_amount">
                                            CO:
                                            {{
                                                formatCurrency(
                                                    category.co_amount,
                                                )
                                            }}
                                        </p>
                                    </div>
                                </div>
                                <div>
                                    <p class="text-sm text-muted-foreground">
                                        Items
                                    </p>
                                    <p class="text-xl font-bold">
                                        {{ category.items?.length || 0 }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Items Table -->
                        <div
                            v-if="category.items && category.items.length > 0"
                            class="relative overflow-x-auto"
                        >
                            <table class="w-full text-sm">
                                <thead class="border-b bg-muted/50">
                                    <tr>
                                        <th
                                            class="px-4 py-2 text-left font-medium"
                                        >
                                            Item Name
                                        </th>
                                        <th
                                            class="px-4 py-2 text-right font-medium"
                                        >
                                            Estimated Budget
                                        </th>
                                        <th
                                            class="px-4 py-2 text-right font-medium"
                                        >
                                            MOOE
                                        </th>
                                        <th
                                            class="px-4 py-2 text-right font-medium"
                                        >
                                            CO
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="item in category.items"
                                        :key="item.id"
                                        class="border-b last:border-0"
                                    >
                                        <td class="px-4 py-2">
                                            {{ item.name }}
                                            <span
                                                v-if="item.remarks"
                                                class="text-xs text-muted-foreground ml-2"
                                            >
                                                ({{ item.remarks }})
                                            </span>
                                        </td>
                                        <td
                                            class="px-4 py-2 text-right font-medium"
                                        >
                                            {{
                                                formatCurrency(
                                                    item.estimated_budget,
                                                )
                                            }}
                                        </td>
                                        <td class="px-4 py-2 text-right">
                                            {{
                                                item.mooe_amount
                                                    ? formatCurrency(
                                                          item.mooe_amount,
                                                      )
                                                    : "-"
                                            }}
                                        </td>
                                        <td class="px-4 py-2 text-right">
                                            {{
                                                item.co_amount
                                                    ? formatCurrency(
                                                          item.co_amount,
                                                      )
                                                    : "-"
                                            }}
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot
                                    class="border-t bg-muted/30 font-semibold"
                                >
                                    <tr>
                                        <td class="px-4 py-2">
                                            Category Total
                                        </td>
                                        <td class="px-4 py-2 text-right">
                                            {{
                                                formatCurrency(
                                                    category.items.reduce(
                                                        (sum, item) =>
                                                            sum +
                                                            parseFloat(
                                                                item.estimated_budget ||
                                                                    0,
                                                            ),
                                                        0,
                                                    ),
                                                )
                                            }}
                                        </td>
                                        <td class="px-4 py-2 text-right">
                                            {{
                                                formatCurrency(
                                                    category.items.reduce(
                                                        (sum, item) =>
                                                            sum +
                                                            parseFloat(
                                                                item.mooe_amount ||
                                                                    0,
                                                            ),
                                                        0,
                                                    ),
                                                )
                                            }}
                                        </td>
                                        <td class="px-4 py-2 text-right">
                                            {{
                                                formatCurrency(
                                                    category.items.reduce(
                                                        (sum, item) =>
                                                            sum +
                                                            parseFloat(
                                                                item.co_amount ||
                                                                    0,
                                                            ),
                                                        0,
                                                    ),
                                                )
                                            }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <div
                            v-else
                            class="text-center py-4 text-muted-foreground text-sm"
                        >
                            No items in this category
                        </div>
                    </div>
                </div>
                <div v-else class="py-12">
                    <div class="flex flex-col items-center gap-4">
                        <Icon
                            icon="lucide:search-x"
                            class="h-16 w-16 text-muted-foreground/50"
                        />
                        <div class="text-center">
                            <p class="text-lg font-medium">No results found</p>
                            <p class="text-sm text-muted-foreground">
                                Try adjusting your search query
                            </p>
                        </div>
                        <Button variant="outline" @click="searchQuery = ''">
                            Clear Search
                        </Button>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Empty State -->
        <Card v-else>
            <CardContent class="py-12">
                <div class="flex flex-col items-center gap-4">
                    <Icon
                        icon="lucide:inbox"
                        class="h-16 w-16 text-muted-foreground/50"
                    />
                    <div class="text-center">
                        <p class="text-lg font-medium">
                            No categories or items yet
                        </p>
                        <p class="text-sm text-muted-foreground">
                            Import a CSV file to populate this APP with
                            procurement data
                        </p>
                    </div>
                    <Button @click="showImportModal = true">
                        <Icon icon="lucide:upload" class="mr-2 h-4 w-4" />
                        Import CSV
                    </Button>
                </div>
            </CardContent>
        </Card>

        <!-- Import CSV Modal -->
        <div
            v-if="showImportModal"
            class="fixed inset-0 z-50 bg-background/80 backdrop-blur-sm"
            @click="showImportModal = false"
        >
            <div
                class="fixed left-1/2 top-1/2 z-50 w-full max-w-lg -translate-x-1/2 -translate-y-1/2"
                @click.stop
            >
                <Card>
                    <CardHeader>
                        <CardTitle>Import CSV Data</CardTitle>
                        <CardDescription>
                            Upload a CSV file to import categories and items
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form @submit.prevent="submitImport" class="space-y-4">
                            <div class="space-y-2">
                                <Label for="import_csv">CSV File</Label>
                                <input
                                    id="import_csv"
                                    type="file"
                                    accept=".csv,.txt,.xlsx,.xls"
                                    @change="handleFileChange"
                                    :class="[
                                        'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm',
                                        'ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium',
                                        'placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2',
                                        'focus-visible:ring-ring focus-visible:ring-offset-2',
                                        importForm.errors.csv_file
                                            ? 'border-destructive'
                                            : '',
                                    ]"
                                />
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
                                    v-if="importForm.errors.csv_file"
                                    class="text-sm text-destructive"
                                >
                                    {{ importForm.errors.csv_file }}
                                </p>
                            </div>

                            <div
                                class="rounded-md bg-yellow-50 dark:bg-yellow-950/30 p-3"
                            >
                                <div class="flex gap-2">
                                    <Icon
                                        icon="lucide:alert-triangle"
                                        class="h-4 w-4 text-yellow-600 dark:text-yellow-400 mt-0.5"
                                    />
                                    <p
                                        class="text-sm text-yellow-800 dark:text-yellow-300"
                                    >
                                        This will delete all existing categories
                                        and items, then import the new data from
                                        the CSV file.
                                    </p>
                                </div>
                            </div>

                            <div class="flex justify-end gap-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    @click="showImportModal = false"
                                >
                                    Cancel
                                </Button>
                                <Button
                                    type="submit"
                                    :disabled="
                                        importForm.processing ||
                                        !importForm.csv_file
                                    "
                                >
                                    <Icon
                                        v-if="importForm.processing"
                                        icon="lucide:loader-2"
                                        class="mr-2 h-4 w-4 animate-spin"
                                    />
                                    <Icon
                                        v-else
                                        icon="lucide:upload"
                                        class="mr-2 h-4 w-4"
                                    />
                                    Import
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <DeleteModal
            v-model:open="showDeleteModal"
            title="Delete Procurement Plan"
            :description="`Are you sure you want to delete this APP for ${app.office?.name || 'Unknown Office'}? This will permanently delete all categories and items.`"
            :delete-url="route('apps.destroy', app.id)"
        />
    </div>
</template>
