<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AiConfig;
use App\Models\Dataset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ConfigController extends Controller
{
    public function create(Request $request): View
    {
        $dataset = $this->dataset($request, (int) $request->query('dataset'));

        return view('configs.form', [
            'config' => new AiConfig(['model_tier' => 'standard', 'dataset_id' => $dataset->id]),
            'dataset' => $dataset,
            'datasets' => Dataset::where('tenant_id', $request->user()->tenant_id)->orderBy('id')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $dataset = $this->dataset($request, $request->integer('dataset_id'));

        $config = AiConfig::create($this->validated($request) + [
            'tenant_id' => $request->user()->tenant_id,
            'dataset_id' => $dataset->id,
        ]);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'id' => $config->id, 'name' => $config->name]);
        }

        return redirect("/dashboard/datasets/{$dataset->id}")->with('status', 'ჩატბოტი შეიქმნა.');
    }

    public function edit(Request $request, AiConfig $config): View
    {
        $this->authorizeOwner($request, $config);

        return view('configs.form', [
            'config' => $config,
            'dataset' => $config->dataset,
            'datasets' => Dataset::where('tenant_id', $request->user()->tenant_id)->orderBy('id')->get(),
        ]);
    }

    public function update(Request $request, AiConfig $config): RedirectResponse
    {
        $this->authorizeOwner($request, $config);
        $config->update($this->validated($request));

        return redirect("/dashboard/datasets/{$config->dataset_id}")->with('status', 'ჩატბოტი განახლდა.');
    }

    public function destroy(Request $request, AiConfig $config): RedirectResponse
    {
        $this->authorizeOwner($request, $config);
        $datasetId = $config->dataset_id;
        $config->delete();

        return redirect("/dashboard/datasets/{$datasetId}")->with('status', 'ჩატბოტი წაიშალა.');
    }

    private function dataset(Request $request, int $datasetId): Dataset
    {
        return Dataset::where('tenant_id', $request->user()->tenant_id)->findOrFail($datasetId);
    }

    private function authorizeOwner(Request $request, AiConfig $config): void
    {
        abort_unless($config->tenant_id === $request->user()->tenant_id, 403);
    }

    /** @return array<string,mixed> */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'system_prompt' => ['required', 'string'],
            'model_tier' => ['required', 'in:fast,standard,max'],
            'enabled_tools' => ['nullable', 'string'],
            'allowed_domains' => ['nullable', 'string'],
            'additional_datasets' => ['nullable', 'array'],
        ]);

        $split = fn (?string $s) => array_values(array_filter(array_map('trim', explode(',', $s ?? ''))));

        // Extra datasets this agent may also search (within the tenant, excluding its home dataset).
        $tenantDatasetIds = Dataset::where('tenant_id', $request->user()->tenant_id)->pluck('id')->all();
        $home = $request->integer('dataset_id');
        $extra = collect($request->input('additional_datasets', []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id !== $home && in_array($id, $tenantDatasetIds, true))
            ->unique()->values()->all();

        return [
            'name' => $data['name'],
            'system_prompt' => $data['system_prompt'],
            'model_tier' => $data['model_tier'],
            'enabled_tools' => $split($data['enabled_tools'] ?? ''),
            'allowed_domains' => $split($data['allowed_domains'] ?? ''),
            'widget_enabled' => $request->boolean('widget_enabled'),
            'data_scope' => $extra ? ['dataset_ids' => $extra] : null,
            'settings' => [
                'rerank' => $request->boolean('rerank'),
                'widget' => [
                    'color' => $request->string('widget_color')->toString() ?: '#4f46e5',
                    'position' => $request->input('widget_position') === 'left' ? 'left' : 'right',
                    'greeting' => $request->string('widget_greeting')->toString() ?: 'გამარჯობა! რით დაგეხმაროთ?',
                    'title' => $request->string('widget_title')->toString() ?: $data['name'],
                    'launcher' => $request->string('widget_launcher')->toString() ?: '💬',
                ],
            ],
        ];
    }
}
