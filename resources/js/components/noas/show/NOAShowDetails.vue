<script setup>
const props = defineProps({
    noa: Object,
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
            <p class="text-muted-foreground">NOA Date</p>
            <p class="font-medium">{{ formatDate(noa.noa_date) }}</p>
        </div>
        <div class="rounded-lg border bg-card p-4 text-sm">
            <p class="text-muted-foreground">BAC Resolution</p>
            <p class="font-medium">
                {{ noa.bac_resolution?.resolution_no || "—" }}
            </p>
        </div>
        <div class="rounded-lg border bg-card p-4 text-sm">
            <p class="text-muted-foreground">SVP No.</p>
            <p class="font-medium">
                {{ noa.aoq?.rfq?.svp_no || "—" }}
            </p>
        </div>
        <div class="rounded-lg border bg-card p-4 text-sm">
            <p class="text-muted-foreground">Winner Amount</p>
            <p class="font-medium">
                {{ formatCurrency(noa.winner_amount ?? 0) }}
            </p>
        </div>

        <div class="rounded-lg border bg-card p-4 text-sm md:col-span-2">
            <p class="text-muted-foreground">Project Name</p>
            <p class="font-medium">
                {{ noa.aoq?.rfq?.project_name || "—" }}
            </p>
        </div>
        <div class="rounded-lg border bg-card p-4 text-sm md:col-span-2">
            <p class="text-muted-foreground">Winner Supplier</p>
            <p class="font-medium">
                {{ noa.aoq?.winner_supplier?.name || "—" }}
            </p>
        </div>
    </div>
</template>
