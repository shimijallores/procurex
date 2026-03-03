<script setup>
import { Link } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import { Button } from "@/components/ui/button";
</script>

<template>
    <div class="flex items-center justify-between">
        <div class="space-y-1">
            <h1 class="text-2xl font-bold tracking-tight md:text-3xl">
                Master List
            </h1>
            <p class="text-muted-foreground">
                Manage all canvassable items and their default prices
            </p>
        </div>
        <div class="flex gap-2">
            <Link :href="route('master-list-categories.index')">
                <Button variant="outline">
                    <Icon icon="lucide:tag" class="mr-2 h-4 w-4" />
                    Categories
                </Button>
            </Link>
            <Link :href="route('master-list-items.create')">
                <Button>
                    <Icon icon="lucide:plus" class="mr-2 h-4 w-4" />
                    New Item
                </Button>
            </Link>
        </div>
    </div>
</template>
