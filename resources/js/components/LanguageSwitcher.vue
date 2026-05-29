<script setup lang="ts">
import { Check, Languages } from 'lucide-vue-next';
import { type Locale, localeFlags, localeLabels, useLocale } from '@/composables/useLocale';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

const { locale, updateLocale } = useLocale();

const locales: Locale[] = ['vi', 'en', 'ja'];
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button variant="ghost" size="sm" class="gap-1.5 px-2">
                <span class="text-base leading-none">{{ localeFlags[locale as Locale] }}</span>
                <Languages class="h-3.5 w-3.5 text-muted-foreground" />
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="min-w-36">
            <DropdownMenuItem
                v-for="loc in locales"
                :key="loc"
                class="cursor-pointer gap-2"
                @click="updateLocale(loc)"
            >
                <span class="text-base">{{ localeFlags[loc] }}</span>
                <span class="flex-1">{{ localeLabels[loc] }}</span>
                <Check v-if="locale === loc" class="h-4 w-4 text-primary" />
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
