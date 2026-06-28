<script setup>
import { Link } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";

defineProps({
    links: {
        type: Array,
        default: () => [],
    },
    title: { type: String, default: "Quick Access" },
    description: { type: String, default: "Shortcut links for your most common tasks." },
});
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>{{ title }}</CardTitle>
            <CardDescription>{{ description }}</CardDescription>
        </CardHeader>
        <CardContent>
            <div class="grid gap-2">
                <Link v-for="link in links" :key="link.label" :href="link.href">
                    <Button variant="outline" class="w-full justify-start">
                        <Icon :icon="link.icon" class="mr-2 size-4" />
                        {{ link.label }}
                    </Button>
                </Link>
            </div>
        </CardContent>
    </Card>
</template>
