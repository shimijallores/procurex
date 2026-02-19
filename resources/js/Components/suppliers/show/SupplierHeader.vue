<script setup>
import { Link } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";

defineProps({
    supplier: Object,
});
</script>

<template>
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <Link :href="route('suppliers.index')">
                <Button variant="outline" size="icon">
                    <Icon icon="lucide:arrow-left" class="h-4 w-4" />
                </Button>
            </Link>
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ supplier.name }}
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Supplier Profile
                </p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <Badge :variant="supplier.is_active ? 'default' : 'secondary'">
                {{ supplier.is_active ? "Active" : "Inactive" }}
            </Badge>
            <Link :href="route('suppliers.edit', supplier.id)">
                <Button variant="outline" size="sm">
                    <Icon icon="lucide:pencil" class="mr-2 h-4 w-4" />
                    Edit
                </Button>
            </Link>
        </div>
    </div>
</template>
