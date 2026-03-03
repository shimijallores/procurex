<script setup>
import { Link } from "@inertiajs/vue3";
import { Button } from "@/components/ui/button";
import { Icon } from "@iconify/vue";

const props = defineProps({
    purchaseRequest: Object,
    approveProcessing: Boolean,
});

defineEmits(["approve", "return", "delete"]);

const openPdf = () => {
    window.open(
        route("purchase-requests.pdf", props.purchaseRequest.id),
        "_blank",
    );
};

const getStatusBadge = (status) => {
    const map = {
        draft: {
            text: "Draft",
            color: "bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300",
            icon: "lucide:file-edit",
        },
        approved: {
            text: "Approved",
            color: "bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300",
            icon: "lucide:check-circle",
        },
        returned: {
            text: "Returned to Office",
            color: "bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300",
            icon: "lucide:undo-2",
        },
        cancelled: {
            text: "Cancelled",
            color: "bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300",
            icon: "lucide:x-circle",
        },
    };
    return map[status] || map.draft;
};
</script>

<template>
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <Link :href="route('purchase-requests.index')">
                    <Button variant="ghost" size="sm">
                        <Icon icon="lucide:arrow-left" class="mr-2 h-4 w-4" />
                        Back
                    </Button>
                </Link>
                <div>
                    <div class="flex items-center gap-3">
                        <h1
                            class="text-2xl font-bold tracking-tight md:text-3xl"
                        >
                            Purchase Request
                            <span
                                v-if="purchaseRequest.pr_no"
                                class="text-primary"
                            >
                                #{{ purchaseRequest.pr_no }}
                            </span>
                        </h1>
                        <span
                            :class="
                                getStatusBadge(purchaseRequest.status).color
                            "
                            class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold"
                        >
                            <Icon
                                :icon="
                                    getStatusBadge(purchaseRequest.status).icon
                                "
                                class="h-3 w-3"
                            />
                            {{ getStatusBadge(purchaseRequest.status).text }}
                        </span>
                    </div>
                    <p class="text-muted-foreground mt-1">
                        {{ purchaseRequest.office?.name || "Unknown Office" }}
                        &mdash;
                        {{ purchaseRequest.fund?.name || "Unknown Fund" }}
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-end gap-2">
                <!-- Edit (draft only) -->
                <Link
                    v-if="purchaseRequest.status === 'draft'"
                    :href="route('purchase-requests.edit', purchaseRequest.id)"
                >
                    <Button variant="outline">
                        <Icon icon="lucide:pencil" class="mr-2 h-4 w-4" />
                        Edit
                    </Button>
                </Link>

                <!-- Approve -->
                <Button
                    v-if="purchaseRequest.status === 'draft'"
                    :disabled="approveProcessing"
                    @click="$emit('approve')"
                >
                    <Icon icon="lucide:send" class="mr-2 h-4 w-4" />
                    Approve
                </Button>

                <!-- Return -->
                <Button
                    v-if="purchaseRequest.status === 'draft'"
                    variant="destructive"
                    @click="$emit('return')"
                >
                    <Icon icon="lucide:undo-2" class="mr-2 h-4 w-4" />
                    Return
                </Button>

                <!-- Print (always visible) -->
                <Button variant="outline" @click="openPdf">
                    <Icon icon="lucide:printer" class="mr-2 h-4 w-4" />
                    Print
                </Button>

                <!-- Delete -->
                <Button variant="destructive" @click="$emit('delete')">
                    <Icon icon="lucide:trash-2" class="mr-2 h-4 w-4" />
                    Delete
                </Button>
            </div>
        </div>
    </div>
</template>
