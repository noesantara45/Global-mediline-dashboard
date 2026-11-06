<?php

namespace Tests\Feature\Filament;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminThemeTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_and_dashboard_render_global_mediline_brand_logo_and_clean_medical_theme(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('logo-gm-clear.png', false)
            ->assertSee('GLOBAL')
            ->assertSee('MEDILINE')
            ->assertSee('--primary-600:45, 138, 86', false);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/admin')
            ->assertOk()
            ->assertSee('logo-gm-clear.png', false)
            ->assertSee('GLOBAL')
            ->assertSee('MEDILINE')
            ->assertSee('#F2F8F4', false)
            ->assertSee('#84C038', false);
    }
}
