<script setup>
import { useForm } from "@inertiajs/vue3";
import Layout from "@/Layout/Layout.vue";
import RFQCreateHeader from "@/components/rfqs/create/RFQCreateHeader.vue";
import RFQCreateForm from "@/components/rfqs/create/RFQCreateForm.vue";

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
                    { label: "Create" },
                ],
            },
            () => page,
        ),
});

const props = defineProps({
    eligiblePurchaseRequests: Array,
    suppliers: Array,
    defaultRfqDate: String,
    defaultSubmissionDeadline: String,
});

const form = useForm({
    pr_id: "",
    rfq_date: props.defaultRfqDate || "",
    submission_deadline: props.defaultSubmissionDeadline || "",
    remarks: "",
    supplier_ids: [],
});

const submit = () => {
    form.post(route("rfqs.store"));
};
</script>

<template>
    <div class="space-y-6">
        <RFQCreateHeader />

        <RFQCreateForm
            :form="form"
            :eligible-purchase-requests="eligiblePurchaseRequests"
            :suppliers="suppliers"
            @submit="submit"
        />
    </div>
</template>
