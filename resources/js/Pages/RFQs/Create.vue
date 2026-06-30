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
    defaultRfqDate: String,
    defaultSubmissionDeadline: String,
    defaultSvpNo: String,
});

const form = useForm({
    pr_id: "",
    pr_no: "",
    svp_no: props.defaultSvpNo || "",
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
            @submit="submit"
        />
    </div>
</template>
