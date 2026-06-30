<script setup>
import { useForm } from "@inertiajs/vue3";
import Layout from "@/Layout/Layout.vue";
import RFQEditHeader from "@/components/rfqs/edit/RFQEditHeader.vue";
import RFQEditForm from "@/components/rfqs/edit/RFQEditForm.vue";

defineOptions({
    layout: (h, page) =>
        h(
            Layout,
            {
                breadcrumbs: [
                    {
                        label: "Request for Quotation",
                        href: route("rfqs.index"),
                    },
                    {
                        label: "Edit",
                    },
                ],
            },
            () => page,
        ),
});

const toDateInput = (date) => {
    if (!date) return "";
    if (typeof date === "string" && /^\d{4}-\d{2}-\d{2}$/.test(date)) {
        return date;
    }
    const d = new Date(date);
    if (isNaN(d.getTime())) return "";
    const mm = String(d.getMonth() + 1).padStart(2, "0");
    const dd = String(d.getDate()).padStart(2, "0");
    return `${d.getFullYear()}-${mm}-${dd}`;
};

const props = defineProps({
    rfq: Object,
});

const form = useForm({
    svp_no: props.rfq.svp_no || "",
    rfq_date: toDateInput(props.rfq.rfq_date),
    submission_deadline: toDateInput(props.rfq.submission_deadline),
    project_name: props.rfq.project_name || "",
    abc_amount: String(props.rfq.abc_amount || ""),
    remarks: props.rfq.remarks || "",
    items: (props.rfq.items || []).map((item) => ({
        id: item.id,
        pr_item_id: item.pr_item_id,
        item_name: item.item_name || "",
        unit: item.unit || "",
        quantity: Number(item.quantity || 0),
    })),
});

const submit = () => {
    form.put(route("rfqs.update", props.rfq.id));
};
</script>

<template>
    <div class="space-y-6">
        <RFQEditHeader :rfq="rfq" />

        <RFQEditForm
            :form="form"
            @submit="submit"
        />
    </div>
</template>
