<?php

namespace Tests\Support;

use Closure;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

/**
 * Test-only. The in-process server of Pest Browser hands Laravel the raw
 * body of a request but never its files ("@TODO files" in its
 * LaravelHttpServer), so a real multipart upload from Chromium would reach
 * the app empty. This parses that body the way PHP would; production never
 * loads it. Register it in a browser test with ParseMultipartBody::register().
 */
final class ParseMultipartBody
{
    public static function register(): void
    {
        app(Kernel::class)->prependMiddleware(self::class);
    }

    public function handle(Request $request, Closure $next): Response
    {
        $type = (string) $request->headers->get('Content-Type');

        if ($request->files->count() === 0 && preg_match('/^multipart\/form-data;\s*boundary="?([^";]+)"?/i', $type, $match) === 1) {
            $this->parse($request, (string) $request->getContent(), $match[1]);
        }

        return $next($request);
    }

    private function parse(Request $request, string $body, string $boundary): void
    {
        foreach (explode("--{$boundary}", $body) as $part) {
            if (! str_contains($part, "\r\n\r\n")) {
                continue;
            }

            [$head, $content] = explode("\r\n\r\n", ltrim($part, "\r\n"), 2);
            $content = (string) preg_replace('/\r\n$/', '', $content);

            if (preg_match('/name="([^"]*)"/', $head, $name) !== 1) {
                continue;
            }

            if (preg_match('/filename="([^"]*)"/', $head, $filename) === 1) {
                $path = (string) tempnam(sys_get_temp_dir(), 'upload');
                file_put_contents($path, $content);
                preg_match('/Content-Type:\s*(\S+)/i', $head, $mime);
                $request->files->set($name[1], new UploadedFile($path, $filename[1], $mime[1] ?? null, null, true));
            } else {
                $request->request->set($name[1], $content);
            }
        }
    }
}
