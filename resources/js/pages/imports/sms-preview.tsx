import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { ListCard, ListRow } from '@/components/list';
import { Money } from '@/components/money';
import { PageBody, PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useFormat } from '@/lib/format';

interface Parsed {
    amount: number | null;
    type: 'income' | 'expense' | null;
    balance?: number | null;
    card_last_four?: string | null;
    confidence: 'high' | 'medium' | 'low';
}

const CONFIDENCE = { high: 'زیاد', medium: 'متوسط', low: 'کم' } as const;

export default function SmsPreview({ parsed }: { parsed: Parsed }) {
    const format = useFormat();
    const [processing, setProcessing] = useState(false);
    const canConfirm = parsed.amount !== null && parsed.type !== null;

    const confirm = () =>
        router.post(
            route('imports.sms-confirm'),
            { amount: parsed.amount, type: parsed.type },
            { onStart: () => setProcessing(true), onFinish: () => setProcessing(false) },
        );

    return (
        <>
            <Head title="پیش‌نمایش پیامک" />
            <PageHeader title="پیش‌نمایش پیامک" back={route('imports.index')} backComponent="imports/index" />
            <PageBody>
                <ListCard>
                    <ListRow title={<span className="text-sm text-muted-foreground">مبلغ</span>} trailing={parsed.amount !== null ? <Money amount={parsed.amount} className="font-bold" /> : <span className="text-sm">نامشخص</span>} />
                    <ListRow
                        title={<span className="text-sm text-muted-foreground">نوع</span>}
                        trailing={<span className="text-sm font-medium">{parsed.type === 'income' ? 'درآمد' : parsed.type === 'expense' ? 'هزینه' : 'نامشخص'}</span>}
                    />
                    <ListRow
                        title={<span className="text-sm text-muted-foreground">مانده</span>}
                        trailing={parsed.balance != null ? <Money amount={parsed.balance} className="text-sm font-medium" /> : <span className="text-sm">نامشخص</span>}
                    />
                    <ListRow
                        title={<span className="text-sm text-muted-foreground">کارت</span>}
                        trailing={<span className="text-sm font-medium tabular-nums">{parsed.card_last_four ? format.digits(parsed.card_last_four) : 'نامشخص'}</span>}
                    />
                    <ListRow title={<span className="text-sm text-muted-foreground">اطمینان</span>} trailing={<span className="text-sm font-medium">{CONFIDENCE[parsed.confidence]}</span>} />
                </ListCard>
                {!canConfirm && <p className="px-1 text-sm text-muted-foreground">مبلغ یا نوع پیامک تشخیص داده نشد. یک الگوی پیامک بسازید یا متن را بررسی کنید.</p>}
                <Button size="lg" disabled={!canConfirm || processing} onClick={confirm}>
                    {processing && <Spinner />}
                    تایید پیش‌نمایش
                </Button>
            </PageBody>
        </>
    );
}
