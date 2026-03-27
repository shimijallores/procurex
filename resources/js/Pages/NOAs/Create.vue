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
    eligibleResolutions: Array,
    suppliers: Array,
    defaultResolutionDate: String,
    defaultNoaDate: String,
});

const form = useForm({
    bac_resolution_id: "",
    selected_aoq_id: "",
    noa_no: "",
    noa_date: props.defaultNoaDate || "",
    resolution_no: "",
    resolution_date: "",
    calculation_label: "",
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
            :eligible-resolutions="eligibleResolutions"
            :suppliers="suppliers"
            :default-resolution-date="defaultResolutionDate"
            :default-noa-date="defaultNoaDate"
            @submit="submit"
        />
    </div>
</template>
