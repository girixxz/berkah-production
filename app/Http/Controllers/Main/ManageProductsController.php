<?php

namespace App\Http\Controllers\Main;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProductCategory;
use App\Models\MaterialCategory;
use App\Models\MaterialTexture;
use App\Models\MaterialSleeve;
use App\Models\MaterialSize;
use App\Models\Shipping;
use App\Models\Service;


class ManageProductsController extends Controller
{
    public function index()
    {
        $productCategories = ProductCategory::paginate(5, ['*'], 'product_page');
        $materialCategories = MaterialCategory::paginate(5, ['*'], 'material_page');
        $materialTextures = MaterialTexture::paginate(5, ['*'], 'texture_page');
        $materialSleeves = MaterialSleeve::paginate(5, ['*'], 'sleeve_page');
        $materialSizes = MaterialSize::paginate(5, ['*'], 'size_page');
        $services = Service::paginate(5, ['*'], 'service_page');
        $shippings = Shipping::paginate(5, ['*'], 'shipping_page');

        return view('pages.owner.manage-data.products', compact(
            'productCategories',
            'materialCategories',
            'materialTextures',
            'materialSleeves',
            'materialSizes',
            'services',
            'shippings'
        ));
    }
}
