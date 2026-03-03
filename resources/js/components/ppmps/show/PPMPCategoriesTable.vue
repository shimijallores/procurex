<script setup>
import { ref } from "vue";
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
});

const searchQuery = ref("");

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
    return monthNames[monthNum - 1] || "";
};

const getItemMonths = (item) => {
    if (!item.months || item.months.length === 0) return "-";
    return item.months
        .map((m) => `${getMonthName(m.month)}(${m.planned_quantity})`)
        .join(", ");
};
</script>

<template>
    <Card v-if="props.categories && props.categories.length > 0">
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
                            v-model="searchQuery"
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
                        <div class="space-y-1 flex-1">
                            <div class="flex items-center gap-2">
                                <span
                                    class="font-mono text-sm bg-primary/10 px-2 py-1 rounded"
                                >
                                    {{ category.code }}
                                </span>
                                <h3 class="font-semibold">
                                    {{ category.name }}
                                </h3>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-muted-foreground">
                                PPMP Budget
                            </p>
                            <p class="text-lg font-semibold">
                                {{ formatCurrency(category.estimated_budget) }}
                            </p>
                            <p class="text-sm text-muted-foreground mt-2">
                                {{ category.items?.length || 0 }} items
                            </p>
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
                                        class="px-4 py-2 text-center font-medium"
                                    >
                                        Quantity
                                    </th>
                                    <th
                                        class="px-4 py-2 text-center font-medium"
                                    >
                                        Unit
                                    </th>
                                    <th
                                        class="px-4 py-2 text-right font-medium"
                                    >
                                        Budget
                                    </th>
                                    <th
                                        class="px-4 py-2 text-center font-medium"
                                    >
                                        Mode
                                    </th>
                                    <th class="px-4 py-2 text-left font-medium">
                                        Schedule (Month: Qty)
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
                                    </td>
                                    <td class="px-4 py-2 text-center">
                                        {{ item.quantity }}
                                    </td>
                                    <td class="px-4 py-2 text-center">
                                        {{ item.unit }}
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
                                    <td class="px-4 py-2 text-center text-xs">
                                        {{ item.mode_of_procurement }}
                                    </td>
                                    <td class="px-4 py-2 text-xs">
                                        {{ getItemMonths(item) }}
                                    </td>
                                </tr>
                            </tbody>
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
                    <Button variant="outline" @click="searchQuery = ''">
                        Clear Search
                    </Button>
                </div>
            </div>
        </CardContent>
    </Card>

    <!-- Empty State -->
    <Card v-else>
        <CardContent class="py-12">
            <div class="flex flex-col items-center gap-4">
                <Icon
                    icon="lucide:inbox"
                    class="h-16 w-16 text-muted-foreground/50"
                />
                <div class="text-center">
                    <p class="text-lg font-medium">
                        No categories or items yet
                    </p>
                    <p class="text-sm text-muted-foreground">
                        Import an XLSX file to populate this PPMP with
                        procurement data
                    </p>
                </div>
                <Button @click="$emit('import')">
                    <Icon icon="lucide:upload" class="mr-2 h-4 w-4" />
                    Import XLSX
                </Button>
            </div>
        </CardContent>
    </Card>
</template>
