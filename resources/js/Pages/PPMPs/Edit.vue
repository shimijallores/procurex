<script setup>
import { useForm } from "@inertiajs/vue3";
import Layout from "@/Layout/Layout.vue";
import PPMPEditHeader from "@/components/ppmps/edit/PPMPEditHeader.vue";
import PPMPEditForm from "@/components/ppmps/edit/PPMPEditForm.vue";

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
                    { label: "Edit" },
                ],
            },
            () => page,
        ),
});

const props = defineProps({
    ppmp: Object,
    offices: Array,
    projects: Array,
});

const form = useForm({
    office_id: props.ppmp.office_id,
    project_id: props.ppmp.project_id,
    account_code: props.ppmp.account_code || "",
    project_code: props.ppmp.project_code || "",
    fiscal_year: props.ppmp.fiscal_year,
    is_addendum: props.ppmp.is_addendum || false,
    remarks: props.ppmp.remarks || "",
});

const submit = () => {
    // Ensure is_addendum is a boolean
    const data = form.data();
    data.is_addendum = form.is_addendum === true || form.is_addendum === 1;

    form.transform(() => data).put(route("ppmps.update", props.ppmp.id));
};
</script>

<template>
    <div class="space-y-6">
        <PPMPEditHeader />
        <PPMPEditForm
            :form="form"
            :ppmp="ppmp"
            :offices="offices"
            :projects="projects"
            @submit="submit"
        />
    </div>
</template>
