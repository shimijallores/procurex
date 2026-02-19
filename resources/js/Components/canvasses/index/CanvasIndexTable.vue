<script setup>
import { Icon } from "@iconify/vue";
import { Link } from "@inertiajs/vue3";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";
import { Button } from "@/components/ui/button";

defineProps({
    canvasses: Object,
    onDeleteClick: Function,
});

const statusConfig = (status) =>
    ({
        pending: {
            label: "Pending",
            class: "bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300",
            icon: "lucide:clock",
        },
        completed: {
            label: "Completed",
            class: "bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300",
            icon: "lucide:check-circle",
        },
        returned: {
            label: "Returned",
            class: "bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300",
            icon: "lucide:rotate-ccw",
        },
    })[status] ?? {
        label: status,
        class: "bg-gray-100 text-gray-600",
        icon: "lucide:circle",
    };

const formatCurrency = (value) => {
    if (!value) return "—";
    return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
    }).format(value);
};

const formatDate = (date) => {
    if (!date) return "—";
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
                    <CardTitle>All Canvasses</CardTitle>
                    <CardDescription>
                        A list of all price canvasses linked to emanating
                        requests
                    </CardDescription>
                </div>
                <slot name="search" />
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
                                Emanating Request
                            </th>
                            <th
                                class="h-12 px-4 text-center align-middle font-medium text-muted-foreground"
                            >
                                Status
                            </th>
                            <th
                                class="h-12 px-4 text-right align-middle font-medium text-muted-foreground"
                            >
                                Total Amount
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
                            v-for="canvas in canvasses.data"
                            :key="canvas.id"
                            class="border-b transition-colors hover:bg-muted/50"
                        >
                            <td class="p-4 align-middle">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10"
                                    >
                                        <Icon
                                            icon="lucide:clipboard-list"
                                            class="h-5 w-5 text-primary"
                                        />
                                    </div>
                                    <div>
                                        <div class="font-medium">
                                            {{
                                                canvas.emanating?.project
                                                    ?.name ?? "—"
                                            }}
                                        </div>
                                        <div
                                            class="text-xs text-muted-foreground"
                                        >
                                            {{
                                                canvas.emanating?.office
                                                    ?.name ??
                                                "Emanating #" +
                                                    canvas.emanating_id
                                            }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="p-4 align-middle text-center">
                                <span
                                    :class="[
                                        'inline-flex items-center rounded-full px-2 py-1 text-xs font-medium',
                                        statusConfig(canvas.status).class,
                                    ]"
                                >
                                    <Icon
                                        :icon="statusConfig(canvas.status).icon"
                                        class="mr-1 h-3 w-3"
                                    />
                                    {{ statusConfig(canvas.status).label }}
                                </span>
                            </td>
                            <td class="p-4 align-middle text-right font-medium">
                                {{ formatCurrency(canvas.total_amount) }}
                            </td>
                            <td class="p-4 align-middle">
                                <div class="flex items-center gap-2">
                                    <Icon
                                        icon="lucide:clock"
                                        class="h-4 w-4 text-muted-foreground"
                                    />
                                    <span class="text-sm">{{
                                        formatDate(canvas.created_at)
                                    }}</span>
                                </div>
                            </td>
                            <td class="p-4 align-middle text-right">
                                <div
                                    class="flex items-center justify-end gap-2"
                                >
                                    <Link
                                        :href="
                                            route('canvasses.show', canvas.id)
                                        "
                                    >
                                        <Button variant="ghost" size="sm">
                                            <Icon
                                                icon="lucide:eye"
                                                class="h-4 w-4"
                                            />
                                        </Button>
                                    </Link>
                                    <Button
                                        v-if="canvas.status === 'pending'"
                                        variant="ghost"
                                        size="sm"
                                        @click="onDeleteClick(canvas)"
                                    >
                                        <Icon
                                            icon="lucide:trash-2"
                                            class="h-4 w-4 text-destructive"
                                        />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="canvasses.data.length === 0">
                            <td colspan="6" class="p-8 text-center">
                                <div class="flex flex-col items-center gap-2">
                                    <Icon
                                        icon="lucide:shopping-cart"
                                        class="h-12 w-12 text-muted-foreground/50"
                                    />
                                    <p class="text-muted-foreground">
                                        No canvasses found
                                    </p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-4 flex items-center justify-between border-t pt-4">
                <div class="text-sm text-muted-foreground">
                    Showing {{ canvasses.from }} to {{ canvasses.to }} of
                    {{ canvasses.total }} entries
                </div>
                <div class="flex items-center gap-1">
                    <template
                        v-for="(link, index) in canvasses.links"
                        :key="index"
                    >
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
