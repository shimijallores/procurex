<script setup>
import { Icon } from "@iconify/vue";
import { ref, computed } from "vue";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog";

defineProps({
    open: Boolean,
    returnReason: String,
    returningCanvas: Boolean,
    returnReasons: Array,
});

const emit = defineEmits([
    "update:open",
    "update:return-reason",
    "submit-return",
]);

const suggestedReasons = [
    "Incomplete specification indicated",
    "Requested item has been phased out",
];

const inputRef = ref(null);
const showSuggestions = ref(false);

const filteredSuggestions = computed(() => {
    const searchTerm = inputRef.value?.value?.toLowerCase() || "";
    if (!searchTerm) return suggestedReasons;
    return suggestedReasons.filter((reason) =>
        reason.toLowerCase().includes(searchTerm),
    );
});

const selectSuggestion = (reason) => {
    emit("update:return-reason", reason);
    showSuggestions.value = false;
};
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="max-w-md">
            <DialogHeader>
                <DialogTitle>Return Canvas</DialogTitle>
                <DialogDescription
                    >Enter or select a reason for returning this canvas to the
                    requesting office.</DialogDescription
                >
            </DialogHeader>
            <div class="space-y-3 py-2">
                <Label
                    >Return Reason
                    <span class="text-destructive">*</span></Label
                >
                <div class="relative">
                    <input
                        ref="inputRef"
                        type="text"
                        :value="returnReason"
                        placeholder="Enter reason or select from suggestions..."
                        class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                        @input="
                            emit('update:return-reason', $event.target.value)
                        "
                        @focus="showSuggestions = true"
                    />
                    <div
                        v-if="showSuggestions && filteredSuggestions.length > 0"
                        class="absolute top-full left-0 right-0 mt-1 bg-popover border border-input rounded-md shadow-md z-50"
                    >
                        <div
                            v-for="reason in filteredSuggestions"
                            :key="reason"
                            class="px-3 py-2 hover:bg-accent cursor-pointer text-sm"
                            @click="selectSuggestion(reason)"
                        >
                            {{ reason }}
                        </div>
                    </div>
                </div>
                <p class="text-xs text-muted-foreground">
                    Suggested reasons: {{ suggestedReasons.join(", ") }}
                </p>
            </div>
            <DialogFooter>
                <Button variant="outline" @click="emit('update:open', false)"
                    >Cancel</Button
                >
                <Button
                    variant="destructive"
                    :disabled="!returnReason || returningCanvas"
                    @click="emit('submit-return')"
                >
                    <Icon icon="lucide:undo-2" class="mr-2 h-4 w-4" />
                    Confirm Return
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
