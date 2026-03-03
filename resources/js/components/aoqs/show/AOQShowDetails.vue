<script setup>
const props = defineProps({
    aoq: Object,
    calculation: Object,
});

const formatDate = (date) => {
    if (!date) return "—";
    return new Date(date).toLocaleDateString("en-US", {
        year: "numeric",
        month: "long",
        day: "numeric",
    });
};

const formatCurrency = (value) =>
    new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
    }).format(value || 0);
</script>

<template>
    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-lg border bg-card p-4 text-sm">
            <p class="text-muted-foreground">AOQ Date</p>
            <p class="font-medium">{{ formatDate(aoq.aoq_date) }}</p>
        </div>
        <div class="rounded-lg border bg-card p-4 text-sm">
            <p class="text-muted-foreground">Calculation Mode</p>
            <p class="font-medium">
                {{
                    calculation?.calculated_supplier_count >= 2
                        ? "Lowest Calculated"
                        : "Single Calculated"
                }}
            </p>
        </div>
        <div class="rounded-lg border bg-card p-4 text-sm">
            <p class="text-muted-foreground">Winner Supplier</p>
            <p class="font-medium">{{ aoq.winner_supplier?.name || "—" }}</p>
        </div>
        <div class="rounded-lg border bg-card p-4 text-sm">
            <p class="text-muted-foreground">Winning Amount</p>
            <p class="font-medium">
                {{ formatCurrency(calculation?.winner_total_amount || 0) }}
            </p>
        </div>
    </div>
</template>
