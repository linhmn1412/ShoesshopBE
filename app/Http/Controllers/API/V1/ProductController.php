<?php

namespace App\Http\Controllers\API\V1;


use App\Models\Brand;
use App\Models\Category;
use App\Models\Discount;
use App\Models\Shoe;
use App\Models\ShoeVariant;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as RoutingController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends RoutingController
{
    public function createProduct(Request $request)
    {
        $user = $request->user();
       // dd($user);
        if ($user->id_role === 1 || $user->id_role === 2) {
            try {
                $imageName = Str::random(32) . "." . $request->image->getClientOriginalExtension();
                $shoe = Shoe::create([
                    'name_shoe' => $request->name_shoe,
                    'id_category' => $request->id_category,
                    'id_brand' => $request->id_brand,
                    'description' => $request->description,
                    'price' => $request->price,
                    'image' => $imageName,
                    'id_discount' => $request->id_discount,
                    'id_staff' => $user->id_user,
                    'status' => true,
                ]);
                // Save Image in Storage folder
                Storage::disk('public')->put($imageName, file_get_contents($request->image));
                
                $variants = json_decode($request->input('data'), true);
                foreach ($variants as $variant) {
                    ShoeVariant::create([
                        'id_shoe' => $shoe->id_shoe,
                        'color' => $variant['color'],
                        'size' => $variant['size'],
                        'quantity_stock' => $variant['quantity_stock'],
                        'quantity_sold' => 0,
                    ]);
                } 
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


public function updateShoe(Request $request, $id)
{
    $user = $request->user();
    if ($user->id_role === 1 || $user->id_role === 2) {
            $product = Shoe::find($id);

            if (!$product) {
                return response()->json([
                    'message' => 'Sản phẩm không tồn tại'
                ], 404);
            }
            $imageName = $product->image;
            if ($request->hasFile('image')) {
                $uploadedFile = $request->file('image');
                $imageName = Str::random(32) . "." . $uploadedFile->getClientOriginalExtension();
                // Storage::disk('public')->put($imageName, file_get_contents($uploadedFile));
                Storage::disk('public')->put($imageName, file_get_contents($uploadedFile));
               
            }
        //     $product['name_shoe'] = $request->name_shoe;
        //     $product['id_category'] = $request->id_category;
        //     $product['id_brand'] = $request->id_brand;
        //     $product['description'] = $request->description;
        //     $product['price'] = $request->price;
        //    // $product['image'] = $imageName;
        //     $product['id_discount'] = $request->id_discount;
        //     $product['id_staff'] = $user->id_user;

          

        //     $product->save();
        
            Shoe::where('id_shoe', $id)->update([
                'name_shoe' => $request->input('name_shoe'),
                 'id_category' => $request->id_category,
                'id_brand' => $request->id_brand,
                'description' => $request->description,
                'price' => $request->price,
            
               'image' => $imageName  ,
                'id_discount' => $request->id_discount,
                'id_staff' => $user->id_user,
                
            ]);

            return response()->json([
                'message' => "Cập nhật sản phẩm thành công"
            ], 200);
      
         
    }
    return response()->json([
        'message' => "Người dùng không có quyền truy cập!"
    ], 403);
}

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $product = Shoe::findOrFail($id);
    
            // Kiểm tra xem sản phẩm có tồn tại trong ShoeVariant không
            $shoeVariant = DB::table('shoevariant')->where('id_shoe', $id)->first();
    
            if ($shoeVariant) {
                return response()->json([
                    'message' => 'Không thể xóa sản phẩm vì đã bán hoặc còn tồn kho'
                ], 201);
            }
            $product->delete();
            return response()->json([
                'message' => 'Xóa sản phẩm thành công'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Xóa sản phẩm thất bại'
            ], 202);
        }
    }
 
    public function destroyVariant($id)
    {
        try {
            $variant = ShoeVariant::findOrFail($id);
    
            // Kiểm tra xem sản phẩm có tồn tại trong ShoeVariant không
            $orderDetail = DB::table('orderdetail')->where('id_variant', $id)->first();
    
            if ($orderDetail) {
                return response()->json([
                    'message' => 'Không thể xóa biến thể này vì đã bán.'
                ], 201);
            }
            $variant->delete();
            return response()->json([
                'message' => 'Xóa biến thể sản phẩm thành công'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Xóa biến thể sản phẩm thất bại'
            ], 202);
        }
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
            ->where("shoe.status",true)
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
            ->where("shoe.status",true)
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
            ->where("shoe.status",true)
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
            ->where("shoe.status",true)
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
            ->where("shoe.status",true)
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
            ->where("shoe.status",true)
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
            ->where("shoe.status",true)
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

        )->with(['variants' => function ($query) {
            $query->select('id_variant', 'id_shoe', 'color', 'size', 'quantity_stock', 'quantity_sold');
        }])->paginate(10);

        return response()->json($result);
    }

    public function revenueStatistics (Request $request){
        $revenueByMonth = DB::table('order')
        ->select(DB::raw('MONTH(created_at) as month'), DB::raw('SUM(total) as total_revenue'))
        ->where('status', 'Đã xác nhận')->orWhere('status', 'Hoàn thành')
        ->groupBy(DB::raw('MONTH(created_at)'))
        ->orderBy(DB::raw('MONTH(created_at)'))
        ->get();

    return response()->json($revenueByMonth);
    }
    public function topSellingProducts (Request $request){
        try {
            $year = $request->query('year', now()->year);
            $month = $request->query('month', now()->month);
    
            $topSellingProducts = DB::table('orderdetail')
                ->select('shoe.name_shoe', 'shoe.id_shoe', DB::raw('SUM(orderdetail.quantity) as total_quantity'))
                ->join('order', 'orderdetail.id_order', '=', 'order.id_order')
                ->join('shoevariant', 'orderdetail.id_variant', '=', 'shoevariant.id_variant')
                ->join('shoe', 'shoevariant.id_shoe', '=', 'shoe.id_shoe')
                ->where('order.status', 'Đã xác nhận')->orWhere('order.status', 'Hoàn thành')
                ->whereYear('order.created_at',"=", date('Y'))
                ->whereMonth('order.created_at',"=", date('m'))
                ->groupBy('shoe.id_shoe')
                ->orderByDesc('total_quantity')
                ->take(10)
                ->get();
    
            return response()->json([
                'top_selling_products' => $topSellingProducts
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Lỗi khi truy vấn thông kế'
            ], 500);
        }
    }
}
