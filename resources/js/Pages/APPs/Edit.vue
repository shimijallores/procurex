<script setup>
import { useForm } from "@inertiajs/vue3";
import Layout from "@/Layout/Layout.vue";
import AppEditHeader from "@/components/apps/edit/AppEditHeader.vue";
import AppEditForm from "@/components/apps/edit/AppEditForm.vue";

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
                    { label: "Edit" },
                ],
            },
            () => page,
        ),
});

const props = defineProps({
    app: Object,
    offices: Array,
});

const form = useForm({
    office_id: props.app.office_id,
    fiscal_year: props.app.fiscal_year,
});

const submit = () => {
    form.put(route("apps.update", props.app.id));
};
</script>

<template>
    <div class="space-y-6">
        <AppEditHeader />
        <AppEditForm
            :form="form"
            :app="app"
            :offices="offices"
            @submit="submit"
        />
    </div>
</template>
