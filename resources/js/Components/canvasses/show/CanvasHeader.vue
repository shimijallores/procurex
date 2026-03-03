<script setup>
import { Link } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";

defineProps({
    canvas: Object,
    statusVariant: Function,
    isPending: Boolean,
    allRowsPriced: Boolean,
    completing: Boolean,
    formatCurrency: Function,
    onCompleteCanvas: Function,
});
</script>

<template>
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="flex items-center gap-4">
            <Link :href="route('canvasses.index')">
                <Button variant="ghost" size="sm">
                    <Icon icon="lucide:arrow-left" class="mr-2 h-4 w-4" />
                    Back
                </Button>
            </Link>
            <div class="space-y-1">
                <div class="flex items-center gap-2">
                    <h1 class="text-2xl font-bold tracking-tight md:text-3xl">
                        Canvas #{{ canvas?.id ?? "—" }}
                    </h1>
                    <Badge
                        :variant="statusVariant(canvas.status)"
                        class="capitalize text-sm"
                    >
                        {{ canvas.status }}
                    </Badge>
                </div>
                <p class="text-muted-foreground text-sm">
                    <span class="font-medium">{{
                        canvas.emanating?.project?.name
                    }}</span>
                    <span class="mx-1 text-muted-foreground/50">·</span>
                    <span>{{
                        canvas.emanating?.project?.fund?.office?.name
                    }}</span>
                    <span class="mx-1 text-muted-foreground/50">·</span>
                    <span class="font-mono"
                        >PR {{ canvas.emanating?.pr_no ?? "—" }}</span
                    >
                </p>
            </div>
        </div>

        <!-- Actions -->
        <div v-if="isPending" class="flex gap-2">
            <Button
                :disabled="!allRowsPriced || completing"
                @click="onCompleteCanvas"
            >
                <Icon icon="lucide:check-circle" class="mr-2 h-4 w-4" />
                Complete Canvas
            </Button>
        </div>

        <!-- Completed summary -->
        <div v-else-if="canvas.status === 'completed'" class="text-right">
            <div class="text-sm text-muted-foreground">Total Amount</div>
            <div class="text-2xl font-bold">
                {{ formatCurrency(canvas.total_amount) }}
            </div>
            <div v-if="canvas.emanating?.reimbursement" class="mt-1">
                <Badge variant="secondary">Reimbursement</Badge>
            </div>
        </div>
    </div>
</template>
