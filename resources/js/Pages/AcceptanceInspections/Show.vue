<script setup>
import { ref } from "vue";
import { useForm } from "@inertiajs/vue3";
import Layout from "@/Layout/Layout.vue";
import DeleteModal from "@/components/DeleteModal.vue";
import AcceptanceInspectionShowHeader from "@/components/acceptance-inspections/show/AcceptanceInspectionShowHeader.vue";
import AcceptanceInspectionShowDetails from "@/components/acceptance-inspections/show/AcceptanceInspectionShowDetails.vue";

defineOptions({
    layout: (h, page) =>
        h(
            Layout,
            {
                breadcrumbs: [
                    {
                        label: "Acceptance & Inspection",
                        href: route("acceptance-inspections.index"),
                    },
                    { label: "Details" },
                ],
            },
            () => page,
        ),
});

const props = defineProps({
    acceptanceInspection: Object,
});

const form = useForm({
    purchase_order_id: props.acceptanceInspection.purchase_order_id,
    air_no: props.acceptanceInspection.air_no || "",
    invoice_no: props.acceptanceInspection.invoice_no || "",
    acceptance_date_received:
        props.acceptanceInspection.acceptance_date_received || "",
    acceptance_status: props.acceptanceInspection.acceptance_status || "",
    inspection_date_inspected:
        props.acceptanceInspection.inspection_date_inspected || "",
    inspection_findings_text:
        props.acceptanceInspection.inspection_findings_text || "",
    inspection_status_ok: props.acceptanceInspection.inspection_status_ok ?? "",
    property_officer_name:
        props.acceptanceInspection.property_officer_name || "",
    property_officer_title:
        props.acceptanceInspection.property_officer_title || "",
    inspection_officer_name:
        props.acceptanceInspection.inspection_officer_name || "",
    inspection_officer_title:
        props.acceptanceInspection.inspection_officer_title || "",
});

const save = () => {
    form.put(
        route("acceptance-inspections.update", props.acceptanceInspection.id),
    );
};

const showDeleteModal = ref(false);
</script>

<template>
    <div class="space-y-6">
        <AcceptanceInspectionShowHeader
            :acceptance-inspection="acceptanceInspection"
            @delete="showDeleteModal = true"
        />

        <AcceptanceInspectionShowDetails
            :acceptance-inspection="acceptanceInspection"
            :form="form"
            @submit="save"
        />

        <DeleteModal
            v-model:open="showDeleteModal"
            title="Delete Acceptance & Inspection"
            :description="`Are you sure you want to delete report for PO ${acceptanceInspection?.purchase_order?.po_no || ''}? This action cannot be undone.`"
            :delete-url="
                route('acceptance-inspections.destroy', acceptanceInspection.id)
            "
        />
    </div>
</template>
