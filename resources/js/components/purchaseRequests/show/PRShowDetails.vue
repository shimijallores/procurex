<script setup>
import { Icon } from "@iconify/vue";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";

defineProps({
    purchaseRequest: Object,
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
    <div class="grid gap-4 md:grid-cols-2">
        <!-- PR Information -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-base">
                    <Icon
                        icon="lucide:file-text"
                        class="h-4 w-4 text-primary"
                    />
                    PR Information
                </CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <p
                            class="text-xs font-medium text-muted-foreground uppercase tracking-wide"
                        >
                            PR Number
                        </p>
                        <p class="mt-1 font-medium">
                            {{ purchaseRequest.pr_no || "—" }}
                        </p>
                    </div>
                    <div>
                        <p
                            class="text-xs font-medium text-muted-foreground uppercase tracking-wide"
                        >
                            PR Date
                        </p>
                        <p class="mt-1 font-medium">
                            {{ formatDate(purchaseRequest.pr_date) }}
                        </p>
                    </div>
                    <div>
                        <p
                            class="text-xs font-medium text-muted-foreground uppercase tracking-wide"
                        >
                            SAI Number
                        </p>
                        <p class="mt-1 font-medium">
                            {{ purchaseRequest.sai_no || "—" }}
                        </p>
                    </div>
                    <div>
                        <p
                            class="text-xs font-medium text-muted-foreground uppercase tracking-wide"
                        >
                            SAI Date
                        </p>
                        <p class="mt-1 font-medium">
                            {{ formatDate(purchaseRequest.sai_date) }}
                        </p>
                    </div>
                    <div>
                        <p
                            class="text-xs font-medium text-muted-foreground uppercase tracking-wide"
                        >
                            Requested By
                        </p>
                        <p class="mt-1 font-medium">
                            {{ purchaseRequest.requested_by_name || "—" }}
                        </p>
                    </div>
                    <div>
                        <p
                            class="text-xs font-medium text-muted-foreground uppercase tracking-wide"
                        >
                            Designation
                        </p>
                        <p class="mt-1 font-medium">
                            {{
                                purchaseRequest.requested_by_designation || "—"
                            }}
                        </p>
                    </div>
                    <div class="col-span-2">
                        <p
                            class="text-xs font-medium text-muted-foreground uppercase tracking-wide"
                        >
                            Total Amount
                        </p>
                        <p class="mt-1 text-xl font-bold text-primary">
                            {{ formatCurrency(purchaseRequest.total_amount) }}
                        </p>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Office & Fund -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-base">
                    <Icon
                        icon="lucide:building-2"
                        class="h-4 w-4 text-primary"
                    />
                    Office & Fund Details
                </CardTitle>
            </CardHeader>
            <CardContent class="space-y-4 text-sm">
                <div>
                    <p
                        class="text-xs font-medium text-muted-foreground uppercase tracking-wide"
                    >
                        Office
                    </p>
                    <p class="mt-1 font-medium">
                        {{ purchaseRequest.office?.name || "—" }}
                    </p>
                </div>
                <div>
                    <p
                        class="text-xs font-medium text-muted-foreground uppercase tracking-wide"
                    >
                        Fund
                    </p>
                    <p class="mt-1 font-medium">
                        {{ purchaseRequest.fund?.name || "—" }}
                    </p>
                </div>
                <div v-if="purchaseRequest.emanating?.project">
                    <p
                        class="text-xs font-medium text-muted-foreground uppercase tracking-wide"
                    >
                        Project
                    </p>
                    <p class="mt-1 font-medium">
                        {{ purchaseRequest.emanating.project.name }}
                    </p>
                </div>
                <div v-if="purchaseRequest.emanating?.ppmp_category">
                    <p
                        class="text-xs font-medium text-muted-foreground uppercase tracking-wide"
                    >
                        Category
                    </p>
                    <p class="mt-1 font-medium">
                        {{
                            purchaseRequest.emanating.ppmp_category?.name || "—"
                        }}
                    </p>
                </div>
            </CardContent>
        </Card>

        <!-- Purpose -->
        <Card class="md:col-span-2">
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-base">
                    <Icon icon="lucide:target" class="h-4 w-4 text-primary" />
                    Purpose
                </CardTitle>
            </CardHeader>
            <CardContent>
                <p class="text-sm leading-relaxed">
                    {{ purchaseRequest.purpose || "No purpose stated." }}
                </p>
                <div
                    v-if="purchaseRequest.remarks"
                    class="mt-4 rounded-md border border-border bg-muted/30 p-3"
                >
                    <p class="text-xs font-medium text-muted-foreground mb-1">
                        Remarks / Return Reason
                    </p>
                    <p class="text-sm text-destructive">
                        {{ purchaseRequest.remarks }}
                    </p>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
