<script setup lang="ts">
import { Button } from "@/components/ui/button";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Form } from "@inertiajs/vue3";
import { route } from "ziggy-js";
</script>

<template>
    <div class="min-h-svh w-full bg-muted/30 px-4 py-8 sm:px-6 lg:px-8">
        <div class="mx-auto w-full max-w-md">
            <div
                class="flex min-h-[calc(100svh-8rem)] items-center justify-center"
            >
                <Card class="w-full border-border/60">
                    <CardHeader>
                        <div class="mb-3 flex justify-center">
                            <img
                                src="/images/batangas-seal.png"
                                alt="Province of Batangas Seal"
                                class="h-20 w-20 object-contain"
                            />
                        </div>
                        <CardTitle class="text-center">Procurex</CardTitle>
                        <CardDescription>
                            <p class="text-center">
                                General Services Office<br />
                                Province of Batangas
                            </p>
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Form
                            :action="route('login.login')"
                            method="post"
                            disableWhileProcessing
                            :resetOnError="['password']"
                            #default="{ errors }"
                        >
                            <div class="grid w-full items-center gap-4">
                                <div class="flex flex-col space-y-1.5">
                                    <Label for="email">Email</Label>
                                    <Input
                                        id="email"
                                        type="email"
                                        name="email"
                                        placeholder="superadmin@procurex.com"
                                        required
                                    />
                                </div>
                                <div class="flex flex-col space-y-1.5">
                                    <div class="flex items-center">
                                        <Label for="password">Password</Label>
                                    </div>
                                    <Input
                                        id="password"
                                        name="password"
                                        type="password"
                                        required
                                    />
                                </div>
                            </div>

                            <div
                                v-if="errors.content"
                                v-text="errors.content"
                                class="text-red-500 mt-2 mb-2 text-xs"
                            />
                            <Button type="submit" class="mt-4 w-full"
                                >Sign in</Button
                            >
                        </Form>
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>
