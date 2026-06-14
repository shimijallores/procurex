<script setup>
import { Link } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import { Button } from "@/components/ui/button";
import PageTitle from "@/components/PageTitle.vue";
</script>

<template>
    <div class="flex items-start justify-between">
        <div>
            <PageTitle title="PO Transmittal" />
            <p class="text-sm text-muted-foreground mt-1">
                Manage COA and OPG transmittal records sourced from Purchase
                Orders.
            </p>
        </div>

        <Link :href="route('po-transmittals.create')">
            <Button>
                <Icon icon="lucide:plus" class="mr-2 h-4 w-4" />
                New Transmittal
            </Button>
        </Link>
    </div>
</template>
