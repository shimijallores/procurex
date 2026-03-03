<script setup>
import { ref, watch } from "vue";
import { useForm } from "@inertiajs/vue3";
import Layout from "@/Layout/Layout.vue";
import FundEditHeader from "@/components/funds/edit/FundEditHeader.vue";
import FundEditForm from "@/components/funds/edit/FundEditForm.vue";

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
                    { label: "Edit" },
                ],
            },
            () => page,
        ),
});

const props = defineProps({
    fund: Object,
    offices: Array,
});

const form = useForm({
    office_id: props.fund.office_id,
    project_code_id: props.fund.project_code_id,
    code: props.fund.code,
    name: props.fund.name,
    type: props.fund.type,
    fiscal_year: props.fund.fiscal_year,
    remarks: props.fund.remarks,
    project_name: props.fund.project?.name || "",
    work_program: null,
    project_brief: null,
    project_proposal: null,
});

const fundType = ref(props.fund.type);
const showProjectFields = ref(props.fund.type === "project");

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

    // If files exist, add _method for Laravel
    const hasFiles =
        data.work_program || data.project_brief || data.project_proposal;
    if (hasFiles) {
        data._method = "PUT";
    }

    form.transform(() => data)[hasFiles ? "post" : "put"](
        route("funds.update", props.fund.id),
    );
};
</script>

<template>
    <div class="space-y-6">
        <FundEditHeader />
        <FundEditForm
            :form="form"
            :fund="fund"
            :offices="offices"
            :fund-type="fundType"
            :show-project-fields="showProjectFields"
            @update:fundType="(value) => (fundType = value)"
            @submit="submit"
        />
    </div>
</template>
