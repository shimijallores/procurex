<script setup>
import { ref, computed } from "vue";
import { router } from "@inertiajs/vue3";
import Layout from "@/Layout/Layout.vue";
import DeleteModal from "@/components/DeleteModal.vue";
import CalendarIndexHeader from "@/components/calendars/index/CalendarIndexHeader.vue";
import CalendarIndexStats from "@/components/calendars/index/CalendarIndexStats.vue";
import CalendarIndexCalendarCard from "@/components/calendars/index/CalendarIndexCalendarCard.vue";
import CalendarIndexUpcoming from "@/components/calendars/index/CalendarIndexUpcoming.vue";
import CalendarIndexTable from "@/components/calendars/index/CalendarIndexTable.vue";
import CalendarEntryModal from "@/components/calendars/modals/CalendarEntryModal.vue";

defineOptions({
    layout: (h, page) =>
        h(Layout, { breadcrumbs: [{ label: "Calendar" }] }, () => page),
});

const props = defineProps({
    calendars: Array,
    filters: Object,
});

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
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <CalendarIndexHeader :on-add-entry="openCreateModal" />

        <!-- Stats Cards -->
        <CalendarIndexStats :stats="stats" />

        <!-- Calendar and Upcoming Events -->
        <div class="grid gap-6 lg:grid-cols-3">
            <CalendarIndexCalendarCard
                :calendars="calendars"
                :current-month="new Date().getMonth()"
                :current-year="new Date().getFullYear()"
                :on-day-click="handleDayClick"
            />

            <CalendarIndexUpcoming
                :upcoming-entries="upcomingEntries"
                :type-config="typeConfig"
                :on-entry-click="openEditModal"
            />
        </div>

        <!-- All Entries Table -->
        <CalendarIndexTable
            :calendars="calendars"
            :type-config="typeConfig"
            :on-edit="openEditModal"
            :on-delete="openDeleteModal"
            :on-add-entry="openCreateModal"
        />

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
