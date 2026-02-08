<script setup>
import { ref, computed } from "vue";
import { router } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import Layout from "@/Layout/Layout.vue";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Calendar } from "@/components/ui/calendar";
import { Badge } from "@/components/ui/badge";
import DeleteModal from "@/components/DeleteModal.vue";
import CalendarEntryModal from "./CalendarEntryModal.vue";
import { CalendarDate } from "@internationalized/date";

defineOptions({
    layout: (h, page) =>
        h(Layout, { breadcrumbs: [{ label: "Calendar" }] }, () => page),
});

const props = defineProps({
    calendars: Array,
    filters: Object,
});

// Calendar state
const selectedDate = ref(
    new CalendarDate(
        new Date().getFullYear(),
        new Date().getMonth() + 1,
        new Date().getDate(),
    ),
);
const currentMonth = ref(new Date().getMonth());
const currentYear = ref(new Date().getFullYear());

// Modal state
const showEntryModal = ref(false);
const showDeleteModal = ref(false);
const entryToEdit = ref(null);
const entryToDelete = ref(null);

// Calendar entries map (date -> entry)
const calendarMap = computed(() => {
    const map = new Map();
    props.calendars.forEach((entry) => {
        map.set(entry.date, entry);
    });
    return map;
});

// Upcoming entries (next 30 days)
const upcomingEntries = computed(() => {
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    return props.calendars
        .filter((entry) => {
            const entryDate = new Date(entry.date);
            entryDate.setHours(0, 0, 0, 0);
            const diffTime = entryDate - today;
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            return diffDays >= 0 && diffDays <= 30;
        })
        .sort((a, b) => new Date(a.date) - new Date(b.date))
        .slice(0, 5);
});

// Stats
const stats = computed(() => {
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    const thisYear = props.calendars.filter((entry) => {
        const entryDate = new Date(entry.date);
        return entryDate.getFullYear() === today.getFullYear();
    });

    return {
        total: props.calendars.length,
        holidays: thisYear.filter((e) => e.type === "holiday").length,
        suspended: thisYear.filter((e) => e.type === "suspended").length,
        upcoming: upcomingEntries.value.length,
    };
});

// Type configuration
const typeConfig = {
    holiday: {
        label: "Holiday",
        color: "text-red-600 bg-red-50 dark:bg-red-950 dark:text-red-400",
        icon: "lucide:party-popper",
    },
    special_workday: {
        label: "Special Workday",
        color: "text-blue-600 bg-blue-50 dark:bg-blue-950 dark:text-blue-400",
        icon: "lucide:briefcase",
    },
    blackout: {
        label: "Blackout",
        color: "text-gray-600 bg-gray-50 dark:bg-gray-950 dark:text-gray-400",
        icon: "lucide:ban",
    },
    suspended: {
        label: "Suspended",
        color: "text-orange-600 bg-orange-50 dark:bg-orange-950 dark:text-orange-400",
        icon: "lucide:cloud-rain",
    },
};

// Get entry for a specific date
const getEntryForDate = (date) => {
    // date is CalendarDate object
    const dateStr = `${date.year}-${String(date.month).padStart(2, "0")}-${String(date.day).padStart(2, "0")}`;
    return calendarMap.value.get(dateStr);
};

// Calendar day customization
const isUnavailable = (date) => {
    // Check if it's a weekend (0 = Sunday, 6 = Saturday)
    const jsDate = new Date(date.year, date.month - 1, date.day);
    const dayOfWeek = jsDate.getDay();
    const isWeekend = dayOfWeek === 0 || dayOfWeek === 6;

    // Check if there's a calendar entry
    const entry = getEntryForDate(date);

    // Unavailable if it's a weekend OR has a calendar entry
    return isWeekend || entry !== undefined;
};

const handleMonthChange = (date) => {
    currentMonth.value = date.month - 1;
    currentYear.value = date.year;

    // Reload data for the new month
    router.get(
        route("calendars.index"),
        { month: date.month, year: date.year },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

const openCreateModal = (date = null) => {
    entryToEdit.value = null;
    if (date) {
        const dateStr = `${date.year}-${String(date.month).padStart(2, "0")}-${String(date.day).padStart(2, "0")}`;
        entryToEdit.value = { date: dateStr };
    }
    showEntryModal.value = true;
};

const openEditModal = (entry) => {
    entryToEdit.value = { ...entry };
    showEntryModal.value = true;
};

const openDeleteModal = (entry) => {
    entryToDelete.value = entry;
    showDeleteModal.value = true;
};

const handleDayClick = (date) => {
    selectedDate.value = date;
    const entry = getEntryForDate(date);
    if (entry) {
        openEditModal(entry);
    } else {
        openCreateModal(date);
    }
};

const formatDate = (dateStr) => {
    return new Date(dateStr).toLocaleDateString("en-US", {
        month: "short",
        day: "numeric",
        year: "numeric",
    });
};

const getDaysUntil = (dateStr) => {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const entryDate = new Date(dateStr);
    entryDate.setHours(0, 0, 0, 0);
    const diffTime = entryDate - today;
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

    if (diffDays === 0) return "Today";
    if (diffDays === 1) return "Tomorrow";
    return `In ${diffDays} days`;
};
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div class="space-y-1">
                <h1 class="text-2xl font-bold tracking-tight md:text-3xl">
                    System Calendar
                </h1>
                <p class="text-muted-foreground">
                    Manage holidays, special workdays, and unavailable dates
                </p>
            </div>
            <Button @click="openCreateModal()">
                <Icon icon="lucide:plus" class="mr-2 h-4 w-4" />
                Add Entry
            </Button>
        </div>

        <!-- Stats Cards -->
        <div class="grid gap-4 md:grid-cols-4">
            <Card>
                <CardHeader
                    class="flex flex-row items-center justify-between space-y-0 pb-2"
                >
                    <CardTitle class="text-sm font-medium"
                        >Total Entries</CardTitle
                    >
                    <Icon
                        icon="lucide:calendar"
                        class="h-4 w-4 text-muted-foreground"
                    />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ stats.total }}</div>
                    <p class="text-xs text-muted-foreground">
                        All calendar entries
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader
                    class="flex flex-row items-center justify-between space-y-0 pb-2"
                >
                    <CardTitle class="text-sm font-medium">Holidays</CardTitle>
                    <Icon
                        icon="lucide:party-popper"
                        class="h-4 w-4 text-muted-foreground"
                    />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ stats.holidays }}</div>
                    <p class="text-xs text-muted-foreground">This year</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader
                    class="flex flex-row items-center justify-between space-y-0 pb-2"
                >
                    <CardTitle class="text-sm font-medium"
                        >Suspended Days</CardTitle
                    >
                    <Icon
                        icon="lucide:cloud-rain"
                        class="h-4 w-4 text-muted-foreground"
                    />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ stats.suspended }}</div>
                    <p class="text-xs text-muted-foreground">This year</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader
                    class="flex flex-row items-center justify-between space-y-0 pb-2"
                >
                    <CardTitle class="text-sm font-medium">Upcoming</CardTitle>
                    <Icon
                        icon="lucide:calendar-clock"
                        class="h-4 w-4 text-muted-foreground"
                    />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ stats.upcoming }}</div>
                    <p class="text-xs text-muted-foreground">Next 30 days</p>
                </CardContent>
            </Card>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <!-- Calendar Display -->
            <Card class="lg:col-span-2">
                <CardHeader>
                    <CardTitle>Calendar</CardTitle>
                    <CardDescription>
                        Click on a date to add or edit an entry
                    </CardDescription>
                </CardHeader>
                <CardContent class="flex justify-center">
                    <Calendar
                        v-model="selectedDate"
                        :is-date-unavailable="isUnavailable"
                        @update:model-value="handleDayClick"
                        class="rounded-md border"
                    />
                </CardContent>
            </Card>

            <!-- Upcoming Entries -->
            <Card>
                <CardHeader>
                    <CardTitle>Upcoming Events</CardTitle>
                    <CardDescription>Next 30 days</CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="upcomingEntries.length > 0" class="space-y-4">
                        <div
                            v-for="entry in upcomingEntries"
                            :key="entry.id"
                            class="flex items-start gap-3 rounded-lg border p-3 hover:bg-accent/50 cursor-pointer transition-colors"
                            @click="openEditModal(entry)"
                        >
                            <div
                                class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg"
                                :class="typeConfig[entry.type].color"
                            >
                                <Icon
                                    :icon="typeConfig[entry.type].icon"
                                    class="h-5 w-5"
                                />
                            </div>
                            <div class="flex-1 space-y-1">
                                <div class="flex items-center justify-between">
                                    <p class="font-medium leading-none">
                                        {{
                                            entry.name ||
                                            typeConfig[entry.type].label
                                        }}
                                    </p>
                                    <Badge variant="secondary" class="text-xs">
                                        {{ getDaysUntil(entry.date) }}
                                    </Badge>
                                </div>
                                <p class="text-xs text-muted-foreground">
                                    {{ formatDate(entry.date) }}
                                </p>
                                <Badge
                                    :class="typeConfig[entry.type].color"
                                    class="text-xs"
                                >
                                    {{ typeConfig[entry.type].label }}
                                </Badge>
                            </div>
                        </div>
                    </div>
                    <div v-else class="flex flex-col items-center gap-2 py-8">
                        <Icon
                            icon="lucide:calendar-x"
                            class="h-12 w-12 text-muted-foreground/50"
                        />
                        <p class="text-sm text-muted-foreground">
                            No upcoming events
                        </p>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- All Entries Table -->
        <Card>
            <CardHeader>
                <CardTitle>All Calendar Entries</CardTitle>
                <CardDescription
                    >Complete list of all dates marked in the
                    system</CardDescription
                >
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
                                    Date
                                </th>
                                <th
                                    class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                                >
                                    Type
                                </th>
                                <th
                                    class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                                >
                                    Name
                                </th>
                                <th
                                    class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                                >
                                    Status
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
                                v-for="entry in calendars"
                                :key="entry.id"
                                class="border-b transition-colors hover:bg-muted/50"
                            >
                                <td class="p-4 align-middle">
                                    <div class="flex items-center gap-2">
                                        <Icon
                                            icon="lucide:calendar"
                                            class="h-4 w-4 text-muted-foreground"
                                        />
                                        <span class="font-medium">{{
                                            formatDate(entry.date)
                                        }}</span>
                                    </div>
                                </td>
                                <td class="p-4 align-middle">
                                    <div class="flex items-center gap-2">
                                        <Icon
                                            :icon="typeConfig[entry.type].icon"
                                            class="h-4 w-4"
                                        />
                                        <Badge
                                            :class="
                                                typeConfig[entry.type].color
                                            "
                                        >
                                            {{ typeConfig[entry.type].label }}
                                        </Badge>
                                    </div>
                                </td>
                                <td class="p-4 align-middle">
                                    {{ entry.name || "-" }}
                                </td>
                                <td class="p-4 align-middle">
                                    <Badge>
                                        {{ entry.type }}
                                    </Badge>
                                </td>
                                <td class="p-4 align-middle text-right">
                                    <div
                                        class="flex items-center justify-end gap-2"
                                    >
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            @click="openEditModal(entry)"
                                        >
                                            <Icon
                                                icon="lucide:pencil"
                                                class="h-4 w-4"
                                            />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            @click="openDeleteModal(entry)"
                                        >
                                            <Icon
                                                icon="lucide:trash-2"
                                                class="h-4 w-4 text-destructive"
                                            />
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="calendars.length === 0">
                                <td colspan="5" class="p-8 text-center">
                                    <div
                                        class="flex flex-col items-center gap-2"
                                    >
                                        <Icon
                                            icon="lucide:inbox"
                                            class="h-12 w-12 text-muted-foreground/50"
                                        />
                                        <p class="text-muted-foreground">
                                            No calendar entries found
                                        </p>
                                        <Button
                                            @click="openCreateModal()"
                                            variant="outline"
                                            size="sm"
                                            class="mt-2"
                                        >
                                            <Icon
                                                icon="lucide:plus"
                                                class="mr-2 h-4 w-4"
                                            />
                                            Add your first entry
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>

        <!-- Modals -->
        <CalendarEntryModal
            v-model:open="showEntryModal"
            :entry="entryToEdit"
        />

        <DeleteModal
            v-model:open="showDeleteModal"
            title="Delete Calendar Entry"
            :description="`Are you sure you want to delete the entry for '${formatDate(entryToDelete?.date)}'? This action cannot be undone.`"
            :delete-url="
                entryToDelete
                    ? route('calendars.destroy', entryToDelete.id)
                    : ''
            "
        />
    </div>
</template>
