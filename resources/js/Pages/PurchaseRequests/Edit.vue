<script setup>
import { useForm } from "@inertiajs/vue3";
import Layout from "@/Layout/Layout.vue";
import PREditHeader from "@/components/purchaseRequests/edit/PREditHeader.vue";
import PRCreateForm from "@/components/purchaseRequests/create/PRCreateForm.vue";

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
                    { label: "Edit" },
                ],
            },
            () => page,
        ),
});

const props = defineProps({
    purchaseRequest: Object,
    commonPurposes: Array,
});

// Build items from existing PR items (for edit mode)
const existingItems = (props.purchaseRequest.items || []).map((item) => ({
    id: item.id,
    emanating_item_id: item.emanating_item_id,
    _name:
        item.emanating_item?.name ||
        item.emanating_item?.ppmp_item?.name ||
        item.emanating_item?.ppmpItem?.name ||
        "Unknown Item",
    _unit: item.emanating_item?.unit || "",
    _original_qty: item.emanating_item?.quantity || item.quantity,
    quantity: item.quantity,
    unit_cost: parseFloat(item.unit_cost || 0),
    vat_applicable: item.vat_applicable,
    vat_rate: parseFloat(item.vat_rate || 0.12),
    remarks: item.remarks || "",
}));

const form = useForm({
    emanating_id: props.purchaseRequest.emanating_id,
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
    form.put(route("purchase-requests.update", props.purchaseRequest.id));
};

// Provide the emanating as a single-element array so PRCreateForm can display it
const eligibleEmanatings = props.purchaseRequest.emanating
    ? [
          {
              ...props.purchaseRequest.emanating,
              id: props.purchaseRequest.emanating_id,
          },
      ]
    : [];
</script>

<template>
    <div class="space-y-6">
        <PREditHeader :purchase-request="purchaseRequest" />

        <PRCreateForm
            :form="form"
            :eligible-emanatings="eligibleEmanatings"
            :common-purposes="commonPurposes"
            :suggested-pr-date="purchaseRequest.pr_date"
            :is-edit="true"
            @submit="submit"
        />
    </div>
</template>
