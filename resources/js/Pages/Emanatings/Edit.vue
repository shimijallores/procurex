<script setup>
import { ref } from "vue";
import { useForm } from "@inertiajs/vue3";
import Layout from "@/Layout/Layout.vue";
import EmanatingEditHeader from "@/components/emanatings/edit/EmanatingEditHeader.vue";
import EmanatingEditForm from "@/components/emanatings/edit/EmanatingEditForm.vue";

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
                    {
                        label: "Details",
                        href: route("emanatings.show", page.props.emanating.id),
                    },
                    { label: "Edit" },
                ],
            },
            () => page,
        ),
});

const props = defineProps({
    emanating: Object,
    ppmps: Array,
    ppmpCategories: Array,
});

const form = useForm({
    ppmp_id: props.emanating.ppmp_id || "",
    ppmp_category_id: props.emanating.ppmp_category_id || "",
    pr_no: props.emanating.pr_no || "",
    is_addendum: props.emanating.is_addendum || false,
    remarks: props.emanating.remarks || "",
    reimbursement: props.emanating.reimbursement || false,
});

const submit = () => {
    const data = { ...form.data() };

    // Ensure booleans are properly formatted
    data.is_addendum = form.is_addendum === true || form.is_addendum === 1;
    data.reimbursement =
        form.reimbursement === true || form.reimbursement === 1;

    form.transform(() => data).put(
        route("emanatings.update", props.emanating.id),
    );
};
</script>

<template>
    <div class="space-y-6">
        <EmanatingEditHeader :emanating="emanating" />
        <EmanatingEditForm
            :form="form"
            :emanating="emanating"
            :ppmps="ppmps"
            :ppmp-categories="ppmpCategories"
            @submit="submit"
        />
    </div>
</template>
