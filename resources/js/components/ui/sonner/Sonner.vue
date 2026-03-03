<script lang="ts" setup>
import type { ToasterProps } from "vue-sonner";
import {
    CircleCheckIcon,
    InfoIcon,
    Loader2Icon,
    OctagonXIcon,
    TriangleAlertIcon,
    XIcon,
} from "lucide-vue-next";
import { Toaster as Sonner } from "vue-sonner";
import { cn } from "@/lib/utils";

const props = defineProps<ToasterProps>();
</script>

<template>
    <Sonner
        :theme="props.theme || 'light'"
        :class="cn('toaster group', props.class)"
        :toast-options="{
            classes: {
                toast: 'group toast group-[.toaster]:bg-background group-[.toaster]:text-foreground group-[.toaster]:border-border group-[.toaster]:shadow-lg',
                description: 'group-[.toast]:text-muted-foreground',
                actionButton:
                    'group-[.toast]:bg-primary group-[.toast]:text-primary-foreground',
                cancelButton:
                    'group-[.toast]:bg-muted group-[.toast]:text-muted-foreground',
                error: 'group-[.toaster]:bg-destructive group-[.toaster]:text-destructive-foreground',
                success:
                    'group-[.toaster]:bg-green-600 group-[.toaster]:text-white',
                warning:
                    'group-[.toaster]:bg-yellow-600 group-[.toaster]:text-white',
                info: 'group-[.toaster]:bg-blue-600 group-[.toaster]:text-white',
            },
        }"
        v-bind="props"
    >
        <template #success-icon>
            <CircleCheckIcon class="size-4" />
        </template>
        <template #info-icon>
            <InfoIcon class="size-4" />
        </template>
        <template #warning-icon>
            <TriangleAlertIcon class="size-4" />
        </template>
        <template #error-icon>
            <OctagonXIcon class="size-4" />
        </template>
        <template #loading-icon>
            <div>
                <Loader2Icon class="size-4 animate-spin" />
            </div>
        </template>
        <template #close-icon>
            <XIcon class="size-4" />
        </template>
    </Sonner>
</template>
