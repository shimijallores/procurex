<script setup>
import { computed } from "vue";
import { Alert, AlertDescription } from "@/components/ui/alert";
import { Icon } from "@iconify/vue";

const props = defineProps({
    itemsMatch: Boolean,
    ppmpApproved: Boolean,
    isApproved: Boolean,
    comparison: Object,
});

const alertType = computed(() => {
    if (props.isApproved) return "success";
    if (!props.ppmpApproved) return "warning";

    // Use comparison data if available
    if (props.comparison) {
        return props.comparison.status === "all_matched" ? "success" : "error";
    }

    // Fallback to itemsMatch boolean
    if (!props.itemsMatch) return "error";
    return "success";
});

const alertIcon = computed(() => {
    if (props.isApproved) return "lucide:check-circle";
    if (!props.ppmpApproved) return "lucide:alert-triangle";

    // Use comparison data if available
    if (props.comparison) {
        return props.comparison.status === "all_matched"
            ? "lucide:check-circle"
            : "lucide:x-circle";
    }

    // Fallback to itemsMatch boolean
    if (!props.itemsMatch) return "lucide:x-circle";
    return "lucide:check-circle";
});

const alertMessage = computed(() => {
    if (props.isApproved) {
        return "This emanating request has been approved and is ready for canvassing.";
    }
    if (!props.ppmpApproved) {
        return "The related PPMP must be approved before this emanating request can be approved.";
    }

    // Use comparison data if available (more reliable than cached boolean)
    if (props.comparison) {
        const matchedCount = props.comparison.total_matched_items || 0;
        const totalPPMPItems = props.comparison.total_ppmp_items || 0;
        const allMatch = props.comparison.status === "all_matched";

        if (allMatch && matchedCount === totalPPMPItems) {
            return "All items match the PPMP. This emanating request is ready for approval.";
        }

        return `${matchedCount} of ${totalPPMPItems} items match the PPMP. Please review the comparison below.`;
    }

    // Fallback to itemsMatch boolean if no comparison data
    if (props.itemsMatch) {
        return "All items match the PPMP. This emanating request is ready for approval.";
    }

    return "Items do not match the PPMP. Please review the comparison below.";
});

const alertClass = computed(() => {
    const baseClasses = "border-l-4";

    // Check if items match using comparison data or boolean prop
    const itemsMatchCheck = props.comparison
        ? props.comparison.status === "all_matched"
        : props.itemsMatch;

    // Always show green/success when items match perfectly
    if (itemsMatchCheck || props.isApproved) {
        return `${baseClasses} border-l-green-500 bg-green-50 text-green-700`;
    }
    // Show warning if PPMP not approved
    if (!props.ppmpApproved) {
        return `${baseClasses} border-l-yellow-500 bg-yellow-50 text-yellow-700`;
    }
    // Show error if items don't match
    return `${baseClasses} border-l-red-500 bg-red-50 text-red-700`;
});
</script>

<template>
    <Alert :class="alertClass">
        <Icon :icon="alertIcon" class="h-5 w-5" />
        <AlertDescription class="ml-2">
            {{ alertMessage }}
        </AlertDescription>
    </Alert>
</template>
