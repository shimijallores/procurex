<script setup>
import { Link } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import { Button } from "@/components/ui/button";
import PageTitle from "@/components/PageTitle.vue";
</script>

<template>
    <div class="flex items-center justify-between">
        <div class="space-y-1">
            <PageTitle title="Suppliers" />
            <p class="text-muted-foreground">
                Manage vendors and suppliers for the master list
            </p>
        </div>
        <Link :href="route('suppliers.create')">
            <Button>
                <Icon icon="lucide:plus" class="mr-2 h-4 w-4" />
                New Supplier
            </Button>
        </Link>
    </div>
</template>
