<?php

namespace App\Http\Controllers;

use App\Models\CoinTransaction;
use App\Models\Constants;
use App\Models\GlobalFunction;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductOrder;
use App\Models\ProductReview;
use App\Models\ProductVariant;
use App\Models\PostProductTag;
use App\Models\Posts;
use App\Models\Users;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    // ─── API Endpoints ──────────────────────────────────────────

    public function createProduct(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => 'This user is frozen!'];
        }

        // Must be approved seller
        if (!$user->is_approved_seller) {
            return GlobalFunction::sendSimpleResponse(false, 'You must be an approved seller to list products');
        }

        $rules = [
            'name' => 'required|string|max:255',
            'price_paise' => 'required|integer|min:100', // Min ₹1
            'compare_at_price_paise' => 'nullable|integer|min:0',
            'description' => 'nullable|string|max:5000',
            'category_id' => 'nullable|integer|exists:tbl_product_categories,id',
            'stock' => 'nullable|integer|min:-1',
            'is_digital' => 'nullable|boolean',
            'sku' => 'nullable|string|max:100',
            'brand_name' => 'nullable|string|max:200',
            'weight_grams' => 'nullable|integer|min:1',
            'length_cm' => 'nullable|numeric|min:0.1',
            'breadth_cm' => 'nullable|numeric|min:0.1',
            'height_cm' => 'nullable|numeric|min:0.1',
            'shipping_charge_paise' => 'nullable|integer|min:0',
            'gst_rate' => 'nullable|numeric|in:0,5,12,18,28',
            'hsn_code' => 'nullable|string|max:20',
            'min_order_qty' => 'nullable|integer|min:1',
            'max_order_qty' => 'nullable|integer|min:1',
            'shipping_type' => 'nullable|in:self,platform,both',
            'cod_available' => 'nullable|boolean',
            'return_window_days_override' => 'nullable|integer|min:0|max:30',
            'pickup_location_name' => 'nullable|string|max:255',
            'has_variants' => 'nullable|boolean',
            // Variants as JSON array
            'variants' => 'nullable|array',
            'variants.*.size' => 'nullable|string|max:50',
            'variants.*.color' => 'nullable|string|max:50',
            'variants.*.sku' => 'nullable|string|max:100',
            'variants.*.price_paise' => 'nullable|integer|min:0',
            'variants.*.stock' => 'nullable|integer|min:-1',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return GlobalFunction::sendSimpleResponse(false, $validator->errors()->first());
        }

        $product = new Product();
        $product->seller_id = $user->id;
        $product->name = $request->name;
        $product->description = $request->description;
        $product->price_paise = $request->price_paise;
        $product->compare_at_price_paise = $request->compare_at_price_paise;
        $product->price_coins = 0; // Legacy field
        $product->category_id = $request->category_id;
        $product->stock = $request->stock ?? -1;
        $product->is_digital = $request->is_digital ?? false;
        $product->sku = $request->sku;
        $product->brand_name = $request->brand_name;
        $product->weight_grams = $request->weight_grams;
        $product->length_cm = $request->length_cm;
        $product->breadth_cm = $request->breadth_cm;
        $product->height_cm = $request->height_cm;
        $product->shipping_charge_paise = $request->shipping_charge_paise ?? 0;
        $product->gst_rate = $request->gst_rate ?? ($product->category ? $product->category->default_gst_rate : null);
        $product->hsn_code = $request->hsn_code;
        $product->min_order_qty = $request->min_order_qty ?? 1;
        $product->max_order_qty = $request->max_order_qty;
        $product->shipping_type = $request->shipping_type ?? Product::SHIPPING_PLATFORM;
        $product->cod_available = $request->cod_available ?? true;
        $product->return_window_days_override = $request->return_window_days_override;
        $product->pickup_location_name = $request->pickup_location_name;
        $product->has_variants = $request->has_variants ?? false;
        $product->status = Product::STATUS_PENDING;

        // Handle multiple images
        $images = [];
        if ($request->hasFile('images')) {
            $files = is_array($request->file('images')) ? $request->file('images') : [$request->file('images')];
            foreach ($files as $file) {
                $images[] = GlobalFunction::saveFileAndGivePath($file);
            }
        }
        $product->images = $images;

        // Handle digital file
        if ($request->is_digital && $request->hasFile('digital_file')) {
            $product->digital_file = GlobalFunction::saveFileAndGivePath($request->digital_file);
        }

        $product->save();

        // Create variants if provided
        if ($request->has_variants && $request->variants) {
            foreach ($request->variants as $v) {
                ProductVariant::create([
                    'product_id' => $product->id,
                    'size' => $v['size'] ?? null,
                    'color' => $v['color'] ?? null,
                    'sku' => $v['sku'] ?? null,
                    'price_paise' => $v['price_paise'] ?? 0,
                    'stock' => $v['stock'] ?? -1,
                ]);
            }
        }

        $product->load('variants');

        return GlobalFunction::sendDataResponse(true, 'Product created and submitted for approval', $product);
    }

    public function updateProduct(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => 'This user is frozen!'];
        }

        $product = Product::where('id', $request->product_id)
            ->where('seller_id', $user->id)
            ->first();
        if (!$product) {
            return GlobalFunction::sendSimpleResponse(false, 'Product not found');
        }

        // Core fields
        if ($request->has('name')) $product->name = $request->name;
        if ($request->has('description')) $product->description = $request->description;
        if ($request->has('price_paise')) $product->price_paise = $request->price_paise;
        if ($request->has('compare_at_price_paise')) $product->compare_at_price_paise = $request->compare_at_price_paise;
        if ($request->has('category_id')) $product->category_id = $request->category_id;
        if ($request->has('stock')) $product->stock = $request->stock;
        if ($request->has('is_active')) $product->is_active = $request->is_active;

        // Real money fields
        if ($request->has('sku')) $product->sku = $request->sku;
        if ($request->has('brand_name')) $product->brand_name = $request->brand_name;
        if ($request->has('weight_grams')) $product->weight_grams = $request->weight_grams;
        if ($request->has('length_cm')) $product->length_cm = $request->length_cm;
        if ($request->has('breadth_cm')) $product->breadth_cm = $request->breadth_cm;
        if ($request->has('height_cm')) $product->height_cm = $request->height_cm;
        if ($request->has('shipping_charge_paise')) $product->shipping_charge_paise = $request->shipping_charge_paise;
        if ($request->has('gst_rate')) $product->gst_rate = $request->gst_rate;
        if ($request->has('hsn_code')) $product->hsn_code = $request->hsn_code;
        if ($request->has('min_order_qty')) $product->min_order_qty = $request->min_order_qty;
        if ($request->has('max_order_qty')) $product->max_order_qty = $request->max_order_qty;
        if ($request->has('shipping_type')) $product->shipping_type = $request->shipping_type;
        if ($request->has('cod_available')) $product->cod_available = $request->cod_available;
        if ($request->has('return_window_days_override')) $product->return_window_days_override = $request->return_window_days_override;
        if ($request->has('pickup_location_name')) $product->pickup_location_name = $request->pickup_location_name;
        if ($request->has('has_variants')) $product->has_variants = $request->has_variants;

        if ($request->hasFile('images')) {
            // Delete old images
            if ($product->images) {
                foreach ($product->images as $oldImage) {
                    GlobalFunction::deleteFile($oldImage);
                }
            }
            $images = [];
            $files = is_array($request->file('images')) ? $request->file('images') : [$request->file('images')];
            foreach ($files as $file) {
                $images[] = GlobalFunction::saveFileAndGivePath($file);
            }
            $product->images = $images;
        }

        $product->save();

        // Update variants if provided
        if ($request->has('variants') && is_array($request->variants)) {
            // Delete existing variants and re-create
            ProductVariant::where('product_id', $product->id)->delete();
            foreach ($request->variants as $v) {
                ProductVariant::create([
                    'product_id' => $product->id,
                    'size' => $v['size'] ?? null,
                    'color' => $v['color'] ?? null,
                    'sku' => $v['sku'] ?? null,
                    'price_paise' => $v['price_paise'] ?? 0,
                    'stock' => $v['stock'] ?? -1,
                    'images' => $v['images'] ?? null,
                ]);
            }
            $product->has_variants = true;
            $product->save();
        }

        $product->load('variants');

        return GlobalFunction::sendDataResponse(true, 'Product updated', $product);
    }

    public function deleteProduct(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => 'This user is frozen!'];
        }

        $product = Product::where('id', $request->product_id)
            ->where('seller_id', $user->id)
            ->first();
        if (!$product) {
            return GlobalFunction::sendSimpleResponse(false, 'Product not found');
        }

        // Delete images
        if ($product->images) {
            foreach ($product->images as $img) {
                GlobalFunction::deleteFile($img);
            }
        }
        if ($product->digital_file) {
            GlobalFunction::deleteFile($product->digital_file);
        }

        $product->delete();

        return GlobalFunction::sendSimpleResponse(true, 'Product deleted');
    }

    public function fetchProducts(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => 'This user is frozen!'];
        }

        $limit = $request->limit ?? 20;

        $query = Product::where('is_active', true)
            ->where('status', Product::STATUS_APPROVED)
            ->orderByDesc('id')
            ->limit($limit);

        if ($request->has('last_item_id') && $request->last_item_id) {
            $query->where('id', '<', $request->last_item_id);
        }

        if ($request->has('category_id') && $request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('seller_id') && $request->seller_id) {
            $query->where('seller_id', $request->seller_id);
        }

        if ($request->has('search') && $request->search) {
            $query->where('name', 'LIKE', '%' . $request->search . '%');
        }

        if ($request->has('sort_by')) {
            switch ($request->sort_by) {
                case 'price_low':
                    $query->reorder()->orderByRaw('COALESCE(price_paise, price_coins * 100) ASC');
                    break;
                case 'price_high':
                    $query->reorder()->orderByRaw('COALESCE(price_paise, price_coins * 100) DESC');
                    break;
                case 'popular':
                    $query->reorder()->orderByDesc('sold_count');
                    break;
                case 'rating':
                    $query->reorder()->orderByDesc('avg_rating');
                    break;
            }
        }

        $products = $query->get();

        $products->each(function ($p) {
            $p->seller_info = $p->seller()->first(['id', 'username', 'fullname', 'profile_photo', 'is_verify']);
            $p->category_name = $p->category ? $p->category->name : null;
            $p->image_urls = $this->generateImageUrls($p->images);
            $p->price_rupees = $p->price_paise ? round($p->price_paise / 100, 2) : null;
        });

        return GlobalFunction::sendDataResponse(true, 'Products fetched', $products);
    }

    public function fetchMyProducts(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => 'This user is frozen!'];
        }

        $products = Product::where('seller_id', $user->id)
            ->orderByDesc('id')
            ->get();

        $products->each(function ($p) {
            $p->category_name = $p->category ? $p->category->name : null;
            $p->image_urls = $this->generateImageUrls($p->images);
        });

        return GlobalFunction::sendDataResponse(true, 'My products fetched', $products);
    }

    public function fetchProductById(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => 'This user is frozen!'];
        }

        $product = Product::with('variants')->find($request->product_id);
        if (!$product || !$product->is_active || $product->status != Product::STATUS_APPROVED) {
            return GlobalFunction::sendSimpleResponse(false, 'Product not found');
        }

        // Increment view count
        $product->increment('view_count');

        $product->seller_info = $product->seller()->first(['id', 'username', 'fullname', 'profile_photo', 'is_verify', 'is_approved_seller']);
        $product->category_name = $product->category ? $product->category->name : null;
        $product->image_urls = $this->generateImageUrls($product->images);

        // Real money pricing info
        $product->price_rupees = $product->price_paise ? round($product->price_paise / 100, 2) : null;
        $product->compare_at_price_rupees = $product->compare_at_price_paise ? round($product->compare_at_price_paise / 100, 2) : null;
        $product->shipping_charge_rupees = $product->shipping_charge_paise ? round($product->shipping_charge_paise / 100, 2) : 0;
        $product->return_window_days = $product->getReturnWindowDays();
        $product->is_returnable = $product->isReturnable();

        // Variant images
        if ($product->has_variants) {
            $product->variants->each(function ($v) {
                if ($v->images) {
                    $v->image_urls = $this->generateImageUrls($v->images);
                }
                $v->price_rupees = $v->getEffectivePricePaise() / 100;
            });
        }

        // Check if user has purchased
        $product->has_purchased = ProductOrder::where('product_id', $product->id)
            ->where('buyer_id', $user->id)
            ->whereIn('status', [ProductOrder::STATUS_PENDING, ProductOrder::STATUS_CONFIRMED, ProductOrder::STATUS_SHIPPED, ProductOrder::STATUS_DELIVERED])
            ->exists();

        // Get reviews summary
        $product->review_summary = [
            'total' => $product->rating_count,
            'average' => $product->avg_rating,
        ];

        // Get recent reviews
        $reviews = ProductReview::where('product_id', $product->id)
            ->orderByDesc('id')
            ->limit(5)
            ->get();
        $reviews->each(function ($r) {
            $r->reviewer = Users::where('id', $r->user_id)->first(['id', 'username', 'fullname', 'profile_photo']);
            if ($r->photos) {
                $r->photo_urls = array_map(fn($path) => GlobalFunction::generateFileUrl($path), $r->photos);
            }
        });
        $product->recent_reviews = $reviews;

        return GlobalFunction::sendDataResponse(true, 'Product details', $product);
    }

    public function purchaseProduct(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => 'This user is frozen!'];
        }

        $product = Product::where('id', $request->product_id)
            ->where('is_active', true)
            ->where('status', Product::STATUS_APPROVED)
            ->first();
        if (!$product) {
            return GlobalFunction::sendSimpleResponse(false, 'Product not found');
        }

        if ($product->seller_id == $user->id) {
            return GlobalFunction::sendSimpleResponse(false, 'You cannot purchase your own product');
        }

        // Check stock
        if ($product->stock != -1 && $product->stock < 1) {
            return GlobalFunction::sendSimpleResponse(false, 'Product is out of stock');
        }

        $quantity = $request->quantity ?? 1;
        $totalCoins = $product->price_coins * $quantity;

        if ($user->coin_wallet < $totalCoins) {
            return GlobalFunction::sendSimpleResponse(false, 'Insufficient coins');
        }

        return DB::transaction(function () use ($user, $product, $quantity, $totalCoins, $request) {
            // Deduct coins from buyer
            $user->coin_wallet -= $totalCoins;
            $user->save();

            // Credit coins to seller
            $seller = Users::find($product->seller_id);
            $seller->coin_wallet += $totalCoins;
            $seller->coin_collected_lifetime += $totalCoins;
            $seller->save();

            // Create order
            $order = ProductOrder::create([
                'product_id' => $product->id,
                'buyer_id' => $user->id,
                'seller_id' => $product->seller_id,
                'quantity' => $quantity,
                'total_coins' => $totalCoins,
                'status' => ProductOrder::STATUS_PENDING,
                'shipping_address' => $request->shipping_address,
                'buyer_note' => $request->buyer_note,
            ]);

            // Create coin transactions
            $txnDebit = CoinTransaction::create([
                'user_id' => $user->id,
                'type' => Constants::txnProductPurchase,
                'coins' => $totalCoins,
                'direction' => Constants::debit,
                'related_user_id' => $product->seller_id,
                'reference_id' => $order->id,
                'note' => "Purchased: {$product->name}",
            ]);

            CoinTransaction::create([
                'user_id' => $product->seller_id,
                'type' => Constants::txnProductRevenue,
                'coins' => $totalCoins,
                'direction' => Constants::credit,
                'related_user_id' => $user->id,
                'reference_id' => $order->id,
                'note' => "Product sale: {$product->name} by {$user->fullname}",
            ]);

            $order->transaction_id = $txnDebit->id;
            $order->save();

            // Update product stats
            $product->sold_count += $quantity;
            if ($product->stock != -1) {
                $product->stock -= $quantity;
            }
            $product->save();

            // Process affiliate commission if applicable
            if ($request->affiliate_code) {
                \App\Http\Controllers\AffiliateController::processAffiliateCommission($order, $request->affiliate_code);
            }

            return [
                'status' => true,
                'message' => 'Product purchased successfully',
                'data' => [
                    'order_id' => $order->id,
                    'coins_remaining' => $user->coin_wallet,
                ],
            ];
        });
    }

    public function fetchMyOrders(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => 'This user is frozen!'];
        }

        $limit = $request->limit ?? 20;

        $query = ProductOrder::where('buyer_id', $user->id)
            ->with(['product.seller:id,username,fullname,profile_photo,is_verify'])
            ->orderByDesc('id')
            ->limit($limit);

        if ($request->has('last_item_id') && $request->last_item_id) {
            $query->where('id', '<', $request->last_item_id);
        }

        $orders = $query->get();

        $orders->each(function ($o) {
            if ($o->product && $o->product->images) {
                $o->product->image_urls = $this->generateImageUrls($o->product->images);
            }
        });

        return GlobalFunction::sendDataResponse(true, 'Orders fetched', $orders);
    }

    public function fetchSellerOrders(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => 'This user is frozen!'];
        }

        $limit = $request->limit ?? 20;

        $query = ProductOrder::where('seller_id', $user->id)
            ->with(['product', 'buyer:id,username,fullname,profile_photo'])
            ->orderByDesc('id')
            ->limit($limit);

        if ($request->has('last_item_id') && $request->last_item_id) {
            $query->where('id', '<', $request->last_item_id);
        }

        if ($request->has('status_filter') && $request->status_filter !== null) {
            $query->where('status', $request->status_filter);
        }

        $orders = $query->get();

        $orders->each(function ($o) {
            if ($o->product && $o->product->images) {
                $o->product->image_urls = $this->generateImageUrls($o->product->images);
            }
        });

        return GlobalFunction::sendDataResponse(true, 'Seller orders fetched', $orders);
    }

    public function updateOrderStatus(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => 'This user is frozen!'];
        }

        $order = ProductOrder::where('id', $request->order_id)
            ->where('seller_id', $user->id)
            ->first();
        if (!$order) {
            return GlobalFunction::sendSimpleResponse(false, 'Order not found');
        }

        $newStatus = (int) $request->status;

        // Validate status transition
        $validTransitions = [
            ProductOrder::STATUS_PENDING => [ProductOrder::STATUS_CONFIRMED, ProductOrder::STATUS_CANCELLED],
            ProductOrder::STATUS_CONFIRMED => [ProductOrder::STATUS_SHIPPED, ProductOrder::STATUS_CANCELLED],
            ProductOrder::STATUS_SHIPPED => [ProductOrder::STATUS_DELIVERED],
        ];

        if (!isset($validTransitions[$order->status]) || !in_array($newStatus, $validTransitions[$order->status])) {
            return GlobalFunction::sendSimpleResponse(false, 'Invalid status transition');
        }

        $order->status = $newStatus;

        if ($request->has('tracking_number')) {
            $order->tracking_number = $request->tracking_number;
        }
        if ($request->has('seller_note')) {
            $order->seller_note = $request->seller_note;
        }

        // Handle cancellation — refund coins
        if ($newStatus == ProductOrder::STATUS_CANCELLED) {
            DB::transaction(function () use ($order) {
                $buyer = Users::find($order->buyer_id);
                $buyer->coin_wallet += $order->total_coins;
                $buyer->save();

                $seller = Users::find($order->seller_id);
                $seller->coin_wallet -= $order->total_coins;
                $seller->coin_collected_lifetime -= $order->total_coins;
                $seller->save();

                CoinTransaction::create([
                    'user_id' => $order->buyer_id,
                    'type' => Constants::txnProductPurchase,
                    'coins' => $order->total_coins,
                    'direction' => Constants::credit,
                    'related_user_id' => $order->seller_id,
                    'reference_id' => $order->id,
                    'note' => "Refund: Order #{$order->id} cancelled",
                ]);
            });
        }

        $order->save();

        return GlobalFunction::sendSimpleResponse(true, 'Order status updated');
    }

    public function submitReview(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => 'This user is frozen!'];
        }

        $rules = [
            'product_id' => 'required|integer',
            'rating' => 'required|integer|min:1|max:5',
            'review_text' => 'nullable|string|max:2000',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return GlobalFunction::sendSimpleResponse(false, $validator->errors()->first());
        }

        $product = Product::find($request->product_id);
        if (!$product) {
            return GlobalFunction::sendSimpleResponse(false, 'Product not found');
        }

        // Check if already reviewed
        $existing = ProductReview::where('product_id', $request->product_id)
            ->where('user_id', $user->id)
            ->first();
        if ($existing) {
            return GlobalFunction::sendSimpleResponse(false, 'You have already reviewed this product');
        }

        // Check if purchased (verified purchase)
        $hasPurchased = ProductOrder::where('product_id', $request->product_id)
            ->where('buyer_id', $user->id)
            ->where('status', ProductOrder::STATUS_DELIVERED)
            ->exists();

        $review = ProductReview::create([
            'product_id' => $request->product_id,
            'user_id' => $user->id,
            'rating' => $request->rating,
            'review_text' => $request->review_text,
            'is_verified_purchase' => $hasPurchased,
        ]);

        // Handle review photos
        if ($request->hasFile('photos')) {
            $photos = [];
            $files = is_array($request->file('photos')) ? $request->file('photos') : [$request->file('photos')];
            foreach ($files as $file) {
                $photos[] = GlobalFunction::saveFileAndGivePath($file);
            }
            $review->photos = $photos;
            $review->save();
        }

        // Update product rating
        $stats = ProductReview::where('product_id', $request->product_id)
            ->selectRaw('COUNT(*) as count, AVG(rating) as avg')
            ->first();
        $product->rating_count = $stats->count;
        $product->avg_rating = round($stats->avg, 2);
        $product->save();

        return GlobalFunction::sendDataResponse(true, 'Review submitted', $review);
    }

    public function fetchProductReviews(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => 'This user is frozen!'];
        }

        $limit = $request->limit ?? 20;

        $query = ProductReview::where('product_id', $request->product_id)
            ->orderByDesc('id')
            ->limit($limit);

        if ($request->has('last_item_id') && $request->last_item_id) {
            $query->where('id', '<', $request->last_item_id);
        }

        $reviews = $query->get();

        $reviews->each(function ($r) {
            $r->reviewer = Users::where('id', $r->user_id)->first(['id', 'username', 'fullname', 'profile_photo']);
            if ($r->photos) {
                $r->photo_urls = array_map(fn($path) => GlobalFunction::generateFileUrl($path), $r->photos);
            }
        });

        return GlobalFunction::sendDataResponse(true, 'Reviews fetched', $reviews);
    }

    public function fetchProductCategories(Request $request)
    {
        $categories = ProductCategory::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return GlobalFunction::sendDataResponse(true, 'Categories fetched', $categories);
    }

    // ─── Marketplace Endpoints ──────────────────────────────────

    /**
     * Full-text search products with advanced filters.
     */
    public function searchProducts(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => 'This user is frozen!'];
        }

        $limit = $request->limit ?? 20;
        $query = Product::where('is_active', true)
            ->where('status', Product::STATUS_APPROVED);

        // Full-text search
        if ($request->has('q') && $request->q) {
            $searchTerm = trim($request->q);
            $tsQuery = implode(' & ', array_filter(explode(' ', $searchTerm)));
            $query->whereRaw("search_vector @@ to_tsquery('english', ?)", [$tsQuery . ':*']);
            $query->orderByRaw("ts_rank(search_vector, to_tsquery('english', ?)) DESC", [$tsQuery . ':*']);
        } else {
            $query->orderByDesc('sold_count');
        }

        // Category filter
        if ($request->has('category_id') && $request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        // Price range filter
        if ($request->has('min_price') && $request->min_price !== null) {
            $query->where('price_coins', '>=', (int) $request->min_price);
        }
        if ($request->has('max_price') && $request->max_price !== null) {
            $query->where('price_coins', '<=', (int) $request->max_price);
        }

        // Rating filter
        if ($request->has('min_rating') && $request->min_rating) {
            $query->where('avg_rating', '>=', (float) $request->min_rating);
        }

        // Cursor pagination
        if ($request->has('last_item_id') && $request->last_item_id) {
            $query->where('id', '<', $request->last_item_id);
        }

        $products = $query->limit($limit)->get();

        $products->each(function ($p) {
            $p->seller_info = $p->seller()->first(['id', 'username', 'fullname', 'profile_photo', 'is_verify']);
            $p->category_name = $p->category ? $p->category->name : null;
            $p->image_urls = $this->generateImageUrls($p->images);
        });

        return GlobalFunction::sendDataResponse(true, 'Search results', $products);
    }

    /**
     * Fetch featured/promoted products for marketplace.
     */
    public function fetchFeaturedProducts(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => 'This user is frozen!'];
        }

        $featured = Product::where('is_active', true)
            ->where('status', Product::STATUS_APPROVED)
            ->where('featured_in_marketplace', true)
            ->orderByDesc('sold_count')
            ->limit(10)
            ->get();

        $featured->each(function ($p) {
            $p->seller_info = $p->seller()->first(['id', 'username', 'fullname', 'profile_photo', 'is_verify']);
            $p->image_urls = $this->generateImageUrls($p->images);
        });

        // Also fetch trending (top sold this week)
        $trending = Product::where('is_active', true)
            ->where('status', Product::STATUS_APPROVED)
            ->where('sold_count', '>', 0)
            ->orderByDesc('sold_count')
            ->limit(10)
            ->get();

        $trending->each(function ($p) {
            $p->seller_info = $p->seller()->first(['id', 'username', 'fullname', 'profile_photo', 'is_verify']);
            $p->image_urls = $this->generateImageUrls($p->images);
        });

        return GlobalFunction::sendDataResponse(true, 'Featured products', [
            'featured' => $featured,
            'trending' => $trending,
        ]);
    }

    /**
     * Fetch product tags with position/timing for reel overlay.
     */
    public function fetchProductTagsInReel(Request $request)
    {
        $token = $request->header('authtoken');
        GlobalFunction::getUserFromAuthToken($token);

        $postId = $request->post_id;
        if (!$postId) {
            return GlobalFunction::sendDataResponse(false, 'post_id required');
        }

        $tags = PostProductTag::where('post_id', $postId)
            ->with(['product' => function ($q) {
                $q->select('id', 'name', 'price_coins', 'images', 'seller_id', 'sold_count', 'avg_rating');
            }, 'product.seller:' . Constants::userPublicFields])
            ->get();

        foreach ($tags as $tag) {
            if ($tag->product && $tag->product->images) {
                $tag->product->image_urls = $this->generateImageUrls($tag->product->images);
            }
        }

        return GlobalFunction::sendDataResponse(true, 'Reel product tags', $tags);
    }

    /**
     * Enhanced tag products with position/timing data + auto-affiliate.
     */
    public function tagProductsEnhanced(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);

        $post = Posts::find($request->post_id);
        if (!$post || $post->user_id != $user->id) {
            return GlobalFunction::sendSimpleResponse(false, 'Post not found or not yours');
        }

        // Expect JSON array of tag objects
        $tagsData = $request->tags;
        if (!is_array($tagsData) || count($tagsData) > 5) {
            return GlobalFunction::sendSimpleResponse(false, 'Provide 1-5 product tags');
        }

        $productIds = array_map(fn($t) => $t['product_id'], $tagsData);
        $products = Product::whereIn('id', $productIds)
            ->where('status', Product::STATUS_APPROVED)
            ->where('is_active', true)
            ->get()
            ->keyBy('id');

        if ($products->count() != count($productIds)) {
            return GlobalFunction::sendSimpleResponse(false, 'One or more products are invalid');
        }

        // Remove existing tags and re-insert
        PostProductTag::where('post_id', $post->id)->delete();

        foreach ($tagsData as $tagData) {
            $product = $products[$tagData['product_id']];
            $isAutoAffiliate = $product->seller_id != $user->id && ($product->affiliate_enabled ?? false);

            PostProductTag::create([
                'post_id' => $post->id,
                'product_id' => $tagData['product_id'],
                'label' => $tagData['label'] ?? null,
                'display_position_x' => $tagData['position_x'] ?? null,
                'display_position_y' => $tagData['position_y'] ?? null,
                'display_time_start_ms' => $tagData['time_start_ms'] ?? null,
                'display_time_end_ms' => $tagData['time_end_ms'] ?? null,
                'is_auto_affiliate' => $isAutoAffiliate,
            ]);

            // Auto-create affiliate link if creator is not the seller
            if ($isAutoAffiliate) {
                \App\Models\AffiliateLink::firstOrCreate(
                    ['creator_id' => $user->id, 'product_id' => $product->id],
                    ['affiliate_code' => strtoupper(substr(md5($user->id . '_' . $product->id), 0, 8))]
                );
            }
        }

        return GlobalFunction::sendSimpleResponse(true, 'Products tagged with positions');
    }

    /**
     * Fetch products from a specific seller (storefront).
     */
    public function fetchSellerProducts(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => 'This user is frozen!'];
        }

        $sellerId = $request->seller_id;
        if (!$sellerId) {
            return GlobalFunction::sendSimpleResponse(false, 'seller_id required');
        }

        $limit = $request->limit ?? 20;

        $query = Product::where('seller_id', $sellerId)
            ->where('is_active', true)
            ->where('status', Product::STATUS_APPROVED)
            ->orderByDesc('sold_count')
            ->limit($limit);

        if ($request->has('last_item_id') && $request->last_item_id) {
            $query->where('id', '<', $request->last_item_id);
        }

        $products = $query->get();

        $products->each(function ($p) {
            $p->category_name = $p->category ? $p->category->name : null;
            $p->image_urls = $this->generateImageUrls($p->images);
        });

        $seller = Users::where('id', $sellerId)->first(['id', 'username', 'fullname', 'profile_photo', 'is_verify', 'bio']);

        return GlobalFunction::sendDataResponse(true, 'Seller products', [
            'seller' => $seller,
            'products' => $products,
        ]);
    }

    // ─── Admin Endpoints ────────────────────────────────────────

    public function productsAdmin()
    {
        return view('products');
    }

    public function productCategoriesAdmin()
    {
        return view('product_categories');
    }

    public function productOrdersAdmin()
    {
        return view('product_orders');
    }

    public function listProducts_Admin(Request $request)
    {
        $query = Product::query();

        if ($request->has('status_filter') && $request->status_filter !== '') {
            $query->where('status', $request->status_filter);
        }

        $totalData = $query->count();

        $limit = $request->input('length');
        $start = $request->input('start');
        $searchValue = $request->input('search.value');

        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('name', 'LIKE', "%{$searchValue}%")
                  ->orWhereHas('seller', function ($uq) use ($searchValue) {
                      $uq->where('username', 'LIKE', "%{$searchValue}%");
                  });
            });
        }
        $totalFiltered = $query->count();

        $result = $query->offset($start)
                        ->limit($limit)
                        ->orderBy('id', 'DESC')
                        ->get();

        $data = $result->map(function ($item) {
            $imageUrl = ($item->images && count($item->images) > 0)
                ? GlobalFunction::generateFileUrl($item->images[0])
                : url('assets/img/placeholder.png');
            $image = "<img class='rounded' width='60' height='60' src='{$imageUrl}' alt=''>";

            $sellerName = $item->seller ? $item->seller->username : 'Unknown';
            $categoryName = $item->category ? $item->category->name : '-';

            $statusLabel = match ($item->status) {
                Product::STATUS_PENDING => "<span class='badge bg-warning'>Pending</span>",
                Product::STATUS_APPROVED => "<span class='badge bg-success'>Approved</span>",
                Product::STATUS_REJECTED => "<span class='badge bg-danger'>Rejected</span>",
                default => "<span class='badge bg-secondary'>Unknown</span>",
            };

            $stockLabel = $item->stock == -1 ? 'Unlimited' : $item->stock;

            $approve = "<a href='#'
                        rel='{$item->id}'
                        data-status='2'
                        class='action-btn update-status d-flex align-items-center justify-content-center btn border rounded-2 text-success ms-1'
                        title='Approve'>
                        <i class='uil-check'></i>
                        </a>";

            $reject = "<a href='#'
                        rel='{$item->id}'
                        data-status='3'
                        class='action-btn update-status d-flex align-items-center justify-content-center btn border rounded-2 text-warning ms-1'
                        title='Reject'>
                        <i class='uil-times'></i>
                        </a>";

            $delete = "<a href='#'
                          rel='{$item->id}'
                          class='action-btn delete d-flex align-items-center justify-content-center btn border rounded-2 text-danger ms-1'>
                            <i class='uil-trash-alt'></i>
                        </a>";

            $action = "<span class='d-flex justify-content-end align-items-center'>{$approve}{$reject}{$delete}</span>";

            return [
                $image,
                htmlspecialchars($item->name),
                $sellerName,
                $categoryName,
                $item->price_coins . ' coins',
                $stockLabel,
                $item->sold_count,
                number_format($item->avg_rating, 1) . ' (' . $item->rating_count . ')',
                $statusLabel,
                GlobalFunction::formateDatabaseTime($item->created_at),
                $action,
            ];
        });

        return response()->json([
            "draw" => intval($request->input('draw')),
            "recordsTotal" => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data" => $data,
        ]);
    }

    public function updateProductStatus(Request $request)
    {
        $product = Product::find($request->id);
        if (!$product) {
            return GlobalFunction::sendSimpleResponse(false, 'Product not found');
        }

        $product->status = $request->status;
        $product->save();

        $statusText = match ((int)$request->status) {
            Product::STATUS_APPROVED => 'approved',
            Product::STATUS_REJECTED => 'rejected',
            default => 'updated',
        };

        return GlobalFunction::sendSimpleResponse(true, "Product {$statusText} successfully");
    }

    public function deleteProduct_Admin(Request $request)
    {
        $product = Product::find($request->id);
        if (!$product) {
            return GlobalFunction::sendSimpleResponse(false, 'Product not found');
        }

        if ($product->images) {
            foreach ($product->images as $img) {
                GlobalFunction::deleteFile($img);
            }
        }
        if ($product->digital_file) {
            GlobalFunction::deleteFile($product->digital_file);
        }

        $product->delete();

        return GlobalFunction::sendSimpleResponse(true, 'Product deleted successfully');
    }

    public function listProductCategories_Admin(Request $request)
    {
        $totalData = ProductCategory::count();

        $limit = $request->input('length');
        $start = $request->input('start');
        $searchValue = $request->input('search.value');

        $query = ProductCategory::query();

        if (!empty($searchValue)) {
            $query->where('name', 'LIKE', "%{$searchValue}%");
        }
        $totalFiltered = $query->count();

        $result = $query->offset($start)
                        ->limit($limit)
                        ->orderBy('sort_order')
                        ->get();

        $data = $result->map(function ($item) {
            $activeLabel = $item->is_active
                ? "<span class='badge bg-success'>Active</span>"
                : "<span class='badge bg-secondary'>Inactive</span>";

            $edit = "<a href='#'
                       rel='{$item->id}'
                       data-name='" . htmlspecialchars($item->name) . "'
                       data-icon='" . htmlspecialchars($item->icon ?? '') . "'
                       data-sort='{$item->sort_order}'
                       class='action-btn edit d-flex align-items-center justify-content-center btn border rounded-2 text-primary ms-1'
                       title='Edit'>
                       <i class='uil-pen'></i>
                    </a>";

            $delete = "<a href='#'
                          rel='{$item->id}'
                          class='action-btn delete d-flex align-items-center justify-content-center btn border rounded-2 text-danger ms-1'>
                            <i class='uil-trash-alt'></i>
                        </a>";

            $action = "<span class='d-flex justify-content-end align-items-center'>{$edit}{$delete}</span>";

            return [
                $item->id,
                htmlspecialchars($item->name),
                $item->icon ?? '-',
                $item->sort_order,
                $activeLabel,
                $item->products()->count(),
                $action,
            ];
        });

        return response()->json([
            "draw" => intval($request->input('draw')),
            "recordsTotal" => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data" => $data,
        ]);
    }

    public function addProductCategory(Request $request)
    {
        $category = ProductCategory::create([
            'name' => $request->name,
            'icon' => $request->icon,
            'sort_order' => $request->sort_order ?? 0,
            'is_active' => true,
        ]);

        return GlobalFunction::sendDataResponse(true, 'Category created', $category);
    }

    public function editProductCategory(Request $request)
    {
        $category = ProductCategory::find($request->id);
        if (!$category) {
            return GlobalFunction::sendSimpleResponse(false, 'Category not found');
        }

        if ($request->has('name')) $category->name = $request->name;
        if ($request->has('icon')) $category->icon = $request->icon;
        if ($request->has('sort_order')) $category->sort_order = $request->sort_order;
        $category->save();

        return GlobalFunction::sendSimpleResponse(true, 'Category updated');
    }

    public function deleteProductCategory(Request $request)
    {
        $category = ProductCategory::find($request->id);
        if (!$category) {
            return GlobalFunction::sendSimpleResponse(false, 'Category not found');
        }

        $category->delete();

        return GlobalFunction::sendSimpleResponse(true, 'Category deleted');
    }

    public function listProductOrders_Admin(Request $request)
    {
        $query = ProductOrder::query();

        if ($request->has('status_filter') && $request->status_filter !== '') {
            $query->where('status', $request->status_filter);
        }

        $totalData = $query->count();

        $limit = $request->input('length');
        $start = $request->input('start');
        $searchValue = $request->input('search.value');

        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->whereHas('buyer', function ($uq) use ($searchValue) {
                      $uq->where('username', 'LIKE', "%{$searchValue}%");
                  })
                  ->orWhereHas('product', function ($pq) use ($searchValue) {
                      $pq->where('name', 'LIKE', "%{$searchValue}%");
                  });
            });
        }
        $totalFiltered = $query->count();

        $result = $query->offset($start)
                        ->limit($limit)
                        ->orderBy('id', 'DESC')
                        ->with(['product', 'buyer:id,username,fullname', 'seller:id,username,fullname'])
                        ->get();

        $data = $result->map(function ($item) {
            $productName = $item->product ? htmlspecialchars($item->product->name) : 'Deleted';
            $buyerName = $item->buyer ? $item->buyer->username : 'Unknown';
            $sellerName = $item->seller ? $item->seller->username : 'Unknown';

            $statusLabel = match ($item->status) {
                ProductOrder::STATUS_PENDING => "<span class='badge bg-warning'>Pending</span>",
                ProductOrder::STATUS_CONFIRMED => "<span class='badge bg-info'>Confirmed</span>",
                ProductOrder::STATUS_SHIPPED => "<span class='badge bg-primary'>Shipped</span>",
                ProductOrder::STATUS_DELIVERED => "<span class='badge bg-success'>Delivered</span>",
                ProductOrder::STATUS_CANCELLED => "<span class='badge bg-danger'>Cancelled</span>",
                ProductOrder::STATUS_REFUNDED => "<span class='badge bg-secondary'>Refunded</span>",
                default => "<span class='badge bg-secondary'>Unknown</span>",
            };

            return [
                $item->id,
                $productName,
                $buyerName,
                $sellerName,
                $item->quantity,
                $item->total_coins . ' coins',
                $statusLabel,
                $item->tracking_number ?? '-',
                GlobalFunction::formateDatabaseTime($item->created_at),
            ];
        });

        return response()->json([
            "draw" => intval($request->input('draw')),
            "recordsTotal" => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data" => $data,
        ]);
    }

    // ─── Product Tags in Posts ──────────────────────────────────

    public function tagProducts(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);

        $rules = [
            'post_id' => 'required|integer',
            'product_ids' => 'required|string', // comma-separated product IDs
        ];
        $validator = \Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $post = Posts::find($request->post_id);
        if (!$post || $post->user_id != $user->id) {
            return GlobalFunction::sendDataResponse(false, 'Post not found or not yours');
        }

        $productIds = array_filter(array_map('intval', explode(',', $request->product_ids)));
        if (count($productIds) > 5) {
            return GlobalFunction::sendDataResponse(false, 'Maximum 5 product tags allowed');
        }

        // Validate products exist and are approved
        $products = Product::whereIn('id', $productIds)
            ->where('status', Product::STATUS_APPROVED)
            ->where('is_active', true)
            ->get();

        if ($products->count() != count($productIds)) {
            return GlobalFunction::sendDataResponse(false, 'One or more products are invalid or not approved');
        }

        // Labels from request (optional JSON array)
        $labels = [];
        if ($request->has('labels')) {
            $decoded = json_decode($request->labels, true);
            if (is_array($decoded)) {
                $labels = $decoded;
            }
        }

        // Remove existing tags and re-insert
        PostProductTag::where('post_id', $post->id)->delete();

        foreach ($productIds as $i => $productId) {
            PostProductTag::create([
                'post_id' => $post->id,
                'product_id' => $productId,
                'label' => $labels[$i] ?? null,
            ]);
        }

        return GlobalFunction::sendDataResponse(true, 'Products tagged successfully');
    }

    public function untagProduct(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);

        $rules = [
            'post_id' => 'required|integer',
            'product_id' => 'required|integer',
        ];
        $validator = \Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $post = Posts::find($request->post_id);
        if (!$post || $post->user_id != $user->id) {
            return GlobalFunction::sendDataResponse(false, 'Post not found or not yours');
        }

        PostProductTag::where('post_id', $post->id)
            ->where('product_id', $request->product_id)
            ->delete();

        return GlobalFunction::sendDataResponse(true, 'Product untagged');
    }

    public function fetchPostProductTags(Request $request)
    {
        $token = $request->header('authtoken');
        GlobalFunction::getUserFromAuthToken($token);

        $postId = $request->post_id;
        if (!$postId) {
            return GlobalFunction::sendDataResponse(false, 'post_id required');
        }

        $tags = PostProductTag::where('post_id', $postId)
            ->with(['product' => function ($q) {
                $q->select('id', 'name', 'price_coins', 'images', 'seller_id', 'sold_count', 'avg_rating');
            }, 'product.seller:' . Constants::userPublicFields])
            ->get();

        // Generate image URLs
        foreach ($tags as $tag) {
            if ($tag->product && $tag->product->images) {
                $tag->product->image_urls = $this->generateImageUrls($tag->product->images);
            }
        }

        return GlobalFunction::sendDataResponse(true, 'Product tags', $tags);
    }

    // ─── Helper ─────────────────────────────────────────────────

    private function generateImageUrls(?array $images): array
    {
        if (!$images) return [];
        return array_map(fn($path) => GlobalFunction::generateFileUrl($path), $images);
    }
}
