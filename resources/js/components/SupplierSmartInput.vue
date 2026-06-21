<script setup>
import { computed, ref, watch } from "vue";
import { Icon } from "@iconify/vue";

const props = defineProps({
    modelValue: { type: [String, Number], default: "" },
    suppliers: { type: Array, default: () => [] },
    selectedIds: { type: Array, default: () => [] },
    disabled: { type: Boolean, default: false },
    placeholder: { type: String, default: "Search supplier..." },
});

const emit = defineEmits(["update:modelValue", "select"]);

const inputValue = ref("");
const showDropdown = ref(false);
const focused = ref(false);

watch(
    () => props.modelValue,
    (val) => {
        if (!val) {
            inputValue.value = "";
        }
    },
);

const availableSuppliers = computed(() => {
    return (props.suppliers || []).filter(
        (s) => !props.selectedIds.includes(String(s.id)),
    );
});

const selectedSupplier = computed(() => {
    if (!props.modelValue) return null;
    return (props.suppliers || []).find(
        (s) => String(s.id) === String(props.modelValue),
    );
});

const filteredSuppliers = computed(() => {
    const query = (inputValue.value || "").trim().toLowerCase();
    let list = availableSuppliers.value;

    if (!query) return list;

    return list.filter((s) => s.name.toLowerCase().includes(query));
});

const onFocus = () => {
    focused.value = true;
    showDropdown.value = true;
    if (selectedSupplier.value) {
        inputValue.value = selectedSupplier.value.name;
    }
};

const onBlur = () => {
    setTimeout(() => {
        showDropdown.value = false;
        focused.value = false;
        if (!props.modelValue) {
            inputValue.value = "";
        } else if (selectedSupplier.value) {
            inputValue.value = selectedSupplier.value.name;
        }
    }, 200);
};

const onInput = (e) => {
    const raw = e.target.value;
    inputValue.value = raw;

    if (!raw) {
        emit("update:modelValue", "");
    }
};

const selectSupplier = (supplier) => {
    inputValue.value = supplier.name;
    showDropdown.value = false;
    emit("update:modelValue", String(supplier.id));
    emit("select", supplier);
};

const clearValue = () => {
    inputValue.value = "";
    emit("update:modelValue", "");
};
</script>

<template>
    <div class="relative">
        <div class="relative">
            <input
                :value="inputValue"
                type="text"
                :placeholder="placeholder"
                class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                :disabled="disabled"
                @input="onInput"
                @focus="onFocus"
                @blur="onBlur"
            />
            <button
                v-if="inputValue"
                type="button"
                @click="clearValue"
                class="absolute right-2 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
            >
                <Icon icon="lucide:x" class="h-3.5 w-3.5" />
            </button>
        </div>

        <div
            v-if="showDropdown && filteredSuppliers.length"
            class="absolute z-20 mt-1 w-full overflow-hidden rounded-md border border-input bg-popover shadow-sm"
        >
            <button
                v-for="supplier in filteredSuppliers"
                :key="supplier.id"
                type="button"
                class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm hover:bg-accent"
                @mousedown.prevent="selectSupplier(supplier)"
            >
                <Icon
                    icon="lucide:building-2"
                    class="h-3.5 w-3.5 shrink-0 text-muted-foreground"
                />
                <div class="flex flex-col">
                    <span>{{ supplier.name }}</span>
                    <span
                        v-if="supplier.address"
                        class="text-xs text-muted-foreground truncate"
                    >
                        {{ supplier.address }}
                    </span>
                </div>
            </button>
        </div>
    </div>
</template>
