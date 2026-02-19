<script setup>
import MasterListItemForm from "../create/MasterListItemForm.vue";

defineExpose({
    ...MasterListItemForm,
});
</script>

<template>
    <MasterListItemForm v-bind="$attrs" />
</template>
