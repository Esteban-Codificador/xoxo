import { Timer } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { t } from '@/i18n';
import { cn } from '@/lib/utils';

function format(seconds: number): string {
    const minutes = Math.floor(seconds / 60);
    const rest = seconds % 60;

    return `${minutes}:${String(rest).padStart(2, '0')}`;
}

/**
 * Counts down the seconds the server says are left. The server decides:
 * this only shows the time and sends the answers when it reaches zero.
 */
export function QuizTimer({
    seconds,
    onExpire,
}: {
    seconds: number;
    onExpire: () => void;
}) {
    const deadline = useRef(Date.now() + seconds * 1000);
    const expired = useRef(false);
    const onExpireRef = useRef(onExpire);
    const [left, setLeft] = useState(seconds);

    useEffect(() => {
        onExpireRef.current = onExpire;
    }, [onExpire]);

    useEffect(() => {
        const tick = () => {
            const next = Math.max(
                0,
                Math.round((deadline.current - Date.now()) / 1000),
            );
            setLeft(next);

            if (next === 0 && !expired.current) {
                expired.current = true;
                onExpireRef.current();
            }
        };

        tick();
        const interval = window.setInterval(tick, 1000);

        return () => window.clearInterval(interval);
    }, []);

    const urgent = left <= 60;

    return (
        <div
            className={cn(
                'inline-flex items-center gap-2 rounded-lg border px-3 py-1.5 text-sm font-medium tabular-nums',
                urgent &&
                    'border-destructive/50 bg-destructive/10 text-destructive',
            )}
        >
            <Timer className="size-4" aria-hidden="true" />
            <span className="sr-only">{t('quiz.timer')}: </span>
            <time dateTime={`PT${left}S`}>{format(left)}</time>
            {/* Announced once, not every second. */}
            <span className="sr-only" aria-live="polite">
                {left === 0
                    ? t('quiz.timeUp')
                    : urgent
                      ? t('quiz.timerWarning')
                      : ''}
            </span>
        </div>
    );
}
