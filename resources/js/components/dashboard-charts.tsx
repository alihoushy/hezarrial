import { Area, AreaChart, CartesianGrid, Cell, Pie, PieChart, XAxis } from 'recharts';
import { ChartContainer, ChartTooltip, ChartTooltipContent, type ChartConfig } from '@/components/ui/chart';
import { todayIso, useFormat } from '@/lib/format';

export interface Charts {
    daily: { date: string; income: number; expense: number }[];
    categories: { name: string; color: string | null; value: number }[];
}

const CATEGORY_COLORS = ['var(--chart-1)', 'var(--chart-2)', 'var(--chart-3)', 'var(--chart-4)', 'var(--chart-5)'];

export function MonthTrend({ daily }: { daily: Charts['daily'] }) {
    const format = useFormat();
    const today = todayIso();
    let income = 0;
    let expense = 0;
    const data = daily
        .filter((day) => day.date <= today)
        .map((day) => {
            income += day.income;
            expense += day.expense;

            return { date: day.date, income, expense };
        });

    const config = {
        income: { label: 'درآمد', color: 'var(--income)' },
        expense: { label: 'هزینه', color: 'var(--expense)' },
    } satisfies ChartConfig;

    return (
        <div className="rounded-2xl bg-card p-4 ring-1 ring-foreground/5">
            <div className="mb-3 flex items-center justify-between">
                <h3 className="font-bold">روند این ماه</h3>
                <div className="flex items-center gap-3 text-xs text-muted-foreground">
                    <span className="flex items-center gap-1.5">
                        <span className="size-2 rounded-full bg-income" />
                        درآمد
                    </span>
                    <span className="flex items-center gap-1.5">
                        <span className="size-2 rounded-full bg-expense" />
                        هزینه
                    </span>
                </div>
            </div>
            <ChartContainer config={config} className="aspect-auto h-48 w-full">
                <AreaChart data={data} margin={{ top: 8, left: 4, right: 4, bottom: 0 }}>
                    <defs>
                        <linearGradient id="fill-income" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="5%" stopColor="var(--color-income)" stopOpacity={0.3} />
                            <stop offset="95%" stopColor="var(--color-income)" stopOpacity={0.02} />
                        </linearGradient>
                        <linearGradient id="fill-expense" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="5%" stopColor="var(--color-expense)" stopOpacity={0.25} />
                            <stop offset="95%" stopColor="var(--color-expense)" stopOpacity={0.02} />
                        </linearGradient>
                    </defs>
                    <CartesianGrid vertical={false} />
                    <XAxis
                        dataKey="date"
                        reversed
                        tickLine={false}
                        axisLine={false}
                        tickMargin={8}
                        minTickGap={24}
                        tickFormatter={(value: string) => format.dayOfMonth(value)}
                    />
                    <ChartTooltip
                        cursor={false}
                        content={
                            <ChartTooltipContent
                                indicator="dot"
                                labelFormatter={(_, payload) => format.shortDate(payload?.[0]?.payload?.date)}
                                formatter={(value, name) => (
                                    <div className="flex w-full items-center justify-between gap-3">
                                        <span className="text-muted-foreground">{config[name as keyof typeof config]?.label}</span>
                                        <span className="font-semibold tabular-nums">
                                            {format.money(Number(value))} {format.unit}
                                        </span>
                                    </div>
                                )}
                            />
                        }
                    />
                    <Area dataKey="income" type="monotone" stroke="var(--color-income)" fill="url(#fill-income)" strokeWidth={2} />
                    <Area dataKey="expense" type="monotone" stroke="var(--color-expense)" fill="url(#fill-expense)" strokeWidth={2} />
                </AreaChart>
            </ChartContainer>
        </div>
    );
}

export function CategorySpending({ categories }: { categories: Charts['categories'] }) {
    const format = useFormat();
    const total = categories.reduce((sum, category) => sum + category.value, 0);
    const top = categories.slice(0, 5);
    const colorOf = (index: number) => top[index]?.color || CATEGORY_COLORS[index % CATEGORY_COLORS.length];

    if (categories.length === 0) {
        return (
            <div className="rounded-2xl bg-card p-4 ring-1 ring-foreground/5">
                <h3 className="font-bold">هزینه بر اساس دسته</h3>
                <p className="py-6 text-center text-sm text-muted-foreground">این ماه هنوز هزینه‌ای ثبت نشده است.</p>
            </div>
        );
    }

    return (
        <div className="rounded-2xl bg-card p-4 ring-1 ring-foreground/5">
            <h3 className="mb-2 font-bold">هزینه بر اساس دسته</h3>
            <div className="flex items-center gap-4">
                <ChartContainer config={{}} className="aspect-square h-32 shrink-0">
                    <PieChart>
                        <Pie data={top} dataKey="value" nameKey="name" innerRadius={38} outerRadius={60} strokeWidth={2} paddingAngle={2}>
                            {top.map((category, index) => (
                                <Cell key={category.name} fill={colorOf(index)} />
                            ))}
                        </Pie>
                    </PieChart>
                </ChartContainer>
                <ul className="flex min-w-0 flex-1 flex-col gap-2">
                    {top.map((category, index) => (
                        <li key={category.name} className="flex items-center gap-2 text-sm">
                            <span className="size-2.5 shrink-0 rounded-full" style={{ background: colorOf(index) }} />
                            <span className="min-w-0 flex-1 truncate">{category.name}</span>
                            <span className="shrink-0 text-xs text-muted-foreground tabular-nums">{format.number(total ? Math.round((category.value / total) * 100) : 0)}٪</span>
                        </li>
                    ))}
                </ul>
            </div>
        </div>
    );
}
