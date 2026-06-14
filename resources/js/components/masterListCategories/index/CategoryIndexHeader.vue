<script setup>
import { Link } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import { Button } from "@/components/ui/button";
import PageTitle from "@/components/PageTitle.vue";
</script>

<template>
    <div class="flex items-center justify-between">
        <div class="space-y-1">
            <PageTitle title="Master List Categories" />
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
            <Link :href="route('master-list-categories.create')">
                <Button>
                    <Icon icon="lucide:plus" class="mr-2 h-4 w-4" />
                    Add Category
                </Button>
            </Link>
        </div>
    </div>
</template>
