<script setup>
import { ref } from "vue";
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
});

const form = useForm({
    office_id: "",
    fund_id: "",
    fiscal_year: new Date().getFullYear(),
    is_addendum: false,
    remarks: "",
    xlsx_file: null,
});

const xlsxFileName = ref("");

const handleFileChange = (event) => {
    const file = event.target.files[0];
    form.xlsx_file = file;
    xlsxFileName.value = file ? file.name : "";
};

const submit = () => {
    const data = { ...form.data() };

    // Ensure is_addendum is a boolean
    data.is_addendum = form.is_addendum === true || form.is_addendum === 1;

    // Remove null file field if no file uploaded
    if (!data.xlsx_file) {
        delete data.xlsx_file;
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
            :xlsx-file-name="xlsxFileName"
            @submit="submit"
            @file-change="handleFileChange"
        />
    </div>
</template>
