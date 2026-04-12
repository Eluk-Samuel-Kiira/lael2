<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{ Auth, DB, Log };
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\{ Category, ProductCategory, Product, ProductVariant, UnitOfMeasure };

class ProductImportController extends Controller
{
    /**
     * Show the import modal / landing page.
     * (GET)  /catalog/import
     */
    public function index()
    {
        if (!Auth::user()->hasPermissionTo('create product')) {
            abort(403, __('payments.not_authorized'));
        }

        return view('inventory.import.index');
    }

    /**
     * Process the uploaded Excel file.
     * (POST) /catalog/import
     */
    public function store(Request $request)
    {
        $user     = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$user->hasPermissionTo('create product')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ], 403);
        }

        // ── Validate the upload ───────────────────────────────────────────────
        $request->validate([
            'excel_file' => [
                'required',
                'file',
                'mimes:xlsx,xls',
                'max:10240', // 10 MB
            ],
        ]);

        // ── Load the workbook ─────────────────────────────────────────────────
        try {
            $spreadsheet = IOFactory::load($request->file('excel_file')->getRealPath());
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Could not read the Excel file: ' . $e->getMessage(),
            ], 422);
        }

        $report = [
            'categories'     => ['created' => 0, 'skipped' => 0, 'errors' => []],
            'sub_categories' => ['created' => 0, 'skipped' => 0, 'errors' => []],
            'products'       => ['created' => 0, 'skipped' => 0, 'errors' => []],
            'variants'       => ['created' => 0, 'skipped' => 0, 'errors' => []],
        ];

        DB::beginTransaction();

        try {
            // ── 1. Categories ─────────────────────────────────────────────────
            $this->importCategories(
                $spreadsheet, $tenantId, $user->id, $report['categories']
            );

            // ── 2. Sub-Categories ─────────────────────────────────────────────
            $this->importSubCategories(
                $spreadsheet, $tenantId, $user->id, $report['sub_categories']
            );

            // ── 3. Products ───────────────────────────────────────────────────
            $this->importProducts(
                $spreadsheet, $tenantId, $user->id, $report['products']
            );

            // ── 4. Variants ───────────────────────────────────────────────────
            $this->importVariants(
                $spreadsheet, $tenantId, $user->id, $report['variants']
            );

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Catalog import failed', [
                'tenant_id' => $tenantId,
                'error'     => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage(),
            ], 500);
        }

        // ── Build summary ─────────────────────────────────────────────────────
        $hasErrors = collect($report)->contains(fn($s) => count($s['errors']) > 0);

        return response()->json([
            'success' => true,
            'message' => $hasErrors
                ? 'Import completed with some warnings. Review the report below.'
                : 'Import completed successfully!',
            'report'  => $report,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // PRIVATE HELPERS
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Read a sheet by partial name match (emoji-safe).
     */
    private function getSheet($spreadsheet, string $keyword)
    {
        foreach ($spreadsheet->getSheetNames() as $name) {
            if (stripos($name, $keyword) !== false) {
                return $spreadsheet->getSheetByName($name);
            }
        }
        return null;
    }

    /**
     * Return all non-empty rows from row 3 onwards (row 1=banner, row 2=header).
     */
    private function getRows($sheet): array
    {
        if (!$sheet) {
            return [];
        }

        $rows   = [];
        $maxRow = $sheet->getHighestRow();

        for ($r = 3; $r <= $maxRow; $r++) {
            // Check if the entire row is empty
            $rowData = [];
            $isEmpty = true;

            $highestCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString(
                $sheet->getHighestColumn()
            );

            for ($c = 1; $c <= $highestCol; $c++) {
                $val = trim((string) $sheet->getCell([$c, $r])->getCalculatedValue());
                $rowData[] = $val;
                if ($val !== '') {
                    $isEmpty = false;
                }
            }

            if (!$isEmpty) {
                $rows[] = $rowData;
            }
        }

        return $rows;
    }

    // ───────────────────────────────────────────────────────────────────────────
    // 1. CATEGORIES
    //    Columns: name | description | is_active
    // ───────────────────────────────────────────────────────────────────────────
    private function importCategories($spreadsheet, int $tenantId, int $userId, array &$stat): void
    {
        $sheet = $this->getSheet($spreadsheet, 'Categories');
        $rows  = $this->getRows($sheet);

        foreach ($rows as $idx => $row) {
            $rowNum = $idx + 3;
            $name   = $row[0] ?? '';
            $desc   = $row[1] ?? '';
            $active = isset($row[2]) && $row[2] !== '' ? (int) $row[2] : 1;

            if ($name === '') {
                $stat['errors'][] = "Row {$rowNum}: name is required.";
                continue;
            }

            if (strlen($name) > 255) {
                $stat['errors'][] = "Row {$rowNum}: name exceeds 255 characters.";
                continue;
            }

            // Duplicate check (scoped to tenant)
            $exists = Category::where('tenant_id', $tenantId)
                               ->where('name', $name)
                               ->exists();

            if ($exists) {
                $stat['skipped']++;
                continue;
            }

            Category::create([
                'name'        => $name,
                'slug'        => $this->uniqueSlug('categories', Str::slug($name), $tenantId),
                'description' => $desc ?: null,
                'is_active'   => in_array($active, [0, 1]) ? $active : 1,
                'created_by'  => $userId,
                'tenant_id'   => $tenantId,
            ]);

            $stat['created']++;
        }
    }

    // ───────────────────────────────────────────────────────────────────────────
    // 2. SUB-CATEGORIES
    //    Columns: name | parent_category_name | description | is_active
    // ───────────────────────────────────────────────────────────────────────────
    private function importSubCategories($spreadsheet, int $tenantId, int $userId, array &$stat): void
    {
        $sheet = $this->getSheet($spreadsheet, 'Sub-Categories');
        $rows  = $this->getRows($sheet);

        // Cache category lookups to minimise DB hits
        $categoryCache = [];

        foreach ($rows as $idx => $row) {
            $rowNum    = $idx + 3;
            $name      = $row[0] ?? '';
            $parentName = $row[1] ?? '';
            $desc      = $row[2] ?? '';
            $active    = isset($row[3]) && $row[3] !== '' ? (int) $row[3] : 1;

            if ($name === '') {
                $stat['errors'][] = "Row {$rowNum}: name is required.";
                continue;
            }
            if ($parentName === '') {
                $stat['errors'][] = "Row {$rowNum}: parent_category_name is required.";
                continue;
            }

            // Resolve parent category
            if (!isset($categoryCache[$parentName])) {
                $categoryCache[$parentName] = Category::where('tenant_id', $tenantId)
                                                       ->where('name', $parentName)
                                                       ->first();
            }
            $parentCategory = $categoryCache[$parentName];

            if (!$parentCategory) {
                $stat['errors'][] = "Row {$rowNum}: Parent category \"{$parentName}\" not found. Create it first.";
                continue;
            }

            // Duplicate check (name + tenant)
            $exists = ProductCategory::where('tenant_id', $tenantId)
                                      ->where('name', $name)
                                      ->where('parent_category_id', $parentCategory->id)
                                      ->exists();

            if ($exists) {
                $stat['skipped']++;
                continue;
            }

            ProductCategory::create([
                'name'               => $name,
                'slug'               => $this->uniqueSlug('product_categories', Str::slug($name), $tenantId),
                'parent_category_id' => $parentCategory->id,
                'description'        => $desc ?: null,
                'is_active'          => in_array($active, [0, 1]) ? $active : 1,
                'created_by'         => $userId,
                'tenant_id'          => $tenantId,
            ]);

            $stat['created']++;
        }
    }

    // ───────────────────────────────────────────────────────────────────────────
    // 3. PRODUCTS
    //    Columns: sku | name | sub_category_name | type | description | is_taxable | is_active
    // ───────────────────────────────────────────────────────────────────────────
    private function importProducts($spreadsheet, int $tenantId, int $userId, array &$stat): void
    {
        $sheet = $this->getSheet($spreadsheet, 'Products');
        $rows  = $this->getRows($sheet);

        $validTypes   = ['physical', 'digital', 'service', 'composite'];
        $subCatCache  = [];

        foreach ($rows as $idx => $row) {
            $rowNum     = $idx + 3;
            $sku        = $row[0] ?? '';
            $name       = $row[1] ?? '';
            $subCatName = $row[2] ?? '';
            $type       = strtolower(trim($row[3] ?? 'physical'));
            $desc       = $row[4] ?? '';
            $isTaxable  = isset($row[5]) && $row[5] !== '' ? (int) $row[5] : 1;
            $isActive   = isset($row[6]) && $row[6] !== '' ? (int) $row[6] : 1;

            // ── Validate required fields ───────────────────────────────────────
            if ($sku === '') {
                $stat['errors'][] = "Row {$rowNum}: sku is required.";
                continue;
            }
            if ($name === '') {
                $stat['errors'][] = "Row {$rowNum}: name is required.";
                continue;
            }
            if ($subCatName === '') {
                $stat['errors'][] = "Row {$rowNum}: sub_category_name is required.";
                continue;
            }
            if (!in_array($type, $validTypes)) {
                $stat['errors'][] = "Row {$rowNum}: type \"{$type}\" is invalid. Use: " . implode(', ', $validTypes);
                continue;
            }

            // ── Resolve sub-category ──────────────────────────────────────────
            if (!isset($subCatCache[$subCatName])) {
                $subCatCache[$subCatName] = ProductCategory::where('tenant_id', $tenantId)
                                                            ->where('name', $subCatName)
                                                            ->first();
            }
            $subCategory = $subCatCache[$subCatName];

            if (!$subCategory) {
                $stat['errors'][] = "Row {$rowNum}: Sub-category \"{$subCatName}\" not found.";
                continue;
            }

            // ── Duplicate check ────────────────────────────────────────────────
            $skuExists  = Product::where('tenant_id', $tenantId)->where('sku', $sku)->exists();
            $nameExists = Product::where('tenant_id', $tenantId)->where('name', $name)->exists();

            if ($skuExists || $nameExists) {
                $stat['skipped']++;
                continue;
            }

            Product::create([
                'sku'         => $sku,
                'name'        => $name,
                'slug'        => $this->uniqueSlug('products', Str::slug($name), $tenantId),
                'category_id' => $subCategory->id,
                'type'        => $type,
                'description' => $desc ?: null,
                'is_taxable'  => in_array($isTaxable, [0, 1]) ? $isTaxable : 1,
                'is_active'   => in_array($isActive,  [0, 1]) ? $isActive  : 1,
                'created_by'  => $userId,
                'tenant_id'   => $tenantId,
            ]);

            $stat['created']++;
        }
    }

    // ───────────────────────────────────────────────────────────────────────────
    // 4. VARIANTS
    //    Columns: product_sku | variant_sku | name | barcode | price | cost_price
    //             | overal_quantity_at_hand | weight | weight_unit_id | is_taxable | is_active
    // ───────────────────────────────────────────────────────────────────────────
    private function importVariants($spreadsheet, int $tenantId, int $userId, array &$stat): void
    {
        $sheet = $this->getSheet($spreadsheet, 'Variants');
        $rows  = $this->getRows($sheet);

        $productCache = [];
        $uomCache     = [];

        foreach ($rows as $idx => $row) {
            $rowNum    = $idx + 3;
            $prodSku   = $row[0]  ?? '';
            $varSku    = $row[1]  ?? '';
            $name      = $row[2]  ?? '';
            $barcode   = $row[3]  !== '' ? $row[3]  : null;
            $price     = $row[4]  ?? '';
            $costPrice = $row[5]  ?? '';
            $qty       = isset($row[6])  && $row[6]  !== '' ? (int) $row[6]  : 0;
            $weight    = isset($row[7])  && $row[7]  !== '' ? (float) $row[7] : 0.00;
            $weightUnitId = $row[8] ?? '';
            $isTaxable = isset($row[9])  && $row[9]  !== '' ? (int) $row[9]  : 1;
            $isActive  = isset($row[10]) && $row[10] !== '' ? (int) $row[10] : 1;

            // ── Required field validation ──────────────────────────────────────
            if ($prodSku === '') {
                $stat['errors'][] = "Row {$rowNum}: product_sku is required.";
                continue;
            }
            if ($varSku === '') {
                $stat['errors'][] = "Row {$rowNum}: variant_sku is required.";
                continue;
            }
            if ($name === '') {
                $stat['errors'][] = "Row {$rowNum}: name is required.";
                continue;
            }
            if ($price === '' || !is_numeric($price) || (int)$price < 0) {
                $stat['errors'][] = "Row {$rowNum}: price must be a non-negative number.";
                continue;
            }
            if ($costPrice === '' || !is_numeric($costPrice) || (int)$costPrice < 0) {
                $stat['errors'][] = "Row {$rowNum}: cost_price must be a non-negative number.";
                continue;
            }
            if ($weightUnitId === '' || !is_numeric($weightUnitId)) {
                $stat['errors'][] = "Row {$rowNum}: weight_unit_id is required and must be numeric.";
                continue;
            }

            // ── Resolve parent product ─────────────────────────────────────────
            if (!isset($productCache[$prodSku])) {
                $productCache[$prodSku] = Product::where('tenant_id', $tenantId)
                                                  ->where('sku', $prodSku)
                                                  ->first();
            }
            $product = $productCache[$prodSku];

            if (!$product) {
                $stat['errors'][] = "Row {$rowNum}: Product with SKU \"{$prodSku}\" not found.";
                continue;
            }

            // ── Resolve UOM ────────────────────────────────────────────────────
            $uomKey = (int) $weightUnitId;
            if (!isset($uomCache[$uomKey])) {
                $uomCache[$uomKey] = UnitOfMeasure::where('id', $uomKey)
                                                   ->where('tenant_id', $tenantId)
                                                   ->first();
            }
            if (!$uomCache[$uomKey]) {
                $stat['errors'][] = "Row {$rowNum}: Unit of Measure ID \"{$weightUnitId}\" not found.";
                continue;
            }

            // ── Duplicate checks ───────────────────────────────────────────────
            $skuExists = ProductVariant::where('tenant_id', $tenantId)
                                        ->where('sku', $varSku)
                                        ->exists();
            if ($skuExists) {
                $stat['skipped']++;
                continue;
            }

            if ($barcode) {
                $barcodeExists = ProductVariant::where('tenant_id', $tenantId)
                                                ->where('barcode', $barcode)
                                                ->exists();
                if ($barcodeExists) {
                    $stat['errors'][] = "Row {$rowNum}: Barcode \"{$barcode}\" already exists. Row skipped.";
                    $stat['skipped']++;
                    continue;
                }
            }

            ProductVariant::create([
                'product_id'              => $product->id,
                'sku'                     => $varSku,
                'name'                    => $name,
                'barcode'                 => $barcode,
                'price'                   => (int) $price,      // stored as-is; mutator handles conversion
                'cost_price'              => (int) $costPrice,
                'overal_quantity_at_hand' => $qty,
                'weight'                  => $weight,
                'weight_unit'             => $uomKey,
                'is_taxable'              => in_array($isTaxable, [0, 1]) ? $isTaxable : 1,
                'is_active'               => in_array($isActive,  [0, 1]) ? $isActive  : 1,
                'created_by'              => $userId,
                'tenant_id'               => $tenantId,
            ]);

            $stat['created']++;
        }
    }

    // ───────────────────────────────────────────────────────────────────────────
    // Slug uniqueness helper (appends -2, -3 … if slug already taken)
    // ───────────────────────────────────────────────────────────────────────────
    private function uniqueSlug(string $table, string $slug, int $tenantId): string
    {
        $original = $slug;
        $counter  = 2;

        while (DB::table($table)->where('slug', $slug)->where('tenant_id', $tenantId)->exists()) {
            $slug = $original . '-' . $counter++;
        }

        return $slug;
    }

    public function downloadTemplate()
    {
        if (!Auth::user()->hasPermissionTo('create product')) {
            abort(403, __('payments.not_authorized'));
        }
 
        $path = storage_path('app/templates/pos_catalog_import_template.xlsx');
 
        // If template not on disk yet, generate it on-the-fly (optional approach)
        if (!file_exists($path)) {
            abort(404, 'Template file not found. Ask your administrator to upload it.');
        }
 
        return response()->download($path, 'pos_catalog_import_template.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}