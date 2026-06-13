<script setup>
import { useForm } from "@inertiajs/vue3";
import Layout from "@/Layout/Layout.vue";
import NOACreateHeader from "@/components/noas/create/NOACreateHeader.vue";
import NOACreateForm from "@/components/noas/create/NOACreateForm.vue";

defineOptions({
    layout: (h, page) =>
        h(
            Layout,
            {
                breadcrumbs: [
                    { label: "Notice of Award", href: route("noas.index") },
                    { label: "Create" },
                ],
            },
            () => page,
        ),
});

const props = defineProps({
    eligibleAoqs: Array,
    suppliers: Array,
    defaultNoaDate: String,
});

const form = useForm({
    aoq_id: "",
    noa_no: "",
    noa_date: props.defaultNoaDate || "",
    winner_supplier_name: "",
    recipient_name: "",
    recipient_title: "",
});

const submit = () => {
    form.post(route("noas.store"));
};
</script>

<template>
    <div class="space-y-6">
        <NOACreateHeader />

        <NOACreateForm
            :form="form"
            :eligible-aoqs="eligibleAoqs"
            :suppliers="suppliers"
            :default-noa-date="defaultNoaDate"
            @submit="submit"
        />
    </div>
</template>
