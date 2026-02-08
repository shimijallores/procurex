<script setup>
import { ref } from "vue";
import { useForm } from "@inertiajs/vue3";
import Layout from "@/Layout/Layout.vue";
import AppCreateHeader from "@/components/apps/create/AppCreateHeader.vue";
import AppCreateForm from "@/components/apps/create/AppCreateForm.vue";

defineOptions({
    layout: (h, page) =>
        h(
            Layout,
            {
                breadcrumbs: [
                    {
                        label: "Annual Procurement Plan",
                        href: route("apps.index"),
                    },
                    { label: "Create" },
                ],
            },
            () => page,
        ),
});

const props = defineProps({
    offices: Array,
});

const form = useForm({
    office_id: "",
    fiscal_year: new Date().getFullYear(),
    csv_file: null,
});

const csvFileName = ref("");

const handleFileChange = (event) => {
    const file = event.target.files[0];
    form.csv_file = file;
    csvFileName.value = file ? file.name : "";
};

const submit = () => {
    const data = { ...form.data() };

    // Remove null file field if no file uploaded
    if (!data.csv_file) {
        delete data.csv_file;
    }

    form.transform(() => data).post(route("apps.store"));
};
</script>

<template>
    <div class="space-y-6">
        <AppCreateHeader />
        <AppCreateForm
            :form="form"
            :offices="offices"
            :csv-file-name="csvFileName"
            @submit="submit"
            @file-change="handleFileChange"
        />
    </div>
</template>
