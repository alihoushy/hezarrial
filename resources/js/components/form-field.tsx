import type { ComponentProps, ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import { Field, FieldDescription, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { NativeSelect, NativeSelectOption } from '@/components/ui/native-select';
import { Spinner } from '@/components/ui/spinner';
import { useFormat } from '@/lib/format';
import { cn } from '@/lib/utils';

interface FormFieldProps {
    label: ReactNode;
    htmlFor?: string;
    error?: string;
    description?: ReactNode;
    optional?: boolean;
    className?: string;
    children: ReactNode;
}

export function FormField({ label, htmlFor, error, description, optional, className, children }: FormFieldProps) {
    return (
        <Field data-invalid={error ? true : undefined} className={cn('gap-2', className)}>
            <FieldLabel htmlFor={htmlFor} className="text-foreground">
                {label}
                {optional && <span className="text-xs font-normal text-muted-foreground">(اختیاری)</span>}
            </FieldLabel>
            {children}
            {description && !error && <FieldDescription className="text-xs">{description}</FieldDescription>}
            {error && <FieldError className="text-xs">{error}</FieldError>}
        </Field>
    );
}

interface SelectFieldProps extends Omit<ComponentProps<'select'>, 'size'> {
    options: { value: string | number; label: string }[];
    placeholder?: string;
}

/** The native picker is the most comfortable choice on iPhone (wheel picker). */
export function SelectField({ options, placeholder, ...props }: SelectFieldProps) {
    return (
        <NativeSelect {...props}>
            {placeholder !== undefined && <NativeSelectOption value="">{placeholder}</NativeSelectOption>}
            {options.map((option) => (
                <NativeSelectOption key={option.value} value={option.value}>
                    {option.label}
                </NativeSelectOption>
            ))}
        </NativeSelect>
    );
}

/** Native date input (iOS wheel picker) with the Jalali date spelled out underneath. */
export function DateInput({ value, ...props }: Omit<ComponentProps<typeof Input>, 'type'> & { value: string }) {
    const format = useFormat();

    return (
        <div className="flex flex-col gap-1.5">
            <Input type="date" dir="ltr" value={value} className="text-start" {...props} />
            {value && <p className="px-1 text-xs text-muted-foreground">{format.longDate(value)}</p>}
        </div>
    );
}

export function SubmitButton({ processing, children, className, ...props }: ComponentProps<typeof Button> & { processing?: boolean }) {
    return (
        <Button type="submit" disabled={processing} className={cn('w-full', className)} {...props}>
            {processing && <Spinner />}
            {children}
        </Button>
    );
}
