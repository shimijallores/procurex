<script setup>
import { Icon } from "@iconify/vue";
import { Card, CardHeader, CardTitle, CardContent } from "@/components/ui/card";

const props = defineProps({
    app: Object,
    totalBudget: Number,
});

const formatCurrency = (amount) => {
    return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
    }).format(amount);
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString("en-US", {
        year: "numeric",
        month: "long",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
};
</script>

<template>
    <div class="grid gap-6 md:grid-cols-3">
        <!-- Details Card -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <Icon icon="lucide:info" class="h-5 w-5" />
                    APP Information
                </CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="grid gap-1">
                    <p class="text-sm font-medium text-muted-foreground">
                        Office
                    </p>
                    <p class="font-medium">
                        {{ app.office?.name || "Unknown Office" }}
                    </p>
                </div>
                <div class="grid gap-1">
                    <p class="text-sm font-medium text-muted-foreground">
                        Fiscal Year
                    </p>
                    <p class="font-medium">{{ app.fiscal_year }}</p>
                </div>
                <div class="grid gap-1">
                    <p class="text-sm font-medium text-muted-foreground">
                        Created At
                    </p>
                    <p class="text-sm">{{ formatDate(app.created_at) }}</p>
                </div>
            </CardContent>
        </Card>

        <!-- Stats Card -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <Icon icon="lucide:bar-chart-3" class="h-5 w-5" />
                    Statistics
                </CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="grid gap-1">
                    <p class="text-sm font-medium text-muted-foreground">
                        Total Categories
                    </p>
                    <p class="text-2xl font-bold">
                        {{ app.categories?.length || 0 }}
                    </p>
                </div>
                <div class="grid gap-1">
                    <p class="text-sm font-medium text-muted-foreground">
                        Total Items
                    </p>
                    <p class="text-2xl font-bold">
                        {{
                            app.categories?.reduce(
                                (sum, cat) => sum + (cat.items?.length || 0),
                                0,
                            ) || 0
                        }}
                    </p>
                </div>
            </CardContent>
        </Card>

        <!-- Budget Card -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <Icon icon="lucide:wallet" class="h-5 w-5" />
                    Total Budget
                </CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="grid gap-1">
                    <p class="text-sm font-medium text-muted-foreground">
                        Estimated Budget
                    </p>
                    <p class="text-2xl font-bold">
                        {{ formatCurrency(totalBudget) }}
                    </p>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
