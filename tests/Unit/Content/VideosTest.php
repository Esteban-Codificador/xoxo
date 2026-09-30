<?php

use App\Domain\Content\Videos\VideoDuration;
use App\Domain\Content\Videos\YouTubeId;

/*
| What an editor pastes for a video, read the same way as the editor does
| (youtube.ts), and durations as people write them.
*/

it('reads the video ID from what an editor pastes', function (string $input, ?string $id) {
    expect(YouTubeId::parse($input))->toBe($id);
})->with([
    ['aircAruvnKk', 'aircAruvnKk'],
    ['  aircAruvnKk  ', 'aircAruvnKk'],
    ['https://www.youtube.com/watch?v=aircAruvnKk&t=30s', 'aircAruvnKk'],
    ['https://m.youtube.com/watch?v=aircAruvnKk', 'aircAruvnKk'],
    ['https://youtu.be/aircAruvnKk?si=abc', 'aircAruvnKk'],
    ['https://www.youtube.com/embed/aircAruvnKk', 'aircAruvnKk'],
    ['https://www.youtube-nocookie.com/embed/aircAruvnKk', 'aircAruvnKk'],
    ['https://youtube.com/shorts/aircAruvnKk', 'aircAruvnKk'],
    ['https://vimeo.com/123456789', null],
    ['https://www.youtube.com/watch?v=short', null],
    ['https://evil.example/watch?v=aircAruvnKk', null],
    ['javascript:alert(1)', null],
    ['<iframe src="https://www.youtube.com/embed/aircAruvnKk">', null],
    ['', null],
]);

it('reads and writes durations as minutes:seconds or hours:minutes:seconds', function () {
    expect(VideoDuration::parse('12:34'))->toBe(754)
        ->and(VideoDuration::parse('1:02:03'))->toBe(3723)
        ->and(VideoDuration::parse('0:45'))->toBe(45)
        ->and(VideoDuration::parse('0:00'))->toBeNull()
        ->and(VideoDuration::parse('12:60'))->toBeNull()
        ->and(VideoDuration::parse('12'))->toBeNull()
        ->and(VideoDuration::parse(null))->toBeNull()
        ->and(VideoDuration::format(754))->toBe('12:34')
        ->and(VideoDuration::format(3723))->toBe('1:02:03')
        ->and(VideoDuration::format(45))->toBe('0:45')
        ->and(VideoDuration::format(null))->toBeNull();
});
