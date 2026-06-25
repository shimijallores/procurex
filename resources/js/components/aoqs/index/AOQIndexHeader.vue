<script setup>
import { computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import { Link } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import { Button } from "@/components/ui/button";
import PageTitle from "@/components/PageTitle.vue";

const user = computed(() => usePage().props.auth?.user);
const isSuperAdmin = computed(() =>
    (user.value?.roles ?? []).some((r) => r.name === "SuperAdmin"),
);
</script>

<template>
    <div class="flex items-center justify-between">
        <div class="space-y-1">
            <PageTitle title="Abstract of Quotation" />
            <p class="text-muted-foreground">
                Consolidate submitted supplier quotations and determine winner
            </p>
        </div>
        <div class="flex items-center gap-2">
            <Link v-if="isSuperAdmin" :href="route('batch-aoq-requests.index')">
                <Button variant="outline">
                    <Icon icon="lucide:mail-question" class="mr-2 h-4 w-4" />
                    Batch AOQ Requests
                </Button>
            </Link>
            <Link :href="route('aoqs.create')">
                <Button>
                    <Icon icon="lucide:plus" class="mr-2 h-4 w-4" />
                    New Abstract of Quotation
                </Button>
            </Link>
        </div>
    </div>
</template>
