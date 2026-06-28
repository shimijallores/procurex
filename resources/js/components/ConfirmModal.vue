<script setup>
import { ref, watch } from "vue";
import { Icon } from "@iconify/vue";
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from "@/components/ui/alert-dialog";

const props = defineProps({
    open: { type: Boolean, default: false },
    title: { type: String, default: "Confirm" },
    description: { type: String, default: "Are you sure?" },
    confirmText: { type: String, default: "Continue" },
    variant: { type: String, default: "default" },
    loading: { type: Boolean, default: false },
});

const emit = defineEmits(["update:open", "confirm", "cancel"]);

const isOpen = ref(props.open);

watch(
    () => props.open,
    (value) => {
        isOpen.value = value;
    },
);

watch(isOpen, (value) => {
    emit("update:open", value);
});
</script>

<template>
    <AlertDialog v-model:open="isOpen">
        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle>{{ title }}</AlertDialogTitle>
                <AlertDialogDescription>
                    {{ description }}
                </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
                <AlertDialogCancel :disabled="loading" @click="$emit('cancel')">
                    Cancel
                </AlertDialogCancel>
                <AlertDialogAction
                    :disabled="loading"
                    :class="variant === 'destructive' ? 'bg-destructive text-destructive-foreground hover:bg-destructive/90' : ''"
                    @click="$emit('confirm')"
                >
                    <Icon
                        v-if="loading"
                        icon="lucide:loader-2"
                        class="mr-2 h-4 w-4 animate-spin"
                    />
                    {{ confirmText }}
                </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>
