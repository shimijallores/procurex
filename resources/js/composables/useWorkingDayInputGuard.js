import { useCalendarCheck } from "@/composables/useCalendarCheck";
import { ref } from "vue";

const DEFAULT_NOTICE =
    "Date availability is checked against calendar events and holidays.";

const toTitleCase = (value) => {
    return String(value || "")
        .split(/[_\s-]+/)
        .filter(Boolean)
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1).toLowerCase())
        .join(" ");
};

const isWeekendDate = (dateValue) => {
    if (!dateValue) {
        return false;
    }

    const [year, month, day] = String(dateValue)
        .split("-")
        .map((part) => Number(part));

    if (!year || !month || !day) {
        return false;
    }

    const localDate = new Date(year, month - 1, day);
    const dayOfWeek = localDate.getDay();

    return dayOfWeek === 0 || dayOfWeek === 6;
};

export function useWorkingDayInputGuard(form) {
    const { checkDate } = useCalendarCheck();
    const fieldStatuses = ref({});
    const pendingAutoClears = ref({});

    const clearError = (errorKey) => {
        form?.clearErrors?.(errorKey);
    };

    const setError = (errorKey, message) => {
        form?.setError?.(errorKey, message);
    };

    const setStatus = (statusKey, status) => {
        if (!statusKey) {
            return;
        }

        fieldStatuses.value[statusKey] = status;
    };

    const clearStatus = (statusKey) => {
        if (!statusKey || !fieldStatuses.value[statusKey]) {
            return;
        }

        delete fieldStatuses.value[statusKey];
    };

    const formatNonWorkingMessage = (status) => {
        if (!status || status.is_available !== false) {
            return status?.message || DEFAULT_NOTICE;
        }

        if (status.type && status.name) {
            const typeLabel = toTitleCase(status.type);
            const baseLabel = `${typeLabel}: ${status.name}`;
            const normalizedMessage = String(status.message || "").trim();

            if (!normalizedMessage) {
                return `Non-working day - ${baseLabel}`;
            }

            if (
                normalizedMessage
                    .toLowerCase()
                    .includes(String(status.name).toLowerCase())
            ) {
                return `Non-working day - ${baseLabel}`;
            }

            return `Non-working day - ${baseLabel} (${normalizedMessage})`;
        }

        return status.message || "Date must be a working day.";
    };

    const getFieldErrorMessage = (statusKey) => {
        const message = form?.errors?.[statusKey];
        return typeof message === "string" ? message : "";
    };

    const shouldShowNonWorkingNotice = (statusKey, status) => {
        if (!status || status.is_available !== false) {
            return false;
        }

        const fieldError = getFieldErrorMessage(statusKey);
        if (!fieldError) {
            return true;
        }

        return fieldError !== formatNonWorkingMessage(status);
    };

    const enforceWorkingDay = async ({
        dateValue,
        errorKey,
        clearDate,
        statusKey = errorKey,
    }) => {
        if (!dateValue) {
            if (pendingAutoClears.value[statusKey]) {
                delete pendingAutoClears.value[statusKey];
                return false;
            }

            clearError(errorKey);
            clearStatus(statusKey);
            return true;
        }

        if (isWeekendDate(dateValue)) {
            const weekendStatus = {
                is_available: false,
                is_working_day: false,
                type: "weekend",
                name: "Weekend",
                message: "Weekend dates are not allowed.",
            };

            setStatus(statusKey, weekendStatus);
            pendingAutoClears.value[statusKey] = true;
            clearDate();
            setError(errorKey, formatNonWorkingMessage(weekendStatus));
            return false;
        }

        const status = await checkDate(dateValue);
        if (status) {
            setStatus(statusKey, status);
        } else {
            clearStatus(statusKey);
        }

        if (status && status.is_available === false) {
            pendingAutoClears.value[statusKey] = true;
            clearDate();
            setError(errorKey, formatNonWorkingMessage(status));
            return false;
        }

        clearError(errorKey);
        return true;
    };

    const getDateStatus = (statusKey) => fieldStatuses.value[statusKey] ?? null;

    const getDateNotice = (statusKey) => {
        const status = getDateStatus(statusKey);
        if (!status) {
            return DEFAULT_NOTICE;
        }

        if (shouldShowNonWorkingNotice(statusKey, status)) {
            return formatNonWorkingMessage(status);
        }

        return DEFAULT_NOTICE;
    };

    const getDateNoticeClass = (statusKey) => {
        const status = getDateStatus(statusKey);
        if (shouldShowNonWorkingNotice(statusKey, status)) {
            return "text-xs text-destructive";
        }

        return "text-xs text-muted-foreground";
    };

    return {
        enforceWorkingDay,
        getDateStatus,
        getDateNotice,
        getDateNoticeClass,
    };
}
