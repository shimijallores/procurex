<script setup>
import { useForm } from "@inertiajs/vue3";
import Layout from "@/Layout/Layout.vue";
import PRMatrixEditHeader from "@/components/purchase-request-matrix/edit/PRMatrixEditHeader.vue";
import PRMatrixEditForm from "@/components/purchase-request-matrix/edit/PRMatrixEditForm.vue";

defineOptions({
    layout: (h, page) =>
        h(
            Layout,
            {
                breadcrumbs: [
                    {
                        label: "PR Matrix",
                        href: route("purchase-request-matrix.index"),
                    },
                    { label: "Edit" },
                ],
            },
            () => page,
        ),
});

const props = defineProps({
    matrixRow: Object,
    accounts: Array,
    prAdminUsers: Array,
    budgetingAdminUsers: Array,
});

const form = useForm({
    matrix_amount_below_1m: props.matrixRow.amount_below_1m ?? "",
    matrix_amount_above_1m: props.matrixRow.amount_above_1m ?? "",
    matrix_new_amount: props.matrixRow.new_amount ?? "",
    matrix_account_id: props.matrixRow.account_id ?? "",
    matrix_pr_admin_user_id: props.matrixRow.pr_admin_user_id ?? "",
    matrix_budgeting_admin_user_id:
        props.matrixRow.budgeting_admin_user_id ?? "",
    matrix_date_release: props.matrixRow.date_release ?? "",
    matrix_new_date_release: props.matrixRow.new_date_release ?? "",
    matrix_remarks: props.matrixRow.remarks ?? "",
});

const submit = () => {
    form.put(route("purchase-request-matrix.update", props.matrixRow.id));
};
</script>

<template>
    <div class="space-y-6">
        <PRMatrixEditHeader :matrix-row="matrixRow" />
        <PRMatrixEditForm
            :matrix-row="matrixRow"
            :form="form"
            :accounts="accounts"
            :pr-admin-users="prAdminUsers"
            :budgeting-admin-users="budgetingAdminUsers"
            @submit="submit"
        />
    </div>
</template>
