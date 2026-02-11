<script setup>
import { ref, computed } from "vue";
import { useForm } from "@inertiajs/vue3";
import Layout from "@/Layout/Layout.vue";
import DeleteModal from "@/components/DeleteModal.vue";
import PPMPShowHeader from "@/components/ppmps/show/PPMPShowHeader.vue";
import PPMPBudgetValidationAlert from "@/components/ppmps/show/PPMPBudgetValidationAlert.vue";
import PPMPRejectionAlert from "@/components/ppmps/show/PPMPRejectionAlert.vue";
import PPMPShowSummaryCards from "@/components/ppmps/show/PPMPShowSummaryCards.vue";
import PPMPCategoriesTable from "@/components/ppmps/show/PPMPCategoriesTable.vue";
import PPMPRejectModal from "@/components/ppmps/show/PPMPRejectModal.vue";
import PPMPApproveModal from "@/components/ppmps/show/PPMPApproveModal.vue";

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
const showRejectModal = ref(false);
const showApproveModal = ref(false);
const searchQuery = ref("");

// Forms
const rejectForm = useForm({
    rejection_reason: "",
});

const approveForm = useForm({});

// Check overall budget validation status
const budgetValidationPassed = computed(() => {
    if (!props.ppmp.budget_notices || props.ppmp.budget_notices.length === 0) {
        return null;
    }
    return props.ppmp.budget_notices.every(
        (notice) => notice.status === "within_budget",
    );
});

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

// Form handlers
const approvePpmp = () => {
    approveForm.post(route("ppmps.approve", props.ppmp.id), {
        onSuccess: (response) => {
            showApproveModal.value = false;
        },
    });
};

const rejectPpmp = () => {
    rejectForm.post(route("ppmps.reject", props.ppmp.id), {
        onSuccess: (response) => {
            showRejectModal.value = false;
            rejectForm.reset();
        },
    });
};
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <PPMPShowHeader
            :ppmp="ppmp"
            :budget-validation-passed="budgetValidationPassed"
            :approve-processing="approveForm.processing"
            @approve="showApproveModal = true"
            @reject="showRejectModal = true"
            @delete="showDeleteModal = true"
        />

        <!-- Budget Validation Alert -->
        <PPMPBudgetValidationAlert
            :budget-notices="ppmp.budget_notices"
            :budget-validation-passed="budgetValidationPassed"
            :is-approved="ppmp.is_approved"
        />

        <!-- Rejection Reason Alert -->
        <PPMPRejectionAlert :rejection-reason="ppmp.rejection_reason" />

        <!-- Summary Cards -->
        <PPMPShowSummaryCards :ppmp="ppmp" :total-budget="totalBudget" />

        <!-- Categories and Items -->
        <PPMPCategoriesTable
            :categories="ppmp.categories"
            :filtered-categories="filteredCategories"
            :budget-notices="ppmp.budget_notices"
        />

        <!-- Approve Modal -->
        <PPMPApproveModal
            :open="showApproveModal"
            :processing="approveForm.processing"
            @update:open="showApproveModal = $event"
            @submit="approvePpmp"
        />

        <!-- Delete Confirmation Modal -->
        <DeleteModal
            v-model:open="showDeleteModal"
            title="Delete PPMP"
            :description="`Are you sure you want to delete this PPMP for ${ppmp.office?.name} - ${ppmp.project?.name}? This will permanently delete all categories and items.`"
            :delete-url="route('ppmps.destroy', ppmp.id)"
        />
    </div>
</template>
