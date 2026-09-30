import { Form } from '@inertiajs/react';
import { Play, RotateCcw } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { t } from '@/i18n';
import { store } from '@/routes/lessons/quiz/attempts';

/** Starts an attempt on the server; the redirect lands on its questions. */
export function StartAttempt({
    lessonSlug,
    retry,
}: {
    lessonSlug: string;
    retry: boolean;
}) {
    return (
        <Form {...store.form(lessonSlug)}>
            {({ processing }) => (
                <Button type="submit" disabled={processing}>
                    {retry ? (
                        <RotateCcw aria-hidden="true" />
                    ) : (
                        <Play aria-hidden="true" />
                    )}
                    {processing
                        ? t('quiz.starting')
                        : retry
                          ? t('quiz.retry')
                          : t('quiz.start')}
                </Button>
            )}
        </Form>
    );
}
