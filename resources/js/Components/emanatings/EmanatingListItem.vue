<script setup>
import { Link } from "@inertiajs/vue3";
import { Card } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Icon } from "@iconify/vue";

defineProps({
    emanating: Object,
});

const getStatusColor = (emanating) => {
    if (emanating.is_approved) return "bg-green-500/10 text-green-700";
    if (emanating.rejection_reason) return "bg-red-500/10 text-red-700";
    return "bg-yellow-500/10 text-yellow-700";
};

const getStatusText = (emanating) => {
    if (emanating.is_approved) return "Approved";
    if (emanating.rejection_reason) return "Rejected";
    return "Pending";
};

const getMatchStatusColor = (matched) => {
    return matched
        ? "bg-blue-500/10 text-blue-700"
        : "bg-orange-500/10 text-orange-700";
};
</script>

<template>
    <Link :href="route('emanatings.show', emanating.id)">
        <Card class="p-6 hover:shadow-md transition-shadow cursor-pointer">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-2">
                        <h3 class="text-lg font-semibold">
                            {{ emanating.pr_no || `ER-${emanating.id}` }}
                        </h3>
                        <Badge :class="getStatusColor(emanating)">
                            {{ getStatusText(emanating) }}
                        </Badge>
                        <Badge
                            v-if="emanating.items_match_ppmp !== null"
                            :class="
                                getMatchStatusColor(emanating.items_match_ppmp)
                            "
                        >
                            {{
                                emanating.items_match_ppmp
                                    ? "Items Match"
                                    : "Items Mismatch"
                            }}
                        </Badge>
                    </div>

                    <div
                        class="grid grid-cols-2 gap-4 text-sm text-muted-foreground"
                    >
                        <div>
                            <Icon
                                icon="lucide:building"
                                class="inline h-4 w-4 mr-1"
                            />
                            {{ emanating.project?.fund?.office?.name || "N/A" }}
                        </div>
                        <div>
                            <Icon
                                icon="lucide:folder"
                                class="inline h-4 w-4 mr-1"
                            />
                            {{ emanating.project?.name || "N/A" }}
                        </div>
                        <div>
                            <Icon
                                icon="lucide:calendar"
                                class="inline h-4 w-4 mr-1"
                            />
                            FY {{ emanating.fiscal_year }}
                            <span v-if="emanating.quarter">
                                - Q{{ emanating.quarter }}</span
                            >
                        </div>
                        <div>
                            <Icon
                                icon="lucide:hash"
                                class="inline h-4 w-4 mr-1"
                            />
                            {{ emanating.charged_to_code || "N/A" }}
                        </div>
                    </div>

                    <p
                        v-if="emanating.purpose"
                        class="mt-3 text-sm line-clamp-2"
                    >
                        {{ emanating.purpose }}
                    </p>
                </div>

                <Icon
                    icon="lucide:chevron-right"
                    class="h-5 w-5 text-muted-foreground"
                />
            </div>
        </Card>
    </Link>
</template>
