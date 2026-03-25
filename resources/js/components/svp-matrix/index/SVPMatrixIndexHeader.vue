<script setup>
import { Link } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import { Button } from "@/components/ui/button";

defineProps({
    exportUrl: String,
});
</script>

<template>
    <div class="flex items-center justify-between">
        <div class="space-y-1">
            <h1 class="text-2xl font-bold tracking-tight md:text-3xl">
                SVP Matrix
            </h1>
            <p class="text-muted-foreground">
                End-to-end procurement matrix for SVP purchase orders.
            </p>
        </div>
        <a :href="exportUrl">
            <Button>
                <Icon icon="lucide:file-spreadsheet" class="mr-2 h-4 w-4" />
                Print SVP Matrix
            </Button>
        </a>
    </div>
</template>
