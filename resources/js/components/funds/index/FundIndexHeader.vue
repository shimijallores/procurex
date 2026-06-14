<script setup>
import { Link } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import { Button } from "@/components/ui/button";

defineProps({
    search: String,
});

defineEmits(["update:search", "clear"]);
import PageTitle from "@/components/PageTitle.vue";
</script>

<template>
    <div class="flex items-center justify-between">
        <div class="space-y-1">
            <PageTitle title="Funds" />
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
