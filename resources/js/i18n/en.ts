import type { Messages } from './types';

export const en: Messages = {
    common: {
        save: 'Save',
        saved: 'Saved.',
        cancel: 'Cancel',
        continue: 'Continue',
        back: 'Back',
        confirm: 'Confirm',
        close: 'Close',
        remove: 'Remove',
        retry: 'Try again',
        loading: 'Loading…',
        copy: 'Copy',
        copied: 'Copied',
        optional: 'Optional',
        minutes: '{count} min',
        hours: '{count} h',
    },
    nav: {
        platform: 'Platform',
        dashboard: 'Home',
        admin: 'Administration',
        backToApp: 'Back to the platform',
        adminOverview: 'Overview',
        settings: 'Settings',
        logOut: 'Log out',
        navigationMenu: 'Navigation menu',
        breadcrumbs: 'Breadcrumb',
        toggleSidebar: 'Toggle sidebar',
    },
    welcome: {
        head: 'Welcome',
        title: 'Become an AI Engineer on a path you can prove',
        subtitle:
            'A roadmap with real dependencies between topics, lessons that explain why each concept matters, and projects that show what you can build.',
        logIn: 'Log in',
        register: 'Create account',
        goToDashboard: 'Go to my home',
        pillars: {
            roadmapTitle: 'A graph, not a list',
            roadmapText:
                'Every topic declares what you need first and what it unlocks. You know what to study and in which order.',
            evidenceTitle: 'Evidence, not clicks',
            evidenceText:
                'Completing a lesson is a step; mastering it takes an assessment or a project.',
            portfolioTitle: 'Portfolio projects',
            portfolioText:
                'From a Python CLI to an AI system in production, built around real business problems.',
        },
    },
    auth: {
        fields: {
            name: 'Name',
            fullNamePlaceholder: 'Full name',
            email: 'Email address',
            emailPlaceholder: 'email@example.com',
            password: 'Password',
            confirmPassword: 'Confirm password',
            currentPassword: 'Current password',
            newPassword: 'New password',
            rememberMe: 'Remember me',
            showPassword: 'Show password',
            hidePassword: 'Hide password',
        },
        login: {
            head: 'Log in',
            title: 'Log in to your account',
            description: 'Enter your email and password below to log in',
            submit: 'Log in',
            forgotPassword: 'Forgot your password?',
            noAccount: "Don't have an account?",
            signUp: 'Sign up',
        },
        register: {
            head: 'Register',
            title: 'Create an account',
            description: 'Enter your details below to create your account',
            submit: 'Create account',
            haveAccount: 'Already have an account?',
            logIn: 'Log in',
        },
        forgotPassword: {
            head: 'Forgot password',
            title: 'Forgot password',
            description: 'Enter your email to receive a password reset link',
            submit: 'Email password reset link',
            returnTo: 'Or, return to',
            logIn: 'log in',
        },
        resetPassword: {
            head: 'Reset password',
            title: 'Reset password',
            description: 'Please enter your new password below',
            submit: 'Reset password',
        },
        verifyEmail: {
            head: 'Email verification',
            title: 'Verify your email',
            description:
                'Please verify your email address by clicking on the link we just emailed to you.',
            linkSent:
                'A new verification link has been sent to the email address you provided during registration.',
            resend: 'Resend verification email',
            logOut: 'Log out',
        },
        confirmPassword: {
            head: 'Confirm password',
            title: 'Confirm password',
            description:
                'This is a secure area of the application. Please confirm your password before continuing.',
            submit: 'Confirm password',
            passkeyLabel: 'Confirm with passkey',
            passkeyLoading: 'Confirming…',
            passkeySeparator: 'Or confirm with password',
        },
        twoFactor: {
            head: 'Two-factor authentication',
            codeTitle: 'Authentication code',
            codeDescription:
                'Enter the authentication code provided by your authenticator application.',
            recoveryTitle: 'Recovery code',
            recoveryDescription:
                'Please confirm access to your account by entering one of your emergency recovery codes.',
            recoveryPlaceholder: 'Enter recovery code',
            orYouCan: 'or you can',
            useRecoveryCode: 'log in using a recovery code',
            useAuthenticationCode: 'log in using an authentication code',
        },
        passkey: {
            signIn: 'Sign in with a passkey',
            verifying: 'Verifying…',
            separator: 'Or continue with email',
        },
    },
    settings: {
        title: 'Settings',
        description: 'Manage your profile and account settings',
        nav: {
            label: 'Settings',
            profile: 'Profile',
            security: 'Security',
            appearance: 'Appearance',
        },
        profile: {
            head: 'Profile settings',
            title: 'Profile',
            description: 'Update your name and email address',
            unverified: 'Your email address is unverified.',
            resend: 'Click here to re-send the verification email.',
            linkSent:
                'A new verification link has been sent to your email address.',
        },
        deleteAccount: {
            title: 'Delete account',
            description: 'Delete your account and all of its resources',
            warningTitle: 'Warning',
            warningText: 'Please proceed with caution, this cannot be undone.',
            button: 'Delete account',
            confirmTitle: 'Are you sure you want to delete your account?',
            confirmDescription:
                'Once your account is deleted, your progress and all of your data will be permanently deleted. Please enter your password to confirm.',
        },
        security: {
            head: 'Security settings',
            passwordTitle: 'Update password',
            passwordDescription:
                'Ensure your account is using a long, random password to stay secure',
        },
        twoFactor: {
            title: 'Two-factor authentication',
            description: 'Manage your two-factor authentication settings',
            enabledText:
                'You will be prompted for a secure, random pin during login, which you can retrieve from the TOTP-supported application on your phone.',
            disabledText:
                'When you enable two-factor authentication, you will be prompted for a secure pin during login. This pin can be retrieved from a TOTP-supported application on your phone.',
            enable: 'Enable 2FA',
            disable: 'Disable 2FA',
            continueSetup: 'Continue setup',
            modal: {
                enabledTitle: 'Two-factor authentication enabled',
                enabledDescription:
                    'Two-factor authentication is now enabled. Scan the QR code or enter the setup key in your authenticator app.',
                verifyTitle: 'Verify authentication code',
                verifyDescription:
                    'Enter the 6-digit code from your authenticator app',
                enableTitle: 'Enable two-factor authentication',
                enableDescription:
                    'To finish enabling two-factor authentication, scan the QR code or enter the setup key in your authenticator app',
                manualEntry: 'or, enter the code manually',
                setupKey: 'Setup key',
                copySetupKey: 'Copy setup key',
            },
            recovery: {
                title: '2FA recovery codes',
                description:
                    'Recovery codes let you regain access if you lose your 2FA device. Store them in a secure password manager.',
                view: 'View recovery codes',
                hide: 'Hide recovery codes',
                regenerate: 'Regenerate codes',
                listLabel: 'Recovery codes',
                loadingLabel: 'Loading recovery codes',
                usageNote:
                    'Each recovery code can be used once to access your account and will be removed after use. If you need more, use "Regenerate codes".',
            },
        },
        passkeys: {
            title: 'Passkeys',
            description: 'Manage your passkeys for passwordless sign-in',
            emptyTitle: 'No passkeys yet',
            emptyDescription: 'Add a passkey to sign in without a password',
            add: 'Add passkey',
            nameLabel: 'Passkey name',
            namePlaceholder: 'e.g., MacBook Pro, iPhone',
            nameHelp: 'A name helps you identify this passkey later.',
            register: 'Register passkey',
            registering: 'Registering…',
            unsupported: 'Passkeys are not supported in this browser.',
            removeTitle: 'Remove passkey',
            removeConfirm:
                'Are you sure you want to remove the "{name}" passkey? You will no longer be able to use it to sign in.',
            removing: 'Removing…',
            added: 'Added {when}',
            lastUsed: 'Last used {when}',
        },
        appearance: {
            head: 'Appearance settings',
            title: 'Appearance',
            description: 'Update the appearance settings for your account',
            light: 'Light',
            dark: 'Dark',
            system: 'System',
        },
    },
    dashboard: {
        head: 'Home',
        title: 'Your learning path',
        description:
            'The tracks of the {roadmap} roadmap, in the recommended study order.',
        lessons: '{count} lessons',
        oneLesson: '1 lesson',
        progressSoon:
            'Progress tracking and unlocks are coming in the next release.',
        emptyTitle: 'No published content yet',
        emptyDescription: 'The first published track will appear here.',
    },
    admin: {
        head: 'Administration',
        title: 'Content overview',
        description:
            'Curriculum status and recent activity. Editing arrives with the CMS.',
        contentTitle: 'Content by status',
        entity: 'Type',
        entities: {
            roadmap: 'Roadmaps',
            track: 'Tracks',
            module: 'Modules',
            lesson: 'Lessons',
            skill: 'Skills',
            resource: 'Resources',
        },
        total: 'Total',
        linksTitle: 'Link status',
        linksDescription: 'Result of the last check of resource URLs.',
        activityTitle: 'Recent activity',
        activityEmpty: 'No activity recorded yet.',
        system: 'System',
        // Whole sentences: word order and articles change between languages.
        activity: {
            CREATED: '{user} created {subject}',
            UPDATED: '{user} updated {subject}',
            DELETED: '{user} deleted {subject}',
            SUBMITTED: '{user} submitted {subject} for review',
            PUBLISHED: '{user} published {subject}',
            UNPUBLISHED: '{user} unpublished {subject}',
            ARCHIVED: '{user} archived {subject}',
            RESTORED: '{user} restored {subject}',
            ROLE_ASSIGNED: '{user} assigned a role to {label}',
            ROLE_REVOKED: '{user} revoked a role from {label}',
            IMPORTED: '{user} imported {subject}',
        },
        subject: {
            roadmap: 'the roadmap',
            track: 'the track',
            module: 'the module',
            lesson: 'the lesson',
            lesson_version: 'a lesson version',
            skill: 'the skill',
            resource: 'the resource',
            user: 'the account',
            learning_activity: 'an activity',
        },
    },
    states: {
        LOCKED: 'Locked',
        AVAILABLE: 'Available',
        IN_PROGRESS: 'In progress',
        COMPLETED: 'Completed',
        MASTERED: 'Mastered',
    },
    contentStatus: {
        DRAFT: 'Draft',
        REVIEW: 'In review',
        PUBLISHED: 'Published',
        ARCHIVED: 'Archived',
    },
    linkStatus: {
        UNCHECKED: 'Unchecked',
        OK: 'OK',
        REDIRECTED: 'Redirected',
        BROKEN: 'Broken',
    },
    difficulty: {
        BEGINNER: 'Beginner',
        INTERMEDIATE: 'Intermediate',
        ADVANCED: 'Advanced',
        EXPERT: 'Expert',
    },
    progress: {
        label: 'Progress: {value}%',
    },
    callout: {
        note: 'Note',
        tip: 'Tip',
        important: 'Important',
        warning: 'Warning',
        caution: 'Caution',
    },
    richContent: {
        copyCode: 'Copy code',
        codeCopied: 'Code copied',
        diagramLabel: 'Diagram',
        diagramError: 'The diagram could not be drawn.',
        mathError: 'Invalid formula',
        videoTitle: 'YouTube video',
        invalidVideo: 'Video unavailable',
        tableOfContents: 'In this lesson',
        table: 'Table',
        opensInNewTab: '(opens in a new tab)',
    },
    errors: {
        head: 'Error {status}',
        goHome: 'Go home',
        forbidden: {
            title: "You don't have access to this page",
            description:
                "Your account doesn't have the required permissions. If you think this is a mistake, contact an administrator.",
        },
        notFound: {
            title: "We couldn't find this page",
            description: 'The link may be broken or the page may have moved.',
        },
        expired: {
            title: 'The page expired',
            description:
                'Too much time passed without activity. Reload the page and try again.',
        },
        serverError: {
            title: 'Something went wrong',
            description:
                'We hit an unexpected error. It has been logged; please try again in a few minutes.',
        },
        unavailable: {
            title: 'Back shortly',
            description:
                "We're doing some maintenance. Please try again in a few minutes.",
        },
        loadFailed: 'This section could not be loaded.',
    },
    designSystem: {
        head: 'Design system',
        title: 'Design system',
        description:
            'Tokens, components and a real lesson rendered. Only available in the local environment.',
        states: 'Learning states',
        progress: 'Progress bars',
        statuses: 'Publishing statuses',
        feedback: 'Empty, error and loading states',
        lesson: 'Lesson rendered from the database',
        noLesson:
            'There are no published lessons. Run php artisan content:import.',
        tokens: 'Color tokens',
        chooseLesson: 'Published lessons',
        emptyExample: 'Nothing here yet',
        emptyExampleDescription:
            'This is how an empty state with an action looks.',
    },
};
