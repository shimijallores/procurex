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

watch(
    () => props.modelValue,
    (val) => {
        inputValue.value = val;
    },
);

const fetchSuggestions = async () => {
    loading.value = true;
    try {
        const { data } = await axios.get(route("batches.recent-batches"));
        suggestions.value = data.batches || [];
    } catch {
        suggestions.value = [];
    } finally {
        loading.value = false;
    }
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
    }, 200);
};

const selectBatch = (batch) => {
    inputValue.value = batch.batch_no;
    showDropdown.value = false;
    emit("select", batch);
};

const onKeydown = (e) => {
    if (e.key === "Enter") {
        emit("select", { batch_no: inputValue.value });
    }

    if (e.key === "Escape") {
        showDropdown.value = false;
    }
};

const filteredSuggestions = computed(() => {
    const query = (inputValue.value || "").trim().toLowerCase();
    if (!query) return suggestions.value;

    return suggestions.value.filter((batch) =>
        batch.batch_no.toLowerCase().includes(query),
    );
});
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
                placeholder="Search batches..."
                class="flex h-10 w-full rounded-md border border-input bg-background pl-9 pr-9 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                :disabled="disabled"
                @input="onInput"
                @focus="onFocus"
                @blur="onBlur"
                @keydown="onKeydown"
            />
            <button
                v-if="inputValue"
                @click="
                    inputValue = '';
                    emit('update:modelValue', '');
                "
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
            v-if="showDropdown && filteredSuggestions.length"
            class="absolute z-20 mt-1 w-full overflow-hidden rounded-md border border-input bg-popover shadow-sm"
        >
            <button
                v-for="batch in filteredSuggestions"
                :key="batch.id"
                type="button"
                class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm font-mono hover:bg-accent"
                @mousedown.prevent="selectBatch(batch)"
            >
                <Icon
                    icon="lucide:search"
                    class="h-3.5 w-3.5 shrink-0 text-muted-foreground"
                />
                {{ batch.batch_no }}
            </button>
        </div>
    </div>
</template>
