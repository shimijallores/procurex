<script setup>
import { useForm } from "@inertiajs/vue3";
import Layout from "@/Layout/Layout.vue";
import PREditHeader from "@/components/purchaseRequests/edit/PREditHeader.vue";
import PRImportedEditForm from "@/components/purchaseRequests/edit/PRImportedEditForm.vue";

defineOptions({
    layout: (h, page) =>
        h(
            Layout,
            {
                breadcrumbs: [
                    {
                        label: "Purchase Requests",
                        href: route("purchase-requests.index"),
                    },
                    { label: "Edit Imported" },
                ],
            },
            () => page,
        ),
});

const props = defineProps({
    purchaseRequest: Object,
    offices: Array,
});

const existingItems = (props.purchaseRequest.items || []).map((item) => ({
    id: item.id,
    item_name: item.item_name || "",
    unit: item.unit || "",
    quantity: item.quantity,
    unit_cost: parseFloat(item.unit_cost || 0),
    vat_applicable: item.vat_applicable,
    vat_rate: parseFloat(item.vat_rate || 0.12),
    remarks: item.remarks || "",
}));

const form = useForm({
    office_id: props.purchaseRequest.office_id,
    fund_id: props.purchaseRequest.fund_id,
    pr_no: props.purchaseRequest.pr_no || "",
    pr_date: props.purchaseRequest.pr_date || "",
    sai_no: props.purchaseRequest.sai_no || "",
    sai_date: props.purchaseRequest.sai_date || "",
    requested_by_name: props.purchaseRequest.requested_by_name || "",
    requested_by_designation:
        props.purchaseRequest.requested_by_designation || "",
    purpose: props.purchaseRequest.purpose || "",
    remarks: props.purchaseRequest.remarks || "",
    status: props.purchaseRequest.status || "draft",
    items: existingItems,
});

const submit = () => {
    form.put(
        route("purchase-requests.update-imported", props.purchaseRequest.id),
    );
};
</script>

<template>
    <div class="space-y-6">
        <PREditHeader :purchase-request="purchaseRequest" />

        <PRImportedEditForm
            :form="form"
            :offices="offices"
            @submit="submit"
        />
    </div>
</template>
