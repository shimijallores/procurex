<script setup>
import { ref } from "vue";
import { useForm, router } from "@inertiajs/vue3";
import Layout from "@/Layout/Layout.vue";
import DeleteModal from "@/components/DeleteModal.vue";
import BACResolutionShowHeader from "@/components/bac-resolutions/show/BACResolutionShowHeader.vue";
import BACResolutionShowEditor from "@/components/bac-resolutions/show/BACResolutionShowEditor.vue";

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
                    { label: "Details" },
                ],
            },
            () => page,
        ),
});

const props = defineProps({
    resolution: Object,
});

const showDeleteModal = ref(false);

const form = useForm({
    resolution_date: props.resolution.resolution_date || "",
    meeting_date: props.resolution.meeting_date || "",
    project_name: props.resolution.project_name || "",
    winner_supplier_name: props.resolution.winner_supplier_name || "",
    winner_amount: props.resolution.winner_amount || 0,
    calculation_label:
        props.resolution.calculation_label || "Lowest Calculated",
    justification: props.resolution.justification || "",
    signatory_chairperson: props.resolution.signatory_chairperson || "",
    signatory_member_one: props.resolution.signatory_member_one || "",
    signatory_member_two: props.resolution.signatory_member_two || "",
    signatory_member_three: props.resolution.signatory_member_three || "",
});

const save = () => {
    form.put(route("bac-resolutions.update", props.resolution.id));
};

const finalize = () => {
    router.post(route("bac-resolutions.finalize", props.resolution.id));
};
</script>

<template>
    <div class="space-y-6">
        <BACResolutionShowHeader
            :resolution="resolution"
            :saving="form.processing"
            @save="save"
            @finalize="finalize"
            @delete="showDeleteModal = true"
        />

        <BACResolutionShowEditor :resolution="resolution" :form="form" />

        <DeleteModal
            v-model:open="showDeleteModal"
            title="Delete BAC Resolution"
            :description="`Are you sure you want to delete ${resolution.resolution_no}? This action cannot be undone.`"
            :delete-url="route('bac-resolutions.destroy', resolution.id)"
        />
    </div>
</template>
