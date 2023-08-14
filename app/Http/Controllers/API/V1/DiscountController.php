<?php

namespace App\Http\Controllers\API\V1;

use App\Models\Discount;
use App\Models\Shoe;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as RoutingController;
use Illuminate\Support\Facades\DB;

class DiscountController extends RoutingController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $discounts = Discount::join('shoe', 'shoe.id_discount', '=', 'discount.id_discount')
                        ->select('discount.*')
                        ->distinct()
                        ->get();
        return response()->json($discounts);
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
        Discount::create([
            'name_discount' => $request->name,
            'discount_value' => $request->discount_value,
            'id_staff' => $user->id_user,
        ]);
        return response()->json(["message" => "Thêm khuyến mãi thành công"]);
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
        $user = $request->user();
        Discount::where('id_discount', $id)->update([
            'name_discount' => $request->name,
            'discount_value' => $request->discount_value,
            'id_staff' => $user->id_user,
        ]);
        return response()->json(["message" => "Cập nhật khuyến mãi thành công"],200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request,$id)
    {
        try {
            $discount = Discount::findOrFail($id);
            $product = Shoe::where('id_discount', $id)->first();
    
            if ($product) {
                return response()->json([
                    'message' => 'Không thể xóa khuyến mãi vì đã có sản phẩm sử dụng'
                ], 201);
            }
            $discount->delete();
            return response()->json([
                'message' => 'Xóa khuyến mãi thành công'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Xóa khuyến mãi thất bại'
            ], 202);
        }
    }

    public function getAllDiscounts(Request $request)
    {
        $user = $request->user();
        if($user->id_role === 1 || $user->id_role === 2){
            $discounts = Discount::select(
                'discount.*',
                DB::raw('(select fullname from user where user.id_user = discount.id_staff) as name_staff'),
            )->paginate(10);
            $allDiscounts = Discount::get();
            $response = [
                'data' => $discounts->items(),
                'dataTotal' => $allDiscounts,
                'current_page' => $discounts->currentPage(),
                'per_page' => $discounts->perPage(),
                'total' => $discounts->total(),
                'last_page' => $discounts->lastPage(),
            ];
            return response()->json($response);
        }
        return response()->json(["message"=>"Người dùng không có quyền truy cập"]);
    }
}
