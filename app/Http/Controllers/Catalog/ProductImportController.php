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
                'mimes:xlsx,xls,csv',
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
            'products'       => ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => []],
            'variants'       => ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => []],
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

            // Duplicate check (scoped to tenant) — this is the FAST PATH that
            // catches most duplicates without ever hitting the database's
            // unique constraint. It won't catch a cross-tenant name collision
            // if the DB constraint is still a global unique(name) — that's
            // what the try/catch below is for.
            $exists = Category::where('tenant_id', $tenantId)
                            ->where('name', $name)
                            ->exists();

            if ($exists) {
                $stat['skipped']++;
                continue;
            }

            // ── Safety net: even if our pre-check above passes (e.g. another
            // tenant already owns this name and the DB constraint is still a
            // bare unique(name) rather than unique(tenant_id, name)), catch
            // the resulting QueryException here instead of letting it bubble
            // up and roll back the ENTIRE import (categories, sub-categories,
            // products, variants — everything already processed in this
            // request). One bad row should only cost you one row. ──────────
            try {
                Category::create([
                    'name'        => $name,
                    'slug'        => $this->uniqueSlug('categories', Str::slug($name), $tenantId),
                    'description' => $desc ?: null,
                    'is_active'   => in_array($active, [0, 1]) ? $active : 1,
                    'created_by'  => $userId,
                    'tenant_id'   => $tenantId,
                ]);

                $stat['created']++;

            } catch (\Illuminate\Database\QueryException $e) {
                // 23000 = SQL integrity constraint violation (duplicate key, etc.)
                if ($e->getCode() === '23000') {
                    $stat['skipped']++;
                    $stat['errors'][] = "Row {$rowNum}: \"{$name}\" already exists (possibly under another account) and was skipped.";
                    continue;
                }

                // Anything else is unexpected — re-throw so it's logged and
                // surfaced properly rather than silently swallowed.
                throw $e;
            }
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
    //
    //    RE-IMPORT BEHAVIOUR: if a product with this SKU already exists for
    //    this tenant, only its `name` is UPDATED from the sheet. Everything
    //    else about the existing product (category, type, description,
    //    tax/active flags) is left untouched. If the SKU is new, a brand
    //    new product is inserted as before.
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

            // ── Check for an existing product with this SKU (this tenant) ──────
            // If found: this is a RE-IMPORT — update the name only, rather
            // than treating it as a duplicate to skip.
            $existingProduct = Product::where('tenant_id', $tenantId)
                                       ->where('sku', $sku)
                                       ->first();

            if ($existingProduct) {
                if ($existingProduct->name !== $name) {
                    // Make sure the new name doesn't collide with a
                    // *different* product before renaming this one.
                    $nameConflict = Product::where('tenant_id', $tenantId)
                                            ->where('name', $name)
                                            ->where('id', '!=', $existingProduct->id)
                                            ->exists();

                    if ($nameConflict) {
                        $stat['errors'][] = "Row {$rowNum}: Cannot rename product \"{$sku}\" to \"{$name}\" — that name is already used by another product.";
                        continue;
                    }

                    $existingProduct->update([
                        'name' => $name,
                    ]);
                }

                $stat['updated'] = ($stat['updated'] ?? 0) + 1;
                continue;
            }

            // ── New product — name must not already be taken ───────────────────
            $nameExists = Product::where('tenant_id', $tenantId)->where('name', $name)->exists();

            if ($nameExists) {
                $stat['errors'][] = "Row {$rowNum}: Product name \"{$name}\" already exists under a different SKU. Row skipped.";
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
    //    Columns: product_sku | variant_sku | name | barcode | supplier_cost_price
    //             | total_shipping_cost | ura_taxes_applied | additional_expenses
    //             | grand_total_cost_price | selling_price | discount_selling_price
    //             | discount_percentage | markup_percentage | overal_quantity_at_hand
    //             | weight | weight_unit_id | is_taxable | is_active
    //
    //    FIELD MEANINGS (business definitions):
    //      supplier_cost_price    = what you pay the supplier
    //      total_shipping_cost    = shipping cost incurred
    //      ura_taxes_applied      = URA taxes applied (import duties, VAT, etc.)
    //      additional_expenses    = handling, insurance, etc.
    //      grand_total_cost_price = supplier + shipping + taxes + expenses
    //                              (AUTO-CALCULATED if left blank)
    //      selling_price          = selling price to customer
    //      discount_selling_price = price after discounts (AUTO-CALCULATED if blank)
    //      discount_percentage    = % discount applied (default 0)
    //      markup_percentage      = % markup from cost to selling price
    //                              (AUTO-CALCULATED if left blank)
    //
    //    RE-IMPORT BEHAVIOUR: if a variant with this SKU already exists for
    //    this tenant, all price-related fields are UPDATED from the sheet.
    //    overal_quantity_at_hand is NEVER touched on update.
    // ───────────────────────────────────────────────────────────────────────────
    private function importVariants($spreadsheet, int $tenantId, int $userId, array &$stat): void
    {
        $sheet = $this->getSheet($spreadsheet, 'Variants');
        $rows  = $this->getRows($sheet);

        $productCache  = [];
        $uomCache      = [];
        $uomNameCache  = [];

        foreach ($rows as $idx => $row) {
            $rowNum = $idx + 3;
            
            // ── Read columns ──────────────────────────────────────────────────────
            $prodSku              = $row[0]  ?? '';
            $varSku               = $row[1]  ?? '';
            $name                 = $row[2]  ?? '';
            $barcode              = isset($row[3]) && $row[3] !== '' ? $row[3] : null;
            
            // ✅ NEW COST BREAKDOWN
            $supplierCostPrice    = $row[4]  ?? '';
            $totalShippingCost    = $row[5]  ?? '';
            $uraTaxesApplied      = $row[6]  ?? '';
            $additionalExpenses   = $row[7]  ?? '';
            $grandTotalCostPrice  = $row[8]  ?? '';
            
            // ✅ PRICING
            $sellingPrice         = $row[9]  ?? '';
            $discountSellingPrice = $row[10] ?? '';
            $discountPercentage   = $row[11] ?? '';
            $markupPercentage     = $row[12] ?? '';
            
            $qty                  = isset($row[13]) && $row[13] !== '' ? (int) $row[13] : 0;
            $weight               = isset($row[14]) && $row[14] !== '' ? (float) $row[14] : 0.00;
            $weightUnitRaw        = $row[15] ?? '';
            $isTaxable            = isset($row[16]) && $row[16] !== '' ? (int) $row[16] : 1;
            $isActive             = isset($row[17]) && $row[17] !== '' ? (int) $row[17] : 1;

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
            
            // ✅ Validate new cost fields
            if ($supplierCostPrice !== '' && (!is_numeric($supplierCostPrice) || (float)$supplierCostPrice < 0)) {
                $stat['errors'][] = "Row {$rowNum}: supplier_cost_price must be a non-negative number.";
                continue;
            }
            if ($totalShippingCost !== '' && (!is_numeric($totalShippingCost) || (float)$totalShippingCost < 0)) {
                $stat['errors'][] = "Row {$rowNum}: total_shipping_cost must be a non-negative number.";
                continue;
            }
            if ($uraTaxesApplied !== '' && (!is_numeric($uraTaxesApplied) || (float)$uraTaxesApplied < 0)) {
                $stat['errors'][] = "Row {$rowNum}: ura_taxes_applied must be a non-negative number.";
                continue;
            }
            if ($additionalExpenses !== '' && (!is_numeric($additionalExpenses) || (float)$additionalExpenses < 0)) {
                $stat['errors'][] = "Row {$rowNum}: additional_expenses must be a non-negative number.";
                continue;
            }
            if ($grandTotalCostPrice !== '' && (!is_numeric($grandTotalCostPrice) || (float)$grandTotalCostPrice < 0)) {
                $stat['errors'][] = "Row {$rowNum}: grand_total_cost_price must be a non-negative number.";
                continue;
            }
            if ($sellingPrice === '' || !is_numeric($sellingPrice) || (float)$sellingPrice < 0) {
                $stat['errors'][] = "Row {$rowNum}: selling_price must be a non-negative number.";
                continue;
            }
            if ($discountSellingPrice !== '' && (!is_numeric($discountSellingPrice) || (float)$discountSellingPrice < 0)) {
                $stat['errors'][] = "Row {$rowNum}: discount_selling_price must be a non-negative number.";
                continue;
            }
            if ($discountPercentage !== '' && (!is_numeric($discountPercentage) || (float)$discountPercentage < 0 || (float)$discountPercentage > 100)) {
                $stat['errors'][] = "Row {$rowNum}: discount_percentage must be between 0 and 100.";
                continue;
            }
            if ($markupPercentage !== '' && (!is_numeric($markupPercentage) || (float)$markupPercentage < 0)) {
                $stat['errors'][] = "Row {$rowNum}: markup_percentage must be a non-negative number.";
                continue;
            }
            if ($weightUnitRaw === '') {
                $stat['errors'][] = "Row {$rowNum}: weight_unit_id is required (numeric id, or a Unit of Measure name).";
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

            // ── Resolve weight unit ─────────────────────────────────────────────
            $uomKey = $this->resolveWeightUnitId($weightUnitRaw, $tenantId, $rowNum, $stat, $uomNameCache);
            if ($uomKey === null) {
                continue;
            }

            if (!isset($uomCache[$uomKey])) {
                $uomCache[$uomKey] = UnitOfMeasure::where('id', $uomKey)
                                                ->where('tenant_id', $tenantId)
                                                ->first();
            }
            if (!$uomCache[$uomKey]) {
                $stat['errors'][] = "Row {$rowNum}: Unit of Measure ID \"{$uomKey}\" not found for this tenant.";
                continue;
            }

            // ── Normalize pricing fields ─────────────────────────────────────────
            $supplierCostVal     = $supplierCostPrice !== '' ? (float) $supplierCostPrice : 0;
            $shippingCostVal     = $totalShippingCost !== '' ? (float) $totalShippingCost : 0;
            $taxesVal            = $uraTaxesApplied !== '' ? (float) $uraTaxesApplied : 0;
            $expensesVal         = $additionalExpenses !== '' ? (float) $additionalExpenses : 0;
            
            // ✅ Calculate Grand Total Cost Price if not provided or zero
            $grandTotalVal = $grandTotalCostPrice !== '' ? (float) $grandTotalCostPrice : 0;
            if ($grandTotalVal <= 0) {
                $grandTotalVal = $supplierCostVal + $shippingCostVal + $taxesVal + $expensesVal;
            }
            
            $sellingPriceVal     = (float) $sellingPrice;
            $discountPct         = $discountPercentage !== '' ? (float) $discountPercentage : 0;
            
            // ✅ Calculate discount selling price if not provided
            $discountSellingVal = $discountSellingPrice !== '' 
                ? (float) $discountSellingPrice 
                : round($sellingPriceVal * (1 - ($discountPct / 100)), 2);
            
            // ✅ Calculate markup percentage if not provided
            $markupPct = $markupPercentage !== '' ? (float) $markupPercentage : 0;
            if ($markupPct <= 0 && $grandTotalVal > 0 && $sellingPriceVal > 0 && $sellingPriceVal > $grandTotalVal) {
                $markupPct = round((($sellingPriceVal - $grandTotalVal) / $grandTotalVal) * 100, 2);
                if ($markupPct > 999.99) {
                    $markupPct = 999.99;
                }
            }

            // ── Check for an existing variant ─────────────────────────────────────
            $existingVariant = ProductVariant::where('tenant_id', $tenantId)
                                            ->where('sku', $varSku)
                                            ->first();

            if ($existingVariant) {
                $existingVariant->update([
                    'name'                     => $name,
                    'supplier_cost_price'      => (int) round($supplierCostVal),
                    'total_shipping_cost'      => (int) round($shippingCostVal),
                    'ura_taxes_applied'        => (int) round($taxesVal),
                    'additional_expenses'      => (int) round($expensesVal),
                    'grand_total_cost_price'   => (int) round($grandTotalVal),
                    'selling_price'            => (int) round($sellingPriceVal),
                    'discount_selling_price'   => (int) round($discountSellingVal),
                    'discount_percentage'      => $discountPct,
                    'markup_percentage'        => $markupPct,
                    'weight_unit'              => $uomKey,
                    'weight'                   => $weight,
                    'is_taxable'               => in_array($isTaxable, [0, 1]) ? $isTaxable : 1,
                    'is_active'                => in_array($isActive,  [0, 1]) ? $isActive  : 1,
                    // overal_quantity_at_hand intentionally NOT included
                ]);

                $stat['updated'] = ($stat['updated'] ?? 0) + 1;
                continue;
            }

            // ── New variant — auto-generate barcode if left blank ───────────────
            if ($barcode === null) {
                $barcode = $this->generateUniqueBarcode($tenantId);
            } else {
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
                'product_id'               => $product->id,
                'sku'                      => $varSku,
                'name'                     => $name,
                'barcode'                  => $barcode,
                'supplier_cost_price'      => (int) round($supplierCostVal),
                'total_shipping_cost'      => (int) round($shippingCostVal),
                'ura_taxes_applied'        => (int) round($taxesVal),
                'additional_expenses'      => (int) round($expensesVal),
                'grand_total_cost_price'   => (int) round($grandTotalVal),
                'selling_price'            => (int) round($sellingPriceVal),
                'discount_selling_price'   => (int) round($discountSellingVal),
                'discount_percentage'      => $discountPct,
                'markup_percentage'        => $markupPct,
                'overal_quantity_at_hand'  => $qty,
                'weight'                   => $weight,
                'weight_unit'              => $uomKey,
                'is_taxable'               => in_array($isTaxable, [0, 1]) ? $isTaxable : 1,
                'is_active'                => in_array($isActive,  [0, 1]) ? $isActive  : 1,
                'created_by'               => $userId,
                'tenant_id'                => $tenantId,
            ]);

            $stat['created']++;
        }
    }


    /**
     * Resolve a weight_unit_id cell value to a numeric UnitOfMeasure id.
     *
     * Accepts EITHER:
     *   - a raw numeric id (backward compatible, used as-is), OR
     *   - the Unit of Measure's NAME, matched case-insensitively against
     *     THIS TENANT's own unit_of_measures table (no fixed/shared mapping —
     *     every tenant configures their own units under Settings > Units of
     *     Measure, so "Pieces" for Tenant A and Tenant B may have completely
     *     different ids, or not exist at all for one of them).
     *
     * Returns null and records an error on $stat if the value can't be
     * resolved to a real UnitOfMeasure row for this tenant.
     */
    private function resolveWeightUnitId(string $value, int $tenantId, int $rowNum, array &$stat, array &$uomNameCache): ?int
    {
        $trimmed = trim($value);

        if (is_numeric($trimmed)) {
            return (int) $trimmed;
        }

        $lookupKey = strtolower($trimmed);

        if (!array_key_exists($lookupKey, $uomNameCache)) {
            $uom = UnitOfMeasure::where('tenant_id', $tenantId)
                ->whereRaw('LOWER(name) = ?', [$lookupKey])
                ->first();

            $uomNameCache[$lookupKey] = $uom?->id;
        }

        if ($uomNameCache[$lookupKey] !== null) {
            return $uomNameCache[$lookupKey];
        }

        $stat['errors'][] = "Row {$rowNum}: weight_unit_id \"{$value}\" is not a valid numeric id, and no Unit of Measure named \"{$value}\" was found for your account. Check Settings > Units of Measure.";

        return null;
    }


    // ───────────────────────────────────────────────────────────────────────────
    // BARCODE AUTO-GENERATION (only — SKU stays required from the sheet)
    // ───────────────────────────────────────────────────────────────────────────

    /**
     * Generate a unique, standard-format EAN-13 barcode, scoped per tenant.
     */
    private function generateUniqueBarcode(int $tenantId): string
    {
        do {
            $digits = (string) random_int(1, 9);
            for ($i = 0; $i < 11; $i++) {
                $digits .= (string) random_int(0, 9);
            }

            $barcode = $digits . $this->calculateEan13CheckDigit($digits);

            $exists = ProductVariant::where('tenant_id', $tenantId)
                ->where('barcode', $barcode)
                ->exists();
        } while ($exists);

        return $barcode;
    }

    /**
     * Standard EAN-13 check digit algorithm (odd positions x1, even x3, mod 10).
     */
    private function calculateEan13CheckDigit(string $twelveDigits): int
    {
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $digit  = (int) $twelveDigits[$i];
            $weight = ($i % 2 === 0) ? 1 : 3;
            $sum   += $digit * $weight;
        }

        return (10 - ($sum % 10)) % 10;
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