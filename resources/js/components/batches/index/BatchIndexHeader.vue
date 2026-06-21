<script setup>
import { Link } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import { Button } from "@/components/ui/button";
import PageTitle from "@/components/PageTitle.vue";
</script>

<template>
    <div class="flex items-start justify-between">
        <div>
            <PageTitle title="Batches" />
            <p class="text-sm text-muted-foreground mt-1">
                Manage all batch numbers and tracking dates in the system.
            </p>
        </div>

        <Link :href="route('batches.create')">
            <Button>
                <Icon icon="lucide:plus" class="mr-2 h-4 w-4" />
                New Batch
            </Button>
        </Link>
    </div>
</template>
