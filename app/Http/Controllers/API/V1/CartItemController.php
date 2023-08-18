<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as RoutingController;
use Illuminate\Support\Facades\DB;

class CartItemController extends RoutingController
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
    public function store(Request $request)
    {
        //
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
    public function update(Request $request,$id)
    {
        $user = $request->user();
        $id_customer = $user->id_user;
        $quantity = $request->quantity;
        $cartItem = CartItem::where('id_variant', $id)
            ->where('id_customer', $id_customer)
            ->first();
        if ($cartItem) {
            CartItem::where('id_variant', $id)
                ->where('id_customer', $id_customer)
                ->update(['quantity' => $quantity]);
            return response()->json(['message' => 'Cập nhật sản phẩm trong giỏ hàng thành công'],200);
        }
        return response()->json(['message' => 'Sản phẩm không tồn tại']);
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $id_customer = $user->id_user;
        $deletedRows = CartItem::where('id_customer', $id_customer)
                               ->where('id_variant',  $id)
                               ->delete();

        if ($deletedRows === 0) {
            return response()->json(['message' => 'Xóa sản phẩm thất bại'], 201);
        }

        return response()->json(['message' => 'Sản phẩm đã xóa khỏi giỏ hàng'], 200);
    }
    
    public function getCartItemByUser(Request $request)
    {
      
        $user = $request->user();
        $id_customer = $user->id_user;
       
        $cartItems = DB::table('cart_item')
        ->join('shoevariant', 'shoevariant.id_variant', '=', 'cart_item.id_variant')
        ->join('shoe', 'shoe.id_shoe', '=', 'shoevariant.id_shoe')
        ->leftJoin('discount', 'discount.id_discount', '=', 'shoe.id_discount')
        ->select('shoe.id_shoe', 'shoe.name_shoe', 'shoe.price', 'discount.discount_value', 'cart_item.*', 'shoevariant.*')
        ->where('cart_item.id_customer', $id_customer)->orderBy('updated_at', 'desc')
        ->paginate(8);

        return response()->json($cartItems);
        
    }

    public function addToCart(Request $request)
    {
        $request->validate([
            'id_variant' => 'required|integer',
            'quantity' => 'required|integer|min:1',
        ]);
        $user = $request->user();
        $id_customer = $user->id_user;
        $quantity = $request->quantity;
        $cartItem = CartItem::where('id_variant', $request->id_variant)
            ->where('id_customer', $id_customer)
            ->first();
        if ($cartItem) {
            CartItem::where('id_variant', $request->id_variant)
                ->where('id_customer', $id_customer)
                ->increment('quantity', $quantity);
            return response()->json(['message' => 'Cập nhật sản phẩm trong giỏ hàng thành công'],200);
        } else {
            CartItem::create([
                'id_customer' => $id_customer,
                'id_variant' => $request->id_variant,
                'quantity' => $quantity,
            ]);
            return response()->json(['message' => 'Thêm sản phẩm vào giỏ hàng thành công'],200);
        }
    }
  
}
