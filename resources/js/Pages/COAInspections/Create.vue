<script setup>
import { useForm } from "@inertiajs/vue3";
import Layout from "@/Layout/Layout.vue";
import COAInspectionCreateHeader from "@/components/coa-inspections/create/COAInspectionCreateHeader.vue";
import COAInspectionCreateForm from "@/components/coa-inspections/create/COAInspectionCreateForm.vue";

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
    svp: {
        header_text: props.defaults?.svp?.header_text || "",
        salutation: props.defaults?.svp?.salutation || "",
    },
    bidding: {
        header_text: props.defaults?.bidding?.header_text || "",
        salutation: props.defaults?.bidding?.salutation || "",
    },
    signatory_name: props.defaults?.signatory_name || "",
    signatory_title: props.defaults?.signatory_title || "",
});

const submit = () => {
    form.post(route("coa-inspections.store"));
};
</script>

<template>
    <div class="space-y-6">
        <COAInspectionCreateHeader />

        <COAInspectionCreateForm
            :form="form"
            :purchase-orders="purchaseOrders"
            @submit="submit"
        />
    </div>
</template>
