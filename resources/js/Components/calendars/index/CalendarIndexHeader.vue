<script setup>
import { Icon } from "@iconify/vue";
import { Button } from "@/components/ui/button";

defineProps({
    onAddEntry: {
        type: Function,
        required: true,
    },
});
</script>

<template>
    <div class="flex items-center justify-between">
        <div class="space-y-1">
            <h1 class="text-2xl font-bold tracking-tight md:text-3xl">
                System Calendar
            </h1>
            <p class="text-muted-foreground">
                Manage holidays, special workdays, and unavailable dates
            </p>
        </div>
        <Button @click="onAddEntry()">
            <Icon icon="lucide:plus" class="mr-2 h-4 w-4" />
            Add Entry
        </Button>
    </div>
</template>
