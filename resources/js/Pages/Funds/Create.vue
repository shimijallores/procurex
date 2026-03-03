<script setup>
import { ref, watch } from "vue";
import { useForm } from "@inertiajs/vue3";
import Layout from "@/Layout/Layout.vue";
import FundCreateHeader from "@/components/funds/create/FundCreateHeader.vue";
import FundCreateForm from "@/components/funds/create/FundCreateForm.vue";

defineOptions({
    layout: (h, page) =>
        h(
            Layout,
            {
                breadcrumbs: [
                    {
                        label: "Funds",
                        href: route("funds.index"),
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
    project_code_id: "",
    code: "",
    name: "",
    type: "general",
    fiscal_year: new Date().getFullYear(),
    remarks: "",
    project_name: "",
    work_program: null,
    project_brief: null,
    project_proposal: null,
});

const fundType = ref("general");
const showProjectFields = ref(false);

watch(fundType, (newType) => {
    showProjectFields.value = newType === "project";
    form.type = newType;
});

const submit = () => {
    const data = { ...form.data() };

    // Remove null file fields to allow JSON requests when no files are uploaded
    if (!data.work_program) delete data.work_program;
    if (!data.project_brief) delete data.project_brief;
    if (!data.project_proposal) delete data.project_proposal;

    form.transform(() => data).post(route("funds.store"));
};
</script>

<template>
    <div class="space-y-6">
        <FundCreateHeader />
        <FundCreateForm
            :form="form"
            :offices="offices"
            :fund-type="fundType"
            :show-project-fields="showProjectFields"
            @update:fundType="(value) => (fundType = value)"
            @submit="submit"
        />
    </div>
</template>
