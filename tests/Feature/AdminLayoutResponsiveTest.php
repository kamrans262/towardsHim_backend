<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLayoutResponsiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_audio_admin_uses_only_one_sidebar_layout_system(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin'])->save();

        $response = $this->actingAs($admin)->get('/admin/audio/podcasts');
        $response->assertOk();
        $response->assertSee('class="admin-sidebar"', false);
        $response->assertSee('class="admin-main-content flex-grow-1"', false);
        $response->assertDontSee('sidebar sidebar-default sidebar-white', false);
        $response->assertDontSee('class="main-content flex-grow-1"', false);

        $css = file_get_contents(public_path('admin-assets/css/toward-admin.css'));
        $this->assertNotFalse($css);
        $this->assertStringContainsString('.toward-admin .admin-main-content{flex:1 1 0;min-width:0', $css);
        $this->assertStringContainsString('.toward-admin .admin-main-content .table-responsive', $css);
    }
}
