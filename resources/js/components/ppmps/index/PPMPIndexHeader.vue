<script setup>
import { Link } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import { Button } from "@/components/ui/button";

defineProps({
    onCreateClick: Function,
});
</script>

<template>
    <div class="flex items-center justify-between">
        <div class="space-y-1">
            <h1 class="text-2xl font-bold tracking-tight md:text-3xl">
                Project Procurement Management Plan
            </h1>
            <p class="text-muted-foreground">
                Manage all project procurement plans and their items
            </p>
        </div>
        <Link :href="route('ppmps.create')">
            <Button>
                <Icon icon="lucide:plus" class="mr-2 h-4 w-4" />
                New PPMP
            </Button>
        </Link>
    </div>
</template>
