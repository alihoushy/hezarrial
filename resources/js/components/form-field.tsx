import type { ComponentProps, ReactNode } from 'react';
import { JalaliDatePicker } from '@/components/jalali-date-picker';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Field, FieldDescription, FieldError, FieldLabel } from '@/components/ui/field';
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

interface DateInputProps {
    id?: string;
    value: string;
    onChange: (event: { target: { value: string } }) => void;
    clearable?: boolean;
    'aria-invalid'?: boolean;
}

/**
 * Persian uses the Jalali calendar sheet; other languages use the device's own
 * (Gregorian) date picker. Either way the form value is an ISO date.
 */
export function DateInput({ onChange, clearable, ...props }: DateInputProps) {
    const { locale } = useFormat();

    if (locale === 'fa') {
        return <JalaliDatePicker {...props} clearable={clearable} onChange={(value) => onChange({ target: { value } })} />;
    }

    return <Input type="date" dir="ltr" className="text-start" {...props} onChange={onChange} />;
}

export function SubmitButton({ processing, children, className, ...props }: ComponentProps<typeof Button> & { processing?: boolean }) {
    return (
        <Button type="submit" disabled={processing} className={cn('w-full', className)} {...props}>
            {processing && <Spinner />}
            {children}
        </Button>
    );
}
