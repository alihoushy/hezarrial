import { Head, Link } from '@inertiajs/react';
import { PlusIcon, TagsIcon } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import { ListCard, ListRow } from '@/components/list';
import { PageBody, PageHeader } from '@/components/page-header';
import { ListSkeleton } from '@/components/skeletons';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { Category } from '@/types';
import { t } from '@/lib/i18n';

function Group({ title, items }: { title: string; items: Category[] }) {
    if (items.length === 0) {
        return null;
    }

    return (
        <section>
            <h2 className="px-1 pb-2 text-sm font-semibold text-muted-foreground">{title}</h2>
            <ListCard>
                {items.map((category) => (
                    <ListRow
                        key={category.id}
                        href={route('categories.edit', category.id)}
                        component="categories/form"
                        pageProps={{ category }}
                        media={
                            <span className="flex size-10 shrink-0 items-center justify-center rounded-full bg-muted">
                                <span className="size-4 rounded-full" style={{ background: category.color || 'var(--muted-foreground)' }} />
                            </span>
                        }
                        title={
                            <span className="flex items-center gap-2">
                                {category.name}
                                {!category.is_active && <Badge variant="secondary">{t('غیرفعال')}</Badge>}
                            </span>
                        }
                        chevron
                    />
                ))}
            </ListCard>
        </section>
    );
}

export default function CategoriesIndex({ categories }: { categories?: Category[] }) {
    return (
        <>
            <Head title={t('دسته‌بندی‌ها')} />
            <PageHeader
                title={t('دسته‌بندی‌ها')}
                back={route('settings.index')}
                backComponent="settings/index"
                actions={
                    <Button size="icon" variant="ghost" className="rounded-full" asChild>
                        <Link href={route('categories.create')} component="categories/form" aria-label={t('دسته‌بندی جدید')}>
                            <PlusIcon className="size-6" />
                        </Link>
                    </Button>
                }
            />
            <PageBody>
                {!categories ? (
                    <ListSkeleton rows={6} />
                ) : categories.length === 0 ? (
                    <EmptyState
                        icon={TagsIcon}
                        title={t('دسته‌بندی‌ای ثبت نشده')}
                        description={t('برای ثبت درآمد و هزینه به دسته‌بندی نیاز دارید.')}
                        action={
                            <Button asChild>
                                <Link href={route('categories.create')} component="categories/form">
                                    {t('افزودن دسته‌بندی')}
                                </Link>
                            </Button>
                        }
                    />
                ) : (
                    <>
                        <Group title={t('هزینه')} items={categories.filter((category) => category.type === 'expense')} />
                        <Group title={t('درآمد')} items={categories.filter((category) => category.type === 'income')} />
                    </>
                )}
            </PageBody>
        </>
    );
}
