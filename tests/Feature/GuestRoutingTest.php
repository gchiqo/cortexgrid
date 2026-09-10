<?php

namespace Tests\Feature;

use Tests\TestCase;

class GuestRoutingTest extends TestCase
{
    public function test_the_landing_page_is_public(): void
    {
        $this->get('/')->assertStatus(200);
    }

    public function test_auth_pages_are_reachable(): void
    {
        $this->get('/login')->assertStatus(200);
        $this->get('/register')->assertStatus(200);
    }

    public function test_the_dashboard_sends_guests_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_platform_settings_are_not_public(): void
    {
        $this->get('/dashboard/settings')->assertRedirect('/login');
    }
}
