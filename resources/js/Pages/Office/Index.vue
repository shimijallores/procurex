<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { Icon } from '@iconify/vue'
import Layout from '@/Layout/Layout.vue'
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card'

defineOptions({
    layout: Layout,
})

const page = usePage()
const user = computed(() => page.props.auth?.user.data)

</script>

<template>
    <div class="space-y-6">
        <!-- Title -->
        <div class="space-y-1">
            <h1 class="text-2xl font-bold tracking-tight md:text-3xl">
                Welcome back, {{ user?.name?.split(' ')[0] ?? 'User' }}!
            </h1>
            <p class="text-muted-foreground">
                Here's what's happening with your procurement activities today.
            </p>
        </div>
    </div>
</template>
