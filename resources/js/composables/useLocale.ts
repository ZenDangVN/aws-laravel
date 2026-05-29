import { i18n } from '@/plugins/i18n';

export type Locale = 'vi' | 'en' | 'ja';

export const localeLabels: Record<Locale, string> = {
    vi: 'Tiếng Việt',
    en: 'English',
    ja: '日本語',
};

export const localeFlags: Record<Locale, string> = {
    vi: '🇻🇳',
    en: '🇬🇧',
    ja: '🇯🇵',
};

export function useLocale() {
    const locale = i18n.global.locale;

    function updateLocale(newLocale: Locale): void {
        locale.value = newLocale as string;
        localStorage.setItem('locale', newLocale);
        document.cookie = `locale=${newLocale};path=/;max-age=31536000`;
    }

    return { locale, updateLocale };
}
