<script setup>
import { ref, computed } from "vue";
import { router } from "@inertiajs/vue3";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";
import { Calendar } from "@/components/ui/calendar";
import { CalendarDate } from "@internationalized/date";

const props = defineProps({
    calendars: {
        type: Array,
        required: true,
    },
    currentMonth: {
        type: Number,
        required: true,
    },
    currentYear: {
        type: Number,
        required: true,
    },
    onDayClick: {
        type: Function,
        required: true,
    },
});

const selectedDate = ref(
    new CalendarDate(
        new Date().getFullYear(),
        new Date().getMonth() + 1,
        new Date().getDate(),
    ),
);

// Calendar entries map (date -> entry) - REACTIVE
const calendarMap = computed(() => {
    const map = new Map();
    props.calendars.forEach((entry) => {
        map.set(entry.date, entry);
    });
    return map;
});

// Get entry for a specific date
const getEntryForDate = (date) => {
    const dateStr = `${date.year}-${String(date.month).padStart(2, "0")}-${String(date.day).padStart(2, "0")}`;
    return calendarMap.value.get(dateStr);
};

// Calendar day customization
const isUnavailable = (date) => {
    const jsDate = new Date(date.year, date.month - 1, date.day);
    const dayOfWeek = jsDate.getDay();
    const isWeekend = dayOfWeek === 0 || dayOfWeek === 6;
    const entry = getEntryForDate(date);
    return isWeekend || entry !== undefined;
};

const handleMonthChange = (date) => {
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

const handleDayClick = (date) => {
    selectedDate.value = date;
    props.onDayClick(date);
};
</script>

<template>
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
</template>
