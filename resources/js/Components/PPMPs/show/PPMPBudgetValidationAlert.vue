<script setup>
import { Icon } from "@iconify/vue";
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert";

defineProps({
    budgetValidationPassed: Boolean,
    budgetNotices: Array,
    ppmpIsApproved: Boolean,
});
</script>

<template>
    <div class="space-y-4">
        <!-- Overall Budget Validation Summary -->
        <Alert
            v-if="budgetNotices && budgetNotices.length > 0"
            :class="[
                budgetValidationPassed
                    ? 'border-green-200 dark:border-green-900 bg-green-50 dark:bg-green-950/30'
                    : budgetValidationPassed === false
                    ? 'border-red-200 dark:border-red-900 bg-red-50 dark:bg-red-950/30'
                    : 'border-yellow-200 dark:border-yellow-900 bg-yellow-50 dark:bg-yellow-950/30',
            ]"
        >
            <Icon
                :icon="
                    budgetValidationPassed
                        ? 'lucide:check-circle'
                        : budgetValidationPassed === false
                        ? 'lucide:alert-circle'
                        : 'lucide:info'
                "
                :class="[
                    'h-5 w-5',
                    budgetValidationPassed
                        ? 'text-green-600 dark:text-green-400'
                        : budgetValidationPassed === false
                        ? 'text-red-600 dark:text-red-400'
                        : 'text-yellow-600 dark:text-yellow-400',
                ]"
            />
            <AlertTitle
                :class="[
                    budgetValidationPassed
                        ? 'text-green-800 dark:text-green-300'
                        : budgetValidationPassed === false
                        ? 'text-red-800 dark:text-red-300'
                        : 'text-yellow-800 dark:text-yellow-300',
                ]"
            >
                Budget Validation
                {{ budgetValidationPassed ? "Passed" : "Failed" }}
            </AlertTitle>
            <AlertDescription
                :class="[
                    budgetValidationPassed
                        ? 'text-green-700 dark:text-green-400'
                        : budgetValidationPassed === false
                        ? 'text-red-700 dark:text-red-400'
                        : 'text-yellow-700 dark:text-yellow-400',
                ]"
            >
                <span v-if="budgetValidationPassed">
                    All PPMP budgets are within their corresponding APP category
                    allocations. This PPMP can be approved.
                </span>
                <span v-else>
                    Some PPMP budgets exceed their APP category allocations.
                    Please review the budget validation status in each category
                    below.
                    {{
                        !ppmpIsApproved
                            ? "You can either create an addendum or upload a revised CSV file."
                            : ""
                    }}
                </span>
            </AlertDescription>
        </Alert>

        <!-- Rejection Reason -->
        <Alert
            v-if="$attrs['rejection-reason']"
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
                <p>{{ $attrs["rejection-reason"] }}</p>
                <p class="mt-2 text-sm">
                    Please address the issues mentioned above and upload a
                    revised CSV file or create an addendum.
                </p>
            </AlertDescription>
        </Alert>
    </div>
</template>
