<?php

return [
    'saved' => 'Cambios guardados. Los estudiantes siguen viendo la versión publicada hasta que publiques.',
    'published' => 'Versión :version publicada: los estudiantes ya la ven.',
    'unchanged' => 'No hay cambios nuevos: la versión :version sigue publicada.',
    'not_ready' => 'Todavía no se puede publicar. Revisa la lista de requisitos.',
    'invalid_body' => 'El contenido tiene una estructura no permitida: :errors',
    'invalid_slug' => 'Usa solo minúsculas sin tildes, números y guiones (por ejemplo: git-y-colaboracion).',
    'track_saved' => 'Track guardado. Los estudiantes ven el cambio de inmediato.',
    'track_status' => [
        'PUBLISHED' => 'Track publicado: los estudiantes ya lo ven.',
        'DRAFT' => 'El track quedó en borrador: los estudiantes no lo ven.',
        'REVIEW' => 'El track quedó en revisión: los estudiantes no lo ven.',
        'ARCHIVED' => 'Track archivado: los estudiantes no lo ven.',
    ],
    'module_saved' => 'Módulo «:module» guardado.',
    'module_status' => [
        'PUBLISHED' => 'Módulo «:module» publicado: sus lecciones publicadas ya se ven.',
        'DRAFT' => 'El módulo «:module» quedó en borrador: sus lecciones no se ven.',
        'REVIEW' => 'El módulo «:module» quedó en revisión: sus lecciones no se ven.',
        'ARCHIVED' => 'Módulo «:module» archivado: sus lecciones no se ven.',
    ],
    'order_saved' => 'Orden de los módulos guardado.',
    'order_mismatch' => 'El orden debe incluir exactamente los módulos del track.',
    'dependencies_saved' => 'Prerrequisitos guardados.',
    'dependency_cycle' => 'Ese cambio crearía un ciclo de prerrequisitos: :path.',
];
