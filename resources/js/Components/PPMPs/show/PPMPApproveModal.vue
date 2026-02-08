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

defineProps({
    open: Boolean,
    processing: Boolean,
});

const emit = defineEmits(["update:open", "submit"]);
</script>

<template>
    <Dialog :open="open" @update:open="(val) => emit('update:open', val)">
        <DialogContent class="sm:max-w-[500px]">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2">
                    <Icon
                        icon="lucide:check-circle"
                        class="h-5 w-5 text-green-600"
                    />
                    Approve PPMP
                </DialogTitle>
                <DialogDescription>
                    Are you sure you want to approve this PPMP? Once approved,
                    it cannot be edited.
                </DialogDescription>
            </DialogHeader>
            <form @submit.prevent="emit('submit')" class="space-y-4">
                <div
                    class="rounded-md bg-green-50 dark:bg-green-950/30 p-3"
                >
                    <div class="flex gap-2">
                        <Icon
                            icon="lucide:info"
                            class="h-4 w-4 text-green-600 dark:text-green-400 mt-0.5"
                        />
                        <p
                            class="text-sm text-green-800 dark:text-green-300"
                        >
                            This PPMP has passed all budget validations and is
                            ready to be approved.
                        </p>
                    </div>
                </div>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="emit('update:open', false)"
                    >
                        Cancel
                    </Button>
                    <Button type="submit" :disabled="processing">
                        <Icon
                            v-if="processing"
                            icon="lucide:loader-2"
                            class="mr-2 h-4 w-4 animate-spin"
                        />
                        <Icon
                            v-else
                            icon="lucide:check-circle"
                            class="mr-2 h-4 w-4"
                        />
                        Approve PPMP
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
