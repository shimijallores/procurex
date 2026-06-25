<script setup>
import { Icon } from "@iconify/vue";

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
        <a
            v-if="aoq.batch"
            :href="route('batches.show', aoq.batch.id)"
            target="_blank"
            class="rounded-lg border bg-card p-4 text-sm hover:bg-accent transition-colors block"
        >
            <p class="text-muted-foreground flex items-center gap-1">
                Batch
                <Icon icon="lucide:external-link" class="h-3 w-3" />
            </p>
            <p class="font-medium underline underline-offset-2">{{ aoq.batch.batch_no }}</p>
        </a>
        <div v-else class="rounded-lg border bg-card p-4 text-sm">
            <p class="text-muted-foreground">Batch</p>
            <p class="font-medium">—</p>
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
