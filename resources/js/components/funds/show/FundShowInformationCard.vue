<script setup>
import { Icon } from "@iconify/vue";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";

defineProps({
    fund: Object,
});

const getFundTypeBadgeClass = (type) => {
    return type === "project"
        ? "bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300"
        : "bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300";
};
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle class="flex items-center gap-2">
                <Icon icon="lucide:wallet" class="h-5 w-5" />
                Fund Information
            </CardTitle>
            <CardDescription>
                Basic information about this fund
            </CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
            <div class="grid gap-1">
                <p class="text-sm font-medium text-muted-foreground">
                    Fund Code
                </p>
                <p class="font-medium">{{ fund.code }}</p>
            </div>
            <div class="grid gap-1">
                <p class="text-sm font-medium text-muted-foreground">
                    Fund Name
                </p>
                <p class="font-medium">{{ fund.name }}</p>
            </div>
            <div class="grid gap-1">
                <p class="text-sm font-medium text-muted-foreground">Office</p>
                <p class="font-medium">{{ fund.office.name }}</p>
            </div>
            <div class="grid gap-1">
                <p class="text-sm font-medium text-muted-foreground">
                    Project Code
                </p>
                <p class="font-medium">
                    {{ fund.project_code?.code }} -
                    {{ fund.project_code?.name }}
                </p>
            </div>
            <div class="grid gap-1">
                <p class="text-sm font-medium text-muted-foreground">Type</p>
                <span
                    :class="[
                        'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold w-fit',
                        getFundTypeBadgeClass(fund.type),
                    ]"
                >
                    {{ fund.type.charAt(0).toUpperCase() + fund.type.slice(1) }}
                </span>
            </div>
            <div class="grid gap-1">
                <p class="text-sm font-medium text-muted-foreground">
                    Fiscal Year
                </p>
                <p class="font-medium">{{ fund.fiscal_year }}</p>
            </div>
            <div v-if="fund.remarks" class="grid gap-1">
                <p class="text-sm font-medium text-muted-foreground">Remarks</p>
                <p class="font-medium">{{ fund.remarks }}</p>
            </div>
        </CardContent>
    </Card>
</template>
