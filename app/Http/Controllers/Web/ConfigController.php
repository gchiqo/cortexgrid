<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AiConfig;
use App\Models\Dataset;
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
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $dataset = $this->dataset($request, $request->integer('dataset_id'));

        AiConfig::create($this->validated($request) + [
            'tenant_id' => $request->user()->tenant_id,
            'dataset_id' => $dataset->id,
        ]);

        return redirect("/dashboard/datasets/{$dataset->id}")->with('status', 'ჩატბოტი შეიქმნა.');
    }

    public function edit(Request $request, AiConfig $config): View
    {
        $this->authorizeOwner($request, $config);

        return view('configs.form', ['config' => $config, 'dataset' => $config->dataset]);
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
        ]);

        $split = fn (?string $s) => array_values(array_filter(array_map('trim', explode(',', $s ?? ''))));

        return [
            'name' => $data['name'],
            'system_prompt' => $data['system_prompt'],
            'model_tier' => $data['model_tier'],
            'enabled_tools' => $split($data['enabled_tools'] ?? ''),
            'allowed_domains' => $split($data['allowed_domains'] ?? ''),
            'widget_enabled' => $request->boolean('widget_enabled'),
        ];
    }
}
