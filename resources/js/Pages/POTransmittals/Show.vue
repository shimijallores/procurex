<script setup>
import { ref } from "vue";
import { useForm } from "@inertiajs/vue3";
import Layout from "@/Layout/Layout.vue";
import DeleteModal from "@/components/DeleteModal.vue";
import POTransmittalShowHeader from "@/components/po-transmittals/show/POTransmittalShowHeader.vue";
import POTransmittalShowForm from "@/components/po-transmittals/show/POTransmittalShowForm.vue";

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
                    { label: "Details" },
                ],
            },
            () => page,
        ),
});

const props = defineProps({
    poTransmittal: Object,
    coaTransmittal: Object,
    opgTransmittal: Object,
    relatedTransmittals: Array,
});

const form = useForm({
    purchase_order_id: props.poTransmittal.purchase_order_id,
    coa: {
        transmittal_no: props.coaTransmittal?.transmittal_no || "",
        transmittal_date: props.coaTransmittal?.transmittal_date || "",
        header_text: props.coaTransmittal?.header_text || "",
        signatory_name: props.coaTransmittal?.signatory_name || "",
        signatory_title: props.coaTransmittal?.signatory_title || "",
        coa_circular_no: props.coaTransmittal?.coa_circular_no || "",
    },
    opg: {
        transmittal_no: props.opgTransmittal?.transmittal_no || "",
        transmittal_date: props.opgTransmittal?.transmittal_date || "",
        header_text: props.opgTransmittal?.header_text || "",
        signatory_name: props.opgTransmittal?.signatory_name || "",
        signatory_title: props.opgTransmittal?.signatory_title || "",
        coa_circular_no: props.opgTransmittal?.coa_circular_no || "",
    },
});

const save = () => {
    form.put(route("po-transmittals.update", props.poTransmittal.id));
};

const showDeleteModal = ref(false);
</script>

<template>
    <div class="space-y-6">
        <POTransmittalShowHeader
            :po-transmittal="poTransmittal"
            :related-transmittals="relatedTransmittals"
            @delete="showDeleteModal = true"
        />

        <POTransmittalShowForm
            :form="form"
            :po-transmittal="poTransmittal"
            :coa-transmittal="coaTransmittal"
            :opg-transmittal="opgTransmittal"
            @submit="save"
        />

        <DeleteModal
            v-model:open="showDeleteModal"
            title="Delete PO Transmittal"
            :description="'Are you sure you want to delete this PO transmittal set (COA + OPG)? This action cannot be undone.'"
            :delete-url="route('po-transmittals.destroy', poTransmittal.id)"
        />
    </div>
</template>
