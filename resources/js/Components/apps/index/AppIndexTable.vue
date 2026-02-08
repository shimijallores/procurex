<script setup>
import { Link } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";
import { Button } from "@/components/ui/button";

defineProps({
    apps: Object,
    search: String,
});

defineEmits(["update:search", "delete"]);

const formatDate = (date) => {
    return new Date(date).toLocaleDateString("en-US", {
        year: "numeric",
        month: "short",
        day: "numeric",
    });
};
</script>

<template>
    <Card>
        <CardHeader>
            <div class="flex items-center justify-between">
                <div>
                    <CardTitle>All Procurement Plans</CardTitle>
                    <CardDescription>
                        A list of all annual procurement plans
                    </CardDescription>
                </div>
                <div class="relative w-64">
                    <Icon
                        icon="lucide:search"
                        class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <input
                        :model-value="search"
                        @input="$emit('update:search', $event.target.value)"
                        type="text"
                        placeholder="Search APPs..."
                        class="flex h-10 w-full rounded-md border border-input bg-background pl-9 pr-9 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                    />
                    <button
                        v-if="search"
                        @click="$emit('update:search', '')"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                    >
                        <Icon icon="lucide:x" class="h-4 w-4" />
                    </button>
                </div>
            </div>
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
                                Office
                            </th>
                            <th
                                class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                            >
                                Fiscal Year
                            </th>
                            <th
                                class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                            >
                                Created
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
                            v-for="app in apps.data"
                            :key="app.id"
                            class="border-b transition-colors hover:bg-muted/50"
                        >
                            <td class="p-4 align-middle font-medium">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10"
                                    >
                                        <Icon
                                            icon="lucide:building-2"
                                            class="h-5 w-5 text-primary"
                                        />
                                    </div>
                                    <div>
                                        <div class="font-medium">
                                            {{ app.office.name }}
                                        </div>
                                        <div
                                            class="text-xs text-muted-foreground"
                                        >
                                            ID: {{ app.id }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="p-4 align-middle">
                                <div class="flex items-center gap-2">
                                    <Icon
                                        icon="lucide:calendar"
                                        class="h-4 w-4 text-muted-foreground"
                                    />
                                    <span class="font-medium">{{
                                        app.fiscal_year
                                    }}</span>
                                </div>
                            </td>
                            <td class="p-4 align-middle">
                                <div class="flex items-center gap-2">
                                    <Icon
                                        icon="lucide:clock"
                                        class="h-4 w-4 text-muted-foreground"
                                    />
                                    <span class="text-sm">{{
                                        formatDate(app.created_at)
                                    }}</span>
                                </div>
                            </td>
                            <td class="p-4 align-middle text-right">
                                <div
                                    class="flex items-center justify-end gap-2"
                                >
                                    <Link :href="route('apps.show', app.id)">
                                        <Button variant="ghost" size="sm">
                                            <Icon
                                                icon="lucide:eye"
                                                class="h-4 w-4"
                                            />
                                        </Button>
                                    </Link>
                                    <Link :href="route('apps.edit', app.id)">
                                        <Button variant="ghost" size="sm">
                                            <Icon
                                                icon="lucide:pencil"
                                                class="h-4 w-4"
                                            />
                                        </Button>
                                    </Link>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        @click="$emit('delete', app)"
                                    >
                                        <Icon
                                            icon="lucide:trash-2"
                                            class="h-4 w-4 text-destructive"
                                        />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="apps.data.length === 0">
                            <td colspan="4" class="p-8 text-center">
                                <div class="flex flex-col items-center gap-2">
                                    <Icon
                                        icon="lucide:inbox"
                                        class="h-12 w-12 text-muted-foreground/50"
                                    />
                                    <p class="text-muted-foreground">
                                        No procurement plans found
                                    </p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </CardContent>
    </Card>
</template>
