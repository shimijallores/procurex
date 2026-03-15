<script setup>
import { ref, computed } from "vue";
import Layout from "@/Layout/Layout.vue";
import DeleteModal from "@/components/DeleteModal.vue";
import PPMPShowHeader from "@/components/ppmps/show/PPMPShowHeader.vue";
import PPMPShowSummaryCards from "@/components/ppmps/show/PPMPShowSummaryCards.vue";
import PPMPCategoriesTable from "@/components/ppmps/show/PPMPCategoriesTable.vue";

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
                    { label: "Details" },
                ],
            },
            () => page,
        ),
});

const props = defineProps({
    ppmp: Object,
});

const showDeleteModal = ref(false);
const searchQuery = ref("");

const resolveCategoryEstimatedBudget = (category) => {
    const categoryBudget = parseFloat(category?.estimated_budget || 0);

    if (categoryBudget > 0) {
        return categoryBudget;
    }

    const categoryItems = category?.items || [];
    return categoryItems.reduce(
        (total, item) => total + parseFloat(item?.estimated_budget || 0),
        0,
    );
};

const resolveCategoryRemainingBudget = (category) => {
    const categoryRemaining = category?.remaining_budget;
    if (categoryRemaining !== null && categoryRemaining !== undefined) {
        return parseFloat(categoryRemaining || 0);
    }

    const categoryBudget = parseFloat(category?.estimated_budget || 0);
    const categoryItems = category?.items || [];

    const itemsRemainingBudget = categoryItems.reduce(
        (total, item) =>
            total +
            parseFloat(item?.remaining_budget ?? item?.estimated_budget ?? 0),
        0,
    );

    if (categoryBudget > 0) {
        return itemsRemainingBudget > 0 ? itemsRemainingBudget : categoryBudget;
    }

    return itemsRemainingBudget;
};

// Filtered categories based on search
const filteredCategories = computed(() => {
    if (!searchQuery.value.trim()) {
        return props.ppmp.categories || [];
    }

    const query = searchQuery.value.toLowerCase();
    return (props.ppmp.categories || []).filter((category) => {
        const categoryMatch =
            category.code?.toLowerCase().includes(query) ||
            category.name?.toLowerCase().includes(query);

        const itemsMatch = category.items?.some(
            (item) =>
                item.name?.toLowerCase().includes(query) ||
                item.unit?.toLowerCase().includes(query) ||
                item.mode_of_procurement?.toLowerCase().includes(query),
        );

        return categoryMatch || itemsMatch;
    });
});

const totalEstimatedBudget = computed(() => {
    return filteredCategories.value.reduce((total, category) => {
        return total + resolveCategoryEstimatedBudget(category);
    }, 0);
});

const totalRemainingBudget = computed(() => {
    return filteredCategories.value.reduce((total, category) => {
        return total + resolveCategoryRemainingBudget(category);
    }, 0);
});
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <PPMPShowHeader :ppmp="ppmp" @delete="showDeleteModal = true" />

        <!-- Summary Cards -->
        <PPMPShowSummaryCards
            :ppmp="ppmp"
            :total-remaining-budget="totalRemainingBudget"
            :total-estimated-budget="totalEstimatedBudget"
        />

        <!-- Categories and Items -->
        <PPMPCategoriesTable
            :categories="ppmp.categories"
            :filtered-categories="filteredCategories"
        />

        <!-- Delete Confirmation Modal -->
        <DeleteModal
            v-model:open="showDeleteModal"
            title="Delete PPMP"
            :description="`Are you sure you want to delete this PPMP for ${ppmp.office?.name} (FY ${ppmp.fiscal_year})? This will permanently delete all categories and items.`"
            :delete-url="route('ppmps.destroy', ppmp.id)"
        />
    </div>
</template>
