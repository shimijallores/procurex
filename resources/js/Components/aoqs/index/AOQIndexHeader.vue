<script setup>
import { Link } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import { Button } from "@/components/ui/button";
</script>

<template>
    <div class="flex items-center justify-between">
        <div class="space-y-1">
            <h1 class="text-2xl font-bold tracking-tight md:text-3xl">
                Abstract of Quotation
            </h1>
            <p class="text-muted-foreground">
                Consolidate submitted supplier quotations and determine winner
            </p>
        </div>
        <Link :href="route('aoqs.create')">
            <Button>
                <Icon icon="lucide:plus" class="mr-2 h-4 w-4" />
                New AOQ
            </Button>
        </Link>
    </div>
</template>
