import { Head } from '@inertiajs/react';
import { ArrowDownToLineIcon, ArrowUpFromLineIcon, FileSpreadsheetIcon, FileTextIcon, LandmarkIcon, ReceiptTextIcon, TagsIcon, UsersIcon, WalletIcon, type LucideIcon } from 'lucide-react';
import { ListCard, ListRow } from '@/components/list';
import { Money } from '@/components/money';
import { PageBody, PageHeader, SectionTitle } from '@/components/page-header';
import { t, tr } from '@/lib/i18n';

const REPORTS: { label: string; route: string; component: string; icon: LucideIcon }[] = [
    { label: tr('حساب‌ها'), route: 'reports.accounts', component: 'reports/cards', icon: WalletIcon },
    { label: tr('دسته‌بندی‌ها'), route: 'reports.categories', component: 'reports/cards', icon: TagsIcon },
    { label: tr('اشخاص'), route: 'reports.people', component: 'reports/cards', icon: UsersIcon },
    { label: tr('وام‌ها'), route: 'reports.loans', component: 'reports/cards', icon: LandmarkIcon },
    { label: tr('چک‌ها'), route: 'reports.checks', component: 'reports/cards', icon: ReceiptTextIcon },
];

export default function ReportsIndex({ monthlyIncome, monthlyExpense }: { monthlyIncome: number; monthlyExpense: number }) {
    return (
        <>
            <Head title={t('گزارش‌ها')} />
            <PageHeader title={t('گزارش‌ها')} back={route('settings.index')} backComponent="settings/index" />
            <PageBody>
                <section className="grid grid-cols-2 gap-3">
                    <div className="rounded-2xl bg-card p-4 ring-1 ring-foreground/5">
                        <p className="flex items-center gap-1.5 text-xs text-muted-foreground">
                            <ArrowDownToLineIcon className="size-3.5 text-income" />
                            {t('درآمد این ماه')}
                        </p>
                        <Money amount={monthlyIncome} tone="income" className="mt-2 text-lg font-bold" />
                    </div>
                    <div className="rounded-2xl bg-card p-4 ring-1 ring-foreground/5">
                        <p className="flex items-center gap-1.5 text-xs text-muted-foreground">
                            <ArrowUpFromLineIcon className="size-3.5 text-expense" />
                            {t('هزینه این ماه')}
                        </p>
                        <Money amount={monthlyExpense} tone="expense" className="mt-2 text-lg font-bold" />
                    </div>
                </section>

                <section>
                    <SectionTitle>{t('گزارش‌ها')}</SectionTitle>
                    <ListCard>
                        {REPORTS.map((report) => (
                            <ListRow key={report.route} href={route(report.route)} component={report.component} icon={report.icon} title={t(report.label)} chevron />
                        ))}
                    </ListCard>
                </section>

                <section>
                    <SectionTitle>{t('خروجی')}</SectionTitle>
                    <ListCard>
                        {/* Downloads are plain links: they are files, not Inertia pages. */}
                        <a href={route('exports.transactions.csv')} className="block">
                            <ListRow icon={FileTextIcon} title={t('فایل CSV تراکنش‌ها')} subtitle={t('برای باز کردن در هر برنامه جدول‌بندی')} />
                        </a>
                        <a href={route('exports.transactions.xlsx')} className="block">
                            <ListRow icon={FileSpreadsheetIcon} title={t('فایل Excel تراکنش‌ها')} subtitle={t('فرمت xlsx')} />
                        </a>
                    </ListCard>
                </section>
            </PageBody>
        </>
    );
}
