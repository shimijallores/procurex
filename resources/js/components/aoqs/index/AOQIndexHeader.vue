<script setup>
import { Link } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import { Button } from "@/components/ui/button";
import PageTitle from "@/components/PageTitle.vue";
</script>

<template>
    <div class="flex items-center justify-between">
        <div class="space-y-1">
            <PageTitle title="Abstract of Quotation" />
            <p class="text-muted-foreground">
                Consolidate submitted supplier quotations and determine winner
            </p>
        </div>
        <Link :href="route('aoqs.create')">
            <Button>
                <Icon icon="lucide:plus" class="mr-2 h-4 w-4" />
                New Abstract of Quotation
            </Button>
        </Link>
    </div>
</template>
