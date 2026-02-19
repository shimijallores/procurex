<script setup>
import { Link } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import { Button } from "@/components/ui/button";
</script>

<template>
    <div class="flex items-center justify-between">
        <div class="space-y-1">
            <h1 class="text-2xl font-bold tracking-tight md:text-3xl">
                Annual Procurement Plan
            </h1>
            <p class="text-muted-foreground">
                Manage all procurement plans and their items
            </p>
        </div>
        <Link :href="route('apps.create')">
            <Button>
                <Icon icon="lucide:plus" class="mr-2 h-4 w-4" />
                New APP
            </Button>
        </Link>
    </div>
</template>
