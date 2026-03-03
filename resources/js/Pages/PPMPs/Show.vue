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

// Calculate total budget
const totalBudget = computed(() => {
    return filteredCategories.value.reduce((total, category) => {
        return total + parseFloat(category.estimated_budget || 0);
    }, 0);
});
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <PPMPShowHeader :ppmp="ppmp" @delete="showDeleteModal = true" />

        <!-- Summary Cards -->
        <PPMPShowSummaryCards :ppmp="ppmp" :total-budget="totalBudget" />

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
