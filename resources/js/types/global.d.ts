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
            sidebarOpen: boolean;
            [key: string]: unknown;
        };
    }
}
