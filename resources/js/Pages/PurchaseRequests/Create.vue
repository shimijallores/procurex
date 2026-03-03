<script setup>
import { useForm } from "@inertiajs/vue3";
import Layout from "@/Layout/Layout.vue";
import PRCreateHeader from "@/components/purchaseRequests/create/PRCreateHeader.vue";
import PRCreateForm from "@/components/purchaseRequests/create/PRCreateForm.vue";

defineOptions({
    layout: (h, page) =>
        h(
            Layout,
            {
                breadcrumbs: [
                    {
                        label: "Purchase Requests",
                        href: route("purchase-requests.index"),
                    },
                    { label: "Create" },
                ],
            },
            () => page,
        ),
});

const props = defineProps({
    eligibleEmanatings: Array,
    commonPurposes: Array,
    suggestedPrDate: String,
    suggestedPrNo: String,
    returnReasons: Array,
});

const form = useForm({
    emanating_id: "",
    office_id: "",
    fund_id: "",
    pr_no: "",
    pr_date: "",
    sai_no: "",
    sai_date: "",
    requested_by_name: "",
    requested_by_designation: "",
    purpose: "",
    remarks: "",
    status: "draft",
    items: [],
});

const submit = () => {
    form.post(route("purchase-requests.store"));
};
</script>

<template>
    <div class="space-y-6">
        <PRCreateHeader />

        <PRCreateForm
            :form="form"
            :eligible-emanatings="eligibleEmanatings"
            :common-purposes="commonPurposes"
            :suggested-pr-date="suggestedPrDate"
            :suggested-pr-no="suggestedPrNo"
            @submit="submit"
        />
    </div>
</template>
