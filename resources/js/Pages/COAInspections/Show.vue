<script setup>
import { ref } from "vue";
import { useForm } from "@inertiajs/vue3";
import Layout from "@/Layout/Layout.vue";
import DeleteModal from "@/components/DeleteModal.vue";
import COAInspectionShowHeader from "@/components/coa-inspections/show/COAInspectionShowHeader.vue";
import COAInspectionShowForm from "@/components/coa-inspections/show/COAInspectionShowForm.vue";

defineOptions({
    layout: (h, page) =>
        h(
            Layout,
            {
                breadcrumbs: [
                    {
                        label: "COA Inspection",
                        href: route("coa-inspections.index"),
                    },
                    { label: "Details" },
                ],
            },
            () => page,
        ),
});

const props = defineProps({
    coaInspection: Object,
});

const form = useForm({
    purchase_order_id: props.coaInspection.purchase_order_id,
    svp: {
        header_text: props.coaInspection.svp_header_text || "",
        salutation: props.coaInspection.svp_salutation || "",
    },
    bidding: {
        header_text: props.coaInspection.bidding_header_text || "",
        salutation: props.coaInspection.bidding_salutation || "",
    },
    signatory_name: props.coaInspection.signatory_name || "",
    signatory_title: props.coaInspection.signatory_title || "",
});

const save = () => {
    form.put(route("coa-inspections.update", props.coaInspection.id));
};

const showDeleteModal = ref(false);
</script>

<template>
    <div class="space-y-6">
        <COAInspectionShowHeader
            :coa-inspection="coaInspection"
            @delete="showDeleteModal = true"
        />

        <COAInspectionShowForm
            :coa-inspection="coaInspection"
            :form="form"
            @submit="save"
        />

        <DeleteModal
            v-model:open="showDeleteModal"
            title="Delete COA Inspection"
            :description="`Are you sure you want to delete COA inspection set for PO ${coaInspection?.purchase_order?.po_no || ''}? This action cannot be undone.`"
            :delete-url="route('coa-inspections.destroy', coaInspection.id)"
        />
    </div>
</template>
