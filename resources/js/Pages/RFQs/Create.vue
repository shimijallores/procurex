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
    defaultRfqDate: String,
    defaultSubmissionDeadline: String,
});

const form = useForm({
    pr_id: "",
    pr_no: "",
    rfq_date: props.defaultRfqDate || "",
    submission_deadline: props.defaultSubmissionDeadline || "",
    project_name: "",
    abc_amount: "",
    remarks: "",
    items: [],
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
            @submit="submit"
        />
    </div>
</template>
