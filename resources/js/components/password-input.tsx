import { EyeIcon, EyeOffIcon } from 'lucide-react';
import { useState, type ComponentProps } from 'react';
import { InputGroup, InputGroupAddon, InputGroupButton, InputGroupInput } from '@/components/ui/input-group';

/** Password field with a show/hide toggle, which saves a lot of typos on a phone keyboard. */
export function PasswordInput(props: Omit<ComponentProps<'input'>, 'type'>) {
    const [visible, setVisible] = useState(false);

    return (
        <InputGroup>
            <InputGroupInput type={visible ? 'text' : 'password'} dir="ltr" className="text-start" autoCapitalize="none" autoCorrect="off" {...props} />
            <InputGroupAddon align="inline-end">
                <InputGroupButton size="icon-xs" aria-label={visible ? 'پنهان کردن رمز' : 'نمایش رمز'} onClick={() => setVisible((value) => !value)}>
                    {visible ? <EyeOffIcon /> : <EyeIcon />}
                </InputGroupButton>
            </InputGroupAddon>
        </InputGroup>
    );
}
