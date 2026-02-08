<script setup>
import { ref, computed } from "vue";
import { useForm } from "@inertiajs/vue3";
import Layout from "@/Layout/Layout.vue";
import { Card, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Icon } from "@iconify/vue";
import DeleteModal from "@/components/DeleteModal.vue";
import AppShowHeader from "@/components/apps/show/AppShowHeader.vue";
import AppShowSummaryCards from "@/components/apps/show/AppShowSummaryCards.vue";
import AppCategoriesTable from "@/components/apps/show/AppCategoriesTable.vue";
import AppImportModal from "@/components/apps/show/AppImportModal.vue";

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

const showDeleteModal = ref(false);
const showImportModal = ref(false);
const searchQuery = ref("");

const importForm = useForm({
    csv_file: null,
});

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

const submitImport = (file) => {
    importForm.csv_file = file;
    importForm.post(route("apps.import", props.app.id), {
        onSuccess: () => {
            showImportModal.value = false;
            importForm.reset();
        },
    });
};
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <AppShowHeader
            :app="app"
            @import="showImportModal = true"
            @delete="showDeleteModal = true"
        />

        <!-- Summary Cards -->
        <AppShowSummaryCards :app="app" :total-budget="totalBudget" />

        <!-- Categories and Items -->
        <AppCategoriesTable
            :categories="app.categories"
            :filtered-categories="filteredCategories"
            :search-query="searchQuery"
            @update:search-query="searchQuery = $event"
        />

        <!-- Empty State -->
        <Card v-if="!app.categories || app.categories.length === 0">
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
        <AppImportModal
            :open="showImportModal"
            :app-id="app.id"
            :processing="importForm.processing"
            :errors="importForm.errors"
            @update:open="showImportModal = $event"
            @submit="submitImport"
        />

        <!-- Delete Confirmation Modal -->
        <DeleteModal
            v-model:open="showDeleteModal"
            title="Delete Procurement Plan"
            :description="`Are you sure you want to delete this APP for ${app.office?.name || 'Unknown Office'}? This will permanently delete all categories and items.`"
            :delete-url="route('apps.destroy', app.id)"
        />
    </div>
</template>
