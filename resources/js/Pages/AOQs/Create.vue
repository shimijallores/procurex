<script setup>
import { ref } from "vue";
import { useForm } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import Layout from "@/Layout/Layout.vue";
import {
    AlertDialog,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogCancel,
} from "@/components/ui/alert-dialog";
import { Button } from "@/components/ui/button";
import AOQCreateHeader from "@/components/aoqs/create/AOQCreateHeader.vue";
import AOQCreateForm from "@/components/aoqs/create/AOQCreateForm.vue";

defineOptions({
    layout: (h, page) =>
        h(
            Layout,
            {
                breadcrumbs: [
                    {
                        label: "Abstract of Quotation",
                        href: route("aoqs.index"),
                    },
                    { label: "Create" },
                ],
            },
            () => page,
        ),
});

const props = defineProps({
    suppliers: Array,
    batches: Array,
    defaultAoqDate: String,
    activeEarmarkBatch: Object,
    displayBatch: Object,
});

const form = useForm({
    rfq_id: "",
    batch_id: "",
    aoq_date: props.defaultAoqDate || "",
    quotations: [],
});

const showConfirm = ref(false);
const pendingNewBatch = ref(false);

const onBatchAssigned = ({ isNew }) => {
    pendingNewBatch.value = isNew;
};

const confirmSubmit = () => {
    form.post(route("aoqs.store"), {
        onSuccess: () => {
            showConfirm.value = false;
            if (pendingNewBatch.value) {
                pendingNewBatch.value = false;
                window.open(
                    route("batches.index"),
                    "_blank",
                    "noopener,noreferrer",
                );
            }
        },
        onError: () => {
            showConfirm.value = false;
        },
    });
};
</script>

<template>
    <div class="space-y-6">
        <AOQCreateHeader />

        <AOQCreateForm
            :form="form"
            :suppliers="suppliers"
            :batches="batches"
            :active-earmark-batch="activeEarmarkBatch"
            :display-batch="displayBatch"
            @submit="showConfirm = true"
            @batch-assigned="onBatchAssigned"
        />

        <AlertDialog v-model:open="showConfirm">
            <AlertDialogContent
                @pointer-down-outside="showConfirm = false"
                @escape-key-down="showConfirm = false"
            >
                <AlertDialogHeader>
                    <div class="flex items-start justify-between">
                        <AlertDialogTitle>
                            Confirm Create AOQ
                        </AlertDialogTitle>
                        <button
                            type="button"
                            class="rounded-md p-1 text-muted-foreground hover:text-foreground"
                            @click="showConfirm = false"
                        >
                            <Icon icon="lucide:x" class="h-4 w-4" />
                        </button>
                    </div>
                    <AlertDialogDescription>
                        Are you sure you want to create this Abstract of
                        Quotation? This will lock the supplier quotations and
                        determine the winning bidder.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel :disabled="form.processing"
                        >Cancel</AlertDialogCancel
                    >
                    <Button
                        :disabled="form.processing"
                        @click="confirmSubmit"
                    >
                        <Icon
                            v-if="form.processing"
                            icon="lucide:loader-2"
                            class="mr-2 h-4 w-4 animate-spin"
                        />
                        Confirm &amp; Create
                    </Button>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </div>
</template>
