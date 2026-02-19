<script setup>
import { Link } from "@inertiajs/vue3";
import { Card, CardContent } from "@/components/ui/card";

defineProps({
    data: Object,
});
</script>

<template>
    <Card>
        <CardContent class="pt-6">
            <div class="flex items-center justify-between">
                <div class="text-sm text-muted-foreground">
                    Showing {{ data.from ?? 0 }} to {{ data.to ?? 0 }} of
                    {{ data.total }} entries
                </div>
                <div class="flex items-center gap-1">
                    <template v-for="(link, index) in data.links" :key="index">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            :class="[
                                'inline-flex h-9 items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors',
                                'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2',
                                link.label.includes('Previous') ||
                                link.label.includes('Next')
                                    ? 'px-3'
                                    : 'w-9',
                                link.active
                                    ? 'bg-primary text-primary-foreground hover:bg-primary/90'
                                    : 'hover:bg-accent hover:text-accent-foreground',
                            ]"
                            preserve-state
                            v-html="link.label"
                        />
                        <span
                            v-else
                            :class="[
                                'inline-flex h-9 items-center justify-center rounded-md text-sm font-medium',
                                link.label.includes('Previous') ||
                                link.label.includes('Next')
                                    ? 'px-3'
                                    : 'w-9',
                                'pointer-events-none opacity-50',
                            ]"
                            v-html="link.label"
                        />
                    </template>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
