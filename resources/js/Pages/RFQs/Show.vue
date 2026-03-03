<script setup>
import { ref } from "vue";
import Layout from "@/Layout/Layout.vue";
import DeleteModal from "@/components/DeleteModal.vue";
import RFQShowHeader from "@/components/rfqs/show/RFQShowHeader.vue";
import RFQShowDetails from "@/components/rfqs/show/RFQShowDetails.vue";
import RFQShowItems from "@/components/rfqs/show/RFQShowItems.vue";

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
</script>

<template>
    <div class="space-y-6">
        <RFQShowHeader :rfq="rfq" @delete="showDeleteModal = true" />

        <RFQShowDetails :rfq="rfq" />

        <RFQShowItems :rfq="rfq" />

        <DeleteModal
            v-model:open="showDeleteModal"
            title="Delete RFQ"
            :description="`Are you sure you want to delete ${rfq.svp_no}? This action cannot be undone.`"
            :delete-url="route('rfqs.destroy', rfq.id)"
        />
    </div>
</template>
