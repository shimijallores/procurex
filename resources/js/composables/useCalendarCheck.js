import { ref } from "vue";
import axios from "axios";

/**
 * Composable for checking calendar availability of dates
 */
export function useCalendarCheck() {
    const checkedDates = ref({});
    const isChecking = ref(false);

    const checkDate = async (date) => {
        if (!date || checkedDates.value[date]) {
            return checkedDates.value[date];
        }

        isChecking.value = true;
        try {
            const { data } = await axios.post(route("calendars.check-date"), {
                date,
            });

            checkedDates.value[date] = {
                is_available: data.is_available,
                is_working_day: data.is_working_day,
                type: data.type,
                name: data.name,
                message: data.message,
            };

            return checkedDates.value[date];
        } catch (error) {
            console.error("Failed to check date availability:", error);
            return null;
        } finally {
            isChecking.value = false;
        }
    };

    return {
        checkDate,
        checkedDates,
        isChecking,
    };
}
