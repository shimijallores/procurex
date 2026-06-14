<script setup>
import { Link } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import { Button } from "@/components/ui/button";

defineProps({
    exportUrl: String,
});
import PageTitle from "@/components/PageTitle.vue";
</script>

<template>
    <div class="flex items-center justify-between">
        <div class="space-y-1">
            <PageTitle title="SVP Matrix" />
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
