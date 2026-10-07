import { router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { NativeSelect, NativeSelectOption } from '@/components/ui/native-select';
import { cn } from '@/lib/utils';
import { t } from '@/lib/i18n';

/**
 * Switches the interface language straight away. It posts to /locale, which also
 * works for guests (login screen), and the whole page re-renders in the new language.
 */
export function useChangeLanguage() {
    const [processing, setProcessing] = useState(false);

    return {
        processing,
        change: (locale: string) =>
            router.post(route('locale.update'), { locale }, { preserveScroll: true, onStart: () => setProcessing(true), onFinish: () => setProcessing(false) }),
    };
}

/** A compact dropdown of languages, for settings. */
export function LanguageSelect({ className, id = 'language' }: { className?: string; id?: string }) {
    const { locale, locales } = usePage().props;
    const { change, processing } = useChangeLanguage();

    return (
        <NativeSelect id={id} className={className} value={locale.code} disabled={processing} aria-label={t('زبان')} onChange={(event) => change(event.target.value)}>
            {locales.map((language) => (
                <NativeSelectOption key={language.code} value={language.code}>
                    {language.name}
                </NativeSelectOption>
            ))}
        </NativeSelect>
    );
}

/** Plain text links for the sign-in screens, where a dropdown would feel heavy. */
export function LanguageLinks({ className }: { className?: string }) {
    const { locale, locales } = usePage().props;
    const { change, processing } = useChangeLanguage();

    return (
        <div className={cn('flex items-center justify-center gap-1 text-sm', className)} role="group" aria-label={t('زبان')}>
            {locales.map((language, index) => (
                <span key={language.code} className="flex items-center gap-1">
                    {index > 0 && <span className="text-muted-foreground/50">·</span>}
                    <button
                        type="button"
                        lang={language.code}
                        disabled={processing}
                        aria-current={language.code === locale.code ? 'true' : undefined}
                        onClick={() => language.code !== locale.code && change(language.code)}
                        className={cn('rounded-md px-2 py-1.5 transition-colors', language.code === locale.code ? 'font-bold text-foreground' : 'text-muted-foreground active:text-foreground')}
                    >
                        {language.name}
                    </button>
                </span>
            ))}
        </div>
    );
}
