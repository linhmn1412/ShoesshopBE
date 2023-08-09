<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductStoreRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Discount;
use App\Models\Review;
use App\Models\Shoe;
use App\Models\ShoeVariant;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as RoutingController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mockery\Undefined;

class ProductController extends RoutingController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function createProduct(Request $request)
    {
        $user = $request->user();
       // dd($user);
        if ($user->id_role === 1 || $user->id_role === 2) {
            try {
                $imageName = Str::random(32) . "." . $request->image->getClientOriginalExtension();
                Shoe::create([
                    'name_shoe' => $request->name_shoe,
                    'id_category' => $request->id_category,
                    'id_brand' => $request->id_brand,
                    'description' => $request->description,
                    'price' => $request->price,
                    'image' => $imageName,
                    'id_discount' => $request->id_discount,
                    'id_staff' => $user->id_user,
                ]);
                // Save Image in Storage folder
                Storage::disk('public')->put($imageName, file_get_contents($request->image));
                // Return Json Response
                return response()->json([
                    'message' => "Thêm sản phẩm thành công"
                ], 200);
            } catch (\Exception $e) {
                return response()->json([
                    'message' => "Tạo sản phẩm thất bại!"
                ], 500);
            }
        }
        return response()->json([
            'message' => "Người dùng không có quyền truy cập!"
        ], 403);
    }
    public function getImage($id)
    {
        $product = Shoe::findOrFail($id);

        // Xác định đường dẫn của hình ảnh trong storage

        $imagePath = 'public/' . $product->image;


        // Kiểm tra xem hình ảnh có tồn tại trong storage hay không
        if (!Storage::exists($imagePath)) {
            return response()->json(['message' => 'Image not found'], 404);
        }

        // Đọc nội dung của hình ảnh từ storage và trả về phản hồi HTTP chứa hình ảnh
        $imageContent = Storage::get($imagePath);

        return response($imageContent)->header('Content-Type', 'image/jpeg');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function getAllProducts()
    {
        $result = Shoe::select(
            'shoe.*',
            // DB::raw('GROUP_CONCAT(DISTINCT sv.size) as sizes'),
            // DB::raw('GROUP_CONCAT(DISTINCT sv.color) as colors'),
            DB::raw('SUM(sv.quantity_stock) as stock'),
            DB::raw('SUM(sv.quantity_sold) as sold'),
            DB::raw('(SELECT discount_value FROM discount WHERE id_discount = shoe.id_discount) as discount_value'),
            DB::raw('(SELECT name_category FROM category WHERE id_category = shoe.id_category) as name_category'),
            DB::raw('(SELECT name_brand FROM brand WHERE id_brand = shoe.id_brand) as name_brand'),

        )
            ->join('shoevariant as sv', 'shoe.id_shoe', '=', 'sv.id_shoe')
            ->groupBy('sv.id_shoe')
            ->paginate(9);

        return response()->json($result);
    }

    public function getProductsNewArrivals()
    {
        $result = Shoe::select(
            'shoe.*',
            DB::raw('SUM(sv.quantity_stock) as stock'),
            DB::raw('(SELECT discount_value FROM discount WHERE id_discount = shoe.id_discount) as discount_value'),
        )
            ->join('shoevariant as sv', 'shoe.id_shoe', '=', 'sv.id_shoe')
            ->groupBy('sv.id_shoe')
            ->orderBy('created_at', 'desc')->paginate(9);

        return response()->json($result);
    }

    public function getProductsBestSellers()
    {
        $result = Shoe::select(
            'shoe.*',
            DB::raw('SUM(sv.quantity_stock) as stock'),
            DB::raw('(SELECT discount_value FROM discount WHERE id_discount = shoe.id_discount) as discount_value'),
        )
            ->join('shoevariant as sv', 'shoe.id_shoe', '=', 'sv.id_shoe')
            ->groupBy('sv.id_shoe')
            ->orderByRaw('SUM(sv.quantity_sold) DESC')->paginate(9);

        return response()->json($result);
    }


    public function getProductsByCategory($category)
    {
        $result = Shoe::select(
            'shoe.*',
            DB::raw('SUM(sv.quantity_stock) as stock'),
            DB::raw('(SELECT discount_value FROM discount WHERE id_discount = shoe.id_discount) as discount_value'),
        )
            ->join('shoevariant as sv', 'shoe.id_shoe', '=', 'sv.id_shoe')
            ->where('shoe.id_category', Category::where('name_category', $category)->value('id_category'))
            ->groupBy('sv.id_shoe')
            ->paginate(9);
        return response()->json($result);
    }

    public function getProductsByBrand($brand)
    {
        $result = Shoe::select(
            'shoe.*',
            DB::raw('SUM(sv.quantity_stock) as stock'),
            DB::raw('(SELECT discount_value FROM discount WHERE id_discount = shoe.id_discount) as discount_value'),
        )
            ->join('shoevariant as sv', 'shoe.id_shoe', '=', 'sv.id_shoe')
            ->where('shoe.id_brand', Brand::where('name_brand', $brand)->value('id_brand'))
            ->groupBy('sv.id_shoe')
            ->paginate(9);
        return response()->json($result);
    }

    public function getProductsByPrice($p1, $p2)
    {
        $result = Shoe::select(
            'shoe.*',
            DB::raw('SUM(sv.quantity_stock) as stock'),
            DB::raw('(SELECT discount_value FROM discount WHERE id_discount = shoe.id_discount) as discount_value'),
        )
            ->join('shoevariant as sv', 'shoe.id_shoe', '=', 'sv.id_shoe')
            ->whereBetween(DB::raw('CAST(price AS UNSIGNED)'), [(int)$p1, (int)$p2])
            ->groupBy('sv.id_shoe')
            ->paginate(9);
        return response()->json($result);
    }

    public function getProductsSale($sale)
    {
        $result = Shoe::select(
            'shoe.*',
            DB::raw('SUM(sv.quantity_stock) as stock'),
            DB::raw('(SELECT discount_value FROM discount WHERE id_discount = shoe.id_discount) as discount_value'),
        )
            ->join('shoevariant as sv', 'shoe.id_shoe', '=', 'sv.id_shoe')
            ->where('shoe.id_discount', Discount::where('discount_value', $sale)->value('id_discount'))
            ->groupBy('sv.id_shoe')
            ->paginate(12);
        return response()->json($result);
    }

    public function getProductDetail($id)
    {
        $data = Shoe::select(
            'shoe.id_shoe',
            'shoe.name_shoe',
            'shoe.price',
            'shoe.description',
            DB::raw('(SELECT name_category FROM category WHERE id_category = shoe.id_category) as name_category'),
            DB::raw('(SELECT name_brand FROM brand WHERE id_brand = shoe.id_brand) as name_brand'),
            DB::raw('(SELECT discount_value FROM discount WHERE id_discount = shoe.id_discount) as discount_value'),
            DB::raw('GROUP_CONCAT(DISTINCT sv.size) as sizes'),
            DB::raw('GROUP_CONCAT(DISTINCT sv.color) as colors'),
            DB::raw('SUM(sv.quantity_stock) as stock'),
            DB::raw('SUM(sv.quantity_sold) as sold')
        )
            ->join('shoevariant as sv', 'shoe.id_shoe', '=', 'sv.id_shoe')
            ->where('shoe.id_shoe', $id)
            ->groupBy('sv.id_shoe')
            ->first();

        $variants = ShoeVariant::where('id_shoe', $id)
            ->get();

        //find similar shoes
        $shoe = Shoe::find($id);

        $similarShoes = Shoe::select(
            'shoe.*',
            DB::raw('SUM(sv.quantity_stock) as stock'),
            DB::raw('(SELECT discount_value FROM discount WHERE id_discount = shoe.id_discount) as discount_value'),
        )
            ->join('shoevariant as sv', 'shoe.id_shoe', '=', 'sv.id_shoe')
            ->where('id_brand', $shoe->id_brand)->where('id_category', $shoe->id_category)
            ->where('shoe.id_shoe', '!=', $shoe->id_shoe)->groupBy('sv.id_shoe')->get();
        if ($similarShoes->count() === 0) {
            $similarShoes = Shoe::select(
                'shoe.*',
                DB::raw('SUM(sv.quantity_stock) as stock'),
                DB::raw('(SELECT discount_value FROM discount WHERE id_discount = shoe.id_discount) as discount_value'),

            )
                ->join('shoevariant as sv', 'shoe.id_shoe', '=', 'sv.id_shoe')
                ->where('id_brand', $shoe->id_brand)->where('shoe.id_shoe', '!=', $shoe->id_shoe)
                ->orWhere('id_category', $shoe->id_category)->where('shoe.id_shoe', '!=', $shoe->id_shoe)
                ->groupBy('sv.id_shoe')->get();
        }

        return response()->json([
            'data' => $data,
            'variants' => $variants,
            'similarShoes' => $similarShoes
        ]);
    }

    public function getAllProductsAdmin()
    {
        $result = Shoe::select(
            'shoe.*',
            DB::raw('(select SUM(sv.quantity_stock) from shoevariant sv where sv.id_shoe = shoe.id_shoe) as stock'),
            DB::raw('(select SUM(sv.quantity_sold) from shoevariant sv where sv.id_shoe = shoe.id_shoe)  as sold'),
            DB::raw('(SELECT discount_value FROM discount WHERE id_discount = shoe.id_discount) as discount_value'),
            DB::raw('(SELECT name_category FROM category WHERE id_category = shoe.id_category) as name_category'),
            DB::raw('(SELECT name_brand FROM brand WHERE id_brand = shoe.id_brand) as name_brand'),
            DB::raw('(select fullname from user where user.id_user = shoe.id_staff) as name_staff'),

        )

            ->paginate(6);

        return response()->json($result);
    }
}
