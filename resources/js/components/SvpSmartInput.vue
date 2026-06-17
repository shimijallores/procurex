<script setup>
import { computed, ref, watch } from "vue";
import axios from "axios";
import { Icon } from "@iconify/vue";

const props = defineProps({
    modelValue: { type: String, default: "" },
    disabled: { type: Boolean, default: false },
});

const emit = defineEmits(["update:modelValue", "select"]);

const inputValue = ref(props.modelValue);
const suggestions = ref([]);
const showDropdown = ref(false);
const loading = ref(false);
const focused = ref(false);

const currentPrefix = computed(() => {
    const now = new Date();
    const mm = String(now.getMonth() + 1).padStart(2, "0");
    const yy = String(now.getFullYear()).slice(-2);
    return mm + yy + "-";
});

watch(
    () => props.modelValue,
    (val) => {
        inputValue.value = val;
    },
);

const fetchSuggestions = async () => {
    loading.value = true;
    try {
        const { data } = await axios.get(route("rfqs.recent-svps"));
        suggestions.value = data.svps || [];
    } catch {
        suggestions.value = [];
    } finally {
        loading.value = false;
    }
};

const autoCompleteSvp = (raw) => {
    const val = (raw || "").trim();

    if (!val) return val;

    const fullPattern = /^\d{4}-\d{4}$/;
    if (fullPattern.test(val)) return val;

    const isNumeric = /^\d{1,4}$/;
    if (isNumeric.test(val)) {
        const padded = val.padStart(4, "0");
        return currentPrefix.value + padded;
    }

    return val;
};

const onInput = (e) => {
    const raw = e.target.value;

    if (focused.value && !showDropdown.value && raw.length === 0) {
        showDropdown.value = true;
        fetchSuggestions();
    }

    inputValue.value = raw;
    emit("update:modelValue", raw);
};

const onFocus = async () => {
    focused.value = true;
    showDropdown.value = true;
    await fetchSuggestions();
};

const onBlur = () => {
    setTimeout(() => {
        showDropdown.value = false;
        focused.value = false;

        if (inputValue.value) {
            const completed = autoCompleteSvp(inputValue.value);
            if (completed !== inputValue.value) {
                inputValue.value = completed;
                emit("update:modelValue", completed);
            }
        }
    }, 200);
};

const selectSvp = (svp) => {
    inputValue.value = svp;
    emit("update:modelValue", svp);
    emit("select", svp);
    showDropdown.value = false;
};

const onKeydown = (e) => {
    if (e.key === "Enter") {
        const completed = autoCompleteSvp(inputValue.value);
        if (completed !== inputValue.value) {
            inputValue.value = completed;
            emit("update:modelValue", completed);
        }
        emit("select", inputValue.value);
    }

    if (e.key === "Escape") {
        showDropdown.value = false;
    }
};

const filteredSuggestions = computed(() => {
    const query = (inputValue.value || "").trim().toLowerCase();
    if (!query) return suggestions.value;

    return suggestions.value.filter((svp) =>
        svp.toLowerCase().includes(query),
    );
});
</script>

<template>
    <div class="relative">
        <div class="relative">
            <input
                :value="inputValue"
                type="text"
                placeholder="e.g. 0001"
                class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm font-mono pr-8"
                :disabled="disabled"
                @input="onInput"
                @focus="onFocus"
                @blur="onBlur"
                @keydown="onKeydown"
            />
            <Icon
                v-if="loading"
                icon="lucide:loader-2"
                class="absolute right-2.5 top-1/2 h-4 w-4 -translate-y-1/2 animate-spin text-muted-foreground"
            />
            <Icon
                v-else
                icon="lucide:chevron-down"
                class="absolute right-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"
            />
        </div>

        <div
            v-if="showDropdown && filteredSuggestions.length"
            class="absolute z-20 mt-1 w-full overflow-hidden rounded-md border border-input bg-popover shadow-sm"
        >
            <button
                v-for="svp in filteredSuggestions"
                :key="svp"
                type="button"
                class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm font-mono hover:bg-accent"
                @mousedown.prevent="selectSvp(svp)"
            >
                <Icon
                    icon="lucide:search"
                    class="h-3.5 w-3.5 shrink-0 text-muted-foreground"
                />
                {{ svp }}
            </button>
        </div>

        <p class="mt-1 text-xs text-muted-foreground">
            Type the last 4 digits (e.g. 0001 → {{ currentPrefix }}0001) or pick a recent SVP.
        </p>
    </div>
</template>
