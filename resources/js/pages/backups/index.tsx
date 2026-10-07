import { Head, router, useForm, usePage } from '@inertiajs/react';
import { DatabaseBackupIcon, DownloadIcon, PlusIcon, Trash2Icon, UploadIcon } from 'lucide-react';
import { useEffect, useState, type FormEvent } from 'react';
import { toast } from 'sonner';
import { ConfirmAction } from '@/components/confirm-action';
import { EmptyState } from '@/components/empty-state';
import { FormField, SubmitButton } from '@/components/form-field';
import { ListCard, ListRow } from '@/components/list';
import { PageBody, PageHeader } from '@/components/page-header';
import { PasswordInput } from '@/components/password-input';
import { ResponsiveModal } from '@/components/responsive-modal';
import { ListSkeleton } from '@/components/skeletons';
import { Button } from '@/components/ui/button';
import { FieldGroup } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { useFormat } from '@/lib/format';
import type { Backup } from '@/types';
import { t } from '@/lib/i18n';

/** Downloading is a real file response, so the password is sent with a plain form post. */
function DownloadSheet({ backup, onClose }: { backup: Backup | null; onClose: () => void }) {
    const { csrf_token } = usePage().props;

    return (
        <ResponsiveModal open={backup !== null} onOpenChange={(next) => !next && onClose()} title={t('دانلود پشتیبان')} description={backup?.file_name}>
            {backup && (
                <form method="post" action={route('backups.download', backup.id)} className="pb-4" onSubmit={() => setTimeout(onClose, 400)}>
                    <input type="hidden" name="_token" value={csrf_token} />
                    <FieldGroup className="gap-5">
                        <FormField label={t('رمز عبور')} htmlFor="download_password" description={t('برای دانلود، رمز عبور خود را تایید کنید.')}>
                            <PasswordInput id="download_password" name="password" autoComplete="current-password" required />
                        </FormField>
                        <SubmitButton size="lg">
                            <DownloadIcon />
                            {t('دانلود')}
                        </SubmitButton>
                    </FieldGroup>
                </form>
            )}
        </ResponsiveModal>
    );
}

function RestoreSheet({ open, onOpenChange }: { open: boolean; onOpenChange: (open: boolean) => void }) {
    const form = useForm<{ backup: File | null; password: string }>({ backup: null, password: '' });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(route('backups.restore'), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                onOpenChange(false);
            },
            onFinish: () => form.reset('password'),
        });
    };

    return (
        <ResponsiveModal open={open} onOpenChange={onOpenChange} title={t('بازیابی پشتیبان')} description={t('همه داده‌های مالی فعلی با محتوای فایل جایگزین می‌شود.')}>
            <form onSubmit={submit} noValidate className="pb-4">
                <FieldGroup className="gap-5">
                    <FormField label={t('فایل پشتیبان (JSON)')} htmlFor="restore_file" error={form.errors.backup}>
                        <Input id="restore_file" type="file" accept=".json,.txt" onChange={(event) => form.setData('backup', event.target.files?.[0] ?? null)} className="h-auto py-2" />
                    </FormField>
                    <FormField label={t('رمز عبور')} htmlFor="restore_password" error={form.errors.password}>
                        <PasswordInput id="restore_password" autoComplete="current-password" value={form.data.password} onChange={(event) => form.setData('password', event.target.value)} aria-invalid={form.errors.password ? true : undefined} />
                    </FormField>
                    <SubmitButton size="lg" variant="destructive" processing={form.processing} disabled={!form.data.backup}>
                        {t('بازیابی و جایگزینی داده‌ها')}
                    </SubmitButton>
                </FieldGroup>
            </form>
        </ResponsiveModal>
    );
}

export default function BackupsIndex({ backups }: { backups?: Backup[] }) {
    const format = useFormat();
    const { errors } = usePage().props;
    const [creating, setCreating] = useState(false);
    const [downloading, setDownloading] = useState<Backup | null>(null);
    const [restoring, setRestoring] = useState(false);

    // A wrong download password comes back as a redirect with a validation error.
    useEffect(() => {
        if (errors.password && !restoring) {
            toast.error(errors.password);
        }
    }, [errors.password, restoring]);

    return (
        <>
            <Head title={t('پشتیبان‌گیری')} />
            <PageHeader title={t('پشتیبان‌گیری')} back={route('settings.index')} backComponent="settings/index" />
            <PageBody>
                <div className="grid grid-cols-2 gap-3">
                    <Button size="lg" disabled={creating} onClick={() => router.post(route('backups.store'), {}, { preserveScroll: true, onStart: () => setCreating(true), onFinish: () => setCreating(false) })}>
                        {creating ? <Spinner /> : <PlusIcon />}
                        {t('پشتیبان جدید')}
                    </Button>
                    <Button size="lg" variant="outline" onClick={() => setRestoring(true)}>
                        <UploadIcon />
                        {t('بازیابی')}
                    </Button>
                </div>

                {!backups ? (
                    <ListSkeleton rows={3} />
                ) : backups.length === 0 ? (
                    <EmptyState icon={DatabaseBackupIcon} title={t('پشتیبانی ساخته نشده')} description={t('فایل پشتیبان خارج از پوشه عمومی سرور و فقط با رمز عبور شما قابل دانلود است.')} />
                ) : (
                    <ListCard>
                        {backups.map((backup) => (
                            <ListRow
                                key={backup.id}
                                icon={DatabaseBackupIcon}
                                title={<span dir="ltr" className="block truncate text-start text-sm">{backup.file_name}</span>}
                                subtitle={`${format.dateTime(backup.created_at)} · ${format.fileSize(backup.file_size)}`}
                                trailing={
                                    <span className="flex items-center gap-1">
                                        <Button variant="ghost" size="icon-sm" className="rounded-full" onClick={() => setDownloading(backup)} aria-label={t('دانلود')}>
                                            <DownloadIcon />
                                        </Button>
                                        <ConfirmAction title={t('حذف پشتیبان؟')} description={t('این فایل پشتیبان برای همیشه حذف می‌شود.')} confirmLabel={t('حذف')} href={route('backups.destroy', backup.id)} method="delete" destructive>
                                            <Button variant="ghost" size="icon-sm" className="rounded-full text-destructive" aria-label={t('حذف')}>
                                                <Trash2Icon />
                                            </Button>
                                        </ConfirmAction>
                                    </span>
                                }
                            />
                        ))}
                    </ListCard>
                )}
            </PageBody>
            <DownloadSheet backup={downloading} onClose={() => setDownloading(null)} />
            <RestoreSheet open={restoring} onOpenChange={setRestoring} />
        </>
    );
}
