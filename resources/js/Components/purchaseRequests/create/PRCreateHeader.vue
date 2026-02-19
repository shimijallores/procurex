<script setup>
import { Link } from "@inertiajs/vue3";
import { Button } from "@/components/ui/button";
import { Icon } from "@iconify/vue";
</script>

<template>
    <div class="flex items-center gap-4">
        <Link :href="route('purchase-requests.index')">
            <Button variant="ghost" size="sm">
                <Icon icon="lucide:arrow-left" class="mr-2 h-4 w-4" />
                Back
            </Button>
        </Link>
        <div class="space-y-1">
            <h1 class="text-2xl font-bold tracking-tight md:text-3xl">
                Create Purchase Request
            </h1>
            <p class="text-muted-foreground">
                Create a purchase request from an approved and canvassed
                emanating
            </p>
        </div>
    </div>
</template>
