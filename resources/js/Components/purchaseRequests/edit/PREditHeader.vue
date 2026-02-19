<script setup>
import { Link } from "@inertiajs/vue3";
import { Button } from "@/components/ui/button";
import { Icon } from "@iconify/vue";

defineProps({
    purchaseRequest: Object,
});
</script>

<template>
    <div class="flex items-center gap-4">
        <Link :href="route('purchase-requests.show', purchaseRequest?.id)">
            <Button variant="ghost" size="sm">
                <Icon icon="lucide:arrow-left" class="mr-2 h-4 w-4" />
                Back
            </Button>
        </Link>
        <div class="space-y-1">
            <h1 class="text-2xl font-bold tracking-tight md:text-3xl">
                Edit Purchase Request
                <span v-if="purchaseRequest?.pr_no" class="text-primary">
                    #{{ purchaseRequest.pr_no }}
                </span>
            </h1>
            <p class="text-muted-foreground">
                Update PR details, items, and purpose
            </p>
        </div>
    </div>
</template>
