<script setup>
import { Card, CardHeader, CardTitle, CardContent } from "@/components/ui/card";
import { Icon } from "@iconify/vue";

const props = defineProps({
    emanating: Object,
});

const monthNames = [
    "January",
    "February",
    "March",
    "April",
    "May",
    "June",
    "July",
    "August",
    "September",
    "October",
    "November",
    "December",
];

const getMonthName = (month) => {
    return month ? monthNames[month - 1] : "N/A";
};
</script>

<template>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <!-- Basic Info -->
        <Card>
            <CardHeader>
                <CardTitle class="text-base flex items-center">
                    <Icon icon="lucide:info" class="mr-2 h-5 w-5" />
                    Basic Information
                </CardTitle>
            </CardHeader>
            <CardContent class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-muted-foreground">PR No:</span>
                    <span class="font-medium">{{
                        emanating.pr_no || "N/A"
                    }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-muted-foreground">Fiscal Year:</span>
                    <span class="font-medium">{{ emanating.fiscal_year }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-muted-foreground">Quarter:</span>
                    <span class="font-medium">{{
                        emanating.quarter ? `Q${emanating.quarter}` : "N/A"
                    }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-muted-foreground">Month:</span>
                    <span class="font-medium">{{
                        getMonthName(emanating.month)
                    }}</span>
                </div>
            </CardContent>
        </Card>

        <!-- Office & Project -->
        <Card>
            <CardHeader>
                <CardTitle class="text-base flex items-center">
                    <Icon icon="lucide:building" class="mr-2 h-5 w-5" />
                    Office & Project
                </CardTitle>
            </CardHeader>
            <CardContent class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-muted-foreground">Fund:</span>
                    <span class="font-medium">{{
                        emanating.fund?.name ||
                        emanating.project?.fund?.name ||
                        "N/A"
                    }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-muted-foreground">Office:</span>
                    <span class="font-medium">{{
                        emanating.charged_to_project_code_name || "N/A"
                    }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-muted-foreground">Project:</span>
                    <span class="font-medium">{{
                        emanating.project?.name || "N/A"
                    }}</span>
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle class="text-base flex items-center">
                    <Icon icon="lucide:signature" class="mr-2 h-5 w-5" />
                    Signatories
                </CardTitle>
            </CardHeader>
            <CardContent class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-muted-foreground"
                        >Requesting Officer:</span
                    >
                    <span class="font-medium">{{
                        emanating.requesting_officer_name || "N/A"
                    }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-muted-foreground">Title:</span>
                    <span class="font-medium">{{
                        emanating.requesting_officer_title || "N/A"
                    }}</span>
                </div>
            </CardContent>
        </Card>

        <!-- Codes & Status -->
        <Card>
            <CardHeader>
                <CardTitle class="text-base flex items-center">
                    <Icon icon="lucide:hash" class="mr-2 h-5 w-5" />
                    Codes & Status
                </CardTitle>
            </CardHeader>
            <CardContent class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-muted-foreground">PR No:</span>
                    <span class="font-medium">{{
                        emanating.pr_no || "N/A"
                    }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-muted-foreground">Charged To:</span>
                    <span class="font-medium">{{
                        emanating.charged_to_code || "N/A"
                    }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-muted-foreground">Reimbursement:</span>
                    <span class="font-medium">{{
                        emanating.reimbursement ? "Yes" : "No"
                    }}</span>
                </div>
            </CardContent>
        </Card>

        <!-- Approval Info -->
        <Card v-if="emanating.approved_by" class="md:col-span-2 lg:col-span-3">
            <CardHeader>
                <CardTitle class="text-base flex items-center">
                    <Icon icon="lucide:user" class="mr-2 h-5 w-5" />
                    Approval
                </CardTitle>
            </CardHeader>
            <CardContent class="text-sm">
                <div>
                    <span class="text-muted-foreground block mb-1"
                        >Approved By:</span
                    >
                    <span class="font-medium">{{
                        emanating.approved_by.name
                    }}</span>
                    <span class="text-xs text-muted-foreground block mt-1">
                        {{
                            new Date(emanating.approved_at).toLocaleDateString()
                        }}
                    </span>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
