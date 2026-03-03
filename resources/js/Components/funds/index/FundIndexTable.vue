<script setup>
import { Link } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import { Button } from "@/components/ui/button";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";

defineProps({
    funds: Object,
    search: String,
    offices: Object,
    fiscalYears: Object,
    selectedOffice: String,
    selectedFiscalYear: String,
});

defineEmits([
    "update:search",
    "update:selected-office",
    "update:selected-fiscal-year",
    "clear",
    "delete",
]);

const getFundTypeBadgeClass = (type) => {
    return type === "project"
        ? "bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300"
        : "bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300";
};
</script>

<template>
    <Card>
        <CardHeader>
            <div
                class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <CardTitle>All Funds</CardTitle>
                    <CardDescription>
                        A list of all funds in the system
                    </CardDescription>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <!-- Office filter -->
                    <select
                        :value="selectedOffice"
                        @change="
                            $emit('update:selected-office', $event.target.value)
                        "
                        class="flex h-10 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                    >
                        <option value="">All Offices</option>
                        <option
                            v-for="(office, id) in offices"
                            :key="id"
                            :value="id"
                        >
                            {{ office }}
                        </option>
                    </select>
                    <!-- Fiscal Year filter -->
                    <select
                        :value="selectedFiscalYear"
                        @change="
                            $emit(
                                'update:selected-fiscal-year',
                                $event.target.value,
                            )
                        "
                        class="flex h-10 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                    >
                        <option value="">All Years</option>
                        <option
                            v-for="(year, id) in fiscalYears"
                            :key="id"
                            :value="id"
                        >
                            {{ year }}
                        </option>
                    </select>
                    <!-- Search -->
                    <div class="relative w-64">
                        <Icon
                            icon="lucide:search"
                            class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                        />
                        <input
                            :value="search"
                            @input="$emit('update:search', $event.target.value)"
                            type="text"
                            placeholder="Search funds..."
                            class="flex h-10 w-full rounded-md border border-input bg-background pl-9 pr-9 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                        />
                        <button
                            v-if="search"
                            @click="$emit('clear')"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                        >
                            <Icon icon="lucide:x" class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </div>
        </CardHeader>
        <CardContent>
            <div class="relative w-full overflow-auto">
                <table class="w-full caption-bottom text-sm">
                    <thead class="border-b">
                        <tr
                            class="border-b transition-colors hover:bg-muted/50"
                        >
                            <th
                                class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                            >
                                Fund Name
                            </th>
                            <th
                                class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                            >
                                Office
                            </th>
                            <th
                                class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                            >
                                Project Code
                            </th>
                            <th
                                class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                            >
                                Type
                            </th>
                            <th
                                class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                            >
                                Fiscal Year
                            </th>
                            <th
                                class="h-12 px-4 text-right align-middle font-medium text-muted-foreground"
                            >
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="[&_tr:last-child]:border-0">
                        <tr
                            v-for="fund in funds.data"
                            :key="fund.id"
                            class="border-b transition-colors hover:bg-muted/50"
                        >
                            <td class="p-4 align-middle font-medium">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10"
                                    >
                                        <Icon
                                            icon="lucide:wallet"
                                            class="h-5 w-5 text-primary"
                                        />
                                    </div>
                                    <div>
                                        <div class="font-medium">
                                            {{ fund.name }}
                                        </div>
                                        <div
                                            class="text-xs text-muted-foreground"
                                        >
                                            Code: {{ fund.code }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="p-4 align-middle">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <Icon
                                            icon="lucide:building-2"
                                            class="h-4 w-4 text-muted-foreground"
                                        />
                                        <span>{{ fund.office.name }}</span>
                                    </div>
                                    <div class="text-xs text-muted-foreground">
                                        {{ fund.project_code?.code }}
                                    </div>
                                </div>
                            </td>
                            <td class="p-4 align-middle">
                                <span class="text-sm">
                                    {{ fund.project_code?.name }}
                                </span>
                            </td>
                            <td class="p-4 align-middle">
                                <span
                                    :class="[
                                        'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold',
                                        getFundTypeBadgeClass(fund.type),
                                    ]"
                                >
                                    {{
                                        fund.type.charAt(0).toUpperCase() +
                                        fund.type.slice(1)
                                    }}
                                </span>
                            </td>
                            <td class="p-4 align-middle">
                                <div class="flex items-center gap-2">
                                    <Icon
                                        icon="lucide:calendar"
                                        class="h-4 w-4 text-muted-foreground"
                                    />
                                    <span>{{ fund.fiscal_year }}</span>
                                </div>
                            </td>
                            <td class="p-4 align-middle text-right">
                                <div
                                    class="flex items-center justify-end gap-2"
                                >
                                    <Link :href="route('funds.show', fund.id)">
                                        <Button variant="ghost" size="sm">
                                            <Icon
                                                icon="lucide:eye"
                                                class="h-4 w-4"
                                            />
                                        </Button>
                                    </Link>
                                    <Link :href="route('funds.edit', fund.id)">
                                        <Button variant="ghost" size="sm">
                                            <Icon
                                                icon="lucide:pencil"
                                                class="h-4 w-4"
                                            />
                                        </Button>
                                    </Link>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        @click="$emit('delete', fund)"
                                    >
                                        <Icon
                                            icon="lucide:trash-2"
                                            class="h-4 w-4 text-destructive"
                                        />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="funds.data.length === 0">
                            <td colspan="6" class="p-8 text-center">
                                <div class="flex flex-col items-center gap-2">
                                    <Icon
                                        icon="lucide:inbox"
                                        class="h-12 w-12 text-muted-foreground/50"
                                    />
                                    <p class="text-muted-foreground">
                                        No funds found
                                    </p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-4 flex items-center justify-between border-t pt-4">
                <div class="text-sm text-muted-foreground">
                    Showing {{ funds.from }} to {{ funds.to }} of
                    {{ funds.total }} funds
                </div>
                <div class="flex items-center gap-1">
                    <template v-for="(link, index) in funds.links" :key="index">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            :class="[
                                'inline-flex h-9 items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors',
                                'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2',
                                link.label.includes('Previous') ||
                                link.label.includes('Next')
                                    ? 'px-3'
                                    : 'w-9',
                                link.active
                                    ? 'bg-primary text-primary-foreground hover:bg-primary/90'
                                    : 'hover:bg-accent hover:text-accent-foreground',
                            ]"
                            preserve-scroll
                            v-html="link.label"
                        />
                        <span
                            v-else
                            :class="[
                                'inline-flex h-9 items-center justify-center rounded-md text-sm font-medium',
                                link.label.includes('Previous') ||
                                link.label.includes('Next')
                                    ? 'px-3'
                                    : 'w-9',
                                'pointer-events-none opacity-50',
                            ]"
                            v-html="link.label"
                        />
                    </template>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
