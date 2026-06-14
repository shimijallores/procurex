<script setup>
import InfoModal from "@/components/InfoModal.vue";
import { getPageInfo } from "@/helpers/pageInfo.js";
import { route } from "ziggy-js";
import { computed } from "vue";

defineProps({
    title: {
        type: String,
        required: true,
    },
});

const pageInfo = computed(() => getPageInfo(route().current()));
</script>

<template>
    <div class="flex items-center gap-2">
        <h1 class="text-2xl font-bold tracking-tight md:text-3xl">
            {{ title }}
        </h1>
        <InfoModal
            v-if="pageInfo"
            :title="pageInfo.title"
            :sections="pageInfo.sections"
        />
    </div>
</template>
