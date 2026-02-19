<script setup>
import { Icon } from "@iconify/vue";
import { Link, router } from "@inertiajs/vue3";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";
import { Button } from "@/components/ui/button";

const props = defineProps({
    items: Object,
    categories: Array,
    selectedCategory: String,
    phasedOut: String,
    onDeleteClick: Function,
    onTogglePhaseOut: Function,
});

const formatCurrency = (value) => {
    if (!value) return "—";
    return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
    }).format(value);
};
</script>

<template>
    <Card>
        <CardHeader>
            <div
                class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <CardTitle>All Master List Items</CardTitle>
                    <CardDescription>
                        A list of all priceable items across suppliers and
                        categories
                    </CardDescription>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <!-- Category filter -->
                    <select
                        :value="selectedCategory"
                        @change="
                            $emit(
                                'update:selectedCategory',
                                $event.target.value,
                            )
                        "
                        class="flex h-9 rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                    >
                        <option value="">All Categories</option>
                        <option
                            v-for="cat in categories"
                            :key="cat.id"
                            :value="cat.id"
                        >
                            {{ cat.name }}
                        </option>
                    </select>
                    <!-- Phase-out filter -->
                    <select
                        :value="phasedOut"
                        @change="$emit('update:phasedOut', $event.target.value)"
                        class="flex h-9 rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                    >
                        <option value="">All Items</option>
                        <option value="0">Active Only</option>
                        <option value="1">Phased Out</option>
                    </select>
                    <slot name="search" />
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
                                Item
                            </th>
                            <th
                                class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                            >
                                Category
                            </th>
                            <th
                                class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                            >
                                Supplier
                            </th>
                            <th
                                class="h-12 px-4 text-center align-middle font-medium text-muted-foreground"
                            >
                                Unit
                            </th>
                            <th
                                class="h-12 px-4 text-right align-middle font-medium text-muted-foreground"
                            >
                                Default Price
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
                            v-for="item in items.data"
                            :key="item.id"
                            class="border-b transition-colors hover:bg-muted/50"
                            :class="item.is_phased_out ? 'opacity-60' : ''"
                        >
                            <td class="p-4 align-middle">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10"
                                    >
                                        <Icon
                                            icon="lucide:package"
                                            class="h-5 w-5 text-primary"
                                        />
                                    </div>
                                    <div>
                                        <div class="font-medium">
                                            {{ item.item_name }}
                                        </div>
                                        <div
                                            v-if="item.remarks"
                                            class="text-xs text-muted-foreground"
                                        >
                                            {{ item.remarks }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="p-4 align-middle text-muted-foreground">
                                {{ item.master_list_category?.name ?? "—" }}
                            </td>
                            <td class="p-4 align-middle text-muted-foreground">
                                {{ item.supplier?.name ?? "—" }}
                            </td>
                            <td
                                class="p-4 align-middle text-center text-muted-foreground"
                            >
                                {{ item.unit }}
                            </td>
                            <td class="p-4 align-middle text-right font-medium">
                                {{ formatCurrency(item.default_unit_price) }}
                            </td>
                            <td class="p-4 align-middle text-center">
                                <span
                                    v-if="item.is_phased_out"
                                    class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300"
                                >
                                    <Icon
                                        icon="lucide:archive"
                                        class="mr-1 h-3 w-3"
                                    />
                                    Phased Out
                                </span>
                                <span
                                    v-else
                                    class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300"
                                >
                                    <Icon
                                        icon="lucide:check-circle"
                                        class="mr-1 h-3 w-3"
                                    />
                                    Active
                                </span>
                            </td>
                            <td class="p-4 align-middle text-right">
                                <div
                                    class="flex items-center justify-end gap-2"
                                >
                                    <Link
                                        :href="
                                            route(
                                                'master-list-items.edit',
                                                item.id,
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
                                        variant="ghost"
                                        size="sm"
                                        :title="
                                            item.is_phased_out
                                                ? 'Restore item'
                                                : 'Phase out item'
                                        "
                                        @click="onTogglePhaseOut(item)"
                                    >
                                        <Icon
                                            :icon="
                                                item.is_phased_out
                                                    ? 'lucide:rotate-ccw'
                                                    : 'lucide:archive'
                                            "
                                            class="h-4 w-4"
                                            :class="
                                                item.is_phased_out
                                                    ? 'text-green-600'
                                                    : 'text-yellow-600'
                                            "
                                        />
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        @click="onDeleteClick(item)"
                                    >
                                        <Icon
                                            icon="lucide:trash-2"
                                            class="h-4 w-4 text-destructive"
                                        />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="items.data.length === 0">
                            <td colspan="7" class="p-8 text-center">
                                <div class="flex flex-col items-center gap-2">
                                    <Icon
                                        icon="lucide:package-open"
                                        class="h-12 w-12 text-muted-foreground/50"
                                    />
                                    <p class="text-muted-foreground">
                                        No items found
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
                    Showing {{ items.from }} to {{ items.to }} of
                    {{ items.total }} entries
                </div>
                <div class="flex items-center gap-1">
                    <template v-for="(link, index) in items.links" :key="index">
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
