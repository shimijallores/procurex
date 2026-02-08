<script setup>
import { Icon } from "@iconify/vue";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";

const props = defineProps({
    calendars: {
        type: Array,
        required: true,
    },
    typeConfig: {
        type: Object,
        required: true,
    },
    onEdit: {
        type: Function,
        required: true,
    },
    onDelete: {
        type: Function,
        required: true,
    },
    onAddEntry: {
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
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>All Calendar Entries</CardTitle>
            <CardDescription
                >Complete list of all dates marked in the
                system</CardDescription
            >
        </CardHeader>
        <CardContent>
            <div class="relative w-full overflow-auto">
                <table class="w-full caption-bottom text-sm">
                    <thead class="border-b">
                        <tr
                            class="border-b transition-colors hover:bg-muted/50"
                        >
                            <th
                                class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                            >
                                Date
                            </th>
                            <th
                                class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                            >
                                Type
                            </th>
                            <th
                                class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                            >
                                Name
                            </th>
                            <th
                                class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                            >
                                Status
                            </th>
                            <th
                                class="h-12 px-4 text-right align-middle font-medium text-muted-foreground"
                            >
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="[&_tr:last-child]:border-0">
                        <tr
                            v-for="entry in calendars"
                            :key="entry.id"
                            class="border-b transition-colors hover:bg-muted/50"
                        >
                            <td class="p-4 align-middle">
                                <div class="flex items-center gap-2">
                                    <Icon
                                        icon="lucide:calendar"
                                        class="h-4 w-4 text-muted-foreground"
                                    />
                                    <span class="font-medium">{{
                                        formatDate(entry.date)
                                    }}</span>
                                </div>
                            </td>
                            <td class="p-4 align-middle">
                                <div class="flex items-center gap-2">
                                    <Icon
                                        :icon="typeConfig[entry.type].icon"
                                        class="h-4 w-4"
                                    />
                                    <Badge
                                        :class="typeConfig[entry.type].color"
                                    >
                                        {{ typeConfig[entry.type].label }}
                                    </Badge>
                                </div>
                            </td>
                            <td class="p-4 align-middle">
                                {{ entry.name || "-" }}
                            </td>
                            <td class="p-4 align-middle">
                                <Badge>
                                    {{ entry.type }}
                                </Badge>
                            </td>
                            <td class="p-4 align-middle text-right">
                                <div
                                    class="flex items-center justify-end gap-2"
                                >
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        @click="onEdit(entry)"
                                    >
                                        <Icon
                                            icon="lucide:pencil"
                                            class="h-4 w-4"
                                        />
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        @click="onDelete(entry)"
                                    >
                                        <Icon
                                            icon="lucide:trash-2"
                                            class="h-4 w-4 text-destructive"
                                        />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="calendars.length === 0">
                            <td colspan="5" class="p-8 text-center">
                                <div class="flex flex-col items-center gap-2">
                                    <Icon
                                        icon="lucide:inbox"
                                        class="h-12 w-12 text-muted-foreground/50"
                                    />
                                    <p class="text-muted-foreground">
                                        No calendar entries found
                                    </p>
                                    <Button
                                        @click="onAddEntry()"
                                        variant="outline"
                                        size="sm"
                                        class="mt-2"
                                    >
                                        <Icon
                                            icon="lucide:plus"
                                            class="mr-2 h-4 w-4"
                                        />
                                        Add your first entry
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </CardContent>
    </Card>
</template>
