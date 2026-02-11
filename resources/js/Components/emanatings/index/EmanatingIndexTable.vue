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
    emanatings: Object,
    search: String,
    onDeleteClick: Function,
});

const formatDate = (date) => {
    return new Date(date).toLocaleDateString("en-US", {
        year: "numeric",
        month: "short",
        day: "numeric",
    });
};

const getStatusBadge = (emanating) => {
    if (emanating.is_approved) {
        return {
            text: "Approved",
            color: "bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300",
            icon: "lucide:check-circle",
        };
    } else if (emanating.rejection_reason) {
        return {
            text: "Rejected",
            color: "bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300",
            icon: "lucide:x-circle",
        };
    } else {
        return {
            text: "Pending",
            color: "bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300",
            icon: "lucide:clock",
        };
    }
};
</script>

<template>
    <Card>
        <CardHeader>
            <div class="flex items-center justify-between">
                <div>
                    <CardTitle> All Emanating Requests </CardTitle>
                    <CardDescription>
                        A list of all emanating requests
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
                                Office & Project
                            </th>
                            <th
                                class="h-12 px-4 text-center align-middle font-medium text-muted-foreground"
                            >
                                Fiscal Year
                            </th>
                            <th
                                class="h-12 px-4 text-center align-middle font-medium text-muted-foreground"
                            >
                                Status
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
                            v-for="emanating in emanatings.data"
                            :key="emanating.id"
                            class="border-b transition-colors hover:bg-muted/50"
                        >
                            <td class="p-4 align-middle">
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
                                            {{
                                                emanating.project?.fund?.office
                                                    ?.name || "Unknown Office"
                                            }}
                                        </div>
                                        <div
                                            class="text-xs text-muted-foreground"
                                        >
                                            {{
                                                emanating.project?.name ||
                                                "Unknown Project"
                                            }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="p-4 align-middle text-center">
                                <div
                                    class="flex items-center justify-center gap-2"
                                >
                                    <Icon
                                        icon="lucide:calendar"
                                        class="h-4 w-4 text-muted-foreground"
                                    />
                                    <span class="font-medium">{{
                                        emanating.fiscal_year
                                    }}</span>
                                </div>
                            </td>
                            <td class="p-4 align-middle text-center">
                                <div
                                    class="flex items-center justify-center gap-2"
                                >
                                    <span
                                        :class="[
                                            'inline-flex items-center rounded-full px-2 py-1 text-xs font-medium',
                                            getStatusBadge(emanating).color,
                                        ]"
                                    >
                                        <Icon
                                            :icon="
                                                getStatusBadge(emanating).icon
                                            "
                                            class="mr-1 h-3 w-3"
                                        />
                                        {{ getStatusBadge(emanating).text }}
                                    </span>
                                </div>
                            </td>
                            <td class="p-4 align-middle">
                                <div class="flex items-center gap-2">
                                    <Icon
                                        icon="lucide:clock"
                                        class="h-4 w-4 text-muted-foreground"
                                    />
                                    <span class="text-sm">{{
                                        formatDate(emanating.created_at)
                                    }}</span>
                                </div>
                            </td>
                            <td class="p-4 align-middle text-right">
                                <div
                                    class="flex items-center justify-end gap-2"
                                >
                                    <Link
                                        :href="
                                            route(
                                                'emanatings.show',
                                                emanating.id,
                                            )
                                        "
                                    >
                                        <Button variant="ghost" size="sm">
                                            <Icon
                                                icon="lucide:eye"
                                                class="h-4 w-4"
                                            />
                                        </Button>
                                    </Link>
                                    <Link
                                        v-if="!emanating.is_approved"
                                        :href="
                                            route(
                                                'emanatings.edit',
                                                emanating.id,
                                            )
                                        "
                                    >
                                        <Button variant="ghost" size="sm">
                                            <Icon
                                                icon="lucide:pencil"
                                                class="h-4 w-4"
                                            />
                                        </Button>
                                    </Link>
                                    <Button
                                        v-if="!emanating.is_approved"
                                        variant="ghost"
                                        size="sm"
                                        @click="onDeleteClick(emanating)"
                                    >
                                        <Icon
                                            icon="lucide:trash-2"
                                            class="h-4 w-4 text-destructive"
                                        />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-4 flex items-center justify-between border-t pt-4">
                <div class="text-sm text-muted-foreground">
                    Showing {{ emanatings.from }} to {{ emanatings.to }} of
                    {{ emanatings.total }} entries
                </div>
                <div class="flex items-center gap-1">
                    <template v-for="(link, index) in emanatings.links" :key="index">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            :class="[
                                'inline-flex h-9 items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors',
                                'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2',
                                link.label.includes('Previous') || link.label.includes('Next')
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
                                link.label.includes('Previous') || link.label.includes('Next') ? 'px-3' : 'w-9',
                                'pointer-events-none opacity-50',
                            ]"
                            v-html="link.label"
                        />
                    </template>
                </div>
            </div>

            <div
                v-if="emanatings.data.length === 0"
                class="flex flex-col items-center justify-center py-12"
            >
                <Icon
                    icon="lucide:inbox"
                    class="h-12 w-12 text-muted-foreground mb-4"
                />
                <h3 class="text-lg font-semibold mb-1">
                    No emanating requests found
                </h3>
                <p class="text-muted-foreground text-sm mb-4">
                    {{
                        search
                            ? "Try adjusting your search"
                            : "Get started by creating a new emanating request"
                    }}
                </p>
                <Link v-if="!search" :href="route('emanatings.create')">
                    <Button>
                        <Icon icon="lucide:plus" class="mr-2 h-4 w-4" />
                        Create Emanating
                    </Button>
                </Link>
            </div>
        </CardContent>
    </Card>
</template>
