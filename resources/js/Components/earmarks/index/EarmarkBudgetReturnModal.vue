<script setup>
import { ref } from "vue";
import { useForm } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import { Button } from "@/components/ui/button";
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog";
import { Label } from "@/components/ui/label";

const props = defineProps({
    open: Boolean,
    purchaseRequest: Object,
    returnReasons: Array,
});

const emit = defineEmits(["update:open"]);

const form = useForm({ reason: "" });
const customReason = ref("");
const isCustom = ref(false);

const selectReason = (reason) => {
    if (reason === "__custom__") {
        isCustom.value = true;
        form.reason = "";
    } else {
        isCustom.value = false;
        form.reason = reason;
    }
};

const submitReturn = () => {
    const finalReason = isCustom.value ? customReason.value : form.reason;
    if (!finalReason) return;

    form.reason = finalReason;
    form.post(
        route("purchase-requests.budget-return", props.purchaseRequest?.id),
        {
            onSuccess: () => {
                emit("update:open", false);
                form.reset();
                customReason.value = "";
                isCustom.value = false;
            },
        },
    );
};
</script>

<template>
    <Dialog :open="open" @update:open="$emit('update:open', $event)">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2">
                    <Icon
                        icon="lucide:undo-2"
                        class="h-5 w-5 text-destructive"
                    />
                    Return to Requesting Office
                </DialogTitle>
                <DialogDescription>
                    Return PR
                    <strong>
                        {{
                            purchaseRequest?.pr_no
                                ? `#${purchaseRequest.pr_no}`
                                : `#${purchaseRequest?.id}`
                        }}
                    </strong>
                    to the requesting office for correction. Select or enter a
                    reason.
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-3">
                <Label>Select or enter a reason</Label>
                <div class="grid grid-cols-1 gap-2">
                    <button
                        v-for="reason in returnReasons"
                        :key="reason"
                        type="button"
                        :class="[
                            'rounded-md border px-3 py-2 text-left text-sm transition-colors',
                            form.reason === reason && !isCustom
                                ? 'border-primary bg-primary/10 text-primary font-medium'
                                : 'border-border hover:bg-muted',
                        ]"
                        @click="selectReason(reason)"
                    >
                        {{ reason }}
                    </button>
                    <button
                        type="button"
                        :class="[
                            'rounded-md border px-3 py-2 text-left text-sm transition-colors',
                            isCustom
                                ? 'border-primary bg-primary/10 text-primary font-medium'
                                : 'border-border hover:bg-muted',
                        ]"
                        @click="selectReason('__custom__')"
                    >
                        Other (enter manually)…
                    </button>
                </div>

                <textarea
                    v-if="isCustom"
                    v-model="customReason"
                    rows="3"
                    placeholder="Enter the reason…"
                    class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                />
            </div>

            <DialogFooter>
                <Button variant="outline" @click="$emit('update:open', false)">
                    Cancel
                </Button>
                <Button
                    variant="destructive"
                    :disabled="
                        form.processing || (!form.reason && !customReason)
                    "
                    @click="submitReturn"
                >
                    <Icon icon="lucide:send" class="mr-2 h-4 w-4" />
                    Return to Office
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
