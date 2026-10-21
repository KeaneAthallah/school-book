<?php

namespace App\Http\Controllers;

use App\Models\BookCategory;
use App\Models\Product;
use App\Models\ProductCatalog;
use App\Models\Publisher;
use App\Models\Zona;
use App\Services\PrintHeaderService;
use Barryvdh\DomPDF\Facade\Pdf;
use Dompdf\Canvas;
use Dompdf\FontMetrics;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($this->userCan($request, 'catalogs.view'), 403);

        $manage = $this->userCan($request, 'catalogs.manage');

        $catalogs = ProductCatalog::query()
            ->withCount('items')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (ProductCatalog $catalog): array => [
                'id' => $catalog->getKey(),
                'name' => $catalog->name,
                'description' => $catalog->description,
                'items_count' => (int) $catalog->getAttribute('items_count'),
                'editable' => $manage,
            ]);

        return view('catalog.index', [
            'catalogs' => $catalogs,
            'can' => ['create' => $manage],
        ]);
    }

    public function create(Request $request): View
    {
        abort_unless($this->userCan($request, 'catalogs.manage'), 403);

        return view('catalog.form', $this->formProps());
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->userCan($request, 'catalogs.manage'), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['nullable', 'string', 'max:50'],
            'zona_id' => ['nullable', 'integer', 'exists:zonas,id'],
            'product_ids' => ['array'],
            'product_ids.*' => ['integer', 'exists:products,id'],
        ]);

        $catalog = ProductCatalog::query()->create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'type' => $validated['type'] ?? null,
            'zona_id' => $validated['zona_id'] ?? null,
        ]);

        foreach (array_values($validated['product_ids'] ?? []) as $index => $productId) {
            $catalog->items()->create(['product_id' => (int) $productId, 'sort_order' => $index]);
        }

        return redirect()->route('catalogs.index')->with('success', 'Katalog dibuat.');
    }

    public function edit(Request $request, ProductCatalog $catalog): View
    {
        abort_unless($this->userCan($request, 'catalogs.manage'), 403);

        $catalog->load('items');

        return view('catalog.form', array_merge($this->formProps(), [
            'catalog' => $catalog,
            'selected' => $catalog->items->pluck('product_id')->all(),
        ]));
    }

    public function update(Request $request, ProductCatalog $catalog): RedirectResponse
    {
        abort_unless($this->userCan($request, 'catalogs.manage'), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['nullable', 'string', 'max:50'],
            'zona_id' => ['nullable', 'integer', 'exists:zonas,id'],
            'product_ids' => ['array'],
            'product_ids.*' => ['integer', 'exists:products,id'],
        ]);

        $catalog->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'type' => $validated['type'] ?? null,
            'zona_id' => $validated['zona_id'] ?? null,
        ]);

        $catalog->items()->delete();
        foreach (array_values($validated['product_ids'] ?? []) as $index => $productId) {
            $catalog->items()->create(['product_id' => (int) $productId, 'sort_order' => $index]);
        }

        return redirect()->route('catalogs.index')->with('success', 'Katalog diperbarui.');
    }

    public function destroy(Request $request, ProductCatalog $catalog): RedirectResponse
    {
        abort_unless($this->userCan($request, 'catalogs.manage'), 403);

        $catalog->delete();

        return back()->with('success', 'Katalog dihapus.');
    }

    public function printPdf(Request $request, ProductCatalog $catalog): Response
    {
        abort_unless($this->userCan($request, 'catalogs.view'), 403);

        $catalog->load(['zona', 'items.product.book.publisher', 'items.product.book.category', 'items.product.goods', 'items.product.prices']);

        $company = app(PrintHeaderService::class)->resolve();
        $today = now()->format('d/m/Y');

        $pdf = Pdf::loadView('catalog.pdf', [
            'catalog' => $catalog,
            'company' => $company,
            'logo' => $this->resolveLogoPath($company['logo'] ?? ''),
            'today' => $today,
        ]);
        $pdf->setPaper('A4', 'portrait');
        $pdf->getDomPDF()->setCallbacks([
            [
                'event' => 'end_document',
                'f' => function (int $pageNumber, int $pageCount, Canvas $canvas, FontMetrics $fontMetrics) use ($catalog, $company, $today): void {
                    // The cover carries its own colophon; interior pages get
                    // the running header, footer, and page number.
                    if ($pageNumber > 1) {
                        $this->stampPageFurniture($pageNumber, $pageCount, $canvas, $fontMetrics, $catalog, $company, $today);
                    }
                },
            ],
        ]);

        return $pdf->stream('katalog-'.$catalog->name.'.pdf');
    }

    /**
     * Resolve the stored print logo URL to a local filesystem path.
     *
     * dompdf only reads local files, so the public /storage URL coming from
     * the print header is mapped back onto the public disk, with the project
     * logo as the fallback. Null means no logo can be shown.
     */
    private function resolveLogoPath(string $logoUrl): ?string
    {
        $path = null;

        if ($logoUrl !== '' && preg_match('#/storage/(.+)$#', $logoUrl, $matches) === 1) {
            $relative = str_replace('\\', '/', $matches[1]);

            if (! str_contains($relative, '..')) {
                $candidate = Storage::disk('public')->path($relative);

                if (is_file($candidate)) {
                    $path = $candidate;
                }
            }
        }

        if ($path === null && is_file(public_path('logo.png'))) {
            $path = public_path('logo.png');
        }

        if ($path === null || ! $this->canEmbedImage($path)) {
            return null;
        }

        return $path;
    }

    /**
     * Whether dompdf can embed the given image file.
     *
     * Raster images (and raster formats dompdf converts to PNG first) are
     * decoded through PHP GD; without the extension the render aborts, so an
     * unembeddable logo returns null and the cover shows the typographic
     * placeholder instead of breaking the download. SVG needs no GD.
     */
    private function canEmbedImage(string $path): bool
    {
        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'svg' => true,
            'png', 'jpg', 'jpeg', 'gif', 'bmp', 'webp' => function_exists('imagecreatefrompng'),
            default => false,
        };
    }

    /**
     * Stamp the running header, footer, and page number of an interior page.
     *
     * dompdf has no CSS page counters, so the furniture is drawn onto each
     * page canvas after rendering, inside the @page margins.
     *
     * @param  array<string, string>  $company
     */
    private function stampPageFurniture(int $pageNumber, int $pageCount, Canvas $canvas, FontMetrics $fontMetrics, ProductCatalog $catalog, array $company, string $today): void
    {
        $width = (float) $canvas->get_width();
        $height = (float) $canvas->get_height();
        $left = 42.0;
        $right = $width - 42.0;
        $contentWidth = $right - $left;

        $sans = $fontMetrics->getFont('DejaVu Sans', 'normal');
        $sansBold = $fontMetrics->getFont('DejaVu Sans', 'bold');
        $serifBold = $fontMetrics->getFont('DejaVu Serif', 'bold');

        $navy = [22 / 255, 42 / 255, 66 / 255];
        $gray = [110 / 255, 122 / 255, 136 / 255];
        $gold = [169 / 255, 133 / 255, 69 / 255];

        // Header: company name left, catalog name right, gold hairline below.
        $companyLabel = $this->fitText(mb_strtoupper($company['name']), $sansBold, 7.5, 1.6, $contentWidth * 0.55, $canvas);
        $canvas->text($left, 28, $companyLabel, $sansBold, 7.5, $navy, 0, 1.6);

        $catalogLabel = $this->fitText(mb_strtoupper($catalog->name), $sans, 7.5, 1.0, $contentWidth * 0.4, $canvas);
        $labelWidth = $canvas->get_text_width($catalogLabel, $sans, 7.5, 0, 1.0);
        $canvas->text($right - $labelWidth, 28, $catalogLabel, $sans, 7.5, $gray, 0, 1.0);

        $canvas->line($left, 44, $right, 44, $gold, 0.5);

        // Footer: print date left, serif page number right, gold hairline above.
        $canvas->line($left, $height - 46, $right, $height - 46, $gold, 0.5);
        $canvas->text($left, $height - 40, 'Dicetak pada '.$today, $sans, 7, $gray);

        $pageLabel = 'Halaman '.$pageNumber.' dari '.$pageCount;
        $pageWidth = $canvas->get_text_width($pageLabel, $serifBold, 7.5, 0, 0.3);
        $canvas->text($right - $pageWidth, $height - 40, $pageLabel, $serifBold, 7.5, $navy, 0, 0.3);
    }

    /**
     * Trim a stamped label with an ellipsis so it never runs into its
     * neighbouring text on the same line.
     */
    private function fitText(string $text, ?string $font, float $size, float $charSpace, float $maxWidth, Canvas $canvas): string
    {
        if ($canvas->get_text_width($text, $font, $size, 0, $charSpace) <= $maxWidth) {
            return $text;
        }

        while ($text !== '' && $canvas->get_text_width($text.'…', $font, $size, 0, $charSpace) > $maxWidth) {
            $text = mb_substr($text, 0, -1);
        }

        return $text.'…';
    }

    /**
     * @return array<string, mixed>
     */
    private function formProps(): array
    {
        return [
            'products' => Product::query()->active()->with('book.category')->orderBy('title')->get(['id', 'code', 'title', 'price']),
            'categories' => BookCategory::query()->orderBy('name')->get(['id', 'name']),
            'publishers' => Publisher::query()->active()->orderBy('name')->get(['id', 'name']),
            'zonas' => Zona::query()->active()->orderBy('code')->get(['id', 'name'])
                ->map(fn (Zona $zona): array => ['value' => $zona->getKey(), 'label' => $zona->name])
                ->all(),
        ];
    }
}
