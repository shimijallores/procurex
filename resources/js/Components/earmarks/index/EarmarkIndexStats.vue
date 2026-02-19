<script setup>
import { Icon } from "@iconify/vue";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";

defineProps({
    stats: Object,
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
        maximumFractionDigits: 0,
    }).format(value || 0);
};
</script>

<template>
    <div class="grid gap-4 md:grid-cols-4">
        <Card>
            <CardHeader
                class="flex flex-row items-center justify-between space-y-0 pb-2"
            >
                <CardTitle class="text-sm font-medium"
                    >Total Earmarks</CardTitle
                >
                <Icon
                    icon="lucide:stamp"
                    class="h-4 w-4 text-muted-foreground"
                />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold">
                    {{ stats?.total_earmarks || 0 }}
                </div>
                <p class="text-xs text-muted-foreground">
                    All issued certifications
                </p>
            </CardContent>
        </Card>

        <Card>
            <CardHeader
                class="flex flex-row items-center justify-between space-y-0 pb-2"
            >
                <CardTitle class="text-sm font-medium"
                    >Pending Review</CardTitle
                >
                <Icon
                    icon="lucide:clock"
                    class="h-4 w-4 text-muted-foreground"
                />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold">
                    {{ stats?.pending_review || 0 }}
                </div>
                <p class="text-xs text-muted-foreground">
                    PRs awaiting budget review
                </p>
            </CardContent>
        </Card>

        <Card>
            <CardHeader
                class="flex flex-row items-center justify-between space-y-0 pb-2"
            >
                <CardTitle class="text-sm font-medium"
                    >Issued This Month</CardTitle
                >
                <Icon
                    icon="lucide:check-circle-2"
                    class="h-4 w-4 text-muted-foreground"
                />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold">
                    {{ stats?.approved_this_month || 0 }}
                </div>
                <p class="text-xs text-muted-foreground">
                    Earmarks issued this month
                </p>
            </CardContent>
        </Card>

        <Card>
            <CardHeader
                class="flex flex-row items-center justify-between space-y-0 pb-2"
            >
                <CardTitle class="text-sm font-medium"
                    >Total Certified</CardTitle
                >
                <Icon
                    icon="lucide:wallet"
                    class="h-4 w-4 text-muted-foreground"
                />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold">
                    {{ formatCurrency(stats?.total_certified) }}
                </div>
                <p class="text-xs text-muted-foreground">
                    Total certified amount
                </p>
            </CardContent>
        </Card>
    </div>
</template>
