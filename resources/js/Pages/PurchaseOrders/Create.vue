<script setup>
import { useForm } from "@inertiajs/vue3";
import Layout from "@/Layout/Layout.vue";
import PurchaseOrderCreateHeader from "@/components/purchase-orders/create/PurchaseOrderCreateHeader.vue";
import PurchaseOrderCreateForm from "@/components/purchase-orders/create/PurchaseOrderCreateForm.vue";

defineOptions({
    layout: (h, page) =>
        h(
            Layout,
            {
                breadcrumbs: [
                    {
                        label: "Purchase Order",
                        href: route("purchase-orders.index"),
                    },
                    { label: "Create" },
                ],
            },
            () => page,
        ),
});

const props = defineProps({
    eligibleNoas: Array,
    defaults: Object,
});

const form = useForm({
    noa_id: "",
    po_no: props.defaults?.po_no || "",
    po_date: props.defaults?.po_date || "",
    mode_of_procurement: props.defaults?.mode_of_procurement || "Small Value",
    place_of_delivery: "",
    delivery_term_days: props.defaults?.delivery_term_days || 15,
    payment_term:
        props.defaults?.payment_term || "upon 100% completion /delivery",
    total_amount: 0,
    total_amount_words: "",
    remarks: "",
    items: [],
});

const submit = () => {
    form.post(route("purchase-orders.store"));
};
</script>

<template>
    <div class="space-y-6">
        <PurchaseOrderCreateHeader />

        <PurchaseOrderCreateForm
            :form="form"
            :eligible-noas="eligibleNoas"
            :defaults="defaults"
            @submit="submit"
        />
    </div>
</template>
