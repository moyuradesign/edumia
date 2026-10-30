<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_homepage_renders(): void { $this->get('/')->assertOk()->assertSee('edumia.'); }

    public function test_localized_pages_render(): void
    {
        foreach (['zh', 'fr', 'de', 'ja', 'km', 'ko', 'vi'] as $locale) {
            $this->get('/'.$locale)->assertOk();
        }
    }

    public function test_catalogue_and_missing_course(): void
    {
        $this->get('/courses?q=Leadership')->assertOk()->assertSee('Foundations of Leadership');
        $this->get('/courses/not-real')->assertNotFound();
    }
}
