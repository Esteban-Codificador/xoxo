import type { Locale } from '@/i18n';
import type { Auth } from '@/types/auth';

declare module 'react' {
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            locale: Locale;
            auth: Auth;
            can: {
                accessAdmin: boolean;
            };
            /** Lessons waiting for review; null for who does not review. */
            pendingReviews: number | null;
            sidebarOpen: boolean;
            [key: string]: unknown;
        };
    }
}
