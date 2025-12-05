<?php

namespace App\Http\Controllers\Main;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProductCategory;
use App\Models\MaterialCategory;
use App\Models\MaterialTexture;
use App\Models\MaterialSleeve;
use App\Models\MaterialSize;
use App\Models\Service;


class ManageProductsController extends Controller
{
    public function index(Request $request)
    {
        // Get per_page values for each section with validation
        $perPageProduct = $request->input('per_page_product', 5);
        $perPageProduct = in_array($perPageProduct, [5, 10, 15, 20, 25]) ? $perPageProduct : 5;
        
        $perPageMaterial = $request->input('per_page_material', 5);
        $perPageMaterial = in_array($perPageMaterial, [5, 10, 15, 20, 25]) ? $perPageMaterial : 5;
        
        $perPageTexture = $request->input('per_page_texture', 5);
        $perPageTexture = in_array($perPageTexture, [5, 10, 15, 20, 25]) ? $perPageTexture : 5;
        
        $perPageSleeve = $request->input('per_page_sleeve', 5);
        $perPageSleeve = in_array($perPageSleeve, [5, 10, 15, 20, 25]) ? $perPageSleeve : 5;
        
        $perPageSize = $request->input('per_page_size', 5);
        $perPageSize = in_array($perPageSize, [5, 10, 15, 20, 25]) ? $perPageSize : 5;
        
        $perPageService = $request->input('per_page_service', 5);
        $perPageService = in_array($perPageService, [5, 10, 15, 20, 25]) ? $perPageService : 5;
        
        $productCategories = ProductCategory::paginate($perPageProduct, ['*'], 'product_page');
        $materialCategories = MaterialCategory::paginate($perPageMaterial, ['*'], 'material_page');
        $materialTextures = MaterialTexture::paginate($perPageTexture, ['*'], 'texture_page');
        $materialSleeves = MaterialSleeve::paginate($perPageSleeve, ['*'], 'sleeve_page');
        $materialSizes = MaterialSize::orderBy('sort_order', 'asc')->paginate($perPageSize, ['*'], 'size_page');
        $allMaterialSizes = MaterialSize::orderBy('sort_order', 'asc')->get(); // For sort modal
        $services = Service::paginate($perPageService, ['*'], 'service_page');

        return view('pages.owner.manage-data.products', compact(
            'productCategories',
            'materialCategories',
            'materialTextures',
            'materialSleeves',
            'materialSizes',
            'allMaterialSizes',
            'services',
        ));
    }
}
