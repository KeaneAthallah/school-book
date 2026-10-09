<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request, ReportService $service): View
    {
        abort_unless($this->userCan($request, 'reports.view'), 403);

        $type = $request->string('type')->toString();
        $type = array_key_exists($type, ReportService::types()) ? $type : 'sales_by_school';

        $filters = [
            'date_from' => $request->string('date_from')->toString(),
            'date_to' => $request->string('date_to')->toString(),
        ];

        return view('reports.index', [
            'types' => collect(ReportService::types())
                ->map(fn (string $label, string $value): array => ['value' => $value, 'label' => $label])
                ->values()
                ->all(),
            'type' => $type,
            'filters' => $filters,
            'report' => $service->build($type, $filters),
        ]);
    }

    public function export(Request $request, ReportService $service): StreamedResponse
    {
        abort_unless($this->userCan($request, 'reports.view'), 403);

        $type = $request->string('type')->toString();
        $type = array_key_exists($type, ReportService::types()) ? $type : 'sales_by_school';

        $filters = [
            'date_from' => $request->string('date_from')->toString(),
            'date_to' => $request->string('date_to')->toString(),
        ];

        $report = $service->build($type, $filters);
        $filename = 'laporan-'.$type.'-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($report): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fputcsv($handle, array_map(fn (array $column): string => $column['label'], $report['columns']));

            foreach ($report['rows'] as $row) {
                fputcsv($handle, array_map(
                    fn (array $column): string => (string) ($row[$column['key']] ?? ''),
                    $report['columns'],
                ));
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
