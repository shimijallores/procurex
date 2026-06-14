<script setup>
import { Icon } from "@iconify/vue";
import { Button } from "@/components/ui/button";

defineProps({
    onAddEntry: {
        type: Function,
        required: true,
    },
});
import PageTitle from "@/components/PageTitle.vue";
</script>

<template>
    <div class="flex items-center justify-between">
        <div class="space-y-1">
            <PageTitle title="System Calendar" />
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
