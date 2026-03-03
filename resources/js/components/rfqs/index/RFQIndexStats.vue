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
                <CardDescription>With Deadline</CardDescription>
                <CardTitle class="text-2xl">{{
                    stats?.with_deadline || 0
                }}</CardTitle>
            </CardHeader>
            <CardContent>
                <div
                    class="text-xs text-muted-foreground flex items-center gap-1"
                >
                    <Icon icon="lucide:calendar-check-2" class="h-3.5 w-3.5" />
                    RFQs with a submission deadline
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader class="pb-2">
                <CardDescription>Pending AOQ</CardDescription>
                <CardTitle class="text-2xl">{{
                    stats?.for_aoq || 0
                }}</CardTitle>
            </CardHeader>
            <CardContent>
                <div
                    class="text-xs text-muted-foreground flex items-center gap-1"
                >
                    <Icon icon="lucide:calculator" class="h-3.5 w-3.5" />
                    Ready for AOQ creation
                </div>
            </CardContent>
        </Card>
    </div>
</template>
