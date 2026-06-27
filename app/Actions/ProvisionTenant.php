<?php

namespace App\Actions;

use App\Models\AiConfig;
use App\Models\ApiKey;
use App\Models\Dataset;
use App\Models\Tenant;
use App\Models\User;

/**
 * Ensures a user has a tenant, preset Georgian AI configs, and a first API key.
 * Used on registration and first Google sign-in.
 */
class ProvisionTenant
{
    /** @return string|null the plaintext API key if one was created (shown once), else null */
    public function forUser(User $user): ?string
    {
        if ($user->tenant_id) {
            return null;
        }

        $tenant = Tenant::create(['name' => $user->name ?: $user->email]);

        $user->forceFill(['tenant_id' => $tenant->id, 'role' => 'admin'])->save();

        $dataset = Dataset::create(['tenant_id' => $tenant->id, 'name' => 'ჩემი მონაცემები']);

        $this->seedConfigs($tenant, $dataset);

        [, $secret] = ApiKey::issue($tenant, 'Default key');

        return $secret;
    }

    private function seedConfigs(Tenant $tenant, Dataset $dataset): void
    {
        $presets = [
            [
                'name' => 'ადმინის ასისტენტი',
                'model_tier' => 'standard',
                'system_prompt' => 'შენ ხარ მაღაზიის ადმინისტრატორის ასისტენტი. დაეხმარე პროდუქტების დამატებაში, '
                    .'აღწერების გაუმჯობესებაში და მონაცემების მართვაში. იყავი ზუსტი და პრაქტიკული.',
                'enabled_tools' => ['add_item', 'update_item', 'find_items'],
            ],
            [
                'name' => 'მომხმარებლის ასისტენტი',
                'model_tier' => 'fast',
                'system_prompt' => 'შენ ხარ მაღაზიის გამყიდველი-კონსულტანტი. დაეხმარე მომხმარებელს სწორი პროდუქტის '
                    .'არჩევაში მისი საჭიროებიდან გამომდინარე. იყავი თავაზიანი და მოკლედ ახსენი არჩევანის მიზეზი.',
                'enabled_tools' => [],
            ],
            [
                'name' => 'PC ასამბლერი',
                'model_tier' => 'standard',
                'system_prompt' => 'შენ ხარ კომპიუტერული ტექნიკის ექსპერტი. დაეხმარე მომხმარებელს კომპიუტერის აწყობაში — '
                    .'შეარჩიე თავსებადი კომპონენტები (CPU, დედაპლატა, RAM, GPU, კვება) ბიუჯეტისა და მიზნის მიხედვით '
                    .'და ახსენი თავსებადობა.',
                'enabled_tools' => [],
            ],
        ];

        foreach ($presets as $cfg) {
            AiConfig::create($cfg + ['tenant_id' => $tenant->id, 'dataset_id' => $dataset->id]);
        }
    }
}
