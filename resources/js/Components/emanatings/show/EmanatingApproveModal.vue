<script setup>
import { computed } from "vue";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Alert, AlertDescription } from "@/components/ui/alert";
import { Icon } from "@iconify/vue";

const props = defineProps({
    show: Boolean,
    emanating: Object,
    ppmpApproved: Boolean,
    itemsMatch: Boolean,
    processing: Boolean,
});

const emit = defineEmits(["update:show", "submit"]);

const canApprove = computed(() => {
    return !props.emanating?.is_approved;
});

const hasValidationAlerts = computed(() => {
    return !props.ppmpApproved || !props.itemsMatch;
});

const warningMessage = computed(() => {
    if (!props.ppmpApproved) {
        return "PPMP approval is not yet complete. You may still approve this emanating request.";
    }
    if (!props.itemsMatch) {
        return "Some items are flagged in PPMP/APP checks. You may still approve since these are suggestions.";
    }
    return null;
});

const close = () => {
    emit("update:show", false);
};
</script>

<template>
    <Dialog :open="show" @update:open="close">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Approve Emanating Request</DialogTitle>
                <DialogDescription>
                    Are you sure you want to approve this emanating request?
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-4">
                <!-- Advisory warning when checks are flagged -->
                <Alert
                    v-if="hasValidationAlerts"
                    class="border-l-4 border-l-yellow-500 bg-yellow-50 text-yellow-800"
                >
                    <Icon icon="lucide:alert-triangle" class="h-5 w-5" />
                    <AlertDescription class="ml-2">
                        {{ warningMessage }}
                    </AlertDescription>
                </Alert>

                <!-- Success when all checks pass -->
                <Alert
                    v-else
                    class="border-l-4 border-l-green-500 bg-green-50 text-green-800"
                >
                    <Icon icon="lucide:check-circle" class="h-5 w-5" />
                    <AlertDescription class="ml-2">
                        All validation checks passed. This emanating request is
                        ready for approval.
                    </AlertDescription>
                </Alert>

                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">PR No:</span>
                        <span class="font-medium">{{
                            emanating?.pr_no || "N/A"
                        }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Office:</span>
                        <span class="font-medium">{{
                            emanating?.fund?.office?.name ||
                            emanating?.project?.fund?.office?.name ||
                            "N/A"
                        }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Project:</span>
                        <span class="font-medium">{{
                            emanating?.project?.name
                        }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted-foreground"
                            >PPMP Approved:</span
                        >
                        <span
                            :class="
                                ppmpApproved ? 'text-green-600' : 'text-red-600'
                            "
                        >
                            <Icon
                                :icon="
                                    ppmpApproved ? 'lucide:check' : 'lucide:x'
                                "
                                class="inline h-4 w-4"
                            />
                            {{ ppmpApproved ? "Yes" : "No" }}
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Items Match:</span>
                        <span
                            :class="
                                itemsMatch ? 'text-green-600' : 'text-red-600'
                            "
                        >
                            <Icon
                                :icon="itemsMatch ? 'lucide:check' : 'lucide:x'"
                                class="inline h-4 w-4"
                            />
                            {{ itemsMatch ? "Yes" : "No" }}
                        </span>
                    </div>
                </div>
            </div>

            <DialogFooter>
                <Button variant="outline" @click="close" :disabled="processing">
                    Cancel
                </Button>
                <Button
                    class="bg-green-600 hover:bg-green-700"
                    @click="$emit('submit')"
                    :disabled="!canApprove || processing"
                >
                    <Icon
                        v-if="processing"
                        icon="lucide:loader-2"
                        class="mr-2 h-4 w-4 animate-spin"
                    />
                    <Icon v-else icon="lucide:check" class="mr-2 h-4 w-4" />
                    {{ processing ? "Approving..." : "Approve" }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
