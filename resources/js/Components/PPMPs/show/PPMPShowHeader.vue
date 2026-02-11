<script setup>
import { Link } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import { Button } from "@/components/ui/button";

defineProps({
    ppmp: Object,
    budgetValidationPassed: Boolean,
    onApprove: Function,
    onReject: Function,
    onEdit: Function,
    onDelete: Function,
    approveProcessing: Boolean,
});
</script>

<template>
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <Link :href="route('ppmps.index')">
                <Button variant="ghost" size="sm">
                    <Icon icon="lucide:arrow-left" class="mr-2 h-4 w-4" />
                    Back
                </Button>
            </Link>
            <div class="space-y-1">
                <div class="flex items-center gap-2">
                    <h1 class="text-2xl font-bold tracking-tight md:text-3xl">
                        {{ ppmp.office?.name || "Unknown Office" }} -
                        {{ ppmp.project?.name || "Unknown Project" }}
                    </h1>
                    <span
                        v-if="ppmp.is_addendum"
                        class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300"
                    >
                        Addendum
                    </span>
                    <span
                        v-if="ppmp.is_approved"
                        class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300"
                    >
                        <Icon icon="lucide:check-circle" class="mr-1 h-3 w-3" />
                        Approved
                    </span>
                </div>
                <p class="text-muted-foreground">
                    Project Procurement Management Plan - FY
                    {{ ppmp.fiscal_year }}
                </p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <Button
                v-if="!ppmp.is_approved && budgetValidationPassed"
                @click="onApprove"
                :disabled="approveProcessing"
            >
                <Icon icon="lucide:check-circle" class="mr-2 h-4 w-4" />
                Approve PPMP
            </Button>
            <Button
                v-if="!ppmp.is_approved"
                variant="outline"
                @click="onReject"
            >
                <Icon icon="lucide:x-circle" class="mr-2 h-4 w-4" />
                Reject PPMP
            </Button>
            <a
                v-if="ppmp.csv_path"
                :href="route('ppmps.download-csv', ppmp.id)"
                download
            >
                <Button variant="outline">
                    <Icon icon="lucide:download" class="mr-2 h-4 w-4" />
                    Download PPMP
                </Button>
            </a>

            <Link v-if="!ppmp.is_approved" :href="route('ppmps.edit', ppmp.id)">
                <Button variant="outline">
                    <Icon icon="lucide:pencil" class="mr-2 h-4 w-4" />
                    Edit
                </Button>
            </Link>
            <Button
                v-if="!ppmp.is_approved"
                variant="destructive"
                @click="onDelete"
            >
                <Icon icon="lucide:trash-2" class="mr-2 h-4 w-4" />
                Delete
            </Button>
        </div>
    </div>
</template>
