import { Head, router, useForm, usePage } from '@inertiajs/react';
import {
    ChartPieIcon,
    DatabaseBackupIcon,
    FileUpIcon,
    HandCoinsIcon,
    LandmarkIcon,
    LogOutIcon,
    MonitorSmartphoneIcon,
    MoonIcon,
    SunIcon,
    PiggyBankIcon,
    ReceiptTextIcon,
    RepeatIcon,
    BellIcon,
    TagsIcon,
    UsersIcon,
    type LucideIcon,
} from 'lucide-react';
import type { FormEvent } from 'react';
import { ConfirmAction } from '@/components/confirm-action';
import { FormField, SelectField, SubmitButton } from '@/components/form-field';
import { ListCard, ListRow } from '@/components/list';
import { PageBody, PageHeader, SectionTitle } from '@/components/page-header';
import { PersonAvatar } from '@/components/person-avatar';
import { Button } from '@/components/ui/button';
import { FieldGroup } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Switch } from '@/components/ui/switch';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { applyTheme } from '@/hooks/use-theme';
import { toLatinDigits } from '@/lib/format';
import type { CurrencyDisplay, Theme } from '@/types';

interface Preferences {
    currency_display: CurrencyDisplay;
    persian_digits: boolean;
    theme: Theme;
    session_timeout_minutes: number;
    recurring_mode: string;
}

const LINKS: { label: string; route: string; component: string; icon: LucideIcon }[] = [
    { label: 'گزارش‌ها', route: 'reports.index', component: 'reports/index', icon: ChartPieIcon },
    { label: 'دسته‌بندی‌ها', route: 'categories.index', component: 'categories/index', icon: TagsIcon },
    { label: 'اشخاص', route: 'people.index', component: 'people/index', icon: UsersIcon },
    { label: 'طلب و بدهی', route: 'debts.index', component: 'debts/index', icon: HandCoinsIcon },
    { label: 'وام و اقساط', route: 'loans.index', component: 'loans/index', icon: LandmarkIcon },
    { label: 'چک‌ها', route: 'checks.index', component: 'checks/index', icon: ReceiptTextIcon },
    { label: 'بودجه‌بندی', route: 'budgets.index', component: 'budgets/index', icon: PiggyBankIcon },
    { label: 'یادآوری‌ها', route: 'reminders.index', component: 'reminders/index', icon: BellIcon },
    { label: 'تکرارشونده‌ها', route: 'recurring.index', component: 'recurring/index', icon: RepeatIcon },
];

const DATA_LINKS: typeof LINKS = [
    { label: 'وارد کردن داده', route: 'imports.index', component: 'imports/index', icon: FileUpIcon },
    { label: 'پشتیبان‌گیری', route: 'backups.index', component: 'backups/index', icon: DatabaseBackupIcon },
];

function Links({ links }: { links: typeof LINKS }) {
    return (
        <ListCard>
            {links.map((link) => (
                <ListRow key={link.route} href={route(link.route)} component={link.component} icon={link.icon} title={link.label} chevron />
            ))}
        </ListCard>
    );
}

export default function Settings({ preferences }: { preferences?: Preferences }) {
    const { auth } = usePage().props;
    const form = useForm({
        currency_display: preferences?.currency_display ?? 'both',
        persian_digits: preferences?.persian_digits ?? true,
        theme: preferences?.theme ?? 'system',
        session_timeout_minutes: String(preferences?.session_timeout_minutes ?? 120),
        recurring_mode: preferences?.recurring_mode ?? 'suggestion',
    });

    // The theme applies and saves straight away; the other preferences wait for the save button.
    const changeTheme = (theme: Theme) => {
        if (!preferences) {
            return;
        }

        applyTheme(theme);
        form.setData('theme', theme);
        router.put(route('settings.update'), { ...preferences, theme }, { preserveScroll: true, preserveState: true, only: ['settings', 'preferences', 'flash'] });
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.transform((data) => ({ ...data, session_timeout_minutes: toLatinDigits(data.session_timeout_minutes) }));
        form.put(route('settings.update'), { preserveScroll: true });
    };

    return (
        <>
            <Head title="بیشتر" />
            <PageHeader title="بیشتر" />
            <PageBody>
                {auth.user && (
                    <section className="flex items-center gap-3 rounded-2xl bg-card p-4 ring-1 ring-foreground/5">
                        <PersonAvatar name={auth.user.name} className="size-12 text-lg" />
                        <div className="min-w-0">
                            <p className="truncate font-bold">{auth.user.name}</p>
                            <p className="truncate text-sm text-muted-foreground" dir="ltr">
                                {auth.user.email ?? auth.user.mobile}
                            </p>
                        </div>
                    </section>
                )}

                <Links links={LINKS} />

                <section>
                    <SectionTitle>داده‌ها</SectionTitle>
                    <Links links={DATA_LINKS} />
                </section>

                <section>
                    <SectionTitle>تنظیمات نمایش و امنیت</SectionTitle>
                    <form onSubmit={submit} noValidate className="rounded-2xl bg-card p-4 ring-1 ring-foreground/5">
                        <FieldGroup className="gap-5">
                            <FormField label="نمایش ارز" htmlFor="currency_display" error={form.errors.currency_display}>
                                <SelectField
                                    id="currency_display"
                                    value={form.data.currency_display}
                                    onChange={(event) => form.setData('currency_display', event.target.value as CurrencyDisplay)}
                                    options={[
                                        { value: 'both', label: 'ریال و تومان' },
                                        { value: 'rial', label: 'فقط ریال' },
                                        { value: 'toman', label: 'فقط تومان' },
                                    ]}
                                />
                            </FormField>

                            <FormField label="ظاهر برنامه" error={form.errors.theme}>
                                <ToggleGroup type="single" value={form.data.theme} onValueChange={(value) => value && changeTheme(value as Theme)} className="grid w-full grid-cols-3 gap-2" aria-label="ظاهر برنامه">
                                    {(
                                        [
                                            ['system', 'سیستم', MonitorSmartphoneIcon],
                                            ['light', 'روشن', SunIcon],
                                            ['dark', 'تاریک', MoonIcon],
                                        ] as const
                                    ).map(([value, label, Icon]) => (
                                        <ToggleGroupItem key={value} value={value} className="h-14 flex-col gap-1 rounded-xl border border-border bg-card text-xs data-[state=on]:border-primary data-[state=on]:bg-primary/5 data-[state=on]:font-bold">
                                            <Icon className="size-5" />
                                            {label}
                                        </ToggleGroupItem>
                                    ))}
                                </ToggleGroup>
                            </FormField>

                            <label className="flex min-h-11 items-center justify-between gap-3 text-sm font-medium">
                                نمایش اعداد فارسی
                                <Switch checked={form.data.persian_digits} onCheckedChange={(checked) => form.setData('persian_digits', checked)} />
                            </label>

                            <FormField label="زمان انقضای نشست (دقیقه)" htmlFor="session_timeout_minutes" error={form.errors.session_timeout_minutes}>
                                <Input
                                    id="session_timeout_minutes"
                                    inputMode="numeric"
                                    dir="ltr"
                                    className="text-start"
                                    value={form.data.session_timeout_minutes}
                                    onChange={(event) => form.setData('session_timeout_minutes', toLatinDigits(event.target.value).replace(/\D/g, ''))}
                                />
                            </FormField>

                            <FormField label="تراکنش‌های تکرارشونده" htmlFor="recurring_mode" error={form.errors.recurring_mode}>
                                <SelectField
                                    id="recurring_mode"
                                    value={form.data.recurring_mode}
                                    onChange={(event) => form.setData('recurring_mode', event.target.value)}
                                    options={[
                                        { value: 'suggestion', label: 'فقط پیشنهاد بساز' },
                                        { value: 'automatic', label: 'خودکار، بعد از تایید دستی' },
                                    ]}
                                />
                            </FormField>

                            <SubmitButton size="lg" processing={form.processing}>
                                ذخیره تنظیمات
                            </SubmitButton>
                        </FieldGroup>
                    </form>
                </section>

                <ConfirmAction title="خروج از حساب؟" confirmLabel="خروج" href={route('logout')} destructive>
                    <Button variant="outline" size="lg" className="w-full text-destructive">
                        <LogOutIcon />
                        خروج از حساب
                    </Button>
                </ConfirmAction>
            </PageBody>
        </>
    );
}

