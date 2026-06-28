<script setup>
import { computed, ref, watch } from "vue";
import axios from "axios";
import { route } from "ziggy-js";
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
const selecting = ref(false);

const currentPrefix = computed(() => {
    const now = new Date();
    const mm = String(now.getMonth() + 1).padStart(2, "0");
    const yy = String(now.getFullYear()).slice(-2);
    return `${mm}${yy}-`;
});

watch(
    () => props.modelValue,
    (val) => {
        inputValue.value = val;
    },
);

const fetchSuggestions = async (query) => {
    loading.value = true;
    try {
        const { data } = await axios.get(route("rfqs.suggest-prs"), {
            params: { q: query || "" },
        });
        suggestions.value = data.prs || [];
    } catch {
        suggestions.value = [];
    } finally {
        loading.value = false;
    }
};

const autoCompletePrNo = (raw) => {
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
    inputValue.value = raw;
    emit("update:modelValue", raw);

    if (focused.value) {
        fetchSuggestions(raw);
        showDropdown.value = true;
    }
};

const onFocus = async () => {
    focused.value = true;
    if (!selecting.value) {
        showDropdown.value = true;
        await fetchSuggestions(inputValue.value);
    }
};

const onBlur = () => {
    setTimeout(() => {
        showDropdown.value = false;
        focused.value = false;

        if (inputValue.value) {
            const completed = autoCompletePrNo(inputValue.value);
            if (completed !== inputValue.value) {
                inputValue.value = completed;
                emit("update:modelValue", completed);
            }
        }
    }, 200);
};

const selectPr = (pr) => {
    selecting.value = true;
    inputValue.value = pr.pr_no;
    showDropdown.value = false;
    emit("update:modelValue", pr.pr_no);
    emit("select", pr);
    setTimeout(() => {
        selecting.value = false;
    }, 300);
};

const onKeydown = (e) => {
    if (e.key === "Enter") {
        const completed = autoCompletePrNo(inputValue.value);
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

const clearInput = () => {
    inputValue.value = "";
    emit("update:modelValue", "");
    emit("select", null);
};
</script>

<template>
    <div class="relative">
        <div class="relative">
            <Icon
                icon="lucide:search"
                class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"
            />
            <input
                :value="inputValue"
                type="text"
                placeholder="Search PR number..."
                class="flex h-10 w-full rounded-md border border-input bg-background pl-9 pr-9 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                :disabled="disabled"
                @input="onInput"
                @focus="onFocus"
                @blur="onBlur"
                @keydown="onKeydown"
            />
            <button
                v-if="inputValue"
                @click="clearInput"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
            >
                <Icon icon="lucide:x" class="h-4 w-4" />
            </button>
            <Icon
                v-else-if="loading"
                icon="lucide:loader-2"
                class="absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 animate-spin text-muted-foreground"
            />
            <Icon
                v-else
                icon="lucide:chevron-down"
                class="absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"
            />
        </div>

        <div
            v-if="showDropdown && suggestions.length"
            class="absolute z-20 mt-1 w-full overflow-hidden rounded-md border border-input bg-popover shadow-sm"
        >
            <button
                v-for="pr in suggestions"
                :key="pr.id"
                type="button"
                class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm hover:bg-accent"
                @mousedown.prevent="selectPr(pr)"
            >
                <Icon
                    icon="lucide:file-plus-2"
                    class="h-3.5 w-3.5 shrink-0 text-muted-foreground"
                />
                <div class="min-w-0 flex-1">
                    <div class="font-medium">{{ pr.pr_no }}</div>
                    <div class="truncate text-xs text-muted-foreground">
                        {{ pr.office?.name || "Unknown Office" }}
                    </div>
                </div>
            </button>
        </div>
    </div>
</template>
