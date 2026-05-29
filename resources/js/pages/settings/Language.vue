<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import Heading from '@/components/Heading.vue';
import { type Locale, localeFlags, localeLabels, useLocale } from '@/composables/useLocale';
import { edit } from '@/routes/language';

const { t } = useI18n();
const { locale, updateLocale } = useLocale();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Language settings',
                href: edit(),
            },
        ],
    },
});

const locales: Locale[] = ['vi', 'en', 'ja'];
</script>

<template>
    <Head title="Language settings" />

    <h1 class="sr-only">Language settings</h1>

    <div class="space-y-6">
        <Heading
            variant="small"
            :title="t('settings.language.title')"
            :description="t('settings.language.description')"
        />

        <div class="grid grid-cols-3 gap-3">
            <button
                v-for="loc in locales"
                :key="loc"
                class="flex flex-col items-center gap-2 rounded-lg border-2 p-4 transition-colors hover:bg-muted/50"
                :class="locale === loc ? 'border-primary bg-muted' : 'border-border'"
                @click="updateLocale(loc)"
            >
                <span class="text-3xl">{{ localeFlags[loc] }}</span>
                <span class="text-sm font-medium">{{ localeLabels[loc] }}</span>
            </button>
        </div>
    </div>
</template>
