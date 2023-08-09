<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as RoutingController;
use Illuminate\Support\Facades\DB;

class CategoryController extends RoutingController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $result =Category::join('shoe', 'shoe.id_category', '=', 'category.id_category')
        ->select('category.*')
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

    public function getAllCategories(Request $request)
    {
        $user = $request->user();
        if($user->id_role === 1 || $user->id_role === 2){
            $categories = Category::select(
                'category.*',
                DB::raw('(select fullname from user where user.id_user = category.id_staff) as name_staff'),
            )->paginate(6);
            $allCategories = Category::get();
            $response = [
                'data' => $categories->items(),
                'dataTotal' => $allCategories,
                'current_page' => $categories->currentPage(),
                'per_page' => $categories->perPage(),
                'total' => $categories->total(),
                'last_page' => $categories->lastPage(),
            ];
            return response()->json($response);
        }
        return response()->json(["message"=>"Người dùng không có quyền truy cập"]);
    }
}
