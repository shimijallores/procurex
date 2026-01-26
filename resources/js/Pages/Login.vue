<script setup lang="ts">
import DarkModeButton from "@/components/DarkModeButton.vue";
import { Button } from "@/components/ui/button";
import {
    Card,
    CardAction,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Form } from "@inertiajs/vue3";
import { route } from "ziggy-js";
</script>

<template >
    <div class="w-full h-svh flex justify-center items-center bg-black">
        <Card class="w-full max-w-sm mx-auto">
            <CardHeader>
                <CardTitle>Login to your account</CardTitle>
                <CardDescription>
                    Enter your email below to login to Procurex
                </CardDescription>
                <CardAction>
                    <DarkModeButton />
                </CardAction>
            </CardHeader>
            <CardContent>
                <Form :action="route('login.login')" method="post" disableWhileProcessing :resetOnError="['password']" #default="{ errors }">
                    <div class="grid w-full items-center gap-4">
                        <div class="flex flex-col space-y-1.5"> 
                            <Label for="email">Email</Label>
                            <Input
                                id="email"
                                type="email"
                                name="email"
                                :value="'superadmin@procurex.com'"
                                placeholder="superadmin@procurex.com"
                                required
                            />
                        </div>
                        <div class="flex flex-col space-y-1.5">
                            <div class="flex items-center">
                                <Label for="password">Password</Label>
                            </div>
                            <Input id="password" name="password" type="password" required/>
                        </div>
                    </div>

                    <div v-if="errors.content" v-text="errors.content" class="text-red-500 mt-2 mb-2 text-xs" />
                    <Button type="submit" class="w-full mt-4"> Login </Button>
                </Form>
            </CardContent>
        </Card>
    </div>
</template>
