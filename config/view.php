<?php

declare(strict_types=1);

/**
 * Edumia's compiled Blade templates belong to the currently deployed release.
 *
 * Do not read VIEW_COMPILED_PATH: a historical absolute path can point to a
 * previous aaPanel release's shared storage and its root-owned cached views.
 * Keep this location outside public/ and independently writable by PHP-FPM.
 */
return [
    'paths' => [resource_path('views')],
    'compiled' => base_path('bootstrap/cache/views'),
];
