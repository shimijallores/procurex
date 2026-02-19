<script setup>
import MasterListItemForm from "../create/MasterListItemForm.vue";
</script>

<template>
    <MasterListItemForm
        :action="$attrs.action"
        :route="$attrs.route"
        :returnRoute="$attrs.returnRoute"
        :item="$attrs.item"
        :categories="$attrs.categories"
        :suppliers="$attrs.suppliers"
        :errors="$attrs.errors"
        :processing="$attrs.processing"
    />
</template>
