<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Models\Blog;
use Illuminate\Support\Str;

final class BlogContentRenderer
{
    public function mediaIds(string $body): array
    {
        preg_match_all('/!\[[^\]\r\n]{0,200}\]\(edumia-media:([0-9]{1,16})\)/', $body, $matches);

        return array_values(array_unique(array_map('intval', $matches[1] ?? [])));
    }

    public function render(string $body, ?Blog $blog = null): string
    {
        $body = preg_replace_callback(
            '/(!\[[^\]\r\n]{0,200}\]\()edumia-media:([0-9]{1,16})(\))/',
            static function (array $match) use ($blog): string {
                $url = $blog === null
                    ? route('admin.blogs.media.preview', (int) $match[2])
                    : route('blog.image', ['slug' => $blog->slug, 'media' => (int) $match[2]]);

                return $match[1].$url.$match[3];
            },
            $body
        );

        return Str::markdown($body, [
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
        ]);
    }
}
