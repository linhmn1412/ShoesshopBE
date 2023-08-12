<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Shoe;
use App\Models\ShoeVariant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as RoutingController;
use Illuminate\Support\Facades\DB;

class OrderController extends RoutingController
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
        $request->validate([
            // 'name_buyer' => 'required',
            // 'phone_number' => ['required', 'numeric', 'digits:10'],
            // 'address' => 'required',
            // 'checkout.*.quantity' => 'required|integer|min:1',
            // 'checkout.*.price' => 'required|numeric|min:0',
        ]);
        $customer = $request->user();
        
        $order = Order::create([
            'id_customer'=> $customer->id_user,
            'name_buyer' => $request->name_buyer,
            'phone_number'=> $request->phone_number,
            'address'=>$request->address,
            'note'=> $request->note,
            'total'=> $request->total,
            'payment'=> $request->payment,
            'status'=> 'Chờ xác nhận'
        ]);

        foreach ($request->orderItems as $item) {
            OrderDetail::create([
                'id_order' => $order->id_order,
                'id_variant' => $item['id_variant'],
                'quantity' => $item['quantity'],
                'cur_price' => $item['cur_price']
            ]);
            //update quantity variant
            $variant = ShoeVariant::find($item['id_variant']);
            if (!$variant) {
                return response()->json(['error' => 'Biến thể không tồn tại.'], 404);
            }
    
            if ($variant['quantity_stock'] < $item['quantity']) {
                return response()->json(['error' => 'Số lượng trong kho không đủ.'], 400);
            }
        
            $variant['quantity_stock'] -= $item['quantity'];
            $variant['quantity_sold'] += $item['quantity'];
            $variant->save();
            
            //update cart item
            CartItem::where('id_customer', $customer->id_user)
                               ->where('id_variant', $item['id_variant'])
                               ->delete();
        
        }
        

        return response()->json(['message' => 'Đã đặt hàng thành công',
    'id_order' => $order->id_order], 201);
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
    
    public function getOrderPending (Request $request){
        $user = $request->user();
        if($user->id_role === 1 || $user->id_role === 2){
            $order = Order::with('orderDetails')->where('status','Chờ xác nhận')->paginate(10);
            return response()->json($order);
        }
        return response()->json(["Message"=>"Người dùng không có quyền xem"]);
        
    }

    public function getAllOrders (Request $request){
        $user = $request->user();
        if($user->id_role === 1 || $user->id_role === 2){
            $orders = Order::with('orderDetails')->orderBy('created_at', 'desc')->paginate(10);

            $transformedOrders = $orders->map(function ($order) {
                $orderData = $order->toArray();
                $orderData['fullname'] = User::where('id_user', $orderData['id_staff'])->value('fullname');
                return $orderData;
            });
            $orders->setCollection($transformedOrders);
     
            return response()->json($orders);
        }
        return response()->json(["Message"=>"Người dùng không có quyền xem"]);
        
    }

    public function getOrdersByUser (Request $request){
        $user = $request->user();

            $orders = Order::with('orderDetails.shoeVariant.shoe','reviews')
            ->where('id_customer', $user->id_user)->orderBy('created_at', 'desc')->get();
            return response()->json($orders);     
    }

    public function approveOrder (Request $request){
        $user = $request->user();
        if($user->id_role=== 1 || $user->id_role === 2){
            $order = Order::find($request->id_order);
            if(!$order){
                return response()->json(["message"=>"Không tìm thấy đơn hàng"]);
            }
            if($order->status === "Đã xác nhận")
            {
                return response()->json(["message"=>"Đơn hàng đã được xác nhận trước đó."]);

            }
            $order->update(['status' => 'Đã xác nhận',
            'id_staff' => $user->id_user]);
            return response()->json(["message"=> "Đơn hàng đã duyệt thành công"]);
        }
        return response()->json(["message"=>"Người dùng không có quyền duyệt"]);
        
    }

    public function cancelOrder (Request $request){
        $user = $request->user();
     
            $order = Order::find($request->id_order);
            if(!$order){
                return response()->json(["message"=>"Không tìm thấy đơn hàng"]);
            }
            if($order->status === "Chờ xác nhận");
            {
                $order->update(['status' => 'Đã hủy']);
                foreach ($order->orderDetails as $orderDetail) {
                    $variant = $order->variants->firstWhere('id_variant', $orderDetail->id_variant);
                    if ($variant) {
                        $variant->quantity_stock += $orderDetail->quantity;
                        $variant->quantity_sold -= $orderDetail->quantity;
                        $variant->save();
                    }
                }
                return response()->json(["message"=> "Đơn hàng đã hủy thành công"]);

            }
            return response()->json(["message"=>"Đơn hàng không thể hủy"]);
      
        
    }

    public function receiveOrder (Request $request){
        $user = $request->user();
     
            $order = Order::find($request->id_order);
            if(!$order){
                return response()->json(["message"=>"Không tìm thấy đơn hàng"]);
            }
            if($order->status === "Đã xác nhận");
            {
                $order->update(['status' => 'Hoàn thành']);
                return response()->json(["message"=> "Đơn hàng hoàn tất, hãy cho nhận xét về đơn hàng!"]);

            }
            return response()->json(["message"=>"Đơn hàng không thể cập nhật"]);
      
        
    }
}
