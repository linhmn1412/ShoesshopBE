<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as RoutingController;
use Illuminate\Support\Facades\DB;

class ReviewController extends RoutingController
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
        $user = $request->user();
        $createdReviews = [];
        foreach ($request->all() as $reviewData) {
            $review = new Review();
            $review->id_order = $reviewData['id_order'];
            $review->id_customer = $user->id_user;
            $review->id_variant = $reviewData['id_variant'];
            $review->rated = $reviewData['rated'];
            $review->comment = $reviewData['comment'];
            $review->save();

            $createdReviews[] = $review;
        }

        return response()->json(['message' => 'Sản phẩm đã được đánh giá',
        'reviews' => $createdReviews]);
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
    public function getReviewsProductById($id)
    {
        $reviews = DB::table('review')
            ->select('review.*', 'sv.color', 'sv.size', DB::raw('(select username from user where user.id_user = review.id_customer) as username'))
            ->join('shoevariant as sv', 'sv.id_variant', '=', 'review.id_variant')
            ->where('sv.id_shoe', $id)
            ->orderBy('created_at', 'desc')->paginate(4);
        $avgRated = Review::whereIn('id_variant', function ($subquery) use ($id) {
            $subquery->select('id_variant')
                ->from('shoevariant')
                ->where('id_shoe', $id);
        }) ->value(DB::raw('round(avg(rated),1)'));

        return response()->json([
            'reviews' => $reviews,
             'avgRated' => $avgRated
        ]);
    }
}
