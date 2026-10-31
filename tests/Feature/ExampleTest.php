<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_render_even_with_empty_catalogue(): void
    {
        $this->get('/')->assertOk()->assertSee('Edumia');
        $this->get('/courses')->assertOk()->assertSee('No published courses found');
        $this->get('/courses/unknown-course')->assertNotFound();
    }

    public function test_localized_pages(): void
    {
        foreach (['zh', 'fr', 'de', 'ja', 'km', 'ko', 'vi'] as $locale) {
            $this->get('/'.$locale)->assertOk();
        }
        $this->get('/en')->assertRedirect(route('home'));
    }
}
