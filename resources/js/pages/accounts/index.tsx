import { Head, Link } from '@inertiajs/react';
import { LandmarkIcon, PlusIcon } from 'lucide-react';
import { BankLogo } from '@/components/bank-logo';
import { EmptyState } from '@/components/empty-state';
import { ListCard, ListRow } from '@/components/list';
import { Money } from '@/components/money';
import { PageBody, PageHeader } from '@/components/page-header';
import { HeroSkeleton, ListSkeleton } from '@/components/skeletons';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useFormat } from '@/lib/format';
import { accountTypes } from '@/lib/labels';
import type { Account } from '@/types';
import { t } from '@/lib/i18n';

function AccountRow({ account }: { account: Account }) {
    const format = useFormat();
    const type = accountTypes[account.type];
    const details = [account.bank_label ?? account.bank_name, account.card_last_four ? `•••• ${format.digits(account.card_last_four)}` : null].filter(Boolean).join(' · ');

    return (
        <ListRow
            href={route('accounts.show', account.id)}
            component="accounts/show"
            pageProps={{ account }}
            media={<BankLogo bank={account.bank} fallback={type.icon} />}
            title={
                <span className="flex items-center gap-2">
                    {account.name}
                    {!account.is_active && <Badge variant="secondary">{t('غیرفعال')}</Badge>}
                </span>
            }
            subtitle={details || t(type.label)}
            trailing={<Money amount={account.current_balance} className="text-[0.95rem] font-semibold" />}
            chevron
        />
    );
}

export default function AccountsIndex({ accounts }: { accounts?: Account[] }) {
    const format = useFormat();
    const active = accounts?.filter((account) => account.is_active) ?? [];
    const total = active.reduce((sum, account) => sum + account.current_balance, 0);

    return (
        <>
            <Head title={t('حساب‌ها')} />
            <PageHeader
                title={t('حساب‌ها')}
                actions={
                    <Button size="icon" variant="ghost" className="rounded-full" asChild>
                        <Link href={route('accounts.create')} component="accounts/form" aria-label={t('حساب جدید')}>
                            <PlusIcon className="size-6" />
                        </Link>
                    </Button>
                }
            />
            <PageBody>
                {!accounts ? (
                    <>
                        <HeroSkeleton className="h-28" />
                        <ListSkeleton rows={4} />
                    </>
                ) : accounts.length === 0 ? (
                    <EmptyState
                        icon={LandmarkIcon}
                        title={t('هنوز حسابی ندارید')}
                        description={t('حساب بانکی، کارت، کیف پول یا پول نقد خود را اضافه کنید.')}
                        action={
                            <Button asChild>
                                <Link href={route('accounts.create')} component="accounts/form">
                                    {t('افزودن حساب')}
                                </Link>
                            </Button>
                        }
                    />
                ) : (
                    <>
                        <section className="rounded-3xl bg-card p-5 ring-1 ring-foreground/5">
                            <p className="text-sm text-muted-foreground">{t('جمع موجودی :count حساب فعال', { count: format.number(active.length) })}</p>
                            <Money amount={total} withSecondary className="mt-1 text-[1.75rem] font-extrabold" />
                        </section>

                        <ListCard>
                            {accounts.map((account) => (
                                <AccountRow key={account.id} account={account} />
                            ))}
                        </ListCard>
                    </>
                )}
            </PageBody>
        </>
    );
}
