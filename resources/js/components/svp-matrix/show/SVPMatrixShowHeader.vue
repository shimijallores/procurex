<script setup>
import { Link } from "@inertiajs/vue3";
import { Button } from "@/components/ui/button";
import { Icon } from "@iconify/vue";

defineProps({
    matrixRow: Object,
});
</script>

<template>
    <div class="flex items-start justify-between">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">
                SVP Matrix Row
            </h1>
            <p class="text-sm text-muted-foreground mt-1">
                {{ matrixRow.po_no || "-" }} • {{ matrixRow.office || "-" }}
            </p>
        </div>

        <Link :href="route('svp-matrix.edit', matrixRow.id)">
            <Button>
                <Icon icon="lucide:pencil" class="mr-2 h-4 w-4" />
                Edit Row
            </Button>
        </Link>
    </div>
</template>
