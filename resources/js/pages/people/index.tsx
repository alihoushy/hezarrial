import { Head, Link } from '@inertiajs/react';
import { PlusIcon, UsersIcon } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import { ListCard, ListRow } from '@/components/list';
import { Money } from '@/components/money';
import { PageBody, PageHeader } from '@/components/page-header';
import { PersonAvatar } from '@/components/person-avatar';
import { ListSkeleton } from '@/components/skeletons';
import { Button } from '@/components/ui/button';
import type { Person } from '@/types';
import { t } from '@/lib/i18n';

type PersonWithBalance = Person & { balance: number };

export default function PeopleIndex({ people }: { people?: PersonWithBalance[] }) {
    return (
        <>
            <Head title={t('اشخاص')} />
            <PageHeader
                title={t('اشخاص')}
                back={route('settings.index')}
                backComponent="settings/index"
                actions={
                    <Button size="icon" variant="ghost" className="rounded-full" asChild>
                        <Link href={route('people.create')} component="people/form" aria-label={t('شخص جدید')}>
                            <PlusIcon className="size-6" />
                        </Link>
                    </Button>
                }
            />
            <PageBody>
                {!people ? (
                    <ListSkeleton rows={6} />
                ) : people.length === 0 ? (
                    <EmptyState
                        icon={UsersIcon}
                        title={t('هنوز شخصی ثبت نشده')}
                        description={t('اشخاص را برای ثبت طلب، بدهی و چک اضافه کنید.')}
                        action={
                            <Button asChild>
                                <Link href={route('people.create')} component="people/form">
                                    {t('افزودن شخص')}
                                </Link>
                            </Button>
                        }
                    />
                ) : (
                    <ListCard>
                        {people.map((person) => (
                            <ListRow
                                key={person.id}
                                href={route('people.show', person.id)}
                                component="people/show"
                                media={<PersonAvatar name={person.full_name} />}
                                title={person.full_name}
                                subtitle={person.mobile}
                                trailing={
                                    person.balance !== 0 ? (
                                        <>
                                            <Money amount={Math.abs(person.balance)} tone={person.balance > 0 ? 'income' : 'expense'} className="text-sm font-semibold" />
                                            <span className="text-xs text-muted-foreground">{person.balance > 0 ? t('طلبکارم') : t('بدهکارم')}</span>
                                        </>
                                    ) : undefined
                                }
                                chevron
                            />
                        ))}
                    </ListCard>
                )}
            </PageBody>
        </>
    );
}
