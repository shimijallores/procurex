<script setup>
import { useForm } from "@inertiajs/vue3";
import Layout from "@/Layout/Layout.vue";
import AOQCreateHeader from "@/components/aoqs/create/AOQCreateHeader.vue";
import AOQCreateForm from "@/components/aoqs/create/AOQCreateForm.vue";

defineOptions({
    layout: (h, page) =>
        h(
            Layout,
            {
                breadcrumbs: [
                    {
                        label: "Abstract of Quotation",
                        href: route("aoqs.index"),
                    },
                    { label: "Create" },
                ],
            },
            () => page,
        ),
});

const props = defineProps({
    eligibleRfqs: Array,
    defaultAoqDate: String,
});

const form = useForm({
    rfq_id: "",
    aoq_date: props.defaultAoqDate || "",
});

const submit = () => {
    form.post(route("aoqs.store"));
};
</script>

<template>
    <div class="space-y-6">
        <AOQCreateHeader />

        <AOQCreateForm
            :form="form"
            :eligible-rfqs="eligibleRfqs"
            @submit="submit"
        />
    </div>
</template>
