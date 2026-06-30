<script setup>
import { useForm } from "@inertiajs/vue3";
import Layout from "@/Layout/Layout.vue";
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
                    { label: "Edit" },
                ],
            },
            () => page,
        ),
});

const props = defineProps({
    resolution: Object,
    defaultResolutionDate: String,
    defaultMeetingDate: String,
});

const form = useForm({
    batch_id: String(props.resolution.aoqs?.[0]?.batch_id || props.resolution.aoq?.batch_id || ""),
    resolution_date: props.resolution.resolution_date || props.defaultResolutionDate || "",
    meeting_date: props.resolution.meeting_date || "",
    project_name: props.resolution.project_name || "",
    winner_supplier_name: props.resolution.winner_supplier_name || "",
    winner_amount: String(props.resolution.winner_amount || ""),
    calculation_label: props.resolution.calculation_label || "",
    justification: props.resolution.justification || "",
    signatory_chairperson: props.resolution.signatory_chairperson || "",
    signatory_member_one: props.resolution.signatory_member_one || "",
    signatory_member_two: props.resolution.signatory_member_two || "",
    signatory_member_three: props.resolution.signatory_member_three || "",
});

const submit = () => {
    form.put(route("bac-resolutions.update", props.resolution.id));
};
</script>

<template>
    <div class="space-y-6">
        <BACResolutionCreateForm
            :form="form"
            @submit="submit"
        />
    </div>
</template>
