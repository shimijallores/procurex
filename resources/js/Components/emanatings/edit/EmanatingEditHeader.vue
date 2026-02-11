<script setup>
import { Icon } from "@iconify/vue";

defineProps({
    emanating: Object,
});
</script>

<template>
    <div>
        <h1 class="text-3xl font-bold">Edit Emanating Request</h1>
        <p class="text-muted-foreground mt-1">
            {{ emanating.pr_no || `Emanating #${emanating.id}` }}
        </p>
    </div>
</template>
