<script setup>
import { Link } from "@inertiajs/vue3";
import { Button } from "@/components/ui/button";
import { Icon } from "@iconify/vue";
</script>

<template>
    <div class="flex items-center justify-between">
        <div class="space-y-1">
            <h1 class="text-2xl font-bold tracking-tight md:text-3xl">
                Emanating Requests
            </h1>
            <p class="text-muted-foreground">
                Manage all emanating procurement requests
            </p>
        </div>
        <Link :href="route('emanatings.create')">
            <Button>
                <Icon icon="lucide:plus" class="mr-2 h-4 w-4" />
                Create Emanating
            </Button>
        </Link>
    </div>
</template>
