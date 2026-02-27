<?php

namespace App\Http\Controllers;

use App\Models\GlobalFunction;
use App\Models\LiveStreamProduct;
use App\Models\Product;
use App\Models\CartItem;
use Illuminate\Http\Request;

class LiveStreamProductController extends Controller
{
    public function addProductToLive(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => 'This user is frozen!'];
        }

        $product = Product::where('id', $request->product_id)
            ->where('seller_id', $user->id)
            ->where('is_active', true)
            ->first();

        if (!$product) {
            return GlobalFunction::sendSimpleResponse(false, 'Product not found or not yours');
        }

        $maxPosition = LiveStreamProduct::where('room_id', $request->room_id)
            ->where('is_active', true)
            ->max('position') ?? 0;

        $item = LiveStreamProduct::updateOrCreate(
            ['room_id' => $request->room_id, 'product_id' => $request->product_id],
            [
                'seller_id' => $user->id,
                'position' => $maxPosition + 1,
                'is_active' => true,
            ]
        );

        $item->load('product');

        return [
            'status' => true,
            'message' => 'Product added to live stream',
            'data' => $item,
        ];
    }

    public function removeProductFromLive(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));

        LiveStreamProduct::where('room_id', $request->room_id)
            ->where('product_id', $request->product_id)
            ->where('seller_id', $user->id)
            ->update(['is_active' => false]);

        return GlobalFunction::sendSimpleResponse(true, 'Product removed from live stream');
    }

    public function fetchLiveProducts(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));

        $products = LiveStreamProduct::where('room_id', $request->room_id)
            ->where('is_active', true)
            ->with('product.seller')
            ->orderBy('position')
            ->get();

        return [
            'status' => true,
            'message' => 'Live stream products fetched',
            'data' => $products,
        ];
    }

    public function pinProduct(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));

        // Unpin all
        LiveStreamProduct::where('room_id', $request->room_id)
            ->where('seller_id', $user->id)
            ->update(['is_pinned' => false]);

        // Pin selected
        LiveStreamProduct::where('room_id', $request->room_id)
            ->where('product_id', $request->product_id)
            ->where('seller_id', $user->id)
            ->update(['is_pinned' => true]);

        return GlobalFunction::sendSimpleResponse(true, 'Product pinned');
    }

    public function unpinProduct(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));

        LiveStreamProduct::where('room_id', $request->room_id)
            ->where('product_id', $request->product_id)
            ->where('seller_id', $user->id)
            ->update(['is_pinned' => false]);

        return GlobalFunction::sendSimpleResponse(true, 'Product unpinned');
    }

    public function addToCartFromLive(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => 'This user is frozen!'];
        }

        $product = Product::find($request->product_id);
        if (!$product || !$product->is_active) {
            return GlobalFunction::sendSimpleResponse(false, 'Product not found');
        }

        if ($product->stock != -1 && $product->stock < 1) {
            return GlobalFunction::sendSimpleResponse(false, 'Product is out of stock');
        }

        // Upsert cart item
        CartItem::updateOrCreate(
            ['user_id' => $user->id, 'product_id' => $request->product_id],
            ['quantity' => \DB::raw('quantity + ' . ($request->quantity ?? 1))]
        );

        // Track units in live stream product
        if ($request->room_id) {
            LiveStreamProduct::where('room_id', $request->room_id)
                ->where('product_id', $request->product_id)
                ->increment('units_sold', $request->quantity ?? 1);
        }

        return [
            'status' => true,
            'message' => 'Added to cart',
            'cart_count' => CartItem::where('user_id', $user->id)->count(),
        ];
    }

    public function fetchLiveSalesMetrics(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));

        $products = LiveStreamProduct::where('room_id', $request->room_id)
            ->where('seller_id', $user->id)
            ->with('product')
            ->get();

        $totalSold = $products->sum('units_sold');
        $totalRevenue = $products->sum('revenue_coins');

        return [
            'status' => true,
            'message' => 'Sales metrics fetched',
            'data' => [
                'total_units_sold' => $totalSold,
                'total_revenue' => $totalRevenue,
                'products' => $products,
            ],
        ];
    }
}
