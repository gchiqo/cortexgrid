<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Dataset;
use App\Services\Gemini;
use App\Services\Ingest\IngestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Dashboard file upload: PDF / CSV / XLSX / TXT -> records -> ingest pipeline -> DB.
 */
class UploadController extends Controller
{
    private const MAX_RECORDS = 1000;

    public function store(Request $request, IngestService $ingest, Gemini $gemini): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:20480', 'mimes:pdf,csv,txt,md,xlsx,xls'],
            'source_name' => ['nullable', 'string', 'max:255'],
            'dataset_id' => ['required', 'integer'],
        ]);

        $tenantId = (int) $request->user()->tenant_id;
        $dataset = Dataset::where('tenant_id', $tenantId)->findOrFail($request->integer('dataset_id'));

        $file = $request->file('file');
        $ext = strtolower($file->getClientOriginalExtension());
        $name = $request->input('source_name') ?: $file->getClientOriginalName();
        $path = $file->getRealPath();

        try {
            [$records, $type] = match ($ext) {
                'pdf' => [
                    [['title' => $file->getClientOriginalName(), 'text' => $gemini->extractPdfText(file_get_contents($path))]],
                    'pdf',
                ],
                'csv' => [$this->parseCsv($path), 'csv'],
                'xlsx', 'xls' => [$this->parseSpreadsheet($path), 'xlsx'],
                default => [
                    [['title' => $file->getClientOriginalName(), 'text' => file_get_contents($path)]],
                    'text',
                ],
            };
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors(['file' => 'ფაილის დამუშავება ვერ მოხერხდა: '.$e->getMessage()]);
        }

        $records = array_slice($records, 0, self::MAX_RECORDS);

        if ($records === []) {
            return back()->withErrors(['file' => 'ფაილიდან მონაცემები ვერ ამოვიღე.']);
        }

        $summary = $ingest->ingest($tenantId, $dataset->id, $type, $name, $records);

        return back()->with('status',
            "ჩაიტვირთა «{$name}»: {$summary['documents']} დოკუმენტი, {$summary['chunks']} ჩანკი — ემბედინგი მუშავდება."
        );
    }

    /** @return list<array<string,mixed>> */
    private function parseCsv(string $path): array
    {
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        $rows = array_map('str_getcsv', $lines);
        if ($rows === []) {
            return [];
        }

        return $this->rowsToRecords($rows);
    }

    /** @return list<array<string,mixed>> */
    private function parseSpreadsheet(string $path): array
    {
        $sheet = IOFactory::load($path)->getActiveSheet();
        $rows = $sheet->toArray(null, true, false, false);

        return $this->rowsToRecords($rows);
    }

    /**
     * First row is the header; each subsequent row becomes a record keyed by header.
     *
     * @param  array<int,array<int,mixed>>  $rows
     * @return list<array<string,mixed>>
     */
    private function rowsToRecords(array $rows): array
    {
        if ($rows === []) {
            return [];
        }

        $header = array_map(fn ($h) => trim((string) $h), array_shift($rows));

        $records = [];
        foreach ($rows as $row) {
            if (count(array_filter($row, fn ($v) => $v !== null && $v !== '')) === 0) {
                continue;
            }

            $record = [];
            foreach ($header as $i => $h) {
                $key = $h !== '' ? $h : "col_{$i}";
                $record[$key] = $row[$i] ?? null;
            }
            $records[] = $record;
        }

        return $records;
    }
}
