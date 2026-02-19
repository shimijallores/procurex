<script setup>
import { Link } from "@inertiajs/vue3";
import { Button } from "@/components/ui/button";
import { Icon } from "@iconify/vue";
</script>

<template>
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <Link :href="route('rfqs.index')">
                <Button variant="ghost" size="sm">
                    <Icon icon="lucide:arrow-left" class="mr-2 h-4 w-4" />
                    Back
                </Button>
            </Link>
            <div>
                <h1 class="text-2xl font-bold tracking-tight md:text-3xl">
                    Create Request for Quotation
                </h1>
                <p class="text-muted-foreground mt-1">
                    Generate RFQ from an approved purchase request with earmark
                </p>
            </div>
        </div>
    </div>
</template>
