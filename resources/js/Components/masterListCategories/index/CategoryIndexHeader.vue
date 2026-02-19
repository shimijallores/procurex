<script setup>
import { Link } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import { Button } from "@/components/ui/button";
</script>

<template>
    <div class="flex items-center justify-between">
        <div class="space-y-1">
            <h1 class="text-2xl font-bold tracking-tight md:text-3xl">
                Master List Categories
            </h1>
            <p class="text-muted-foreground">
                Organize items by category for the procurement master list
            </p>
        </div>
        <div class="flex gap-2">
            <Link :href="route('master-list-items.index')">
                <Button variant="outline">
                    <Icon icon="lucide:box" class="mr-2 h-4 w-4" />
                    Items
                </Button>
            </Link>
            <Link :href="route('master-list-items.create')">
                <Button>
                    <Icon icon="lucide:plus" class="mr-2 h-4 w-4" />
                    Add Category
                </Button>
            </Link>
        </div>
    </div>
</template>
