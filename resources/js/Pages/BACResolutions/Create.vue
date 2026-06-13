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
    eligibleBatches: Array,
    defaultResolutionDate: String,
    defaultMeetingDate: String,
});

const form = useForm({
    batch_id: "",
    resolution_date: props.defaultResolutionDate || "",
    meeting_date: props.defaultMeetingDate || "",
    project_name: "",
    winner_supplier_name: "",
    winner_amount: "",
    calculation_label: "",
    justification: "",
    signatory_chairperson: "",
    signatory_member_one: "",
    signatory_member_two: "",
    signatory_member_three: "",
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
            :eligible-batches="eligibleBatches"
            @submit="submit"
        />
    </div>
</template>
