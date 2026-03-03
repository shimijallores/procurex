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
    suppliers: Object,
    onDeleteClick: Function,
});
</script>

<template>
    <Card>
        <CardHeader>
            <div class="flex items-center justify-between">
                <div>
                    <CardTitle>All Suppliers</CardTitle>
                    <CardDescription>
                        A list of all registered vendors and suppliers
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
                                Supplier
                            </th>
                            <th
                                class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                            >
                                Contact Person
                            </th>
                            <th
                                class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                            >
                                Contact Number
                            </th>
                            <th
                                class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                            >
                                Email
                            </th>
                            <th
                                class="h-12 px-4 text-center align-middle font-medium text-muted-foreground"
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
                            v-for="supplier in suppliers.data"
                            :key="supplier.id"
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
                                            {{ supplier.name }}
                                        </div>
                                        <div
                                            v-if="supplier.tin"
                                            class="text-xs text-muted-foreground"
                                        >
                                            TIN: {{ supplier.tin }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="p-4 align-middle text-muted-foreground">
                                {{ supplier.contact_person ?? "—" }}
                            </td>
                            <td class="p-4 align-middle text-muted-foreground">
                                {{ supplier.contact_number ?? "—" }}
                            </td>
                            <td class="p-4 align-middle text-muted-foreground">
                                {{ supplier.email ?? "—" }}
                            </td>
                            <td class="p-4 align-middle text-center">
                                <span
                                    v-if="supplier.is_active"
                                    class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300"
                                >
                                    <Icon
                                        icon="lucide:check-circle"
                                        class="mr-1 h-3 w-3"
                                    />
                                    Active
                                </span>
                                <span
                                    v-else
                                    class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400"
                                >
                                    <Icon
                                        icon="lucide:x-circle"
                                        class="mr-1 h-3 w-3"
                                    />
                                    Inactive
                                </span>
                            </td>
                            <td class="p-4 align-middle text-right">
                                <div
                                    class="flex items-center justify-end gap-2"
                                >
                                    <Link
                                        :href="
                                            route('suppliers.show', supplier.id)
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
                                        :href="
                                            route('suppliers.edit', supplier.id)
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
                                        variant="ghost"
                                        size="sm"
                                        @click="onDeleteClick(supplier)"
                                    >
                                        <Icon
                                            icon="lucide:trash-2"
                                            class="h-4 w-4 text-destructive"
                                        />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="suppliers.data.length === 0">
                            <td colspan="6" class="p-8 text-center">
                                <div class="flex flex-col items-center gap-2">
                                    <Icon
                                        icon="lucide:truck"
                                        class="h-12 w-12 text-muted-foreground/50"
                                    />
                                    <p class="text-muted-foreground">
                                        No suppliers found
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
                    Showing {{ suppliers.from }} to {{ suppliers.to }} of
                    {{ suppliers.total }} entries
                </div>
                <div class="flex items-center gap-1">
                    <template
                        v-for="(link, index) in suppliers.links"
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
