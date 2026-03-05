<script setup>
import { Link } from "@inertiajs/vue3";
import { Button } from "@/components/ui/button";
import { Icon } from "@iconify/vue";

defineProps({
    matrixRow: Object,
});
</script>

<template>
    <div class="flex items-center gap-4">
        <Link :href="route('purchase-request-matrix.show', matrixRow.id)">
            <Button variant="ghost" size="sm">
                <Icon icon="lucide:arrow-left" class="mr-2 h-4 w-4" />
                Back
            </Button>
        </Link>
        <div class="space-y-1">
            <h1 class="text-2xl font-bold tracking-tight md:text-3xl">
                Edit PR Matrix Row
            </h1>
            <p class="text-muted-foreground">
                {{ matrixRow.control_no || "-" }} |
                {{ matrixRow.item_description || "-" }}
            </p>
        </div>
    </div>
</template>
