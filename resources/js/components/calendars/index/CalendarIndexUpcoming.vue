<script setup>
import { Icon } from "@iconify/vue";
import { Badge } from "@/components/ui/badge";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";

const props = defineProps({
    upcomingEntries: {
        type: Array,
        required: true,
    },
    typeConfig: {
        type: Object,
        required: true,
    },
    onEntryClick: {
        type: Function,
        required: true,
    },
});

const formatDate = (dateStr) => {
    return new Date(dateStr).toLocaleDateString("en-US", {
        month: "short",
        day: "numeric",
        year: "numeric",
    });
};

const getDaysUntil = (dateStr) => {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const entryDate = new Date(dateStr);
    entryDate.setHours(0, 0, 0, 0);
    const diffTime = entryDate - today;
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

    if (diffDays === 0) return "Today";
    if (diffDays === 1) return "Tomorrow";
    return `In ${diffDays} days`;
};
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Upcoming Events</CardTitle>
            <CardDescription>Next 30 days</CardDescription>
        </CardHeader>
        <CardContent>
            <div v-if="upcomingEntries.length > 0" class="space-y-4">
                <div
                    v-for="entry in upcomingEntries"
                    :key="entry.id"
                    class="flex items-start gap-3 rounded-lg border p-3 hover:bg-accent/50 cursor-pointer transition-colors"
                    @click="onEntryClick(entry)"
                >
                    <div
                        class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg"
                        :class="typeConfig[entry.type].color"
                    >
                        <Icon
                            :icon="typeConfig[entry.type].icon"
                            class="h-5 w-5"
                        />
                    </div>
                    <div class="flex-1 space-y-1">
                        <div class="flex items-center justify-between">
                            <p class="font-medium leading-none">
                                {{ entry.name || typeConfig[entry.type].label }}
                            </p>
                            <Badge variant="secondary" class="text-xs">
                                {{ getDaysUntil(entry.date) }}
                            </Badge>
                        </div>
                        <p class="text-xs text-muted-foreground">
                            {{ formatDate(entry.date) }}
                        </p>
                        <Badge
                            :class="typeConfig[entry.type].color"
                            class="text-xs"
                        >
                            {{ typeConfig[entry.type].label }}
                        </Badge>
                    </div>
                </div>
            </div>
            <div v-else class="flex flex-col items-center gap-2 py-8">
                <Icon
                    icon="lucide:calendar-x"
                    class="h-12 w-12 text-muted-foreground/50"
                />
                <p class="text-sm text-muted-foreground">No upcoming events</p>
            </div>
        </CardContent>
    </Card>
</template>
