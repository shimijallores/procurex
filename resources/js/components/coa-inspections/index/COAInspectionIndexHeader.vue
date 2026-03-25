<script setup>
import { Link } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import { Button } from "@/components/ui/button";
</script>

<template>
    <div class="flex items-start justify-between">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">
                COA Inspection
            </h1>
            <p class="text-sm text-muted-foreground mt-1">
                Manage SVP and Bidding inspection letters in one combined
                record.
            </p>
        </div>

        <Link :href="route('coa-inspections.create')">
            <Button>
                <Icon icon="lucide:plus" class="mr-2 h-4 w-4" />
                New COA Inspection
            </Button>
        </Link>
    </div>
</template>
