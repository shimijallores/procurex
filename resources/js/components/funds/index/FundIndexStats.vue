<script setup>
import { Icon } from "@iconify/vue";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";

defineProps({
    funds: Object,
});
</script>

<template>
    <div class="grid gap-4 md:grid-cols-3">
        <Card>
            <CardHeader
                class="flex flex-row items-center justify-between space-y-0 pb-2"
            >
                <CardTitle class="text-sm font-medium">Total Funds</CardTitle>
                <Icon
                    icon="lucide:wallet"
                    class="h-4 w-4 text-muted-foreground"
                />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold">{{ funds.total }}</div>
                <p class="text-xs text-muted-foreground">Across all offices</p>
            </CardContent>
        </Card>

        <Card>
            <CardHeader
                class="flex flex-row items-center justify-between space-y-0 pb-2"
            >
                <CardTitle class="text-sm font-medium">General Funds</CardTitle>
                <Icon
                    icon="lucide:banknote"
                    class="h-4 w-4 text-muted-foreground"
                />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold">
                    {{ funds.data.filter((f) => f.type === "general").length }}
                </div>
                <p class="text-xs text-muted-foreground">
                    General allocation funds
                </p>
            </CardContent>
        </Card>

        <Card>
            <CardHeader
                class="flex flex-row items-center justify-between space-y-0 pb-2"
            >
                <CardTitle class="text-sm font-medium">Project Funds</CardTitle>
                <Icon
                    icon="lucide:folder-kanban"
                    class="h-4 w-4 text-muted-foreground"
                />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold">
                    {{ funds.data.filter((f) => f.type === "project").length }}
                </div>
                <p class="text-xs text-muted-foreground">
                    Project-specific funds
                </p>
            </CardContent>
        </Card>
    </div>
</template>
