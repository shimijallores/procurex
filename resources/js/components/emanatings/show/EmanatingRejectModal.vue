<script setup>
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Textarea } from "@/components/ui/textarea";
import { Label } from "@/components/ui/label";
import { Icon } from "@iconify/vue";

const props = defineProps({
    show: Boolean,
    reason: String,
    processing: Boolean,
});

const emit = defineEmits(["update:show", "update:reason", "submit"]);

const close = () => {
    emit("update:show", false);
};
</script>

<template>
    <Dialog :open="show" @update:open="close">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Reject Emanating Request</DialogTitle>
                <DialogDescription>
                    Please provide a reason for rejecting this emanating
                    request.
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-4">
                <div>
                    <Label for="rejection-reason">Rejection Reason</Label>
                    <Textarea
                        id="rejection-reason"
                        :value="reason"
                        @input="$emit('update:reason', $event.target.value)"
                        placeholder="Enter the reason for rejection..."
                        rows="4"
                        class="mt-2"
                    />
                </div>
            </div>

            <DialogFooter>
                <Button variant="outline" @click="close" :disabled="processing">
                    Cancel
                </Button>
                <Button
                    variant="destructive"
                    @click="$emit('submit')"
                    :disabled="!reason || processing"
                >
                    <Icon
                        v-if="processing"
                        icon="lucide:loader-2"
                        class="mr-2 h-4 w-4 animate-spin"
                    />
                    <Icon v-else icon="lucide:x" class="mr-2 h-4 w-4" />
                    {{ processing ? "Rejecting..." : "Reject" }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
