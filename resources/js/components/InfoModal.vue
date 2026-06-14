<script setup>
import { Icon } from "@iconify/vue";
import { Button } from "@/components/ui/button";
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from "@/components/ui/dialog";

defineProps({
    title: {
        type: String,
        required: true,
    },
    sections: {
        type: Array,
        required: true,
    },
});
</script>

<template>
    <Dialog>
        <DialogTrigger as-child>
            <Button
                variant="ghost"
                size="icon"
                class="h-6 w-6 rounded-full text-muted-foreground hover:text-foreground"
            >
                <Icon icon="lucide:info" class="h-4 w-4" />
            </Button>
        </DialogTrigger>
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <div class="flex items-center gap-3">
                    <div>
                        <DialogTitle class="text-lg">
                            {{ title }}
                        </DialogTitle>
                        <DialogDescription>
                            A quick overview of how this module works.
                        </DialogDescription>
                    </div>
                </div>
            </DialogHeader>
            <div class="border-t" />
            <div class="max-h-[60vh] space-y-5 overflow-y-auto pr-1">
                <div v-for="(section, i) in sections" :key="i">
                    <div class="flex items-center gap-2">
                        <Icon
                            v-if="section.icon"
                            :icon="section.icon"
                            class="h-4 w-4 shrink-0 text-primary"
                        />
                        <h4 class="text-sm font-semibold text-foreground">
                            {{ section.heading }}
                        </h4>
                    </div>
                    <p
                        class="mt-1.5 text-sm leading-relaxed text-muted-foreground"
                    >
                        {{ section.content }}
                    </p>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
