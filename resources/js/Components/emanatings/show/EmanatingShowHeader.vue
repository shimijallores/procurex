<script setup>
import { Link } from "@inertiajs/vue3";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Icon } from "@iconify/vue";

const props = defineProps({
    emanating: Object,
    ppmpApproved: Boolean,
    itemsMatch: Boolean,
    approveProcessing: Boolean,
});

defineEmits(["approve", "reject", "delete"]);

const canApprove = (emanating, ppmpApproved, itemsMatch) => {
    return !emanating.is_approved && ppmpApproved && itemsMatch;
};

const canReject = (emanating) => {
    return !emanating.is_approved;
};

const canReturn = (emanating) => {
    return emanating.is_approved;
};

const downloadCSV = () => {
    window.location.href = route("emanatings.download-csv", props.emanating.id);
};
</script>

<template>
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <Link :href="route('emanatings.index')">
                <Button variant="ghost" size="sm">
                    <Icon icon="lucide:arrow-left" class="mr-2 h-4 w-4" />
                    Back
                </Button>
            </Link>
            <div class="space-y-1">
                <div class="flex items-center gap-2">
                    <h1 class="text-2xl font-bold tracking-tight md:text-3xl">
                        {{
                            emanating.pr_no ||
                            `Emanating Request #${emanating.id}`
                        }}
                    </h1>
                    <span
                        v-if="emanating.is_approved"
                        class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300"
                    >
                        <Icon icon="lucide:check-circle" class="mr-1 h-3 w-3" />
                        Approved
                    </span>
                    <span
                        v-else-if="emanating.rejection_reason"
                        class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300"
                    >
                        <Icon icon="lucide:x-circle" class="mr-1 h-3 w-3" />
                        Rejected
                    </span>
                    <span
                        v-else
                        class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300"
                    >
                        <Icon icon="lucide:clock" class="mr-1 h-3 w-3" />
                        Pending
                    </span>
                </div>
                <p class="text-muted-foreground">
                    {{
                        emanating.project?.fund?.office?.name ||
                        "Unknown Office"
                    }}
                    -
                    {{ emanating.project?.name || "Unknown Project" }}
                </p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <Button
                v-if="canApprove(emanating, ppmpApproved, itemsMatch)"
                @click="$emit('approve')"
                :disabled="approveProcessing"
            >
                <Icon icon="lucide:check-circle" class="mr-2 h-4 w-4" />
                Approve
            </Button>
            <Button
                v-if="canReject(emanating)"
                variant="outline"
                @click="$emit('reject')"
            >
                <Icon icon="lucide:x-circle" class="mr-2 h-4 w-4" />
                Reject
            </Button>
            <Button
                v-if="canReturn(emanating)"
                variant="outline"
                @click="$emit('reject')"
            >
                <Icon icon="lucide:undo-2" class="mr-2 h-4 w-4" />
                Return
            </Button>
            <Button variant="outline" @click="downloadCSV">
                <Icon icon="lucide:download" class="mr-2 h-4 w-4" />
                Download Emanating
            </Button>
            <Link
                v-if="!emanating.is_approved"
                :href="route('emanatings.edit', emanating.id)"
            >
                <Button variant="outline">
                    <Icon icon="lucide:pencil" class="mr-2 h-4 w-4" />
                    Edit
                </Button>
            </Link>

            <Button
                v-if="!emanating.is_approved"
                @click="$emit('delete')"
                variant="destructive"
            >
                <Icon icon="lucide:trash-2" class="mr-2 h-4 w-4" />
                Delete
            </Button>
        </div>
    </div>
</template>
