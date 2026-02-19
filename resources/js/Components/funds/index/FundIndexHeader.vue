<script setup>
import { Link } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import { Button } from "@/components/ui/button";

defineProps({
    search: String,
});

defineEmits(["update:search", "clear"]);
</script>

<template>
    <div class="flex items-center justify-between">
        <div class="space-y-1">
            <h1 class="text-2xl font-bold tracking-tight md:text-3xl">Funds</h1>
            <p class="text-muted-foreground">
                Manage all funds and their allocations
            </p>
        </div>
        <Link :href="route('funds.create')">
            <Button>
                <Icon icon="lucide:plus" class="mr-2 h-4 w-4" />
                New Fund
            </Button>
        </Link>
    </div>
</template>
