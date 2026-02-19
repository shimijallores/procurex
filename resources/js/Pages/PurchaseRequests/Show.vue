<script setup>
import { ref } from "vue";
import { useForm } from "@inertiajs/vue3";
import Layout from "@/Layout/Layout.vue";
import DeleteModal from "@/components/DeleteModal.vue";
import PRShowHeader from "@/components/purchaseRequests/show/PRShowHeader.vue";
import PRShowDetails from "@/components/purchaseRequests/show/PRShowDetails.vue";
import PRShowItems from "@/components/purchaseRequests/show/PRShowItems.vue";
import PRReturnModal from "@/components/purchaseRequests/show/PRReturnModal.vue";

defineOptions({
    layout: (h, page) =>
        h(
            Layout,
            {
                breadcrumbs: [
                    {
                        label: "Purchase Requests",
                        href: route("purchase-requests.index"),
                    },
                    { label: "Details" },
                ],
            },
            () => page,
        ),
});

const props = defineProps({
    purchaseRequest: Object,
    returnReasons: Array,
});

const showDeleteModal = ref(false);
const showReturnModal = ref(false);

const approveForm = useForm({});

const approve = () => {
    approveForm.post(
        route("purchase-requests.approve", props.purchaseRequest.id),
    );
};
</script>

<template>
    <div class="space-y-6">
        <PRShowHeader
            :purchase-request="purchaseRequest"
            :approve-processing="approveForm.processing"
            @approve="approve"
            @return="showReturnModal = true"
            @delete="showDeleteModal = true"
        />

        <PRShowDetails :purchase-request="purchaseRequest" />

        <PRShowItems :purchase-request="purchaseRequest" />

        <!-- Return Modal -->
        <PRReturnModal
            v-model:open="showReturnModal"
            :purchase-request-id="purchaseRequest.id"
            :return-reasons="returnReasons"
        />

        <!-- Delete Modal -->
        <DeleteModal
            v-model:open="showDeleteModal"
            title="Delete Purchase Request"
            :description="`Are you sure you want to delete PR ${purchaseRequest.pr_no || '#' + purchaseRequest.id}? This cannot be undone.`"
            :delete-url="route('purchase-requests.destroy', purchaseRequest.id)"
        />
    </div>
</template>
