<script setup>
import MasterListCategoryForm from "../create/MasterListCategoryForm.vue";
</script>

<template>
    <MasterListCategoryForm
        :action="$attrs.action"
        :route="$attrs.route"
        :returnRoute="$attrs.returnRoute"
        :category="$attrs.category"
        :errors="$attrs.errors"
        :processing="$attrs.processing"
    />
</template>
