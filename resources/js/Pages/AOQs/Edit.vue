<script setup>
import { ref } from "vue";
import { useForm, Link } from "@inertiajs/vue3";
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
import AOQEditForm from "@/components/aoqs/edit/AOQEditForm.vue";

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
                    { label: "Edit" },
                ],
            },
            () => page,
        ),
});

const props = defineProps({
    aoq: Object,
    suppliers: Array,
    batches: Array,
});

const form = useForm({
    batch_id: String(props.aoq.batch_id || ""),
    aoq_date: props.aoq.aoq_date?.slice(0, 10) || "",
    quotations: [],
});

const showConfirm = ref(false);

const confirmSubmit = () => {
    form.put(route("aoqs.update", props.aoq.id), {
        onSuccess: () => {
            showConfirm.value = false;
        },
        onError: () => {
            showConfirm.value = false;
        },
    });
};
</script>

<template>
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">
                Edit Abstract of Quotation
            </h1>
            <p class="mt-1 text-sm text-muted-foreground">
                {{ aoq.rfq?.svp_no || "AOQ #" + aoq.id }}
            </p>
        </div>

        <AOQEditForm
            :form="form"
            :aoq="aoq"
            :suppliers="suppliers"
            :batches="batches"
            @submit="showConfirm = true"
        />

        <AlertDialog v-model:open="showConfirm">
            <AlertDialogContent
                @pointer-down-outside="showConfirm = false"
                @escape-key-down="showConfirm = false"
            >
                <AlertDialogHeader>
                    <div class="flex items-start justify-between">
                        <AlertDialogTitle>
                            Confirm Update AOQ
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
                        Are you sure you want to update this Abstract of
                        Quotation? This will overwrite all existing quotation
                        data and recalculate the winning bidder.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <Link :href="route('aoqs.show', aoq.id)">
                        <Button type="button" variant="outline">
                            Cancel
                        </Button>
                    </Link>
                    <Button
                        :disabled="form.processing"
                        @click="confirmSubmit"
                    >
                        <Icon
                            v-if="form.processing"
                            icon="lucide:loader-2"
                            class="mr-2 h-4 w-4 animate-spin"
                        />
                        Confirm &amp; Update
                    </Button>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </div>
</template>
