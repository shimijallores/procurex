<script setup>
import { Icon } from "@iconify/vue";

defineProps({
    returnReason: String,
});
</script>

<template>
    <div
        class="rounded-lg border border-destructive/50 bg-destructive/5 px-4 py-3 text-sm"
    >
        <div class="flex items-center gap-2 font-medium text-destructive">
            <Icon icon="lucide:alert-triangle" class="h-4 w-4" />
            Returned
        </div>
        <p class="mt-1 text-muted-foreground">{{ returnReason }}</p>
    </div>
</template>
