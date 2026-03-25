<script setup>
import { Link } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import { Button } from "@/components/ui/button";

defineProps({
    matrixRow: Object,
});
</script>

<template>
    <div class="flex items-start justify-between">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">
                Edit SVP Matrix Row
            </h1>
            <p class="mt-1 text-sm text-muted-foreground">
                Update this procurement tracking entry.
            </p>
        </div>

        <Link :href="route('svp-matrix.show', matrixRow.id)">
            <Button variant="outline">
                <Icon icon="lucide:arrow-left" class="mr-2 h-4 w-4" />
                Back to Details
            </Button>
        </Link>
    </div>
</template>
