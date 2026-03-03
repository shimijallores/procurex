<script setup>
import { Link } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import { Button } from "@/components/ui/button";

defineProps({
    fund: Object,
});

defineEmits(["delete"]);
</script>

<template>
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <Link :href="route('funds.index')">
                <Button variant="ghost" size="sm">
                    <Icon icon="lucide:arrow-left" class="mr-2 h-4 w-4" />
                    Back
                </Button>
            </Link>
            <div class="space-y-1">
                <h1 class="text-2xl font-bold tracking-tight md:text-3xl">
                    {{ fund.name }}
                </h1>
                <p class="text-muted-foreground">
                    Fund details and information
                </p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <Link :href="route('funds.edit', fund.id)">
                <Button variant="outline">
                    <Icon icon="lucide:pencil" class="mr-2 h-4 w-4" />
                    Edit
                </Button>
            </Link>
            <Button variant="destructive" @click="$emit('delete')">
                <Icon icon="lucide:trash-2" class="mr-2 h-4 w-4" />
                Delete
            </Button>
        </div>
    </div>
</template>
