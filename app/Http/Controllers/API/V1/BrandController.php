<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Shoe;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as RoutingController;
use Illuminate\Support\Facades\DB;

class BrandController extends RoutingController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $result = Brand::join('shoe', 'shoe.id_brand', '=', 'brand.id_brand')
        ->select('brand.*')
        ->distinct()
        ->get();
        return response()->json($result);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $user = $request->user();
        Brand::create([
            'name_brand' => $request->name_brand,
            'id_staff' => $user->id_user,
        ]);
        return response()->json(["message" => "Thêm thương hiệu thành công"]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
       
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $user = $request->user();
        Brand::where('id_brand', $request->id_brand)->update([
            'name_brand' => $request->name_brand,
            'id_staff' => $user->id_user,
        ]);
        return response()->json(["message" => "Cập nhật thương hiệu thành công"]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request )
    {
        try {
            $brand = Brand::findOrFail($request->id_brand);
            $product = Shoe::where('id_brand', $request->id_brand)->first();
    
            if ($product) {
                return response()->json([
                    'message' => 'Không thể xóa thương hiệu vì đã có sản phẩm sử dụng.'
                ], 201);
            }
            $brand->delete();
            return response()->json([
                'message' => 'Xóa thương hiệu thành công.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Xóa thương hiệu thất bại.'
            ], 202);
        }
    }

    public function getAllBrands(Request $request)
    {
        $user = $request->user();
        if($user->id_role === 1 || $user->id_role === 2){
            $brands = Brand::select(
                'brand.*',
                DB::raw('(select fullname from user where user.id_user = brand.id_staff) as name_staff'),
            )->paginate(10);
            $allBrands = Brand::get();
            $response = [
                'data' => $brands->items(),
                'dataTotal' => $allBrands,
                'current_page' => $brands->currentPage(),
                'per_page' => $brands->perPage(),
                'total' => $brands->total(),
                'last_page' => $brands->lastPage(),
            ];
            return response()->json($response);
        }
        return response()->json(["message"=>"Người dùng không có quyền truy cập"]);
    }
}
