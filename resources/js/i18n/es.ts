/**
 * Source of truth for UI copy. Keys are English identifiers; values are the
 * Spanish text. en.ts must match this shape exactly (checked by tsc).
 * Placeholders use {name}.
 */
export const es = {
    common: {
        save: 'Guardar',
        saved: 'Guardado.',
        cancel: 'Cancelar',
        continue: 'Continuar',
        back: 'Volver',
        confirm: 'Confirmar',
        close: 'Cerrar',
        remove: 'Eliminar',
        retry: 'Reintentar',
        loading: 'Cargando…',
        copy: 'Copiar',
        copied: 'Copiado',
        optional: 'Opcional',
        minutes: '{count} min',
        hours: '{count} h',
    },
    nav: {
        platform: 'Plataforma',
        dashboard: 'Inicio',
        admin: 'Administración',
        backToApp: 'Volver a la plataforma',
        adminOverview: 'Resumen',
        settings: 'Configuración',
        logOut: 'Cerrar sesión',
        navigationMenu: 'Menú de navegación',
        breadcrumbs: 'Ruta de navegación',
        toggleSidebar: 'Mostrar u ocultar la barra lateral',
    },
    welcome: {
        head: 'Bienvenida',
        title: 'Conviértete en AI Engineer con un camino verificable',
        subtitle:
            'Un roadmap con dependencias reales entre temas, lecciones que explican por qué importa cada concepto y proyectos que demuestran lo que sabes hacer.',
        logIn: 'Iniciar sesión',
        register: 'Crear cuenta',
        goToDashboard: 'Ir a mi inicio',
        pillars: {
            roadmapTitle: 'Un grafo, no una lista',
            roadmapText:
                'Cada tema declara qué necesitas antes y qué habilita después. Sabes qué estudiar y en qué orden.',
            evidenceTitle: 'Evidencia, no clics',
            evidenceText:
                'Completar una lección es un paso; dominarla exige una evaluación o un proyecto.',
            portfolioTitle: 'Proyectos de portfolio',
            portfolioText:
                'De un CLI en Python a un sistema de IA en producción, con problemas de empresa reales.',
        },
    },
    auth: {
        fields: {
            name: 'Nombre',
            fullNamePlaceholder: 'Nombre completo',
            email: 'Correo electrónico',
            emailPlaceholder: 'correo@ejemplo.com',
            password: 'Contraseña',
            confirmPassword: 'Confirmar contraseña',
            currentPassword: 'Contraseña actual',
            newPassword: 'Nueva contraseña',
            rememberMe: 'Recordarme',
            showPassword: 'Mostrar contraseña',
            hidePassword: 'Ocultar contraseña',
        },
        login: {
            head: 'Iniciar sesión',
            title: 'Inicia sesión en tu cuenta',
            description: 'Ingresa tu correo y tu contraseña para continuar',
            submit: 'Iniciar sesión',
            forgotPassword: '¿Olvidaste tu contraseña?',
            noAccount: '¿No tienes una cuenta?',
            signUp: 'Regístrate',
        },
        register: {
            head: 'Crear cuenta',
            title: 'Crea tu cuenta',
            description: 'Completa tus datos para empezar',
            submit: 'Crear cuenta',
            haveAccount: '¿Ya tienes una cuenta?',
            logIn: 'Inicia sesión',
        },
        forgotPassword: {
            head: 'Recuperar contraseña',
            title: 'Recupera tu contraseña',
            description:
                'Ingresa tu correo y te enviaremos un enlace para restablecerla',
            submit: 'Enviar enlace de recuperación',
            returnTo: 'O vuelve a',
            logIn: 'iniciar sesión',
        },
        resetPassword: {
            head: 'Restablecer contraseña',
            title: 'Restablece tu contraseña',
            description: 'Ingresa tu nueva contraseña',
            submit: 'Restablecer contraseña',
        },
        verifyEmail: {
            head: 'Verificación de correo',
            title: 'Verifica tu correo',
            description:
                'Confirma tu dirección de correo con el enlace que te acabamos de enviar.',
            linkSent:
                'Te enviamos un nuevo enlace de verificación al correo con el que te registraste.',
            resend: 'Reenviar correo de verificación',
            logOut: 'Cerrar sesión',
        },
        confirmPassword: {
            head: 'Confirmar contraseña',
            title: 'Confirma tu contraseña',
            description:
                'Esta es un área protegida. Confirma tu contraseña para continuar.',
            submit: 'Confirmar contraseña',
            passkeyLabel: 'Confirmar con llave de acceso',
            passkeyLoading: 'Confirmando…',
            passkeySeparator: 'O confirma con tu contraseña',
        },
        twoFactor: {
            head: 'Autenticación en dos pasos',
            codeTitle: 'Código de autenticación',
            codeDescription:
                'Ingresa el código que muestra tu aplicación de autenticación.',
            recoveryTitle: 'Código de recuperación',
            recoveryDescription:
                'Confirma el acceso a tu cuenta con uno de tus códigos de recuperación de emergencia.',
            recoveryPlaceholder: 'Código de recuperación',
            orYouCan: 'o puedes',
            useRecoveryCode: 'usar un código de recuperación',
            useAuthenticationCode: 'usar un código de autenticación',
        },
        passkey: {
            signIn: 'Iniciar sesión con llave de acceso',
            verifying: 'Verificando…',
            separator: 'O continúa con tu correo',
        },
    },
    settings: {
        title: 'Configuración',
        description: 'Administra tu perfil y la seguridad de tu cuenta',
        nav: {
            label: 'Configuración',
            profile: 'Perfil',
            security: 'Seguridad',
            appearance: 'Apariencia',
        },
        profile: {
            head: 'Perfil',
            title: 'Perfil',
            description: 'Actualiza tu nombre y tu correo',
            unverified: 'Tu correo no está verificado.',
            resend: 'Reenviar el correo de verificación.',
            linkSent: 'Te enviamos un nuevo enlace de verificación.',
        },
        deleteAccount: {
            title: 'Eliminar cuenta',
            description: 'Elimina tu cuenta y todos sus datos',
            warningTitle: 'Advertencia',
            warningText:
                'Procede con cuidado: esta acción no se puede deshacer.',
            button: 'Eliminar cuenta',
            confirmTitle: '¿Seguro que quieres eliminar tu cuenta?',
            confirmDescription:
                'Al eliminar tu cuenta se borrarán de forma permanente tu progreso y todos tus datos. Ingresa tu contraseña para confirmarlo.',
        },
        security: {
            head: 'Seguridad',
            passwordTitle: 'Cambiar contraseña',
            passwordDescription:
                'Usa una contraseña larga y aleatoria para mantener tu cuenta segura',
        },
        twoFactor: {
            title: 'Autenticación en dos pasos',
            description: 'Administra la autenticación en dos pasos',
            enabledText:
                'Al iniciar sesión se te pedirá un código temporal de tu aplicación de autenticación (TOTP).',
            disabledText:
                'Al activarla, se te pedirá un código temporal al iniciar sesión. Lo obtienes de una aplicación de autenticación compatible con TOTP.',
            enable: 'Activar',
            disable: 'Desactivar',
            continueSetup: 'Continuar la configuración',
            modal: {
                enabledTitle: 'Autenticación en dos pasos activada',
                enabledDescription:
                    'La autenticación en dos pasos está activa. Escanea el código QR o ingresa la clave en tu aplicación de autenticación.',
                verifyTitle: 'Verifica el código',
                verifyDescription:
                    'Ingresa el código de 6 dígitos de tu aplicación de autenticación',
                enableTitle: 'Activa la autenticación en dos pasos',
                enableDescription:
                    'Para terminar, escanea el código QR o ingresa la clave de configuración en tu aplicación de autenticación',
                manualEntry: 'o ingresa el código manualmente',
                setupKey: 'Clave de configuración',
                copySetupKey: 'Copiar la clave de configuración',
            },
            recovery: {
                title: 'Códigos de recuperación',
                description:
                    'Te permiten recuperar el acceso si pierdes tu dispositivo. Guárdalos en un gestor de contraseñas.',
                view: 'Ver códigos de recuperación',
                hide: 'Ocultar códigos de recuperación',
                regenerate: 'Regenerar códigos',
                listLabel: 'Códigos de recuperación',
                loadingLabel: 'Cargando códigos de recuperación',
                usageNote:
                    'Cada código sirve una sola vez y se elimina después de usarlo. Si necesitas más, usa "Regenerar códigos".',
            },
        },
        passkeys: {
            title: 'Llaves de acceso',
            description:
                'Administra tus llaves de acceso para iniciar sesión sin contraseña',
            emptyTitle: 'Aún no tienes llaves de acceso',
            emptyDescription: 'Agrega una para iniciar sesión sin contraseña',
            add: 'Agregar llave de acceso',
            nameLabel: 'Nombre de la llave',
            namePlaceholder: 'Ej.: MacBook Pro, iPhone',
            nameHelp: 'Un nombre te ayuda a reconocerla después.',
            register: 'Registrar llave de acceso',
            registering: 'Registrando…',
            unsupported: 'Este navegador no admite llaves de acceso.',
            removeTitle: 'Eliminar llave de acceso',
            removeConfirm:
                '¿Seguro que quieres eliminar la llave "{name}"? Ya no podrás usarla para iniciar sesión.',
            removing: 'Eliminando…',
            added: 'Agregada {when}',
            lastUsed: 'Último uso {when}',
        },
        appearance: {
            head: 'Apariencia',
            title: 'Apariencia',
            description: 'Elige cómo se ve la plataforma',
            light: 'Claro',
            dark: 'Oscuro',
            system: 'Sistema',
        },
    },
    dashboard: {
        head: 'Inicio',
        title: 'Tu ruta de aprendizaje',
        description:
            'Los tracks del roadmap {roadmap}, en el orden en que se recomienda estudiarlos.',
        lessons: '{count} lecciones',
        oneLesson: '1 lección',
        progressSoon:
            'El seguimiento de tu progreso y los desbloqueos se activan en la próxima versión.',
        emptyTitle: 'Todavía no hay contenido publicado',
        emptyDescription:
            'Cuando el equipo publique el primer track, aparecerá aquí.',
    },
    admin: {
        head: 'Administración',
        title: 'Resumen del contenido',
        description:
            'Estado del currículo y actividad reciente. La edición llega con el CMS.',
        contentTitle: 'Contenido por estado',
        entity: 'Tipo',
        entities: {
            roadmap: 'Roadmaps',
            track: 'Tracks',
            module: 'Módulos',
            lesson: 'Lecciones',
            skill: 'Skills',
            resource: 'Recursos',
        },
        total: 'Total',
        linksTitle: 'Estado de los enlaces',
        linksDescription:
            'Resultado de la última verificación de las URLs de recursos.',
        activityTitle: 'Actividad reciente',
        activityEmpty: 'Todavía no hay actividad registrada.',
        system: 'Sistema',
        // Whole sentences: word order and articles change between languages.
        activity: {
            CREATED: '{user} creó {subject}',
            UPDATED: '{user} actualizó {subject}',
            DELETED: '{user} eliminó {subject}',
            SUBMITTED: '{user} envió a revisión {subject}',
            PUBLISHED: '{user} publicó {subject}',
            UNPUBLISHED: '{user} despublicó {subject}',
            ARCHIVED: '{user} archivó {subject}',
            RESTORED: '{user} restauró {subject}',
            ROLE_ASSIGNED: '{user} asignó un rol a {label}',
            ROLE_REVOKED: '{user} revocó un rol a {label}',
            IMPORTED: '{user} importó {subject}',
        },
        subject: {
            roadmap: 'el roadmap',
            track: 'el track',
            module: 'el módulo',
            lesson: 'la lección',
            lesson_version: 'una versión de la lección',
            skill: 'la skill',
            resource: 'el recurso',
            user: 'la cuenta',
            learning_activity: 'una actividad',
        },
    },
    track: {
        eyebrow: 'Track {position}',
        start: 'Empezar: {lesson}',
        whyItMatters: 'Por qué importa',
        about: 'Sobre este track',
        prerequisites: 'Antes de este track',
        startingPoint:
            'No necesitas un track previo: este es un punto de partida.',
        outline: 'Contenido del track',
        readingTime: '{time} de lectura',
        dedication: 'Dedicación estimada: {hours} h con práctica',
        emptyTitle: 'Este track aún no tiene lecciones publicadas',
        emptyDescription:
            'Las lecciones aparecen aquí cuando el equipo editorial las publica.',
    },
    lesson: {
        module: 'Módulo: {module}',
        whyItMatters: 'Por qué importa',
        objectives: 'Al terminar podrás',
        prerequisites: 'Conviene haber visto antes',
        skills: 'Skills que desarrolla',
        resources: 'Para profundizar',
        official: 'Fuente oficial',
        brokenLink: 'La última verificación no encontró este enlace.',
        version: 'Versión {version}, publicada el {date}',
        pager: 'Navegación entre lecciones',
        previous: 'Anterior',
        next: 'Siguiente',
        backToTrack: 'Volver a {track}',
        endOfTrack: 'Es la última lección del track.',
    },
    contentType: {
        CONCEPT: 'Concepto',
        TUTORIAL: 'Tutorial',
        EXERCISE: 'Ejercicio',
        LAB: 'Laboratorio',
        PROJECT: 'Proyecto',
        QUIZ: 'Quiz',
        READING: 'Lectura',
        VIDEO: 'Video',
        DOCUMENTATION: 'Documentación',
        CHALLENGE: 'Reto',
    },
    resourceType: {
        DOCUMENTATION: 'Documentación',
        ARTICLE: 'Artículo',
        TUTORIAL: 'Tutorial',
        COURSE: 'Curso',
        BOOK: 'Libro',
        PAPER: 'Paper',
        REPOSITORY: 'Repositorio',
        TOOL: 'Herramienta',
    },
    dependencyKind: {
        REQUIRED: 'Necesario',
        RECOMMENDED: 'Recomendado',
    },
    languages: {
        es: 'Español',
        en: 'Inglés',
    },
    states: {
        LOCKED: 'Bloqueado',
        AVAILABLE: 'Disponible',
        IN_PROGRESS: 'En curso',
        COMPLETED: 'Completado',
        MASTERED: 'Dominado',
    },
    contentStatus: {
        DRAFT: 'Borrador',
        REVIEW: 'En revisión',
        PUBLISHED: 'Publicado',
        ARCHIVED: 'Archivado',
    },
    linkStatus: {
        UNCHECKED: 'Sin verificar',
        OK: 'Correcto',
        REDIRECTED: 'Redirigido',
        BROKEN: 'Roto',
    },
    difficulty: {
        BEGINNER: 'Principiante',
        INTERMEDIATE: 'Intermedio',
        ADVANCED: 'Avanzado',
        EXPERT: 'Experto',
    },
    progress: {
        label: 'Progreso: {value} %',
    },
    callout: {
        note: 'Nota',
        tip: 'Consejo',
        important: 'Importante',
        warning: 'Advertencia',
        caution: 'Precaución',
    },
    richContent: {
        copyCode: 'Copiar código',
        codeCopied: 'Código copiado',
        diagramLabel: 'Diagrama',
        diagramError: 'No se pudo dibujar el diagrama.',
        mathError: 'Fórmula no válida',
        videoTitle: 'Video de YouTube',
        invalidVideo: 'Video no disponible',
        tableOfContents: 'En esta lección',
        table: 'Tabla',
        opensInNewTab: '(se abre en una pestaña nueva)',
    },
    errors: {
        head: 'Error {status}',
        goHome: 'Ir al inicio',
        forbidden: {
            title: 'No tienes acceso a esta página',
            description:
                'Tu cuenta no tiene los permisos necesarios. Si crees que es un error, contacta a un administrador.',
        },
        notFound: {
            title: 'No encontramos esta página',
            description:
                'Puede que el enlace esté roto o que la página se haya movido.',
        },
        expired: {
            title: 'La página expiró',
            description:
                'Pasó demasiado tiempo sin actividad. Recarga la página y vuelve a intentarlo.',
        },
        serverError: {
            title: 'Algo salió mal',
            description:
                'Tuvimos un error inesperado. Ya quedó registrado; intenta de nuevo en unos minutos.',
        },
        unavailable: {
            title: 'Volvemos enseguida',
            description:
                'Estamos haciendo mantenimiento. Intenta de nuevo en unos minutos.',
        },
        loadFailed: 'No se pudo cargar esta sección.',
    },
    designSystem: {
        head: 'Design system',
        title: 'Design system',
        description:
            'Tokens, componentes y una lección real renderizada. Solo disponible en el entorno local.',
        states: 'Estados de aprendizaje',
        progress: 'Barras de progreso',
        statuses: 'Estados de publicación',
        feedback: 'Estados vacíos, de error y de carga',
        lesson: 'Lección renderizada desde la base de datos',
        noLesson:
            'No hay lecciones publicadas. Ejecuta php artisan content:import.',
        tokens: 'Tokens de color',
        chooseLesson: 'Lecciones publicadas',
        emptyExample: 'Sin elementos todavía',
        emptyExampleDescription: 'Así se ve un estado vacío con una acción.',
    },
} as const;
