import type { ReactNode } from 'react';

/** Centred, tab-bar-free layout for sign-in and first-run setup. */
export default function AuthLayout({ children }: { children: ReactNode }) {
    return (
        <div className="pt-safe pb-safe flex min-h-dvh flex-col justify-center px-5 py-10">
            <div className="mx-auto flex w-full max-w-sm flex-col gap-8">
                <div className="flex flex-col items-center gap-3 text-center">
                    <span className="flex size-16 items-center justify-center rounded-2xl bg-linear-to-br from-zinc-900 to-teal-800 text-3xl font-black text-white shadow-lg">ه</span>
                    <p className="text-xl font-extrabold">هزار ریال</p>
                </div>
                {children}
            </div>
        </div>
    );
}
