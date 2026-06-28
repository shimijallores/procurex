<script setup>
import { Link } from "@inertiajs/vue3";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";

defineProps({
    items: {
        type: Array,
        default: () => [],
    },
    title: { type: String, default: "Recent Records" },
    description: { type: String, default: "Latest documents relevant to your role." },
});
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>{{ title }}</CardTitle>
            <CardDescription>{{ description }}</CardDescription>
        </CardHeader>
        <CardContent>
            <div v-if="items.length" class="space-y-3">
                <Link
                    v-for="(entry, index) in items"
                    :key="`${entry.title}-${index}`"
                    :href="entry.link"
                    class="flex items-start justify-between gap-3 rounded-md border p-3 transition-colors hover:bg-muted/40"
                >
                    <div>
                        <p class="text-sm font-medium">{{ entry.title }}</p>
                        <p class="text-xs text-muted-foreground">{{ entry.subtitle }}</p>
                        <p class="mt-1 text-xs text-muted-foreground">{{ entry.meta }}</p>
                    </div>
                    <span class="whitespace-nowrap text-xs text-muted-foreground">{{ entry.date }}</span>
                </Link>
            </div>
            <div v-else class="text-sm text-muted-foreground">
                No recent records found.
            </div>
        </CardContent>
    </Card>
</template>
