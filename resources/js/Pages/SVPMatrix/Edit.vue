<script setup>
import { useForm } from "@inertiajs/vue3";
import Layout from "@/Layout/Layout.vue";
import SVPMatrixEditHeader from "@/components/svp-matrix/edit/SVPMatrixEditHeader.vue";
import SVPMatrixEditForm from "@/components/svp-matrix/edit/SVPMatrixEditForm.vue";

defineOptions({
    layout: (h, page) =>
        h(
            Layout,
            {
                breadcrumbs: [
                    {
                        label: "SVP Matrix",
                        href: route("svp-matrix.index"),
                    },
                    { label: "Edit" },
                ],
            },
            () => page,
        ),
});

const props = defineProps({
    matrixRow: Object,
});

const form = useForm({
    office_text: props.matrixRow.office || "",
    po_no_text: props.matrixRow.po_no || "",
    mode_of_procurement_text: props.matrixRow.mode_of_procurement || "",
    pr_no_text: props.matrixRow.pr_no || "",
    abc_amount: props.matrixRow.abc ?? "",
    supplier_text: props.matrixRow.supplier || "",
    particulars_text: props.matrixRow.particulars || "",
    amount_value: props.matrixRow.amount ?? "",
    rfq_value: props.matrixRow.rfq || "",
    abstract_value: props.matrixRow.abstract || "",
    resolution_value: props.matrixRow.resolution || "",
    noa_po_value: props.matrixRow.noa_po || "",
    transmittal_form_value: props.matrixRow.transmittal_form || "",
    admin_value: props.matrixRow.admin || "",
    frontdesk_value: props.matrixRow.frontdesk || "",
    remarks_value: props.matrixRow.remarks || "",
});

const submit = () => {
    form.put(route("svp-matrix.update", props.matrixRow.id));
};
</script>

<template>
    <div class="space-y-6">
        <SVPMatrixEditHeader :matrix-row="matrixRow" />
        <SVPMatrixEditForm
            :matrix-row="matrixRow"
            :form="form"
            @submit="submit"
        />
    </div>
</template>
