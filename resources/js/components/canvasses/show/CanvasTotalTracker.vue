<script setup>
import { Icon } from "@iconify/vue";
import { Badge } from "@/components/ui/badge";

defineProps({
    canvasTotal: Number,
    formatCurrency: Function,
});
</script>

<template>
    <div
        class="flex items-center justify-between rounded-lg border bg-muted/50 px-4 py-3 text-sm"
    >
        <div class="flex items-center gap-2 text-muted-foreground">
            <Icon icon="lucide:calculator" class="h-4 w-4" />
            Running Total
            <span v-if="canvasTotal < 10000" class="ml-2">
                <Badge variant="secondary"
                    >Below ₱10k — will be reimbursement</Badge
                >
            </span>
        </div>
        <div class="font-mono font-bold text-lg">
            {{ formatCurrency(canvasTotal) }}
        </div>
    </div>
</template>
