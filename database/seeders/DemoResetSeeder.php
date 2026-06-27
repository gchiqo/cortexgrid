<?php

namespace Database\Seeders;

use App\Models\ApiKey;
use App\Models\Dataset;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Clean demo state: one admin account + API key + four empty datasets ready for the example uploads.
 * Run after `php artisan migrate:fresh`.
 */
class DemoResetSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::create(['name' => 'Demo Tenant']);

        User::create([
            'name' => 'Demo Admin',
            'email' => 'admin@gtuh.local',
            'password' => Hash::make('password'),
            'tenant_id' => $tenant->id,
            'role' => 'admin',
        ]);

        [, $secret] = ApiKey::issue($tenant, 'Demo key');

        $datasets = [
            'კომპიუტერული მაღაზია',
            'ახალი ამბების პორტალი',
            'ფილმების საიტი',
            'ტურისტული სააგენტო',
        ];
        foreach ($datasets as $name) {
            Dataset::create(['tenant_id' => $tenant->id, 'name' => $name]);
        }

        $this->command?->newLine();
        $this->command?->info('=== Clean demo ready ===');
        $this->command?->info('Login:    admin@gtuh.local / password');
        $this->command?->info("API key:  {$secret}");
        $this->command?->info('Datasets: '.implode(', ', $datasets));
        $this->command?->info('Upload examples/ files into the matching datasets.');
    }
}
