<script setup>
import { ref, watch } from "vue";
import { useForm } from "@inertiajs/vue3";
import Layout from "@/Layout/Layout.vue";
import PPMPCreateHeader from "@/components/ppmps/create/PPMPCreateHeader.vue";
import PPMPCreateForm from "@/components/ppmps/create/PPMPCreateForm.vue";

defineOptions({
    layout: (h, page) =>
        h(
            Layout,
            {
                breadcrumbs: [
                    {
                        label: "Project Procurement Management Plan",
                        href: route("ppmps.index"),
                    },
                    { label: "Create" },
                ],
            },
            () => page,
        ),
});

const props = defineProps({
    offices: Array,
    funds: Array,
    projects: Array,
});

const form = useForm({
    office_id: "",
    fund_id: "",
    project_id: "",
    account_code: "",
    project_code: "",
    fiscal_year: new Date().getFullYear(),
    is_addendum: false,
    remarks: "",
    csv_file: null,
});

const csvFileName = ref("");

watch(
    () => form.fund_id,
    () => {
        form.project_id = "";
    },
);

const handleFileChange = (event) => {
    const file = event.target.files[0];
    form.csv_file = file;
    csvFileName.value = file ? file.name : "";
};

const submit = () => {
    const data = { ...form.data() };

    // Ensure is_addendum is a boolean
    data.is_addendum = form.is_addendum === true || form.is_addendum === 1;

    // Remove null file field if no file uploaded
    if (!data.csv_file) {
        delete data.csv_file;
    }

    form.transform(() => data).post(route("ppmps.store"));
};
</script>

<template>
    <div class="space-y-6">
        <PPMPCreateHeader />
        <PPMPCreateForm
            :form="form"
            :offices="offices"
            :funds="funds"
            :projects="projects"
            :csv-file-name="csvFileName"
            @submit="submit"
            @file-change="handleFileChange"
        />
    </div>
</template>
