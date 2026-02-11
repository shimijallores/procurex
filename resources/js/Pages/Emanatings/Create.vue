<script setup>
import { ref } from "vue";
import { useForm } from "@inertiajs/vue3";
import Layout from "@/Layout/Layout.vue";
import EmanatingCreateHeader from "@/components/emanatings/create/EmanatingCreateHeader.vue";
import EmanatingCreateForm from "@/components/emanatings/create/EmanatingCreateForm.vue";

defineOptions({
    layout: (h, page) =>
        h(
            Layout,
            {
                breadcrumbs: [
                    {
                        label: "Emanating Requests",
                        href: route("emanatings.index"),
                    },
                    { label: "Create" },
                ],
            },
            () => page,
        ),
});

const props = defineProps({
    ppmps: Array,
    ppmpCategories: Array,
});

const form = useForm({
    ppmp_id: "",
    ppmp_category_id: "",
    pr_no: "",
    is_addendum: false,
    remarks: "",
    reimbursement: false,
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

    // Ensure booleans are properly formatted
    data.is_addendum = form.is_addendum === true || form.is_addendum === 1;
    data.reimbursement =
        form.reimbursement === true || form.reimbursement === 1;

    // Remove null file field if no file uploaded
    if (!data.csv_file) {
        delete data.csv_file;
    }

    form.transform(() => data).post(route("emanatings.store"));
};
</script>

<template>
    <div class="space-y-6">
        <EmanatingCreateHeader />
        <EmanatingCreateForm
            :form="form"
            :ppmps="ppmps"
            :ppmp-categories="ppmpCategories"
            :csv-file-name="csvFileName"
            @submit="submit"
            @file-change="handleFileChange"
        />
    </div>
</template>
