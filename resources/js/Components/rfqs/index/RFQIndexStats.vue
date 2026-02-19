<script setup>
import { Icon } from "@iconify/vue";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";

const props = defineProps({
    stats: Object,
});
</script>

<template>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <Card>
            <CardHeader class="pb-2">
                <CardDescription>Total RFQs</CardDescription>
                <CardTitle class="text-2xl">{{ stats?.total || 0 }}</CardTitle>
            </CardHeader>
            <CardContent>
                <div
                    class="text-xs text-muted-foreground flex items-center gap-1"
                >
                    <Icon icon="lucide:file-text" class="h-3.5 w-3.5" />
                    All generated RFQs
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader class="pb-2">
                <CardDescription>Open Deadlines</CardDescription>
                <CardTitle class="text-2xl">{{ stats?.open || 0 }}</CardTitle>
            </CardHeader>
            <CardContent>
                <div
                    class="text-xs text-muted-foreground flex items-center gap-1"
                >
                    <Icon icon="lucide:calendar-clock" class="h-3.5 w-3.5" />
                    RFQs not yet past deadline
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader class="pb-2">
                <CardDescription>Late Filings</CardDescription>
                <CardTitle class="text-2xl">{{
                    stats?.with_late_submission || 0
                }}</CardTitle>
            </CardHeader>
            <CardContent>
                <div
                    class="text-xs text-muted-foreground flex items-center gap-1"
                >
                    <Icon icon="lucide:triangle-alert" class="h-3.5 w-3.5" />
                    RFQs with late supplier submission
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader class="pb-2">
                <CardDescription>Submitted Suppliers</CardDescription>
                <CardTitle class="text-2xl">{{
                    stats?.submitted_supplier_count || 0
                }}</CardTitle>
            </CardHeader>
            <CardContent>
                <div
                    class="text-xs text-muted-foreground flex items-center gap-1"
                >
                    <Icon icon="lucide:inbox" class="h-3.5 w-3.5" />
                    Supplier responses received
                </div>
            </CardContent>
        </Card>
    </div>
</template>
