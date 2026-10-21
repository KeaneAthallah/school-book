<?php

namespace App\Http\Controllers;

use App\Services\MasterImportService;
use App\Support\MasterDataRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Excel import for catalog master data (buku & barang lainnya), sitting
 * next to the generic MasterDataController CRUD screens.
 */
class MasterImportController extends Controller
{
    public function __construct(private readonly MasterImportService $import) {}

    public function template(Request $request, string $resource): StreamedResponse
    {
        $this->authorizeManage($request, $resource);

        return $this->import->template($resource) ?? abort(404);
    }

    public function store(Request $request, string $resource): RedirectResponse
    {
        $this->authorizeManage($request, $resource);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ], [], ['file' => 'file Excel']);

        /** @var UploadedFile $file */
        $file = $request->file('file');

        $result = $this->import->import($resource, $file);

        $summary = sprintf(
            'Impor selesai: %d baris diproses, %d ditambahkan, %d diperbarui, %d gagal.',
            $result['total'],
            $result['created'],
            $result['updated'],
            count($result['errors']),
        );

        $errors = array_slice($result['errors'], 0, 25);
        $remaining = count($result['errors']) - count($errors);

        if ($remaining > 0) {
            $errors[] = "…dan {$remaining} baris bermasalah lainnya.";
        }

        $redirect = back()->with($result['errors'] === [] ? 'success' : 'error', $summary);

        return $errors === [] ? $redirect : $redirect->with('import_errors', $errors);
    }

    private function authorizeManage(Request $request, string $resource): void
    {
        $definition = MasterDataRegistry::get($resource);

        abort_if($definition === null, 404);

        abort_unless(
            $this->userCan($request, (string) $definition['manage_permission']),
            403,
        );
    }
}
