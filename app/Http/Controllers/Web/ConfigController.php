<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AiConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ConfigController extends Controller
{
    public function create(): View
    {
        return view('configs.form', ['config' => new AiConfig(['model_tier' => 'standard'])]);
    }

    public function store(Request $request): RedirectResponse
    {
        AiConfig::create($this->validated($request) + ['tenant_id' => $request->user()->tenant_id]);

        return redirect('/dashboard')->with('status', 'კონფიგურაცია შეიქმნა.');
    }

    public function edit(Request $request, AiConfig $config): View
    {
        $this->authorizeOwner($request, $config);

        return view('configs.form', ['config' => $config]);
    }

    public function update(Request $request, AiConfig $config): RedirectResponse
    {
        $this->authorizeOwner($request, $config);
        $config->update($this->validated($request));

        return redirect('/dashboard')->with('status', 'კონფიგურაცია განახლდა.');
    }

    public function destroy(Request $request, AiConfig $config): RedirectResponse
    {
        $this->authorizeOwner($request, $config);
        $config->delete();

        return back()->with('status', 'კონფიგურაცია წაიშალა.');
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
        ]);

        $tools = array_values(array_filter(array_map(
            'trim',
            explode(',', $data['enabled_tools'] ?? '')
        )));

        return [
            'name' => $data['name'],
            'system_prompt' => $data['system_prompt'],
            'model_tier' => $data['model_tier'],
            'enabled_tools' => $tools,
        ];
    }
}
