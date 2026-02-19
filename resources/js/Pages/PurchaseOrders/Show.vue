<script setup>
import { ref } from "vue";
import Layout from "@/Layout/Layout.vue";
import DeleteModal from "@/components/DeleteModal.vue";
import PurchaseOrderShowHeader from "@/components/purchase-orders/show/PurchaseOrderShowHeader.vue";
import PurchaseOrderShowDetails from "@/components/purchase-orders/show/PurchaseOrderShowDetails.vue";

defineOptions({
    layout: (h, page) =>
        h(
            Layout,
            {
                breadcrumbs: [
                    {
                        label: "Purchase Order",
                        href: route("purchase-orders.index"),
                    },
                    { label: "Details" },
                ],
            },
            () => page,
        ),
});

defineProps({
    purchaseOrder: Object,
});

const showDeleteModal = ref(false);
</script>

<template>
    <div class="space-y-6">
        <PurchaseOrderShowHeader
            :purchase-order="purchaseOrder"
            @delete="showDeleteModal = true"
        />

        <PurchaseOrderShowDetails :purchase-order="purchaseOrder" />

        <DeleteModal
            v-model:open="showDeleteModal"
            title="Delete Purchase Order"
            :description="`Are you sure you want to delete PO ${purchaseOrder.po_no}? This action cannot be undone.`"
            :delete-url="route('purchase-orders.destroy', purchaseOrder.id)"
        />
    </div>
</template>
