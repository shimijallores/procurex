<script setup>
import { Link } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import { Button } from "@/components/ui/button";
</script>

<template>
    <div class="flex items-center justify-between">
        <div class="space-y-1">
            <h1 class="text-2xl font-bold tracking-tight md:text-3xl">
                Request for Quotation
            </h1>
            <p class="text-muted-foreground">
                Manage SVP RFQs from approved and earmarked purchase requests
            </p>
        </div>
        <Link :href="route('rfqs.create')">
            <Button>
                <Icon icon="lucide:plus" class="mr-2 h-4 w-4" />
                New RFQ
            </Button>
        </Link>
    </div>
</template>
