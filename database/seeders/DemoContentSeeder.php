<?php

namespace Database\Seeders;

use App\Models\AiConfig;
use App\Models\Dataset;
use App\Models\Tenant;
use App\Services\Ingest\IngestService;
use Illuminate\Database\Seeder;

/**
 * Imports the example CSVs into their datasets (synchronous embedding) and
 * configures a user-helper + admin-helper agent for each. Idempotent.
 * Run after DemoResetSeeder: `php artisan db:seed --class=DemoContentSeeder`.
 */
class DemoContentSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::first();
        if (! $tenant) {
            $this->command?->error('No tenant — run DemoResetSeeder first.');

            return;
        }

        $ingest = app(IngestService::class);

        foreach ($this->plan() as $entry) {
            $dataset = Dataset::where('tenant_id', $tenant->id)->where('name', $entry['dataset'])->first();
            if (! $dataset) {
                $this->command?->warn("Dataset '{$entry['dataset']}' not found, skipping.");

                continue;
            }

            if ($dataset->documents()->exists()) {
                $this->command?->warn("'{$entry['dataset']}' already has data — skipping import.");
            } else {
                $records = $this->parseCsv(base_path($entry['file']));
                $summary = $ingest->ingest($tenant->id, $dataset->id, 'csv', basename($entry['file']), $records, null, true);
                $this->command?->info("{$entry['dataset']}: {$summary['documents']} ჩანაწერი, {$summary['chunks']} ჩანკი (ემბედირებული).");
            }

            foreach ($entry['agents'] as $agent) {
                AiConfig::updateOrCreate(
                    ['tenant_id' => $tenant->id, 'dataset_id' => $dataset->id, 'name' => $agent['name']],
                    $agent + ['tenant_id' => $tenant->id, 'dataset_id' => $dataset->id]
                );
            }
            $this->command?->info("  → ".count($entry['agents'])." აგენტი კონფიგურირებულია.");
        }
    }

    /** @return list<array<string,mixed>> */
    private function plan(): array
    {
        return [
            [
                'dataset' => 'კომპიუტერული მაღაზია',
                'file' => 'examples/1_computer_hardware_store.csv',
                'agents' => [
                    [
                        'name' => 'პროდუქტების დამხმარე',
                        'model_tier' => 'standard',
                        'enabled_tools' => [],
                        'system_prompt' => 'შენ ხარ კომპიუტერული ტექნიკის მაღაზიის გამყიდველი-კონსულტანტი. დაეხმარე მომხმარებელს პროდუქტების მოძებნაში მისი საჭიროებიდან გამომდინარე. თუ მომხმარებელს ძლიერი კომპიუტერი სურს, შესთავაზე თავსებადი კომპონენტების კომპლექტი (build) — CPU, დედაპლატა (socket-ის მიხედვით), RAM, ვიდეობარათი და კვების ბლოკი. ყოველთვის ჩამოწერე პროდუქტის ზუსტი სახელი, ფასი და ბმული (url) მოწოდებული კონტექსტიდან. ნუ მოიგონებ პროდუქტებს.',
                    ],
                    [
                        'name' => 'ადმინის დამხმარე',
                        'model_tier' => 'standard',
                        'enabled_tools' => ['add_item', 'update_item', 'find_items'],
                        'system_prompt' => 'შენ ხარ მაღაზიის ადმინისტრატორის ასისტენტი. დაეხმარე პროდუქტების დამატებაში და გაუმჯობესებაში — დაწერე მიმზიდველი აღწერები, შესთავაზე თავსებადი კომპონენტები (CPU + დედაპლატა socket-ის მიხედვით, RAM ტიპის მიხედვით) და დააკავშირე მონათესავე პროდუქტები. გამოიყენე ხელსაწყოები (add_item, update_item, find_items) ჩანაწერების დასამატებლად ან განსაახლებლად, როცა მომხმარებელი ამას ითხოვს.',
                    ],
                ],
            ],
            [
                'dataset' => 'ახალი ამბების პორტალი',
                'file' => 'examples/2_news_portal.csv',
                'agents' => [
                    [
                        'name' => 'მკითხველის დამხმარე',
                        'model_tier' => 'fast',
                        'enabled_tools' => [],
                        'system_prompt' => 'შენ ხარ ახალი ამბების პორტალის ასისტენტი. დაეხმარე მკითხველს სტატიების მოძებნაში, შეაჯამე სტატიები მოკლედ და უპასუხე კითხვებზე დღევანდელი ამბების შესახებ. ყოველთვის მიუთითე სტატიის სათაური, თარიღი და ბმული (url) მოწოდებული კონტექსტიდან.',
                    ],
                    [
                        'name' => 'რედაქტორის დამხმარე',
                        'model_tier' => 'standard',
                        'enabled_tools' => ['add_item', 'update_item', 'find_items'],
                        'system_prompt' => 'შენ ხარ ახალი ამბების პორტალის რედაქტორის ასისტენტი. შესთავაზე სტატიების გაუმჯობესება, დასამატებელი ფაქტები და დააკავშირე მონათესავე სტატიები კატეგორიის მიხედვით. გამოიყენე ხელსაწყოები ცვლილებების შესატანად.',
                    ],
                ],
            ],
            [
                'dataset' => 'ფილმების საიტი',
                'file' => 'examples/3_movies_website.csv',
                'agents' => [
                    [
                        'name' => 'ფილმების დამხმარე',
                        'model_tier' => 'fast',
                        'enabled_tools' => [],
                        'system_prompt' => 'შენ ხარ ფილმების საიტის ასისტენტი. დაეხმარე მომხმარებელს ფილმის არჩევაში მისი ინტერესების, ჟანრის და ნანახი/მოწონებული ფილმების მიხედვით. შესთავაზე მსგავსი ფილმები (იმავე ჟანრის ან რეჟისორის) და მიუთითე სათაური, წელი და ბმული (url) კონტექსტიდან.',
                    ],
                    [
                        'name' => 'კატალოგის ადმინი',
                        'model_tier' => 'standard',
                        'enabled_tools' => ['add_item', 'update_item', 'find_items'],
                        'system_prompt' => 'შენ ხარ ფილმების საიტის ადმინისტრატორის ასისტენტი. დააკავშირე მონათესავე ფილმები ჟანრის ან რეჟისორის მიხედვით, შესთავაზე აღწერების გაუმჯობესება და გამოიყენე ხელსაწყოები ცვლილებებისთვის.',
                    ],
                ],
            ],
        ];
    }

    /** @return list<array<string,mixed>> */
    private function parseCsv(string $path): array
    {
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        $rows = array_map('str_getcsv', $lines);
        if ($rows === []) {
            return [];
        }

        $header = array_map(fn ($h) => trim((string) $h), array_shift($rows));
        $records = [];
        foreach ($rows as $row) {
            $record = [];
            foreach ($header as $i => $h) {
                $record[$h !== '' ? $h : "col_{$i}"] = $row[$i] ?? null;
            }
            $records[] = $record;
        }

        return $records;
    }
}
