<script setup>
import { Link } from "@inertiajs/vue3";
import { Button } from "@/components/ui/button";
import { Icon } from "@iconify/vue";
import PageTitle from "@/components/PageTitle.vue";
</script>

<template>
    <div class="flex items-center justify-between">
        <div class="space-y-1">
            <PageTitle title="Purchase Requests" />
            <p class="text-muted-foreground">
                Manage all purchase requests from canvassed emanating requests
            </p>
        </div>
        <Link :href="route('purchase-requests.create')">
            <Button>
                <Icon icon="lucide:plus" class="mr-2 h-4 w-4" />
                New Purchase Request
            </Button>
        </Link>
    </div>
</template>
