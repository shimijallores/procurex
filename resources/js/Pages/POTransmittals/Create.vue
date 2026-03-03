<script setup>
import { useForm } from "@inertiajs/vue3";
import Layout from "@/Layout/Layout.vue";
import POTransmittalCreateHeader from "@/components/po-transmittals/create/POTransmittalCreateHeader.vue";
import POTransmittalCreateForm from "@/components/po-transmittals/create/POTransmittalCreateForm.vue";

defineOptions({
    layout: (h, page) =>
        h(
            Layout,
            {
                breadcrumbs: [
                    {
                        label: "PO Transmittal",
                        href: route("po-transmittals.index"),
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
    coa: {
        transmittal_no: "",
        transmittal_date: props.defaults?.transmittal_date || "",
        header_text: props.defaults?.coa?.header_text || "",
        signatory_name: props.defaults?.coa?.signatory_name || "",
        signatory_title: props.defaults?.coa?.signatory_title || "",
        coa_circular_no: props.defaults?.coa?.coa_circular_no || "",
    },
    opg: {
        transmittal_no: "",
        transmittal_date: props.defaults?.transmittal_date || "",
        header_text: props.defaults?.opg?.header_text || "",
        signatory_name: props.defaults?.opg?.signatory_name || "",
        signatory_title: props.defaults?.opg?.signatory_title || "",
        coa_circular_no: props.defaults?.opg?.coa_circular_no || "",
    },
});

const submit = () => {
    form.post(route("po-transmittals.store"));
};
</script>

<template>
    <div class="space-y-6">
        <POTransmittalCreateHeader />

        <POTransmittalCreateForm
            :form="form"
            :purchase-orders="purchaseOrders"
            @submit="submit"
        />
    </div>
</template>
