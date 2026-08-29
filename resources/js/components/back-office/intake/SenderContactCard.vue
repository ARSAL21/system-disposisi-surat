<script setup lang="ts">
import { AtSign, Phone, UserRound } from '@lucide/vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { IntakeSubmission } from '@/types';

type SenderContact = Pick<
    IntakeSubmission,
    'contact_name' | 'contact_email' | 'contact_phone'
>;

defineProps<{ submission: SenderContact }>();
</script>

<template>
    <Card class="shadow-sm">
        <CardHeader>
            <CardTitle>Kontak pengirim</CardTitle>
        </CardHeader>
        <CardContent>
            <dl class="grid gap-4 text-sm sm:grid-cols-3 xl:grid-cols-1">
                <div class="flex gap-3">
                    <UserRound
                        class="mt-0.5 size-4 shrink-0 text-blue-600"
                        aria-hidden="true"
                    />
                    <div class="min-w-0">
                        <dt class="text-xs text-muted-foreground">
                            Nama kontak
                        </dt>
                        <dd class="mt-1 font-medium">
                            {{ submission.contact_name }}
                        </dd>
                    </div>
                </div>
                <div class="flex gap-3">
                    <AtSign
                        class="mt-0.5 size-4 shrink-0 text-violet-600"
                        aria-hidden="true"
                    />
                    <div class="min-w-0">
                        <dt class="text-xs text-muted-foreground">Email</dt>
                        <dd class="mt-1 font-medium break-all">
                            {{ submission.contact_email }}
                        </dd>
                    </div>
                </div>
                <div class="flex gap-3">
                    <Phone
                        class="mt-0.5 size-4 shrink-0 text-blue-600"
                        aria-hidden="true"
                    />
                    <div class="min-w-0">
                        <dt class="text-xs text-muted-foreground">Telepon</dt>
                        <dd class="mt-1 font-medium">
                            {{
                                submission.contact_phone ?? 'Tidak dicantumkan'
                            }}
                        </dd>
                    </div>
                </div>
            </dl>
        </CardContent>
    </Card>
</template>
