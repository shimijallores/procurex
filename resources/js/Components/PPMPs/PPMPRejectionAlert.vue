<script setup>
import { Icon } from "@iconify/vue";
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert";

defineProps({
    rejectionReason: String,
});
</script>

<template>
    <Alert
        v-if="rejectionReason"
        class="border-red-200 dark:border-red-900 bg-red-50 dark:bg-red-950/30"
    >
        <Icon
            icon="lucide:alert-triangle"
            class="h-5 w-5 text-red-600 dark:text-red-400"
        />
        <AlertTitle class="text-red-800 dark:text-red-300">
            PPMP Rejected
        </AlertTitle>
        <AlertDescription class="text-red-700 dark:text-red-400">
            <p class="font-semibold mb-2">Reason for rejection:</p>
            <p>{{ rejectionReason }}</p>
            <p class="mt-2 text-sm">
                Please address the issues mentioned above and upload a revised
                CSV file or create an addendum.
            </p>
        </AlertDescription>
    </Alert>
</template>
