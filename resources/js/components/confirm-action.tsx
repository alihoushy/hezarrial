import { router } from '@inertiajs/react';
import type { Method, RequestPayload } from '@inertiajs/core';
import { useState, type ReactNode } from 'react';
import {
    AlertDialog,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';

interface ConfirmActionProps {
    /** The element that opens the confirmation (rendered as the trigger). */
    children: ReactNode;
    title: string;
    description?: string;
    confirmLabel: string;
    href: string;
    method?: Method;
    data?: RequestPayload;
    destructive?: boolean;
    /** Runs after the request succeeds, e.g. to close a sheet the trigger lives in. */
    onSuccess?: () => void;
}

/** Asks before running an action, and stays open with a spinner until it finishes. */
export function ConfirmAction({ children, title, description, confirmLabel, href, method = 'post', data, destructive = false, onSuccess }: ConfirmActionProps) {
    const [open, setOpen] = useState(false);
    const [processing, setProcessing] = useState(false);

    const confirm = () => {
        router.visit(href, {
            method,
            data,
            preserveScroll: true,
            onStart: () => setProcessing(true),
            onSuccess: () => onSuccess?.(),
            onFinish: () => {
                setProcessing(false);
                setOpen(false);
            },
        });
    };

    return (
        <AlertDialog open={open} onOpenChange={(next) => !processing && setOpen(next)}>
            <AlertDialogTrigger asChild>{children}</AlertDialogTrigger>
            <AlertDialogContent size="sm">
                <AlertDialogHeader>
                    <AlertDialogTitle>{title}</AlertDialogTitle>
                    {description && <AlertDialogDescription>{description}</AlertDialogDescription>}
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel disabled={processing}>انصراف</AlertDialogCancel>
                    <Button variant={destructive ? 'destructive' : 'default'} disabled={processing} onClick={confirm}>
                        {processing && <Spinner />}
                        {confirmLabel}
                    </Button>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    );
}
