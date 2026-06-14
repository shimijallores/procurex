<script setup>
import { Link } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import { Button } from "@/components/ui/button";
import PageTitle from "@/components/PageTitle.vue";
</script>

<template>
    <div class="flex items-start justify-between">
        <div>
            <PageTitle title="Acceptance & Inspection" />
            <p class="text-sm text-muted-foreground mt-1">
                Manage acceptance and inspection reports generated from Purchase
                Orders.
            </p>
        </div>

        <Link :href="route('acceptance-inspections.create')">
            <Button>
                <Icon icon="lucide:plus" class="mr-2 h-4 w-4" />
                New Report
            </Button>
        </Link>
    </div>
</template>
