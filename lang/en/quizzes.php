<?php

return [
    'no_attempts_left' => 'You have used every attempt of this quiz.',
    'no_questions' => 'This quiz has no questions yet.',
    'not_published' => 'This quiz is not published.',
    'created' => 'Quiz created as a draft. Publish it once it has its questions.',
    'saved' => 'Quiz saved.',
    'prompt_required' => 'Write the question.',
    'explanation_required' => 'Write the explanation: learners read it when reviewing their attempt.',
    'published_needs_questions' => 'A published quiz needs at least one question: unpublish it before removing them all.',
    'status' => [
        'PUBLISHED' => 'Quiz published: it shows on its lesson.',
        'DRAFT' => 'The quiz is a draft: it does not show on its lesson.',
        'REVIEW' => 'The quiz is in review: it does not show on its lesson.',
        'ARCHIVED' => 'Quiz archived: it does not show on its lesson.',
    ],
    'payload' => [
        'text_required' => 'Write the text.',
        'text_too_long' => 'At most :max characters.',
        'count' => 'Between :min and :max items.',
        'duplicate' => 'This text is repeated: each one must be different.',
        'invalid' => 'Invalid value.',
        'single_correct' => 'Mark exactly one option as correct.',
        'multiple_correct' => 'Mark at least one option as correct.',
        'true_false' => 'Say whether the statement is true or false.',
    ],
];
