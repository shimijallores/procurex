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
});

const form = useForm({
    bac_resolution_id: "",
    noa_no: "",
    noa_date: "",
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
            @submit="submit"
        />
    </div>
</template>
