<script setup>
import { useForm } from "@inertiajs/vue3";
import Layout from "@/Layout/Layout.vue";
import AcceptanceInspectionCreateHeader from "@/components/acceptance-inspections/create/AcceptanceInspectionCreateHeader.vue";
import AcceptanceInspectionCreateForm from "@/components/acceptance-inspections/create/AcceptanceInspectionCreateForm.vue";

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
                    { label: "Create" },
                ],
            },
            () => page,
        ),
});

const props = defineProps({
    purchaseOrders: Array,
    defaults: Object,
});

const form = useForm({
    purchase_order_id: "",
    air_no: "",
    invoice_no: "",
    acceptance_date_received: props.defaults?.acceptance_date_received || "",
    acceptance_status: "",
    inspection_date_inspected: props.defaults?.inspection_date_inspected || "",
    inspection_findings_text: props.defaults?.inspection_findings_text || "",
    inspection_status_ok: props.defaults?.inspection_status_ok ?? "",
    property_officer_name: "",
    property_officer_title: props.defaults?.property_officer_title || "",
    inspection_officer_name: "",
    inspection_officer_title: props.defaults?.inspection_officer_title || "",
});

const submit = () => {
    form.post(route("acceptance-inspections.store"));
};
</script>

<template>
    <div class="space-y-6">
        <AcceptanceInspectionCreateHeader />

        <AcceptanceInspectionCreateForm
            :form="form"
            :purchase-orders="purchaseOrders"
            @submit="submit"
        />
    </div>
</template>
