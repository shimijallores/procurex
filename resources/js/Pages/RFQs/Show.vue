<script setup>
import { ref } from "vue";
import Layout from "@/Layout/Layout.vue";
import DeleteModal from "@/components/DeleteModal.vue";
import RFQShowHeader from "@/components/rfqs/show/RFQShowHeader.vue";
import RFQShowDetails from "@/components/rfqs/show/RFQShowDetails.vue";
import RFQShowItems from "@/components/rfqs/show/RFQShowItems.vue";
import RFQSuppliersTable from "@/components/rfqs/show/RFQSuppliersTable.vue";
import RFQSupplierSubmissionModal from "@/components/rfqs/show/RFQSupplierSubmissionModal.vue";

defineOptions({
    layout: (h, page) =>
        h(
            Layout,
            {
                breadcrumbs: [
                    {
                        label: "Request for Quotation",
                        href: route("rfqs.index"),
                    },
                    { label: "Details" },
                ],
            },
            () => page,
        ),
});

const props = defineProps({
    rfq: Object,
});

const showDeleteModal = ref(false);
const selectedSupplier = ref(null);
const showSubmissionModal = ref(false);

const openSubmissionModal = (supplierEntry) => {
    selectedSupplier.value = supplierEntry;
    showSubmissionModal.value = true;
};
</script>

<template>
    <div class="space-y-6">
        <RFQShowHeader :rfq="rfq" @delete="showDeleteModal = true" />

        <RFQShowDetails :rfq="rfq" />

        <RFQShowItems :rfq="rfq" />

        <RFQSuppliersTable :rfq="rfq" @submit-supplier="openSubmissionModal" />

        <RFQSupplierSubmissionModal
            v-model:open="showSubmissionModal"
            :rfq="rfq"
            :supplier-entry="selectedSupplier"
        />

        <DeleteModal
            v-model:open="showDeleteModal"
            title="Delete RFQ"
            :description="`Are you sure you want to delete ${rfq.svp_no}? This action cannot be undone.`"
            :delete-url="route('rfqs.destroy', rfq.id)"
        />
    </div>
</template>
