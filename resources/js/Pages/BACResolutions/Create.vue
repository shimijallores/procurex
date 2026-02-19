<script setup>
import { useForm } from "@inertiajs/vue3";
import Layout from "@/Layout/Layout.vue";
import BACResolutionCreateHeader from "@/components/bac-resolutions/create/BACResolutionCreateHeader.vue";
import BACResolutionCreateForm from "@/components/bac-resolutions/create/BACResolutionCreateForm.vue";

defineOptions({
    layout: (h, page) =>
        h(
            Layout,
            {
                breadcrumbs: [
                    {
                        label: "BAC Resolutions",
                        href: route("bac-resolutions.index"),
                    },
                    { label: "Create" },
                ],
            },
            () => page,
        ),
});

const props = defineProps({
    eligibleAoqs: Array,
    defaultResolutionDate: String,
    defaultMeetingDate: String,
});

const form = useForm({
    aoq_id: "",
    resolution_date: props.defaultResolutionDate || "",
    meeting_date: props.defaultMeetingDate || "",
});

const submit = () => {
    form.post(route("bac-resolutions.store"));
};
</script>

<template>
    <div class="space-y-6">
        <BACResolutionCreateHeader />

        <BACResolutionCreateForm
            :form="form"
            :eligible-aoqs="eligibleAoqs"
            @submit="submit"
        />
    </div>
</template>
