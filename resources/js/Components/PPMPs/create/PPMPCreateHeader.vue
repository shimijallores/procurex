<script setup>
import { Link } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import { Button } from "@/components/ui/button";
</script>

<template>
    <div class="flex items-center gap-4">
        <Link :href="route('ppmps.index')">
            <Button variant="ghost" size="sm">
                <Icon icon="lucide:arrow-left" class="mr-2 h-4 w-4" />
                Back
            </Button>
        </Link>
        <div class="space-y-1">
            <h1 class="text-2xl font-bold tracking-tight md:text-3xl">
                Create Project Procurement Management Plan
            </h1>
            <p class="text-muted-foreground">Add a new PPMP to the system</p>
        </div>
    </div>
</template>
