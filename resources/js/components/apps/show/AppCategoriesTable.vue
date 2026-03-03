<script setup>
import { Icon } from "@iconify/vue";
import {
    Card,
    CardHeader,
    CardTitle,
    CardDescription,
    CardContent,
} from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";

const props = defineProps({
    categories: Array,
    filteredCategories: Array,
    searchQuery: String,
});

const emit = defineEmits(["update:searchQuery"]);

const formatCurrency = (amount) => {
    return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
    }).format(amount);
};

const monthNames = [
    "Jan",
    "Feb",
    "Mar",
    "Apr",
    "May",
    "Jun",
    "Jul",
    "Aug",
    "Sep",
    "Oct",
    "Nov",
    "Dec",
];

const getMonthName = (monthNum) => {
    return monthNum ? monthNames[monthNum - 1] : "-";
};
</script>

<template>
    <Card v-if="categories && categories.length > 0">
        <CardHeader>
            <div class="flex items-center justify-between">
                <div>
                    <CardTitle class="flex items-center gap-2">
                        <Icon icon="lucide:list" class="h-5 w-5" />
                        Procurement Categories & Items
                    </CardTitle>
                    <CardDescription>
                        {{ filteredCategories.length }} of
                        {{ categories.length }} categories ({{
                            filteredCategories.reduce(
                                (sum, cat) => sum + (cat.items?.length || 0),
                                0,
                            )
                        }}
                        items)
                    </CardDescription>
                </div>
                <div class="w-80">
                    <div class="relative">
                        <Icon
                            icon="lucide:search"
                            class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground"
                        />
                        <Input
                            :model-value="searchQuery"
                            @update:model-value="
                                emit('update:searchQuery', $event)
                            "
                            placeholder="Search categories and items..."
                            class="pl-9"
                        />
                    </div>
                </div>
            </div>
        </CardHeader>
        <CardContent>
            <div v-if="filteredCategories.length > 0" class="space-y-6">
                <div
                    v-for="category in filteredCategories"
                    :key="category.id"
                    class="rounded-lg border p-4 space-y-4"
                >
                    <!-- Category Header -->
                    <div class="flex items-start justify-between">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <span
                                    class="font-mono text-sm bg-primary/10 px-2 py-1 rounded"
                                >
                                    {{ category.pap_code }}
                                </span>
                                <h3 class="font-semibold">
                                    {{ category.name }}
                                </h3>
                            </div>
                            <div
                                class="flex items-center gap-4 text-sm text-muted-foreground"
                            >
                                <div class="flex items-center gap-1">
                                    <Icon
                                        icon="lucide:shopping-cart"
                                        class="h-3 w-3"
                                    />
                                    {{ category.mode_of_procurement }}
                                </div>
                                <div
                                    v-if="category.schedule_from_month"
                                    class="flex items-center gap-1"
                                >
                                    <Icon
                                        icon="lucide:calendar"
                                        class="h-3 w-3"
                                    />
                                    {{
                                        getMonthName(
                                            category.schedule_from_month,
                                        )
                                    }}
                                    -
                                    {{
                                        getMonthName(category.schedule_to_month)
                                    }}
                                </div>
                                <div
                                    v-if="category.source_of_fund"
                                    class="flex items-center gap-1"
                                >
                                    <Icon
                                        icon="lucide:piggy-bank"
                                        class="h-3 w-3"
                                    />
                                    {{ category.source_of_fund }}
                                </div>
                                <span
                                    v-if="category.early_procurement"
                                    class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300"
                                >
                                    Early Procurement
                                </span>
                            </div>
                        </div>
                        <div class="text-right space-y-2">
                            <div
                                v-if="
                                    category.estimated_budget ||
                                    category.mooe_amount ||
                                    category.co_amount
                                "
                                class="text-sm"
                            >
                                <p class="text-muted-foreground">
                                    Category Budget
                                </p>
                                <p
                                    v-if="category.estimated_budget"
                                    class="font-semibold"
                                >
                                    {{
                                        formatCurrency(
                                            category.estimated_budget,
                                        )
                                    }}
                                </p>
                                <div
                                    v-if="
                                        category.mooe_amount ||
                                        category.co_amount
                                    "
                                    class="text-xs text-muted-foreground mt-1"
                                >
                                    <p v-if="category.mooe_amount">
                                        MOOE:
                                        {{
                                            formatCurrency(category.mooe_amount)
                                        }}
                                    </p>
                                    <p v-if="category.co_amount">
                                        CO:
                                        {{ formatCurrency(category.co_amount) }}
                                    </p>
                                </div>
                            </div>
                            <div>
                                <p class="text-sm text-muted-foreground">
                                    Items
                                </p>
                                <p class="text-xl font-bold">
                                    {{ category.items?.length || 0 }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Items Table -->
                    <div
                        v-if="category.items && category.items.length > 0"
                        class="relative overflow-x-auto"
                    >
                        <table class="w-full text-sm">
                            <thead class="border-b bg-muted/50">
                                <tr>
                                    <th class="px-4 py-2 text-left font-medium">
                                        Item Name
                                    </th>
                                    <th
                                        class="px-4 py-2 text-right font-medium"
                                    >
                                        Estimated Budget
                                    </th>
                                    <th
                                        class="px-4 py-2 text-right font-medium"
                                    >
                                        MOOE
                                    </th>
                                    <th
                                        class="px-4 py-2 text-right font-medium"
                                    >
                                        CO
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="item in category.items"
                                    :key="item.id"
                                    class="border-b last:border-0"
                                >
                                    <td class="px-4 py-2">
                                        {{ item.name }}
                                        <span
                                            v-if="item.remarks"
                                            class="text-xs text-muted-foreground ml-2"
                                        >
                                            ({{ item.remarks }})
                                        </span>
                                    </td>
                                    <td
                                        class="px-4 py-2 text-right font-medium"
                                    >
                                        {{
                                            formatCurrency(
                                                item.estimated_budget,
                                            )
                                        }}
                                    </td>
                                    <td class="px-4 py-2 text-right">
                                        {{
                                            item.mooe_amount
                                                ? formatCurrency(
                                                      item.mooe_amount,
                                                  )
                                                : "-"
                                        }}
                                    </td>
                                    <td class="px-4 py-2 text-right">
                                        {{
                                            item.co_amount
                                                ? formatCurrency(item.co_amount)
                                                : "-"
                                        }}
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot class="border-t bg-muted/30 font-semibold">
                                <tr>
                                    <td class="px-4 py-2">Category Total</td>
                                    <td class="px-4 py-2 text-right">
                                        {{
                                            formatCurrency(
                                                category.items.reduce(
                                                    (sum, item) =>
                                                        sum +
                                                        parseFloat(
                                                            item.estimated_budget ||
                                                                0,
                                                        ),
                                                    0,
                                                ),
                                            )
                                        }}
                                    </td>
                                    <td class="px-4 py-2 text-right">
                                        {{
                                            formatCurrency(
                                                category.items.reduce(
                                                    (sum, item) =>
                                                        sum +
                                                        parseFloat(
                                                            item.mooe_amount ||
                                                                0,
                                                        ),
                                                    0,
                                                ),
                                            )
                                        }}
                                    </td>
                                    <td class="px-4 py-2 text-right">
                                        {{
                                            formatCurrency(
                                                category.items.reduce(
                                                    (sum, item) =>
                                                        sum +
                                                        parseFloat(
                                                            item.co_amount || 0,
                                                        ),
                                                    0,
                                                ),
                                            )
                                        }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <div
                        v-else
                        class="text-center py-4 text-muted-foreground text-sm"
                    >
                        No items in this category
                    </div>
                </div>
            </div>
            <div v-else class="py-12">
                <div class="flex flex-col items-center gap-4">
                    <Icon
                        icon="lucide:search-x"
                        class="h-16 w-16 text-muted-foreground/50"
                    />
                    <div class="text-center">
                        <p class="text-lg font-medium">No results found</p>
                        <p class="text-sm text-muted-foreground">
                            Try adjusting your search query
                        </p>
                    </div>
                    <Button
                        variant="outline"
                        @click="emit('update:searchQuery', '')"
                    >
                        Clear Search
                    </Button>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
