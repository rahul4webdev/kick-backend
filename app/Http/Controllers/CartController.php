<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\CoinTransaction;
use App\Models\Constants;
use App\Models\GlobalFunction;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductOrder;
use App\Models\ShippingAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CartController extends Controller
{
    // ─── Cart Endpoints ─────────────────────────────────────────

    public function fetchCart(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => 'This user is frozen!'];
        }

        $items = CartItem::where('user_id', $user->id)
            ->with(['product.seller', 'variant'])
            ->get();

        $totalPaise = $items->sum(function ($item) {
            return $item->getLineTotalPaise();
        });

        $totalCoins = $items->sum(function ($item) {
            return ($item->product->price_coins ?? 0) * $item->quantity;
        });

        return [
            'status' => true,
            'message' => 'Cart fetched successfully',
            'data' => $items,
            'total_coins' => $totalCoins,
            'total_paise' => $totalPaise,
            'total_rupees' => round($totalPaise / 100, 2),
            'item_count' => $items->count(),
        ];
    }

    public function addToCart(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => 'This user is frozen!'];
        }

        $product = Product::find($request->product_id);
        if (!$product || !$product->is_active) {
            return GlobalFunction::sendSimpleResponse(false, 'Product not found');
        }

        // If product has variants, variant_id is required
        if ($product->has_variants && !$request->variant_id) {
            return GlobalFunction::sendSimpleResponse(false, 'Please select a variant');
        }

        // Check stock (variant-aware)
        if (!$product->hasStock($request->quantity ?? 1, $request->variant_id)) {
            return GlobalFunction::sendSimpleResponse(false, 'Product is out of stock');
        }

        // Upsert cart item (variant-aware unique key)
        $matchCriteria = [
            'user_id' => $user->id,
            'product_id' => $request->product_id,
        ];
        if ($request->variant_id) {
            $matchCriteria['variant_id'] = $request->variant_id;
        }

        $item = CartItem::updateOrCreate(
            $matchCriteria,
            ['quantity' => DB::raw('quantity + ' . ($request->quantity ?? 1))]
        );

        // If it was an update, reload to get new quantity
        $item->refresh();

        // Validate stock
        $maxStock = $product->stock;
        if ($request->variant_id) {
            $variant = \App\Models\ProductVariant::find($request->variant_id);
            if ($variant && $variant->stock != -1) {
                $maxStock = $variant->stock;
            }
        }
        if ($maxStock != -1 && $item->quantity > $maxStock) {
            $item->quantity = $maxStock;
            $item->save();
        }

        return [
            'status' => true,
            'message' => 'Added to cart',
            'cart_count' => CartItem::where('user_id', $user->id)->count(),
        ];
    }

    public function updateCartItem(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => 'This user is frozen!'];
        }

        $item = CartItem::where('user_id', $user->id)
            ->where('id', $request->cart_item_id)
            ->first();

        if (!$item) {
            return GlobalFunction::sendSimpleResponse(false, 'Cart item not found');
        }

        if ($request->quantity <= 0) {
            $item->delete();
            return GlobalFunction::sendSimpleResponse(true, 'Item removed from cart');
        }

        $item->quantity = $request->quantity;
        $item->save();

        return GlobalFunction::sendSimpleResponse(true, 'Cart updated');
    }

    public function removeFromCart(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => 'This user is frozen!'];
        }

        CartItem::where('user_id', $user->id)
            ->where('id', $request->cart_item_id)
            ->delete();

        return GlobalFunction::sendSimpleResponse(true, 'Item removed from cart');
    }

    public function clearCart(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));
        CartItem::where('user_id', $user->id)->delete();
        return GlobalFunction::sendSimpleResponse(true, 'Cart cleared');
    }

    // ─── Shipping Address Endpoints ─────────────────────────────

    public function fetchAddresses(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));
        $addresses = ShippingAddress::where('user_id', $user->id)
            ->orderByDesc('is_default')
            ->orderByDesc('id')
            ->get();

        return GlobalFunction::sendDataResponse(true, 'Addresses fetched', $addresses);
    }

    public function addAddress(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));

        $rules = [
            'name' => 'required|string|max:200',
            'address_line1' => 'required|string',
            'city' => 'required|string|max:100',
            'zip_code' => 'required|string|max:20',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        // If this is the first address or marked as default, clear other defaults
        $existingCount = ShippingAddress::where('user_id', $user->id)->count();
        $isDefault = $existingCount == 0 || ($request->is_default == true);

        if ($isDefault) {
            ShippingAddress::where('user_id', $user->id)->update(['is_default' => false]);
        }

        $address = ShippingAddress::create([
            'user_id' => $user->id,
            'name' => $request->name,
            'phone' => $request->phone,
            'address_line1' => $request->address_line1,
            'address_line2' => $request->address_line2,
            'city' => $request->city,
            'state' => $request->state,
            'zip_code' => $request->zip_code,
            'country' => $request->country ?? 'India',
            'is_default' => $isDefault,
        ]);

        return GlobalFunction::sendDataResponse(true, 'Address added', $address);
    }

    public function editAddress(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));
        $address = ShippingAddress::where('user_id', $user->id)->where('id', $request->id)->first();
        if (!$address) {
            return GlobalFunction::sendSimpleResponse(false, 'Address not found');
        }

        if ($request->has('name')) $address->name = $request->name;
        if ($request->has('phone')) $address->phone = $request->phone;
        if ($request->has('address_line1')) $address->address_line1 = $request->address_line1;
        if ($request->has('address_line2')) $address->address_line2 = $request->address_line2;
        if ($request->has('city')) $address->city = $request->city;
        if ($request->has('state')) $address->state = $request->state;
        if ($request->has('zip_code')) $address->zip_code = $request->zip_code;
        if ($request->has('country')) $address->country = $request->country;

        if ($request->is_default == true) {
            ShippingAddress::where('user_id', $user->id)->update(['is_default' => false]);
            $address->is_default = true;
        }

        $address->save();
        return GlobalFunction::sendSimpleResponse(true, 'Address updated');
    }

    public function deleteAddress(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));
        ShippingAddress::where('user_id', $user->id)->where('id', $request->id)->delete();
        return GlobalFunction::sendSimpleResponse(true, 'Address deleted');
    }

    // ─── Checkout Endpoint ──────────────────────────────────────

    public function checkout(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => 'This user is frozen!'];
        }

        $cartItems = CartItem::where('user_id', $user->id)->with('product')->get();
        if ($cartItems->isEmpty()) {
            return GlobalFunction::sendSimpleResponse(false, 'Cart is empty');
        }

        // Validate shipping address
        $address = null;
        if ($request->address_id) {
            $address = ShippingAddress::where('user_id', $user->id)
                ->where('id', $request->address_id)->first();
        }

        // Calculate total
        $totalCoins = 0;
        $orderItems = [];
        foreach ($cartItems as $cartItem) {
            $product = $cartItem->product;
            if (!$product || !$product->is_active) continue;

            // Check stock
            if ($product->stock != -1 && $product->stock < $cartItem->quantity) {
                return GlobalFunction::sendSimpleResponse(false, "'{$product->name}' has insufficient stock");
            }

            $itemTotal = $product->price_coins * $cartItem->quantity;
            $totalCoins += $itemTotal;

            $orderItems[] = [
                'product' => $product,
                'quantity' => $cartItem->quantity,
                'price_coins' => $product->price_coins,
                'total' => $itemTotal,
            ];
        }

        // Check buyer's coin balance
        if ($user->my_wallet < $totalCoins) {
            return GlobalFunction::sendSimpleResponse(false, 'Insufficient coins');
        }

        // Process the order in a transaction
        return DB::transaction(function () use ($user, $orderItems, $totalCoins, $address, $request) {
            // Debit buyer
            $user->decrement('my_wallet', $totalCoins);

            // Group items by seller
            $sellerGroups = collect($orderItems)->groupBy(fn($item) => $item['product']->seller_id);

            $orderIds = [];
            foreach ($sellerGroups as $sellerId => $items) {
                $sellerTotal = collect($items)->sum('total');

                // Create order
                $order = ProductOrder::create([
                    'product_id' => $items->first()['product']->id,
                    'buyer_id' => $user->id,
                    'seller_id' => $sellerId,
                    'quantity' => $items->sum('quantity'),
                    'total_coins' => $sellerTotal,
                    'status' => 0, // pending
                    'shipping_address' => $address ? json_encode($address->toArray()) : null,
                    'buyer_note' => $request->note,
                ]);

                // Create order items
                foreach ($items as $item) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $item['product']->id,
                        'quantity' => $item['quantity'],
                        'price_coins' => $item['price_coins'],
                    ]);

                    // Update stock
                    if ($item['product']->stock != -1) {
                        $item['product']->decrement('stock', $item['quantity']);
                    }
                    $item['product']->increment('sold_count', $item['quantity']);
                }

                // Credit seller
                $seller = \App\Models\User::find($sellerId);
                if ($seller) {
                    $seller->increment('my_wallet', $sellerTotal);
                }

                // Create coin transactions
                CoinTransaction::create([
                    'user_id' => $user->id,
                    'type' => 'product_purchase',
                    'coins' => $sellerTotal,
                    'direction' => Constants::debit,
                    'related_user_id' => $sellerId,
                    'reference_id' => $order->id,
                    'note' => 'Product purchase - Order #' . $order->id,
                ]);

                CoinTransaction::create([
                    'user_id' => $sellerId,
                    'type' => 'product_sale',
                    'coins' => $sellerTotal,
                    'direction' => Constants::credit,
                    'related_user_id' => $user->id,
                    'reference_id' => $order->id,
                    'note' => 'Product sale - Order #' . $order->id,
                ]);

                $orderIds[] = $order->id;
            }

            // Clear cart
            CartItem::where('user_id', $user->id)->delete();

            return [
                'status' => true,
                'message' => 'Order placed successfully',
                'order_ids' => $orderIds,
                'total_coins' => $totalCoins,
                'coins_remaining' => $user->fresh()->my_wallet,
            ];
        });
    }
}
