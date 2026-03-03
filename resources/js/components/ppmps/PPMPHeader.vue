<script setup>
import { Link } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import { Button } from "@/components/ui/button";

defineProps({
    ppmp: Object,
    budgetValidationPassed: Boolean,
    onImport: Function,
    onEdit: Function,
    onDelete: Function,
});
</script>

<template>
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <Link :href="route('ppmps.index')">
                <Button variant="ghost" size="sm">
                    <Icon icon="lucide:arrow-left" class="mr-2 h-4 w-4" />
                    Back
                </Button>
            </Link>
            <div class="space-y-1">
                <div class="flex items-center gap-2">
                    <h1 class="text-2xl font-bold tracking-tight md:text-3xl">
                        {{ ppmp.office?.name || "Unknown Office" }}
                    </h1>
                    <span
                        v-if="ppmp.is_addendum"
                        class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300"
                    >
                        Addendum
                    </span>
                </div>
                <p class="text-muted-foreground">
                    Project Procurement Management Plan - FY
                    {{ ppmp.fiscal_year }}
                </p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a
                v-if="ppmp.xlsx_path"
                :href="route('ppmps.download-xlsx', ppmp.id)"
                download
            >
                <Button variant="outline">
                    <Icon icon="lucide:download" class="mr-2 h-4 w-4" />
                    Download XLSX
                </Button>
            </a>
            <Button variant="outline" @click="onImport">
                <Icon icon="lucide:upload" class="mr-2 h-4 w-4" />
                Import XLSX
            </Button>

            <Link :href="route('ppmps.edit', ppmp.id)">
                <Button variant="outline">
                    <Icon icon="lucide:pencil" class="mr-2 h-4 w-4" />
                    Edit
                </Button>
            </Link>
            <Button variant="destructive" @click="onDelete">
                <Icon icon="lucide:trash-2" class="mr-2 h-4 w-4" />
                Delete
            </Button>
        </div>
    </div>
</template>
