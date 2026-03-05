<script setup>
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";

defineProps({
    matrixRow: Object,
});

const formatDate = (date) => {
    if (!date) {
        return "-";
    }

    return new Date(date).toLocaleDateString("en-US", {
        year: "numeric",
        month: "2-digit",
        day: "2-digit",
    });
};

const formatCurrency = (value) => {
    if (!value) {
        return "-";
    }

    return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
    }).format(value);
};
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Matrix Row Information</CardTitle>
        </CardHeader>
        <CardContent>
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <span class="text-muted-foreground">Control No:</span>
                    {{ matrixRow.control_no || "-" }}
                </div>
                <div>
                    <span class="text-muted-foreground">Office:</span>
                    {{ matrixRow.office_name || "-" }}
                </div>
                <div>
                    <span class="text-muted-foreground">Item:</span>
                    {{ matrixRow.item_description || "-" }}
                </div>
                <div>
                    <span class="text-muted-foreground">PR No:</span>
                    {{ matrixRow.pr_no || "-" }}
                </div>
                <div>
                    <span class="text-muted-foreground">PR Date:</span>
                    {{ formatDate(matrixRow.pr_date) }}
                </div>
                <div>
                    <span class="text-muted-foreground">Amount Below 1M:</span>
                    {{ formatCurrency(matrixRow.amount_below_1m) }}
                </div>
                <div>
                    <span class="text-muted-foreground">Amount Above 1M:</span>
                    {{ formatCurrency(matrixRow.amount_above_1m) }}
                </div>
                <div>
                    <span class="text-muted-foreground">New Amount:</span>
                    {{ formatCurrency(matrixRow.new_amount) }}
                </div>
                <div>
                    <span class="text-muted-foreground">Account:</span>
                    {{ matrixRow.account_name || "-" }}
                </div>
                <div>
                    <span class="text-muted-foreground">PR Section:</span>
                    {{ matrixRow.pr_admin_name || "-" }}
                </div>
                <div>
                    <span class="text-muted-foreground">Budgeting:</span>
                    {{ matrixRow.budgeting_admin_name || "-" }}
                </div>
                <div>
                    <span class="text-muted-foreground">Date Release:</span>
                    {{ formatDate(matrixRow.date_release) }}
                </div>
                <div>
                    <span class="text-muted-foreground">New Date Release:</span>
                    {{ formatDate(matrixRow.new_date_release) }}
                </div>
                <div class="md:col-span-2">
                    <span class="text-muted-foreground">Remarks:</span>
                    {{ matrixRow.remarks || "-" }}
                </div>
            </div>
        </CardContent>
    </Card>
</template>
