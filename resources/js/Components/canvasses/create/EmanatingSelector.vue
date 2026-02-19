<script setup>
import { Icon } from "@iconify/vue";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";

defineProps({
    emanatings: Array,
    selectedEmanating: Object,
    formatDate: Function,
    onSelectEmanating: Function,
});
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Select Emanating Request</CardTitle>
            <CardDescription
                >Only approved emanatings without an active canvas are
                shown</CardDescription
            >
        </CardHeader>
        <CardContent class="p-0">
            <div
                v-if="emanatings.length === 0"
                class="px-6 py-10 text-center text-muted-foreground"
            >
                <Icon
                    icon="lucide:inbox"
                    class="mx-auto mb-2 h-10 w-10 opacity-30"
                />
                <p>No approved emanatings available for canvassing.</p>
            </div>
            <div v-else class="divide-y max-h-[500px] overflow-y-auto">
                <button
                    v-for="em in emanatings"
                    :key="em.id"
                    type="button"
                    :class="[
                        'w-full text-left px-4 py-3 hover:bg-muted/50 transition-colors',
                        selectedEmanating?.id === em.id
                            ? 'bg-primary/5 border-l-2 border-primary'
                            : '',
                    ]"
                    @click="onSelectEmanating(em)"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="space-y-0.5">
                            <div class="font-medium text-sm">
                                {{ em.project?.name ?? "Unknown Project" }}
                            </div>
                            <div class="text-xs text-muted-foreground">
                                {{ em.project?.fund?.office?.name ?? "" }}
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <div class="font-mono text-xs font-medium">
                                {{ em.pr_no ?? "—" }}
                            </div>
                            <div class="text-xs text-muted-foreground">
                                FY {{ em.fiscal_year }}
                            </div>
                        </div>
                    </div>
                </button>
            </div>
        </CardContent>
    </Card>
</template>
