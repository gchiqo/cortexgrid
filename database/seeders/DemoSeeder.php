<?php

namespace Database\Seeders;

use App\Models\AiConfig;
use App\Models\ApiKey;
use App\Models\Dataset;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::firstOrCreate(['name' => 'Demo Tenant']);

        $user = User::updateOrCreate(
            ['email' => 'admin@gtuh.local'],
            [
                'name' => 'Demo Admin',
                'password' => Hash::make('password'),
                'tenant_id' => $tenant->id,
                'role' => 'admin',
            ]
        );

        [, $secret] = ApiKey::issue($tenant, 'Demo key');

        $dataset = Dataset::firstOrCreate(['tenant_id' => $tenant->id, 'name' => 'ჩემი მონაცემები']);

        $configs = [
            [
                'name' => 'ადმინის ასისტენტი',
                'model_tier' => 'standard',
                'system_prompt' =>
                    'შენ ხარ მაღაზიის ადმინისტრატორის ასისტენტი. დაეხმარე პროდუქტების დამატებაში, '
                    .'აღწერების გაუმჯობესებაში და მონაცემების მართვაში. იყავი ზუსტი და პრაქტიკული.',
                'enabled_tools' => ['add_product', 'update_product'],
            ],
            [
                'name' => 'მომხმარებლის ასისტენტი',
                'model_tier' => 'fast',
                'system_prompt' =>
                    'შენ ხარ მაღაზიის გამყიდველი-კონსულტანტი. დაეხმარე მომხმარებელს სწორი პროდუქტის არჩევაში '
                    .'მისი საჭიროებიდან გამომდინარე. იყავი თავაზიანი და მოკლედ ახსენი არჩევანის მიზეზი.',
                'enabled_tools' => [],
            ],
            [
                'name' => 'PC ასამბლერი',
                'model_tier' => 'standard',
                'system_prompt' =>
                    'შენ ხარ კომპიუტერული ტექნიკის ექსპერტი. დაეხმარე მომხმარებელს კომპიუტერის აწყობაში — '
                    .'შეარჩიე თავსებადი კომპონენტები (CPU, დედაპლატა, RAM, GPU, კვება) ბიუჯეტისა და მიზნის მიხედვით '
                    .'და ახსენი თავსებადობა.',
                'enabled_tools' => [],
            ],
        ];

        foreach ($configs as $cfg) {
            AiConfig::updateOrCreate(
                ['tenant_id' => $tenant->id, 'name' => $cfg['name']],
                $cfg + ['tenant_id' => $tenant->id, 'dataset_id' => $dataset->id]
            );
        }

        $this->command?->newLine();
        $this->command?->info('=== Demo data ready ===');
        $this->command?->info("Tenant:   {$tenant->name} (id {$tenant->id})");
        $this->command?->info('Login:    admin@gtuh.local / password');
        $this->command?->info("API key:  {$secret}");
        $this->command?->warn('Save the API key now — it is shown only once.');
    }
}
