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
    if (!props.itemsMatch) return "error";
    return "success";
});

const alertIcon = computed(() => {
    if (props.isApproved) return "lucide:check-circle";
    if (!props.ppmpApproved) return "lucide:alert-triangle";
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
    if (props.itemsMatch) {
        return "All items match the PPMP. This emanating request is ready for approval.";
    }
    const matchedCount = props.comparison?.total_matched_items || 0;
    const totalCount = props.comparison?.total_emanating_items || 0;
    return `${matchedCount} of ${totalCount} items match the PPMP. ${matchedCount === totalCount ? "All items matched! Ready for approval." : "Please review the comparison below."}`;
});

const alertClass = computed(() => {
    const baseClasses = "border-l-4";
    // Always show green/success when items match perfectly
    if (props.itemsMatch || props.isApproved) {
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
