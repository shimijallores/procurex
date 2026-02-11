<script setup>
import { Alert, AlertDescription } from "@/components/ui/alert";
import { Icon } from "@iconify/vue";

defineProps({
    rejectionReason: String,
});
</script>

<template>
    <Alert
        v-if="rejectionReason"
        class="border-l-4 border-l-red-500 bg-red-50 text-red-800"
    >
        <Icon icon="lucide:x-circle" class="h-5 w-5" />
        <AlertDescription class="ml-2">
            <strong>Rejection Reason:</strong> {{ rejectionReason }}
        </AlertDescription>
    </Alert>
</template>
