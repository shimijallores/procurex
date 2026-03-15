<script setup>
import { Icon } from "@iconify/vue";
import { Button } from "@/components/ui/button";

defineProps({
    exportUrl: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <div class="flex items-center justify-between">
        <div class="space-y-1">
            <h1 class="text-2xl font-bold tracking-tight md:text-3xl">
                PR Matrix
            </h1>
            <p class="text-muted-foreground">
                Ongoing Purchase Request monitoring matrix per approved request.
            </p>
        </div>

        <a :href="exportUrl">
            <Button>
                <Icon icon="lucide:printer" class="mr-2 h-4 w-4" />
                Print PR Matrix
            </Button>
        </a>
    </div>
</template>
