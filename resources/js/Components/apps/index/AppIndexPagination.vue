<script setup>
import { Link } from "@inertiajs/vue3";

defineProps({
    apps: Object,
});
</script>

<template>
    <div class="mt-4 flex items-center justify-between border-t pt-4">
        <div class="text-sm text-muted-foreground">
            Showing {{ apps.from }} to {{ apps.to }} of {{ apps.total }} plans
        </div>
        <div class="flex items-center gap-1">
            <template v-for="(link, index) in apps.links" :key="index">
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
                    preserve-scroll
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
</template>
