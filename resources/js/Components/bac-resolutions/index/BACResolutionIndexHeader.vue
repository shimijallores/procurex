<script setup>
import { Icon } from "@iconify/vue";
import { Link } from "@inertiajs/vue3";
import { Button } from "@/components/ui/button";
</script>

<template>
    <div class="flex items-start justify-between">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">
                BAC Resolutions
            </h1>
            <p class="text-sm text-muted-foreground mt-1">
                Manage BAC resolution documents linked to AOQs.
            </p>
        </div>

        <Link :href="route('bac-resolutions.create')">
            <Button>
                <Icon icon="lucide:plus" class="mr-2 h-4 w-4" />
                New BAC Resolution
            </Button>
        </Link>
    </div>
</template>
