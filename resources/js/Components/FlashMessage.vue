<script setup>
import { ref, watch } from "vue";
import { usePage } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";

const page = usePage();
const messages = ref([]);
let nextId = 0;

// Track the previous flash state to avoid duplicate messages
let previousFlash = { success: null, error: null };

// Watch for flash messages
watch(
    () => page.props.flash,
    (flash) => {
        // Only add message if the flash content actually changed
        if (flash?.success && flash.success !== previousFlash.success) {
            addMessage("success", flash.success);
            previousFlash.success = flash.success;
        }
        if (flash?.error && flash.error !== previousFlash.error) {
            addMessage("error", flash.error);
            previousFlash.error = flash.error;
        }
        // Clear flash when it becomes empty
        if (!flash?.success && !flash?.error) {
            previousFlash = { success: null, error: null };
        }
    },
    { deep: true },
);

const addMessage = (type, text) => {
    const id = nextId++;
    messages.value.push({ id, type, text });

    // Auto-remove after 5 seconds
    setTimeout(() => {
        removeMessage(id);
    }, 5000);
};

const removeMessage = (id) => {
    const index = messages.value.findIndex((m) => m.id === id);
    if (index !== -1) {
        messages.value.splice(index, 1);
    }
};

const getMessageConfig = (type) => {
    const configs = {
        success: {
            icon: "lucide:circle-check",
            bgClass:
                "bg-green-50 border-green-200 dark:bg-green-950 dark:border-green-800",
            iconClass: "text-green-600 dark:text-green-400",
            textClass: "text-green-900 dark:text-green-100",
        },
        error: {
            icon: "lucide:circle-x",
            bgClass:
                "bg-red-50 border-red-200 dark:bg-red-950 dark:border-red-800",
            iconClass: "text-red-600 dark:text-red-400",
            textClass: "text-red-900 dark:text-red-100",
        },
        info: {
            icon: "lucide:info",
            bgClass:
                "bg-blue-50 border-blue-200 dark:bg-blue-950 dark:border-blue-800",
            iconClass: "text-blue-600 dark:text-blue-400",
            textClass: "text-blue-900 dark:text-blue-100",
        },
        warning: {
            icon: "lucide:triangle-alert",
            bgClass:
                "bg-yellow-50 border-yellow-200 dark:bg-yellow-950 dark:border-yellow-800",
            iconClass: "text-yellow-600 dark:text-yellow-400",
            textClass: "text-yellow-900 dark:text-yellow-100",
        },
    };
    return configs[type] || configs.info;
};
</script>

<template>
    <div
        class="fixed top-4 right-4 z-50 flex flex-col gap-2 pointer-events-none"
    >
        <TransitionGroup
            enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="translate-x-full opacity-0"
            enter-to-class="translate-x-0 opacity-100"
            leave-active-class="transition-all duration-200 ease-in"
            leave-from-class="translate-x-0 opacity-100"
            leave-to-class="translate-x-full opacity-0"
        >
            <div
                v-for="message in messages"
                :key="message.id"
                :class="[
                    'flex items-center justify-center gap-3 p-4 rounded-lg border shadow-lg pointer-events-auto min-w-[320px] max-w-md',
                    getMessageConfig(message.type).bgClass,
                ]"
            >
                <Icon
                    :icon="getMessageConfig(message.type).icon"
                    :class="[
                        'h-5 w-5 flex-shrink-0 mt-0.5',
                        getMessageConfig(message.type).iconClass,
                    ]"
                />
                <div class="flex-1 space-y-1">
                    <p
                        :class="[
                            'text-sm font-medium leading-none',
                            getMessageConfig(message.type).textClass,
                        ]"
                    >
                        {{ message.text }}
                    </p>
                </div>
                <button
                    @click="removeMessage(message.id)"
                    :class="[
                        'flex-shrink-0 rounded-md p-1 hover:bg-black/5 dark:hover:bg-white/5 transition-colors',
                        getMessageConfig(message.type).iconClass,
                    ]"
                >
                    <Icon icon="lucide:x" class="h-4 w-4" />
                </button>
            </div>
        </TransitionGroup>
    </div>
</template>
