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
});

const form = useForm({
    purchase_order_id: props.poTransmittal.purchase_order_id,
    type: props.poTransmittal.type,
    transmittal_no: props.poTransmittal.transmittal_no || "",
    transmittal_date: props.poTransmittal.transmittal_date || "",
    header_text: props.poTransmittal.header_text || "",
    signatory_name: props.poTransmittal.signatory_name || "",
    signatory_title: props.poTransmittal.signatory_title || "",
    coa_circular_no: props.poTransmittal.coa_circular_no || "",
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
            @delete="showDeleteModal = true"
        />

        <POTransmittalShowForm
            :form="form"
            :po-transmittal="poTransmittal"
            @submit="save"
        />

        <DeleteModal
            v-model:open="showDeleteModal"
            title="Delete PO Transmittal"
            :description="`Are you sure you want to delete this ${poTransmittal.type?.toUpperCase()} transmittal? This action cannot be undone.`"
            :delete-url="route('po-transmittals.destroy', poTransmittal.id)"
        />
    </div>
</template>
