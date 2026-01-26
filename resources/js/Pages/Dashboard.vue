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


// Sample stats data
const stats = [
    {
        title: 'Total Orders',
        value: '1,234',
        description: '+20.1% from last month',
        icon: 'lucide:shopping-cart',
        trend: 'up',
    },
    {
        title: 'Pending Approvals',
        value: '23',
        description: '5 require urgent attention',
        icon: 'lucide:clock',
        trend: 'neutral',
    },
    {
        title: 'Active Vendors',
        value: '156',
        description: '+12 new this month',
        icon: 'lucide:users',
        trend: 'up',
    },
    {
        title: 'Total Spent',
        value: '$45,231',
        description: '+10.5% from last month',
        icon: 'lucide:dollar-sign',
        trend: 'up',
    },
]

// Sample recent activities
const recentActivities = [
    {
        id: 1,
        type: 'order',
        title: 'New purchase order created',
        description: 'PO-2024-001 for Office Supplies',
        time: '2 hours ago',
        icon: 'lucide:file-plus',
    },
    {
        id: 2,
        type: 'approval',
        title: 'Purchase request approved',
        description: 'PR-2024-045 by John Smith',
        time: '4 hours ago',
        icon: 'lucide:check-circle',
    },
    {
        id: 3,
        type: 'vendor',
        title: 'New vendor registered',
        description: 'Acme Corporation added to vendor list',
        time: '1 day ago',
        icon: 'lucide:user-plus',
    },
    {
        id: 4,
        type: 'contract',
        title: 'Contract expiring soon',
        description: 'Contract with TechPro Inc. expires in 30 days',
        time: '2 days ago',
        icon: 'lucide:alert-triangle',
    },
]

// Sample quick actions
const quickActions = [
    {
        title: 'Create Purchase Request',
        description: 'Submit a new purchase request',
        icon: 'lucide:plus-circle',
        color: 'bg-blue-500',
    },
    {
        title: 'View Pending Approvals',
        description: 'Review items awaiting approval',
        icon: 'lucide:clock',
        color: 'bg-amber-500',
    },
    {
        title: 'Manage Vendors',
        description: 'Add or update vendor information',
        icon: 'lucide:users',
        color: 'bg-green-500',
    },
    {
        title: 'Generate Report',
        description: 'Create procurement reports',
        icon: 'lucide:bar-chart-3',
        color: 'bg-purple-500',
    },
]
</script>

<template>
    <div class="space-y-6">
        <!-- Welcome Section -->
        <div class="space-y-1">
            <h1 class="text-2xl font-bold tracking-tight md:text-3xl">
                Welcome back, {{ user?.name?.split(' ')[0] ?? 'User' }}!
            </h1>
            <p class="text-muted-foreground">
                Here's what's happening with your procurement activities today.
            </p>
        </div>

        <!-- Stats Grid -->
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <Card v-for="stat in stats" :key="stat.title">
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">
                        {{ stat.title }}
                    </CardTitle>
                    <Icon 
                        :icon="stat.icon" 
                        class="size-4 text-muted-foreground" 
                    />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ stat.value }}</div>
                    <p class="text-xs text-muted-foreground">
                        {{ stat.description }}
                    </p>
                </CardContent>
            </Card>
        </div>

        <!-- Main Content Grid -->
        <div class="grid gap-6 lg:grid-cols-7">
            <!-- Recent Activity -->
            <Card class="lg:col-span-4">
                <CardHeader>
                    <CardTitle>Recent Activity</CardTitle>
                    <CardDescription>
                        Your latest procurement activities and updates.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="space-y-4">
                        <div 
                            v-for="activity in recentActivities" 
                            :key="activity.id"
                            class="flex items-start gap-4 rounded-lg border p-3 transition-colors hover:bg-muted/50"
                        >
                            <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-muted">
                                <Icon :icon="activity.icon" class="size-5 text-muted-foreground" />
                            </div>
                            <div class="flex-1 space-y-1">
                                <p class="text-sm font-medium leading-none">
                                    {{ activity.title }}
                                </p>
                                <p class="text-sm text-muted-foreground">
                                    {{ activity.description }}
                                </p>
                            </div>
                            <span class="text-xs text-muted-foreground whitespace-nowrap">
                                {{ activity.time }}
                            </span>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Quick Actions -->
            <Card class="lg:col-span-3">
                <CardHeader>
                    <CardTitle>Quick Actions</CardTitle>
                    <CardDescription>
                        Common tasks and shortcuts.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-1">
                        <button 
                            v-for="action in quickActions" 
                            :key="action.title"
                            class="flex items-center gap-3 rounded-lg border p-3 text-left transition-colors hover:bg-muted/50"
                        >
                            <div 
                                :class="[
                                    'flex size-10 shrink-0 items-center justify-center rounded-lg text-white',
                                    action.color
                                ]"
                            >
                                <Icon :icon="action.icon" class="size-5" />
                            </div>
                            <div class="flex-1 space-y-1">
                                <p class="text-sm font-medium leading-none">
                                    {{ action.title }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{ action.description }}
                                </p>
                            </div>
                            <Icon icon="lucide:chevron-right" class="size-4 text-muted-foreground" />
                        </button>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Additional Info Section -->
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            <Card>
                <CardHeader class="pb-3">
                    <CardTitle class="text-base">Upcoming Deadlines</CardTitle>
                </CardHeader>
                <CardContent class="space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="space-y-0.5">
                            <p class="text-sm font-medium">Contract Renewal</p>
                            <p class="text-xs text-muted-foreground">TechPro Inc.</p>
                        </div>
                        <span class="text-xs font-medium text-amber-600 dark:text-amber-500">In 5 days</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="space-y-0.5">
                            <p class="text-sm font-medium">Budget Review</p>
                            <p class="text-xs text-muted-foreground">Q1 2026</p>
                        </div>
                        <span class="text-xs font-medium text-muted-foreground">In 2 weeks</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="space-y-0.5">
                            <p class="text-sm font-medium">Vendor Evaluation</p>
                            <p class="text-xs text-muted-foreground">Annual Review</p>
                        </div>
                        <span class="text-xs font-medium text-muted-foreground">In 1 month</span>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="pb-3">
                    <CardTitle class="text-base">Top Categories</CardTitle>
                </CardHeader>
                <CardContent class="space-y-3">
                    <div class="space-y-2">
                        <div class="flex items-center justify-between text-sm">
                            <span>Office Supplies</span>
                            <span class="font-medium">32%</span>
                        </div>
                        <div class="h-2 w-full rounded-full bg-muted">
                            <div class="h-2 w-[32%] rounded-full bg-primary"></div>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <div class="flex items-center justify-between text-sm">
                            <span>IT Equipment</span>
                            <span class="font-medium">28%</span>
                        </div>
                        <div class="h-2 w-full rounded-full bg-muted">
                            <div class="h-2 w-[28%] rounded-full bg-primary"></div>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <div class="flex items-center justify-between text-sm">
                            <span>Services</span>
                            <span class="font-medium">24%</span>
                        </div>
                        <div class="h-2 w-full rounded-full bg-muted">
                            <div class="h-2 w-[24%] rounded-full bg-primary"></div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card class="md:col-span-2 lg:col-span-1">
                <CardHeader class="pb-3">
                    <CardTitle class="text-base">System Status</CardTitle>
                </CardHeader>
                <CardContent class="space-y-3">
                    <div class="flex items-center gap-3">
                        <div class="size-2 rounded-full bg-green-500"></div>
                        <span class="text-sm">All systems operational</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="size-2 rounded-full bg-green-500"></div>
                        <span class="text-sm">Database connected</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="size-2 rounded-full bg-green-500"></div>
                        <span class="text-sm">API services running</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="size-2 rounded-full bg-amber-500"></div>
                        <span class="text-sm">Email notifications delayed</span>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
