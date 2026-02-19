<script setup>
import { Icon } from "@iconify/vue";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Link } from "@inertiajs/vue3";
import { Button } from "@/components/ui/button";

defineProps({
    earmark: Object,
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

const getPRStatusBadge = (status) => {
    const map = {
        draft: {
            text: "Draft",
            color: "bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300",
        },
        for_budget_review: {
            text: "For Budget Review",
            color: "bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-300",
        },
        approved: {
            text: "Approved",
            color: "bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300",
        },
        returned: {
            text: "Returned",
            color: "bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300",
        },
        cancelled: {
            text: "Cancelled",
            color: "bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300",
        },
    };
    return map[status] || map.draft;
};
</script>

<template>
    <div class="grid gap-6 md:grid-cols-2">
        <!-- Earmark Details -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <Icon icon="lucide:stamp" class="h-5 w-5 text-primary" />
                    Earmark Details
                </CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm font-medium text-muted-foreground">
                            Earmark No.
                        </p>
                        <p class="font-semibold">{{ earmark.earmark_no }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-muted-foreground">
                            Date
                        </p>
                        <p>{{ formatDate(earmark.earmark_date) }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-muted-foreground">
                            Certified Amount
                        </p>
                        <p
                            class="text-lg font-bold text-green-600 dark:text-green-400"
                        >
                            {{ formatCurrency(earmark.certified_amount) }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-muted-foreground">
                            Fund
                        </p>
                        <p>{{ earmark.fund?.name || "—" }}</p>
                    </div>
                    <div v-if="earmark.expense_class" class="col-span-2">
                        <p class="text-sm font-medium text-muted-foreground">
                            Expense Class
                        </p>
                        <p>{{ earmark.expense_class }}</p>
                    </div>
                    <div v-if="earmark.resolution_no">
                        <p class="text-sm font-medium text-muted-foreground">
                            Resolution No.
                        </p>
                        <p>{{ earmark.resolution_no }}</p>
                    </div>
                    <div v-if="earmark.ordinance_no">
                        <p class="text-sm font-medium text-muted-foreground">
                            Ordinance No.
                        </p>
                        <p>{{ earmark.ordinance_no }}</p>
                    </div>
                    <div v-if="earmark.ordinance_date" class="col-span-2">
                        <p class="text-sm font-medium text-muted-foreground">
                            Ordinance Date
                        </p>
                        <p>{{ formatDate(earmark.ordinance_date) }}</p>
                    </div>
                    <div v-if="earmark.remarks" class="col-span-2">
                        <p class="text-sm font-medium text-muted-foreground">
                            Remarks
                        </p>
                        <p class="rounded-md bg-muted p-2 text-sm">
                            {{ earmark.remarks }}
                        </p>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Linked Purchase Request -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <Icon
                        icon="lucide:file-text"
                        class="h-5 w-5 text-primary"
                    />
                    Linked Purchase Request
                </CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <div v-if="earmark.purchase_request" class="space-y-3">
                    <div class="flex items-center justify-between">
                        <p class="font-semibold">
                            PR #{{
                                earmark.purchase_request.pr_no ||
                                earmark.purchase_request.id
                            }}
                        </p>
                        <span
                            :class="
                                getPRStatusBadge(
                                    earmark.purchase_request.status,
                                ).color
                            "
                            class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold"
                        >
                            {{
                                getPRStatusBadge(
                                    earmark.purchase_request.status,
                                ).text
                            }}
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <p class="font-medium text-muted-foreground">
                                Office
                            </p>
                            <p>
                                {{
                                    earmark.purchase_request.office?.name || "—"
                                }}
                            </p>
                        </div>
                        <div>
                            <p class="font-medium text-muted-foreground">
                                PR Date
                            </p>
                            <p>
                                {{
                                    formatDate(earmark.purchase_request.pr_date)
                                }}
                            </p>
                        </div>
                        <div>
                            <p class="font-medium text-muted-foreground">
                                SAI No.
                            </p>
                            <p>{{ earmark.purchase_request.sai_no || "—" }}</p>
                        </div>
                        <div>
                            <p class="font-medium text-muted-foreground">
                                Total Amount
                            </p>
                            <p class="font-semibold">
                                {{
                                    formatCurrency(
                                        earmark.purchase_request.total_amount,
                                    )
                                }}
                            </p>
                        </div>
                        <div
                            v-if="earmark.purchase_request.purpose"
                            class="col-span-2"
                        >
                            <p class="font-medium text-muted-foreground">
                                Purpose
                            </p>
                            <p>{{ earmark.purchase_request.purpose }}</p>
                        </div>
                    </div>

                    <!-- PR Items summary -->
                    <div v-if="earmark.purchase_request.items?.length">
                        <p
                            class="mb-2 text-sm font-medium text-muted-foreground"
                        >
                            Items ({{ earmark.purchase_request.items.length }})
                        </p>
                        <div class="rounded-md border">
                            <table class="w-full text-sm">
                                <thead class="bg-muted/50">
                                    <tr>
                                        <th class="px-3 py-2 text-left">
                                            Item
                                        </th>
                                        <th class="px-3 py-2 text-center">
                                            Unit
                                        </th>
                                        <th class="px-3 py-2 text-center">
                                            Qty
                                        </th>
                                        <th class="px-3 py-2 text-right">
                                            Total
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="item in earmark.purchase_request
                                            .items"
                                        :key="item.id"
                                        class="border-t"
                                    >
                                        <td class="px-3 py-2">
                                            {{
                                                item.emanating_item?.ppmp_item
                                                    ?.name || "—"
                                            }}
                                        </td>
                                        <td class="px-3 py-2 text-center">
                                            {{
                                                item.emanating_item?.unit || "—"
                                            }}
                                        </td>
                                        <td class="px-3 py-2 text-center">
                                            {{ item.quantity }}
                                        </td>
                                        <td class="px-3 py-2 text-right">
                                            {{
                                                formatCurrency(item.line_total)
                                            }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div v-else class="text-center text-muted-foreground py-4">
                    No PR linked.
                </div>
            </CardContent>
        </Card>
    </div>
</template>
