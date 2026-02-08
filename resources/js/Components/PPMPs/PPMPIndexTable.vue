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
    ppmps: Object,
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
</script>

<template>
    <Card>
        <CardHeader>
            <div class="flex items-center justify-between">
                <div>
                    <CardTitle>
                        All Project Procurement Management Plans
                    </CardTitle>
                    <CardDescription>
                        A list of all PPMPs across offices and projects
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
                                class="h-12 px-4 text-center align-middle font-medium text-muted-foreground"
                            >
                                Version
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
                            v-for="ppmp in ppmps.data"
                            :key="ppmp.id"
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
                                                ppmp.office?.name ||
                                                "Unknown Office"
                                            }}
                                        </div>
                                        <div
                                            class="text-xs text-muted-foreground"
                                        >
                                            {{
                                                ppmp.project?.name ||
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
                                        ppmp.fiscal_year
                                    }}</span>
                                </div>
                            </td>
                            <td class="p-4 align-middle text-center">
                                <div
                                    class="flex items-center justify-center gap-2"
                                >
                                    <span
                                        v-if="ppmp.is_approved"
                                        class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300"
                                    >
                                        <Icon
                                            icon="lucide:check-circle"
                                            class="mr-1 h-3 w-3"
                                        />
                                        Approved
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300"
                                    >
                                        <Icon
                                            icon="lucide:clock"
                                            class="mr-1 h-3 w-3"
                                        />
                                        Pending
                                    </span>
                                    <span
                                        v-if="ppmp.is_addendum"
                                        class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300"
                                    >
                                        Addendum
                                    </span>
                                </div>
                            </td>
                            <td class="p-4 align-middle text-center">
                                <span
                                    class="font-mono text-sm bg-muted px-2 py-1 rounded"
                                >
                                    v{{ ppmp.version }}
                                </span>
                            </td>
                            <td class="p-4 align-middle">
                                <div class="flex items-center gap-2">
                                    <Icon
                                        icon="lucide:clock"
                                        class="h-4 w-4 text-muted-foreground"
                                    />
                                    <span class="text-sm">{{
                                        formatDate(ppmp.created_at)
                                    }}</span>
                                </div>
                            </td>
                            <td class="p-4 align-middle text-right">
                                <div
                                    class="flex items-center justify-end gap-2"
                                >
                                    <Link
                                        :href="route('ppmps.show', ppmp.id)"
                                    >
                                        <Button variant="ghost" size="sm">
                                            <Icon
                                                icon="lucide:eye"
                                                class="h-4 w-4"
                                            />
                                        </Button>
                                    </Link>
                                    <Link
                                        v-if="!ppmp.is_approved"
                                        :href="
                                            route('ppmps.edit', ppmp.id)
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
                                        v-if="!ppmp.is_approved"
                                        variant="ghost"
                                        size="sm"
                                        @click="onDeleteClick(ppmp)"
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

            <div
                v-if="ppmps.data.length === 0"
                class="flex flex-col items-center justify-center py-12"
            >
                <Icon
                    icon="lucide:inbox"
                    class="h-12 w-12 text-muted-foreground mb-4"
                />
                <h3 class="text-lg font-semibold mb-1">No PPMPs found</h3>
                <p class="text-muted-foreground text-sm mb-4">
                    {{
                        search
                            ? "Try adjusting your search"
                            : "Get started by creating a new PPMP"
                    }}
                </p>
                <Link
                    v-if="!search"
                    :href="route('ppmps.create')"
                >
                    <Button>
                        <Icon icon="lucide:plus" class="mr-2 h-4 w-4" />
                        Create PPMP
                    </Button>
                </Link>
            </div>
        </CardContent>
    </Card>
</template>
