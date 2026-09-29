<?php

return [
    'saved' => 'Changes saved. Learners keep seeing the published version until you publish.',
    'published' => 'Version :version published: learners see it now.',
    'unchanged' => 'Nothing new to publish: version :version is still live.',
    'not_ready' => 'It cannot be published yet. Check the requirements list.',
    'invalid_body' => 'The content has a structure that is not allowed: :errors',
    'invalid_slug' => 'Use only lowercase letters without accents, numbers and hyphens (for example: git-and-collaboration).',
    'track_saved' => 'Track saved. Learners see the change right away.',
    'track_status' => [
        'PUBLISHED' => 'Track published: learners see it now.',
        'DRAFT' => 'The track is a draft: learners cannot see it.',
        'REVIEW' => 'The track is in review: learners cannot see it.',
        'ARCHIVED' => 'Track archived: learners cannot see it.',
    ],
    'module_saved' => 'Module “:module” saved.',
    'module_status' => [
        'PUBLISHED' => 'Module “:module” published: its published lessons are visible.',
        'DRAFT' => 'Module “:module” is a draft: its lessons are hidden.',
        'REVIEW' => 'Module “:module” is in review: its lessons are hidden.',
        'ARCHIVED' => 'Module “:module” archived: its lessons are hidden.',
    ],
    'order_saved' => 'Module order saved.',
    'order_mismatch' => 'The order must list exactly the modules of the track.',
    'dependencies_saved' => 'Prerequisites saved.',
    'dependency_cycle' => 'That change would create a prerequisite cycle: :path.',
];
