import { createI18n } from 'vue-i18n';
import en from '@/locales/en';
import ja from '@/locales/ja';
import vi from '@/locales/vi';

function getInitialLocale(): string {
    if (typeof localStorage === 'undefined') return 'vi';
    return (
        localStorage.getItem('locale') ??
        document.cookie.match(/locale=([^;]+)/)?.[1] ??
        'vi'
    );
}

export const i18n = createI18n({
    legacy: false,
    locale: getInitialLocale(),
    fallbackLocale: 'vi',
    messages: { vi, en, ja },
});
