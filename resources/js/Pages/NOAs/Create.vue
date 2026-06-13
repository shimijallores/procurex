<script setup>
import Layout from "@/Layout/Layout.vue";
import NOACreateHeader from "@/components/noas/create/NOACreateHeader.vue";
import NOACreateForm from "@/components/noas/create/NOACreateForm.vue";

defineOptions({
    layout: (h, page) =>
        h(
            Layout,
            {
                breadcrumbs: [
                    { label: "Notice of Award", href: route("noas.index") },
                    { label: "Create" },
                ],
            },
            () => page,
        ),
});

const props = defineProps({
    batches: Array,
    suppliers: Array,
    defaultNoaDate: String,
});
</script>

<template>
    <div class="space-y-6">
        <NOACreateHeader />

        <NOACreateForm
            :batches="batches"
            :suppliers="suppliers"
            :default-noa-date="defaultNoaDate"
        />
    </div>
</template>
