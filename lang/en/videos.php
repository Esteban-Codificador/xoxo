<?php

return [
    'invalid_url' => 'Paste a YouTube link (youtube.com/watch?v=…, youtu.be/…) or the 11-character video ID.',
    'taken' => 'That video is already in the catalog.',
    'invalid_duration' => 'Write the duration as minutes:seconds (12:34) or hours:minutes:seconds (1:02:03).',
    'created' => 'Video added as a draft. It is being checked on YouTube.',
    'saved' => 'Video saved.',
    'verifying' => 'Checking on YouTube: reload the page in a few seconds to see the result.',
    'status' => [
        'PUBLISHED' => 'Video published: it shows in the lessons that use it while YouTube keeps it available.',
        'DRAFT' => 'The video is now a draft: it does not show in lessons.',
        'REVIEW' => 'The video is now in review: it does not show in lessons.',
        'ARCHIVED' => 'Video archived: it does not show in lessons.',
    ],
];
