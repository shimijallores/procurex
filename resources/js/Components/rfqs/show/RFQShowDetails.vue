<script setup>
import { Icon } from "@iconify/vue";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";

const props = defineProps({
    rfq: Object,
});

const formatDate = (date) => {
    if (!date) return "—";
    return new Date(date).toLocaleDateString("en-US", {
        year: "numeric",
        month: "long",
        day: "numeric",
    });
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
    }).format(value || 0);
};
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle class="flex items-center gap-2 text-base">
                <Icon icon="lucide:info" class="h-4 w-4 text-primary" />
                RFQ Details
            </CardTitle>
        </CardHeader>
        <CardContent class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 text-sm">
            <div>
                <p class="text-muted-foreground">RFQ Date</p>
                <p class="font-medium">{{ formatDate(rfq.rfq_date) }}</p>
            </div>
            <div>
                <p class="text-muted-foreground">Submission Deadline</p>
                <p class="font-medium">
                    {{ formatDate(rfq.submission_deadline) }}
                </p>
            </div>
            <div>
                <p class="text-muted-foreground">ABC Amount</p>
                <p class="font-medium">{{ formatCurrency(rfq.abc_amount) }}</p>
            </div>
            <div>
                <p class="text-muted-foreground">Linked PR</p>
                <p class="font-medium">
                    {{ rfq.purchase_request?.pr_no || "#" + rfq.pr_id }}
                </p>
            </div>
            <div class="sm:col-span-2 lg:col-span-4" v-if="rfq.remarks">
                <p class="text-muted-foreground">Remarks</p>
                <p class="font-medium">{{ rfq.remarks }}</p>
            </div>
        </CardContent>
    </Card>
</template>
