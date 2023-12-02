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
use Illuminate\Support\Facades\URL;

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
            'id_customer' => $customer->id_user,
            'name_buyer' => $request->name_buyer,
            'phone_number' => $request->phone_number,
            'address' => $request->address,
            'note' => $request->note,
            'total' => $request->total,
            'payment' => $request->payment,
            'status' => 'Chờ xác nhận'
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


        return response()->json([
            'message' => 'Đã đặt hàng thành công',
            'id_order' => $order->id_order
        ], 201);
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

    public function getOrderPending(Request $request)
    {
        $user = $request->user();
        if ($user->id_role === 1 || $user->id_role === 2) {
            $order = Order::with('orderDetails')->where('status', 'Chờ xác nhận')->paginate(10);
            return response()->json($order);
        }
        return response()->json(["Message" => "Người dùng không có quyền xem"]);
    }

    public function getAllOrders(Request $request)
    {
        $user = $request->user();
        if ($user->id_role === 1 || $user->id_role === 2) {
            $orders = Order::with('orderDetails')->orderBy('created_at', 'desc')->paginate(10);

            $transformedOrders = $orders->map(function ($order) {
                $orderData = $order->toArray();
                $orderData['fullname'] = User::where('id_user', $orderData['id_staff'])->value('fullname');
                return $orderData;
            });
            $orders->setCollection($transformedOrders);

            return response()->json($orders);
        }
        return response()->json(["Message" => "Người dùng không có quyền xem"]);
    }

    public function getOrdersByUser(Request $request)
    {
        $user = $request->user();

        $orders = Order::with('orderDetails.shoeVariant.shoe', 'reviews')
            ->where('id_customer', $user->id_user)->orderBy('created_at', 'desc')->get();
        return response()->json($orders);
    }

    public function getOrderById(Request $request, $id)
    {
        $user = $request->user();

        $order = Order::with('orderDetails.shoeVariant.shoe')
            ->where('id_order', $id)
            ->where('id_customer', $user->id_user)
            ->first();

        if ($order) {
            // Trả về thông tin đơn hàng nếu tìm thấy
            return response()->json(['order' => $order], 200);
        } else {
            // Trả về thông báo nếu không tìm thấy đơn hàng
            return response()->json(['message' => 'Đơn hàng không tồn tại'], 404);
        }
    }

    public function confirmOrder(Request $request, $id)
    {
        $user = $request->user();
        if ($user->id_role === 1 || $user->id_role === 2) {
            $order = Order::find($id);
            if (!$order) {
                return response()->json(["message" => "Không tìm thấy đơn hàng"], 202);
            }
            if ($order->status === "Đã xác nhận") {
                return response()->json(["message" => "Đơn hàng đã được xác nhận trước đó."], 201);
            }
            $order->update([
                'status' => 'Đã xác nhận',
                'id_staff' => $user->id_user
            ]);
            return response()->json(["message" => "Đơn hàng đã duyệt thành công"], 200);
        }
        return response()->json(["message" => "Người dùng không có quyền duyệt"], 400);
    }

    public function cancelOrder(Request $request, $id)
    {
        $user = $request->user();

        $order = Order::find($id);
        if (!$order) {
            return response()->json(["message" => "Không tìm thấy đơn hàng"], 202);
        }
        if ($order->status === "Chờ xác nhận"); {
            $order->update(['status' => 'Đã hủy']);
            foreach ($order->orderDetails as $orderDetail) {
                $variant = $order->variants->firstWhere('id_variant', $orderDetail->id_variant);
                if ($variant) {
                    $variant->quantity_stock += $orderDetail->quantity;
                    $variant->quantity_sold -= $orderDetail->quantity;
                    $variant->save();
                }
            }
            return response()->json(["message" => "Đơn hàng đã hủy thành công"], 200);
        }
        return response()->json(["message" => "Đơn hàng không thể hủy"], 201);
    }

    public function receiveOrder(Request $request, $id)
    {
        $user = $request->user();

        $order = Order::find($id);
        if (!$order) {
            return response()->json(["message" => "Không tìm thấy đơn hàng"], 202);
        }
        if ($order->status === "Đã xác nhận"); {
            $order->update(['status' => 'Hoàn thành']);
            return response()->json(["message" => "Đơn hàng hoàn tất, hãy cho nhận xét về đơn hàng!"], 200);
        }
        return response()->json(["message" => "Đơn hàng không thể cập nhật"], 201);
    }

    public function payment(Request $request)
    {
        date_default_timezone_set('Asia/Ho_Chi_Minh');
        $vnp_TmnCode = "AOEHQ1ZC"; //Mã định danh merchant kết nối (Terminal Id)
        $vnp_HashSecret = "GTOQBHWLMRTQCLQXGKLCMRJEPXKGTVBU"; //Secret key
        $vnp_Url = "https://sandbox.vnpayment.vn/paymentv2/vpcpay.html";
        $vnp_Returnurl = "http://127.0.0.1:8000/api/v1/payment-vnpay/callback";
        //Config input format
        //Expire
        $startTime = date("YmdHis");
        $expire = date('YmdHis', strtotime('+15 minutes', strtotime($startTime)));
        $vnp_TxnRef = $request->id_order; //Mã đơn hàng. Trong thực tế Merchant cần insert đơn hàng vào DB và gửi mã này sang VNPAY
        $vnp_Amount = $request->total + 20000;
        $vnp_Locale = 'vn';
        $vnp_BankCode = 'NCB';
        $vnp_IpAddr = $request->ip();

        $inputData = array(
            "vnp_Version" => "2.1.0",
            "vnp_TmnCode" => $vnp_TmnCode,
            "vnp_Amount" => $vnp_Amount * 100,
            "vnp_Command" => "pay",
            "vnp_CreateDate" => date('YmdHis'),
            "vnp_CurrCode" => "VND",
            "vnp_IpAddr" => $vnp_IpAddr,
            "vnp_Locale" => $vnp_Locale,
            "vnp_OrderInfo" => $vnp_TxnRef,
            "vnp_OrderType" => "other",
            "vnp_ReturnUrl" => $vnp_Returnurl,
            "vnp_TxnRef" => $vnp_TxnRef,
            "vnp_ExpireDate" => $expire
        );

        if (isset($vnp_BankCode) && $vnp_BankCode != "") {
            $inputData['vnp_BankCode'] = $vnp_BankCode;
        }
        if (isset($vnp_Bill_State) && $vnp_Bill_State != "") {
            $inputData['vnp_Bill_State'] = $vnp_Bill_State;
        }

        ksort($inputData);
        $query = "";
        $i = 0;
        $hashdata = "";
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashdata .= urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
            $query .= urlencode($key) . "=" . urlencode($value) . '&';
        }

        $vnp_Url = $vnp_Url . "?" . $query;
        if (isset($vnp_HashSecret)) {
            $vnpSecureHash =   hash_hmac('sha512', $hashdata, $vnp_HashSecret); //  
            $vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;
        }

        return response()->json(['payment_link' => $vnp_Url]);
    }

    public function callback(Request $request)
    {
        //Log::info('Callback data from VNPAY:', $request->all());
        // Xử lý dữ liệu callback từ VNPay
        $paymentId = $request->get('vnp_TransactionNo'); // Lấy mã thanh toán từ callback
        $orderId = $request->get('vnp_OrderInfo'); // Lấy ID đơn hàng từ callback

        // Cập nhật trạng thái đơn hàng và lưu mã thanh toán vào database
        $order = Order::find($orderId);
        if ($order) {
            $order->status = 'Đã thanh toán'; // Cập nhật trạng thái thanh toán
            $order->payment_id = $paymentId; // Lưu mã thanh toán
            $order->save();
        }
        return redirect()->away('http://localhost:3000/checkout?order=' . $orderId);
    }
}
