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
            @submit="submit"
        />
    </div>
</template>
