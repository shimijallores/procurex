<script setup>
import { useForm } from "@inertiajs/vue3";
import Layout from "@/Layout/Layout.vue";
import POTransmittalCreateHeader from "@/components/po-transmittals/create/POTransmittalCreateHeader.vue";
import POTransmittalCreateForm from "@/components/po-transmittals/create/POTransmittalCreateForm.vue";

defineOptions({
    layout: (h, page) =>
        h(
            Layout,
            {
                breadcrumbs: [
                    {
                        label: "PO Transmittal",
                        href: route("po-transmittals.index"),
                    },
                    { label: "Create" },
                ],
            },
            () => page,
        ),
});

const props = defineProps({
    batches: Array,
});

const form = useForm({
    purchase_order_id: "",
    coa: {
        transmittal_no: "",
        header_text:
            "MARIA VANESSA C. BRIONES - VEGAS\nOIC – SUPERVISING AUDITOR\nCOMMISSION ON AUDIT\nCapitol Site, Batangas City\n\nMa'am,",
        signatory_name: "NOEL R. ROCAFORT",
        signatory_title: "PGDH – GSO",
    },
    opg: {
        transmittal_no: "",
        header_text:
            "HON. VILMA SANTOS - RECTO\nGovernor\nProvince of Batangas\nCapitol Site, Batangas City\n\nMa'am,",
        signatory_name: "NOEL R. ROCAFORT",
        signatory_title: "PGDH – GSO",
    },
});

const submit = () => {
    form.post(route("po-transmittals.store"));
};
</script>

<template>
    <div class="space-y-6">
        <POTransmittalCreateHeader />

        <POTransmittalCreateForm
            :form="form"
            :batches="batches"
            @submit="submit"
        />
    </div>
</template>
