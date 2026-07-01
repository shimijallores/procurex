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
    suppliers: Array,
    defaultNoaDate: String,
    defaultNoaNo: String,
});
</script>

<template>
    <div class="space-y-6">
        <NOACreateHeader />

        <NOACreateForm
            :suppliers="suppliers"
            :default-noa-date="defaultNoaDate"
            :default-noa-no="defaultNoaNo"
        />
    </div>
</template>
