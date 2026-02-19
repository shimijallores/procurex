<script setup>
import { Link } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import { Button } from "@/components/ui/button";
</script>

<template>
    <div class="flex items-start justify-between">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">
                Purchase Order
            </h1>
            <p class="text-sm text-muted-foreground mt-1">
                Manage generated Purchase Order documents.
            </p>
        </div>

        <Link :href="route('purchase-orders.create')">
            <Button>
                <Icon icon="lucide:plus" class="mr-2 h-4 w-4" />
                New Purchase Order
            </Button>
        </Link>
    </div>
</template>
