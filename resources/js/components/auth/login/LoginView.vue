<script setup>
import { Button } from "@/components/ui/button";
import { Checkbox } from "@/components/ui/checkbox";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Form } from "@inertiajs/vue3";

defineProps({
    errors: {
        type: Object,
        default: () => ({}),
    },
});
</script>

<template>
    <div class="flex min-h-svh">
        <!-- Left: Hero Image -->
        <div
            class="relative hidden w-[60%] overflow-hidden lg:block"
        >
            <img
                src="/images/capitol.jpg"
                alt="Batangas Capitol"
                class="absolute inset-0 h-full w-full object-cover"
            />
            <div
                class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/40 to-black/30"
            />
            <div
                class="relative flex h-full flex-col justify-between p-10"
            >
                <div class="flex items-center gap-3">
                    <img
                        src="/images/batangas-seal.png"
                        alt="Seal"
                        class="h-10 w-10"
                    />
                    <span class="text-sm font-medium text-white/80"
                        >Province of Batangas</span
                    >
                </div>
                <div />
                <div class="max-w-md">
                    <h1
                        class="text-4xl font-bold leading-tight text-white"
                    >
                        Procurement System
                    </h1>
                    <p class="mt-3 text-base text-white/70">
                        Government procurement through
                        digital transformation.
                    </p>
                    <div
                        class="mt-4 text-xs text-white/40"
                    >
                        &copy; {{ new Date().getFullYear() }} Province
                        of Batangas
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Login Form -->
        <div
            class="flex w-full items-center justify-center px-6 py-12 lg:w-[40%]"
        >
            <div class="w-full max-w-sm">
                <div class="mb-8 text-center">
                    <img
                        src="/images/batangas-seal.png"
                        alt="Seal"
                        class="mx-auto h-14 w-14"
                    />
                    <h1
                        class="mt-3 text-xl font-semibold text-foreground"
                    >
                        Procurement System
                    </h1>
                    <p
                        class="mt-0.5 text-sm text-muted-foreground"
                    >
                        Province of Batangas
                    </p>

                </div>

                <Form
                    :action="route('login.login')"
                    method="post"
                    disableWhileProcessing
                    :resetOnError="['password']"
                    #default="{ errors }"
                >
                    <div class="grid gap-5">
                        <div class="grid gap-2">
                            <Label for="email">Email</Label>
                            <Input
                                id="email"
                                type="email"
                                name="email"
                                autofocus
                                required
                            />
                        </div>
                        <div class="grid gap-2">
                            <div
                                class="flex items-center justify-between"
                            >
                                <Label for="password"
                                    >Password</Label
                                >
                            </div>
                            <Input
                                id="password"
                                name="password"
                                type="password"
                                required
                            />
                        </div>
                    </div>

                    <div class="mt-3 flex items-center gap-2">
                        <Checkbox id="remember" name="remember" />
                        <Label for="remember" class="text-sm font-normal"
                            >Remember me</Label
                        >
                    </div>

                    <div
                        v-if="errors.content"
                        v-text="errors.content"
                        class="mt-3 text-xs text-red-500"
                    />
                    <Button type="submit" class="mt-4 w-full"
                        >Sign in</Button
                    >
                </Form>

                <p class="mt-6 text-center text-xs text-muted-foreground">
                    Having trouble signing in?
                    <a
                        href="mailto:admin@batangas.gov.ph"
                        class="underline underline-offset-2 hover:text-foreground"
                    >
                        Contact administrator
                    </a>
                </p>
            </div>
        </div>
    </div>
</template>
