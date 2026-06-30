<script setup>
import { Link } from "@inertiajs/vue3";
import { Button } from "@/components/ui/button";
import { Icon } from "@iconify/vue";

const props = defineProps({
    rfq: Object,
});
</script>

<template>
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <Link :href="route('rfqs.show', rfq.id)">
                    <Button variant="ghost" size="sm">
                        <Icon icon="lucide:arrow-left" class="mr-2 h-4 w-4" />
                        Back
                    </Button>
                </Link>
                <div class="min-w-0 break-words">
                    <h1 class="text-2xl font-bold tracking-tight md:text-3xl">
                        Edit RFQ
                        <span class="text-primary">{{ rfq.svp_no }}</span>
                    </h1>
                    <p class="text-muted-foreground mt-1">
                        {{ rfq.purchase_request?.office?.name || "—" }} —
                        {{ rfq.project_name }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
