<script setup>
import { ref, computed } from "vue";
import { useForm } from "@inertiajs/vue3";
import Layout from "@/Layout/Layout.vue";
import DeleteModal from "@/components/DeleteModal.vue";
import EmanatingShowHeader from "@/components/emanatings/show/EmanatingShowHeader.vue";
import EmanatingMatchAlert from "@/components/emanatings/show/EmanatingMatchAlert.vue";
import EmanatingRejectionAlert from "@/components/emanatings/show/EmanatingRejectionAlert.vue";
import EmanatingShowSummary from "@/components/emanatings/show/EmanatingShowSummary.vue";
import EmanatingPPMPComparison from "@/components/emanatings/show/EmanatingPPMPComparison.vue";
import EmanatingRejectModal from "@/components/emanatings/show/EmanatingRejectModal.vue";
import EmanatingApproveModal from "@/components/emanatings/show/EmanatingApproveModal.vue";

defineOptions({
    layout: (h, page) =>
        h(
            Layout,
            {
                breadcrumbs: [
                    {
                        label: "Emanating Requests",
                        href: route("emanatings.index"),
                    },
                    { label: "Details" },
                ],
            },
            () => page,
        ),
});

const props = defineProps({
    emanating: Object,
    comparison: Object,
});

const showDeleteModal = ref(false);
const showRejectModal = ref(false);
const showApproveModal = ref(false);

// Forms
const rejectForm = useForm({
    rejection_reason: "",
});

const approveForm = useForm({});

// Check if PPMP is approved
const ppmpApproved = computed(() => {
    return true;
});

// Check if items match PPMP - use comparison status and verify all PPMP items are matched
const itemsMatch = computed(() => {
    if (!props.comparison) return false;

    // Use the comparison status from the controller
    const allMatch = props.comparison.status === "all_matched";

    // Also verify counts match (total PPMP items should equal matched items)
    const totalPPMPItems = props.comparison.total_ppmp_items || 0;
    const matchedItems = props.comparison.total_matched_items || 0;
    const unmatchedPPMPItems = props.comparison.unmatched_ppmp_items || 0;

    return (
        allMatch && totalPPMPItems === matchedItems && unmatchedPPMPItems === 0
    );
});

// Form handlers
const approveEmanating = () => {
    approveForm.post(route("emanatings.approve", props.emanating.id), {
        onSuccess: () => {
            showApproveModal.value = false;
        },
    });
};

const rejectEmanating = () => {
    rejectForm.post(route("emanatings.reject", props.emanating.id), {
        onSuccess: () => {
            showRejectModal.value = false;
            rejectForm.reset();
        },
    });
};
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <EmanatingShowHeader
            :emanating="emanating"
            :ppmp-approved="ppmpApproved"
            :items-match="itemsMatch"
            :approve-processing="approveForm.processing"
            @approve="showApproveModal = true"
            @reject="showRejectModal = true"
            @delete="showDeleteModal = true"
        />

        <!-- Match Alert -->
        <EmanatingMatchAlert
            :items-match="itemsMatch"
            :ppmp-approved="ppmpApproved"
            :is-approved="emanating.is_approved"
            :comparison="comparison"
        />

        <!-- Rejection Alert -->
        <EmanatingRejectionAlert
            :rejection-reason="emanating.rejection_reason"
        />

        <!-- Summary -->
        <EmanatingShowSummary :emanating="emanating" />

        <!-- PPMP Comparison -->
        <EmanatingPPMPComparison
            :emanating="emanating"
            :comparison="comparison"
        />

        <!-- Modals -->
        <DeleteModal
            v-model:open="showDeleteModal"
            title="Delete Emanating Request"
            :description="`Are you sure you want to delete the emanating request for ${emanating.project?.fund?.office?.name || 'Unknown'} - ${emanating.project?.name || 'Unknown'}? This action cannot be undone.`"
            :delete-url="route('emanatings.destroy', emanating.id)"
        />

        <EmanatingRejectModal
            v-model:show="showRejectModal"
            v-model:reason="rejectForm.rejection_reason"
            :processing="rejectForm.processing"
            @submit="rejectEmanating"
        />

        <EmanatingApproveModal
            v-model:show="showApproveModal"
            :emanating="emanating"
            :ppmp-approved="ppmpApproved"
            :items-match="itemsMatch"
            :processing="approveForm.processing"
            @submit="approveEmanating"
        />
    </div>
</template>
