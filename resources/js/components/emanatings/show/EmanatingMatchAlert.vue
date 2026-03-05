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
        return "The related PPMP is not yet approved. This is an advisory alert only.";
    }

    // Use comparison data if available (more reliable than cached boolean)
    if (props.comparison) {
        const matchedCount = props.comparison.total_matched_items || 0;
        const totalItems = props.comparison.total_emanating_items || 0;
        const allMatch = props.comparison.status === "all_matched";
        const isProjectFund = props.comparison.is_project_fund === true;

        if (allMatch && matchedCount === totalItems) {
            return isProjectFund
                ? "All items passed PPMP, APP, Work Program, and Project Brief checks. This emanating request is ready for approval."
                : "All items passed PPMP and APP checks. This emanating request is ready for approval.";
        }

        return isProjectFund
            ? `${matchedCount} of ${totalItems} items passed PPMP/APP/Work Program/Project Brief checks. Review flagged items below (advisory only).`
            : `${matchedCount} of ${totalItems} items passed PPMP/APP checks. Review flagged items below (advisory only).`;
    }

    // Fallback to itemsMatch boolean if no comparison data
    if (props.itemsMatch) {
        return "All items passed PPMP and APP checks. This emanating request is ready for approval.";
    }

    return "Some items are flagged by PPMP/APP checks. Please review the comparison below (advisory only).";
});

const alertClass = computed(() => {
    const baseClasses = "border-l-4";

    // Check if items match using comparison data or boolean prop
    const itemsMatchCheck = props.comparison
        ? props.comparison.status === "all_matched"
        : props.itemsMatch;

    // Always show green/success when items match perfectly
    if (itemsMatchCheck || props.isApproved) {
        return `${baseClasses} border-l-green-500 bg-green-50 text-green-700 dark:bg-green-950/30 dark:text-green-400 dark:border-l-green-400`;
    }
    // Show warning if PPMP not approved
    if (!props.ppmpApproved) {
        return `${baseClasses} border-l-yellow-500 bg-yellow-50 text-yellow-700 dark:bg-yellow-950/30 dark:text-yellow-400 dark:border-l-yellow-400`;
    }
    // Show error if items don't match
    return `${baseClasses} border-l-red-500 bg-red-50 text-red-700 dark:bg-red-950/30 dark:text-red-400 dark:border-l-red-400`;
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
