<script setup>
import { Icon } from "@iconify/vue";
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";

defineProps({
    open: Boolean,
    processing: Boolean,
    errors: Object,
    modelValue: String,
});

const emit = defineEmits(["update:open", "update:modelValue", "submit"]);
</script>

<template>
    <Dialog :open="open" @update:open="(val) => emit('update:open', val)">
        <DialogContent class="sm:max-w-[500px]">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2">
                    <Icon
                        icon="lucide:x-circle"
                        class="h-5 w-5 text-destructive"
                    />
                    Reject PPMP
                </DialogTitle>
                <DialogDescription>
                    Provide a reason for rejecting this PPMP
                </DialogDescription>
            </DialogHeader>
            <form @submit.prevent="emit('submit')" class="space-y-4">
                <div class="space-y-2">
                    <Label for="rejection_reason">Rejection Reason</Label>
                    <textarea
                        id="rejection_reason"
                        :value="modelValue"
                        @input="(e) => emit('update:modelValue', e.target.value)"
                        rows="4"
                        class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                        placeholder="Explain why this PPMP is being rejected..."
                        required
                    ></textarea>
                    <p v-if="errors?.rejection_reason" class="text-sm text-destructive">
                        {{ errors.rejection_reason }}
                    </p>
                </div>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="emit('update:open', false)"
                    >
                        Cancel
                    </Button>
                    <Button
                        type="submit"
                        variant="destructive"
                        :disabled="processing || !modelValue"
                    >
                        <Icon
                            v-if="processing"
                            icon="lucide:loader-2"
                            class="mr-2 h-4 w-4 animate-spin"
                        />
                        <Icon
                            v-else
                            icon="lucide:x-circle"
                            class="mr-2 h-4 w-4"
                        />
                        Reject PPMP
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
