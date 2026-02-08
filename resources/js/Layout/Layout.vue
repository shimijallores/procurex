<script setup>
import {
    SidebarProvider,
    SidebarInset,
    SidebarTrigger,
} from "@/components/ui/sidebar";
import { Separator } from "@/components/ui/separator";
import { Link } from "@inertiajs/vue3";
import AppSidebar from "@/components/AppSidebar.vue";
import DarkModeButton from "@/components/DarkModeButton.vue";
import FlashMessage from "@/components/FlashMessage.vue";

defineProps({
    title: {
        type: String,
        default: "Dashboard",
    },
    breadcrumbs: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <SidebarProvider>
        <AppSidebar />
        <SidebarInset>
            <!-- Header -->
            <header
                class="flex h-16 shrink-0 items-center justify-between gap-2 border-b px-4 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12"
            >
                <div class="flex items-center gap-2">
                    <SidebarTrigger class="-ml-1" />
                    <Separator orientation="vertical" class="mr-2 h-4" />

                    <!-- Breadcrumbs -->
                    <nav class="flex items-center gap-1.5 text-sm">
                        <template v-if="breadcrumbs.length > 0">
                            <template
                                v-for="(crumb, index) in breadcrumbs"
                                :key="index"
                            >
                                <span
                                    v-if="index > 0"
                                    class="text-muted-foreground"
                                    >/</span
                                >
                                <Link
                                    v-if="
                                        crumb.href &&
                                        index !== breadcrumbs.length - 1
                                    "
                                    :href="crumb.href"
                                    class="text-muted-foreground hover:text-foreground transition-colors"
                                >
                                    {{ crumb.label }}
                                </Link>
                                <span
                                    v-else
                                    :class="[
                                        index === breadcrumbs.length - 1
                                            ? 'font-medium text-foreground'
                                            : 'text-muted-foreground',
                                    ]"
                                >
                                    {{ crumb.label }}
                                </span>
                            </template>
                        </template>
                        <span v-else class="font-medium">{{ title }}</span>
                    </nav>
                </div>

                <!-- Right side actions -->
                <div class="flex items-center gap-2">
                    <DarkModeButton />
                </div>
            </header>

            <!-- Main content -->
            <main class="flex-1 p-4 md:p-6">
                <slot />
            </main>
        </SidebarInset>
        <FlashMessage />
    </SidebarProvider>
</template>
