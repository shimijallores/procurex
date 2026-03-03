<script setup>
import { ref, watch } from "vue";
import { Link } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import {
    AlertDialog,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogCancel,
} from "@/components/ui/alert-dialog";
import { buttonVariants } from "@/components/ui/button";

const props = defineProps({
    open: { type: Boolean, default: false },
    title: { type: String, default: "Delete Confirmation" },
    description: {
        type: String,
        default:
            "Are you sure you want to delete this item? This action cannot be undone.",
    },
    deleteUrl: { type: String, default: "" },
    httpMethod: { type: String, default: "delete" },
});

const emit = defineEmits(["update:open", "cancel"]);

const isOpen = ref(props.open);
const isDeleting = ref(false);

watch(
    () => props.open,
    (value) => {
        isOpen.value = value;
    },
);

watch(isOpen, (value) => {
    emit("update:open", value);
});

const handleStart = () => {
    isDeleting.value = true;
};

const handleFinish = () => {
    isDeleting.value = false;
    isOpen.value = false;
};
</script>

<template>
    <AlertDialog v-model:open="isOpen">
        <AlertDialogContent @pointer-down-outside="isOpen = false">
            <AlertDialogHeader>
                <AlertDialogTitle>{{ title }}</AlertDialogTitle>
                <AlertDialogDescription>
                    {{ description }}
                </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
                <AlertDialogCancel :disabled="isDeleting"
                    >Cancel</AlertDialogCancel
                >
                <Link
                    :href="deleteUrl"
                    :method="httpMethod"
                    as="button"
                    preserve-scroll
                    @start="handleStart"
                    @finish="handleFinish"
                    :class="buttonVariants({ variant: 'destructive' })"
                    :disabled="isDeleting || !deleteUrl"
                >
                    <Icon
                        v-if="isDeleting"
                        icon="lucide:loader-2"
                        class="h-4 w-4 animate-spin"
                    />
                    Delete
                </Link>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>
