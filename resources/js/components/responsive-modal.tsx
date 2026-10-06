import type { ReactNode } from 'react';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Drawer, DrawerContent, DrawerDescription, DrawerFooter, DrawerHeader, DrawerTitle } from '@/components/ui/drawer';
import { useIsDesktop } from '@/hooks/use-media-query';

interface ResponsiveModalProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    title: string;
    description?: string;
    children: ReactNode;
    footer?: ReactNode;
}

/**
 * A bottom sheet on phones (swipe down to close, thumb-reachable actions,
 * safe-area aware) and a centred dialog on wider screens.
 */
export function ResponsiveModal({ open, onOpenChange, title, description, children, footer }: ResponsiveModalProps) {
    const isDesktop = useIsDesktop();

    if (isDesktop) {
        return (
            <Dialog open={open} onOpenChange={onOpenChange}>
                <DialogContent className="max-h-[85dvh] overflow-y-auto sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>{title}</DialogTitle>
                        {description && <DialogDescription>{description}</DialogDescription>}
                    </DialogHeader>
                    {children}
                    {footer && <DialogFooter>{footer}</DialogFooter>}
                </DialogContent>
            </Dialog>
        );
    }

    return (
        <Drawer open={open} onOpenChange={onOpenChange}>
            <DrawerContent className="data-[vaul-drawer-direction=bottom]:max-h-[92dvh]">
                <DrawerHeader className="pb-2">
                    <DrawerTitle className="text-base font-bold">{title}</DrawerTitle>
                    {description && <DrawerDescription>{description}</DrawerDescription>}
                </DrawerHeader>
                <div className="overflow-y-auto overscroll-contain px-4 pb-2">{children}</div>
                {footer && <DrawerFooter className="pb-[calc(1rem+env(safe-area-inset-bottom))]">{footer}</DrawerFooter>}
                {!footer && <div className="pb-safe" />}
            </DrawerContent>
        </Drawer>
    );
}
