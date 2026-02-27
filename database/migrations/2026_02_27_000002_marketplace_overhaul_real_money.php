<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Marketplace Overhaul: Coin-based → Real Money (INR)
 *
 * Adds: Seller verification (GST + Non-GST), payment gateway integration (Razorpay/Cashfree/PhonePe),
 * shipping aggregator (Shiprocket/Delhivery), product variants (size/color), returns & refunds,
 * seller payouts with TCS/TDS compliance, affiliate applications, product shoot requests.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ──────────────────────────────────────────────────
        // 1. SELLER APPLICATIONS (KYC / Verification)
        // ──────────────────────────────────────────────────
        Schema::create('tbl_seller_applications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');

            // Business type
            $table->string('seller_type', 30); // individual, proprietorship, partnership, private_limited, llp

            // GST details
            $table->boolean('has_gst')->default(false);
            $table->string('gstin', 15)->nullable();
            $table->string('gst_state_code', 5)->nullable(); // e.g., "27" for Maharashtra

            // Identity
            $table->string('pan_number', 10);
            $table->string('aadhaar_number', 12)->nullable(); // required for individuals
            $table->string('business_name', 255)->nullable();
            $table->string('legal_business_name', 255)->nullable(); // as per GST/PAN

            // Business address
            $table->text('business_address');
            $table->string('business_city', 100);
            $table->string('business_state', 100);
            $table->string('business_pincode', 10);
            $table->string('business_country', 50)->default('India');

            // Bank details (for payouts)
            $table->string('bank_account_name', 255);
            $table->string('bank_account_number', 50); // encrypted at app layer
            $table->string('bank_ifsc', 11);
            $table->string('bank_name', 150)->nullable();
            $table->string('bank_branch', 200)->nullable();

            // Document uploads (file paths)
            $table->string('pan_document', 500)->nullable();
            $table->string('aadhaar_front_document', 500)->nullable();
            $table->string('aadhaar_back_document', 500)->nullable();
            $table->string('gst_certificate_document', 500)->nullable();
            $table->string('address_proof_document', 500)->nullable();
            $table->string('cancelled_cheque_document', 500)->nullable();
            $table->string('business_license_document', 500)->nullable(); // Shop Act / Udyam
            $table->string('brand_authorization_document', 500)->nullable();
            $table->jsonb('additional_documents')->nullable(); // array of {type, path}

            // Category-specific licenses
            $table->string('fssai_license', 20)->nullable(); // food
            $table->string('drug_license', 50)->nullable(); // pharma

            // Application status
            $table->smallInteger('status')->default(0); // 0=pending, 1=approved, 2=rejected, 3=suspended, 4=revoked
            $table->text('rejection_reason')->nullable();
            $table->text('admin_notes')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestampTz('reviewed_at')->nullable();

            // Payment gateway linked accounts
            $table->string('razorpay_account_id', 50)->nullable(); // acc_XXXX
            $table->string('razorpay_account_status', 30)->nullable(); // created, activated, needs_clarification
            $table->string('cashfree_vendor_id', 50)->nullable();

            // Shipping
            $table->string('shiprocket_pickup_location', 100)->nullable();
            $table->string('shiprocket_pickup_id', 30)->nullable();

            // Tax compliance tracking
            $table->boolean('tcs_applicable')->default(false); // true for GST sellers
            $table->boolean('tds_applicable')->default(false); // true if above 5L threshold
            $table->bigInteger('fy_gross_sales_paise')->default(0); // running total for TDS threshold (500000_00 = 5L)
            $table->string('current_fy', 10)->nullable(); // e.g., "2026-27"

            $table->timestampsTz();

            $table->foreign('user_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->index(['user_id', 'status']);
            $table->index(['status', 'created_at']);
        });

        // ──────────────────────────────────────────────────
        // 2. AFFILIATE APPLICATIONS (Creator Verification)
        // ──────────────────────────────────────────────────
        Schema::create('tbl_affiliate_applications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');

            // Snapshot at time of application
            $table->integer('follower_count')->default(0);
            $table->integer('total_posts')->default(0);
            $table->integer('total_views')->default(0);

            // Application details
            $table->string('niche_category', 100)->nullable(); // fashion, tech, fitness, beauty, etc.
            $table->jsonb('social_links')->nullable(); // {instagram, youtube, twitter, etc.}
            $table->text('bio')->nullable(); // why they want to be affiliate
            $table->text('content_examples')->nullable(); // links to best content

            // Status
            $table->smallInteger('status')->default(0); // 0=pending, 1=approved, 2=rejected, 3=suspended
            $table->boolean('auto_approved')->default(false); // true if criteria-based auto-approval
            $table->text('rejection_reason')->nullable();
            $table->text('admin_notes')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestampTz('reviewed_at')->nullable();

            $table->timestampsTz();

            $table->foreign('user_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->unique('user_id');
            $table->index(['status', 'created_at']);
        });

        // ──────────────────────────────────────────────────
        // 3. PRODUCT VARIANTS (Size + Color)
        // ──────────────────────────────────────────────────
        Schema::create('tbl_product_variants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->string('variant_type', 20); // size, color, size_color

            // Variant attributes
            $table->string('size_value', 30)->nullable(); // S, M, L, XL, 28, 30, Free Size, etc.
            $table->string('color_value', 50)->nullable(); // Red, Blue, Navy Blue, etc.
            $table->string('color_hex', 7)->nullable(); // #FF0000

            // Pricing & stock
            $table->string('sku', 50)->nullable();
            $table->bigInteger('price_paise')->nullable(); // null = use product base price
            $table->integer('stock')->default(-1); // -1 = unlimited
            $table->integer('sold_count')->default(0);

            // Media
            $table->jsonb('images')->nullable(); // variant-specific images

            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestampsTz();

            $table->foreign('product_id')->references('id')->on('tbl_products')->onDelete('cascade');
            $table->index(['product_id', 'is_active']);
            $table->unique(['product_id', 'size_value', 'color_value']);
        });

        // ──────────────────────────────────────────────────
        // 4. PAYMENT TRANSACTIONS (Razorpay/Cashfree/PhonePe)
        // ──────────────────────────────────────────────────
        Schema::create('tbl_payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // buyer
            $table->unsignedBigInteger('order_id')->nullable(); // FK added after order table alter

            // Gateway details
            $table->string('gateway', 20); // razorpay, cashfree, phonepe
            $table->string('gateway_order_id', 100)->nullable(); // Razorpay order_id, Cashfree order_id
            $table->string('gateway_payment_id', 100)->nullable(); // Razorpay payment_id
            $table->string('gateway_signature', 255)->nullable(); // for verification

            // Amount
            $table->bigInteger('amount_paise'); // total in paise (100 paise = ₹1)
            $table->string('currency', 5)->default('INR');

            // Status
            $table->string('status', 20)->default('created'); // created, authorized, captured, failed, refunded, partially_refunded

            // Payment method details
            $table->string('payment_method', 20)->nullable(); // upi, card, netbanking, wallet, cod, emi
            $table->jsonb('payment_details')->nullable(); // method-specific: {upi_id, card_last4, bank_name, wallet_name, etc.}

            // Refund tracking
            $table->bigInteger('refund_amount_paise')->default(0);
            $table->string('refund_id', 100)->nullable();
            $table->timestampTz('refunded_at')->nullable();

            // Metadata
            $table->jsonb('metadata')->nullable(); // any extra gateway-specific data
            $table->text('failure_reason')->nullable();

            $table->timestampsTz();

            $table->foreign('user_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->index(['user_id', 'status', 'created_at']);
            $table->index(['gateway_order_id']);
            $table->index(['gateway_payment_id']);
        });

        // ──────────────────────────────────────────────────
        // 5. RETURNS & REFUNDS
        // ──────────────────────────────────────────────────
        Schema::create('tbl_returns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('order_item_id')->nullable();
            $table->unsignedBigInteger('buyer_id');
            $table->unsignedBigInteger('seller_id');
            $table->unsignedBigInteger('product_id');

            // Return details
            $table->string('reason', 30); // defective, wrong_item, not_as_described, size_issue, change_of_mind, damaged_in_transit, other
            $table->text('description')->nullable();
            $table->jsonb('photos')->nullable(); // evidence photos
            $table->string('return_type', 20); // refund, replacement, exchange

            // Status: full lifecycle
            $table->smallInteger('status')->default(0);
            // 0=requested, 1=approved, 2=rejected, 3=pickup_scheduled, 4=in_transit,
            // 5=received_by_seller, 6=inspection_passed, 7=inspection_failed,
            // 8=refund_initiated, 9=refund_completed, 10=replacement_shipped

            // Seller/admin responses
            $table->text('seller_response')->nullable();
            $table->text('admin_notes')->nullable();
            $table->jsonb('seller_inspection_photos')->nullable();

            // Refund amount
            $table->bigInteger('refund_amount_paise')->nullable();
            $table->string('refund_method', 30)->nullable(); // original_payment, bank_transfer, wallet_credit
            $table->string('refund_gateway_id', 100)->nullable(); // Razorpay refund ID

            // Shipping (reverse logistics)
            $table->string('shiprocket_return_order_id', 30)->nullable();
            $table->string('return_awb', 50)->nullable();
            $table->string('return_courier', 50)->nullable();

            // Timestamps
            $table->timestampTz('approved_at')->nullable();
            $table->timestampTz('pickup_scheduled_at')->nullable();
            $table->timestampTz('received_at')->nullable();
            $table->timestampTz('refund_initiated_at')->nullable();
            $table->timestampTz('refund_completed_at')->nullable();

            $table->timestampsTz();

            $table->foreign('order_id')->references('id')->on('tbl_product_orders')->onDelete('cascade');
            $table->foreign('buyer_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->foreign('seller_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('tbl_products')->onDelete('cascade');
            $table->index(['order_id']);
            $table->index(['buyer_id', 'status']);
            $table->index(['seller_id', 'status']);
        });

        // ──────────────────────────────────────────────────
        // 6. SELLER PAYOUTS
        // ──────────────────────────────────────────────────
        Schema::create('tbl_seller_payouts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('seller_id');

            // Amount breakdown
            $table->bigInteger('gross_amount_paise'); // total order value
            $table->bigInteger('platform_commission_paise')->default(0);
            $table->bigInteger('tcs_deducted_paise')->default(0);
            $table->bigInteger('tds_deducted_paise')->default(0);
            $table->bigInteger('return_deductions_paise')->default(0);
            $table->bigInteger('net_amount_paise'); // what seller actually receives

            // Payout method
            $table->string('payout_method', 30); // razorpay_route, bank_transfer, manual
            $table->string('razorpay_transfer_id', 50)->nullable();
            $table->string('bank_reference', 100)->nullable();
            $table->string('utr_number', 50)->nullable(); // UTR for bank transfers

            // Status
            $table->smallInteger('status')->default(0); // 0=pending, 1=processing, 2=completed, 3=failed, 4=on_hold
            $table->text('failure_reason')->nullable();

            // Period covered
            $table->date('period_start');
            $table->date('period_end');
            $table->integer('order_count')->default(0);
            $table->jsonb('order_ids')->nullable(); // array of order IDs included

            $table->text('notes')->nullable();
            $table->timestampsTz();

            $table->foreign('seller_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->index(['seller_id', 'status']);
            $table->index(['status', 'created_at']);
        });

        // ──────────────────────────────────────────────────
        // 7. PRODUCT SHOOT REQUESTS (Ticket System)
        // ──────────────────────────────────────────────────
        Schema::create('tbl_product_shoot_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('creator_id');
            $table->unsignedBigInteger('seller_id');
            $table->unsignedBigInteger('product_id');

            // Request type
            $table->string('request_type', 20); // sample_delivery, onsite_visit

            // Details
            $table->string('title', 255);
            $table->text('description')->nullable();

            // For sample delivery
            $table->text('delivery_address')->nullable();
            $table->string('delivery_city', 100)->nullable();
            $table->string('delivery_state', 100)->nullable();
            $table->string('delivery_pincode', 10)->nullable();
            $table->string('sample_tracking_number', 100)->nullable();
            $table->bigInteger('security_deposit_paise')->nullable();

            // For onsite visit
            $table->date('proposed_date')->nullable();
            $table->text('proposed_location')->nullable();

            // Status
            $table->smallInteger('status')->default(0);
            // 0=pending, 1=seller_accepted, 2=seller_declined, 3=in_progress,
            // 4=sample_shipped, 5=sample_received, 6=shoot_completed,
            // 7=sample_returned, 8=completed, 9=cancelled

            // Admin
            $table->unsignedBigInteger('admin_assigned_id')->nullable();
            $table->boolean('admin_in_conversation')->default(true);

            // Linked deliverable
            $table->unsignedBigInteger('deliverable_post_id')->nullable(); // the reel/post created from the shoot

            $table->timestampsTz();

            $table->foreign('creator_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->foreign('seller_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('tbl_products')->onDelete('cascade');
            $table->index(['creator_id', 'status']);
            $table->index(['seller_id', 'status']);
        });

        // Shoot request messages (threaded conversation)
        Schema::create('tbl_shoot_request_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('request_id');
            $table->unsignedBigInteger('sender_id');
            $table->string('sender_role', 10); // creator, seller, admin

            $table->text('message')->nullable();
            $table->jsonb('attachments')->nullable(); // [{type: image/video/document, path: ...}]

            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('request_id')->references('id')->on('tbl_product_shoot_requests')->onDelete('cascade');
            $table->foreign('sender_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->index(['request_id', 'created_at']);
        });

        // ──────────────────────────────────────────────────
        // 8. ALTER tbl_products — Add real money fields
        // ──────────────────────────────────────────────────
        Schema::table('tbl_products', function (Blueprint $table) {
            // Pricing in INR (paise)
            $table->bigInteger('price_paise')->default(0)->after('price_coins'); // 99900 = ₹999
            $table->bigInteger('compare_at_price_paise')->nullable()->after('price_paise'); // MRP for discount display
            $table->bigInteger('shipping_charge_paise')->default(0)->after('compare_at_price_paise'); // 0 = free shipping

            // Tax
            $table->decimal('gst_rate', 5, 2)->default(18.00)->after('shipping_charge_paise'); // GST %
            $table->string('hsn_code', 10)->nullable()->after('gst_rate'); // Harmonized System code

            // Physical dimensions (for shipping)
            $table->integer('weight_grams')->nullable()->after('hsn_code');
            $table->decimal('length_cm', 6, 1)->nullable()->after('weight_grams');
            $table->decimal('breadth_cm', 6, 1)->nullable()->after('length_cm');
            $table->decimal('height_cm', 6, 1)->nullable()->after('breadth_cm');

            // Variants
            $table->boolean('has_variants')->default(false)->after('height_cm');

            // SKU & brand
            $table->string('sku', 50)->nullable()->after('has_variants');
            $table->string('brand_name', 200)->nullable()->after('sku');

            // Order limits
            $table->integer('min_order_qty')->default(1)->after('brand_name');
            $table->integer('max_order_qty')->default(10)->after('min_order_qty');

            // Shipping & payment options
            $table->string('shipping_type', 15)->default('platform')->after('max_order_qty'); // self, platform, both
            $table->boolean('cod_available')->default(true)->after('shipping_type');

            // Return policy override (per-product, nullable = use category default)
            $table->integer('return_window_days_override')->nullable()->after('cod_available');
            $table->string('return_type_override', 20)->nullable()->after('return_window_days_override');

            // Seller pickup location (for Shiprocket)
            $table->string('pickup_location_name', 100)->nullable()->after('return_type_override');
        });

        // ──────────────────────────────────────────────────
        // 9. ALTER tbl_product_categories — Add commission & return policy
        // ──────────────────────────────────────────────────
        Schema::table('tbl_product_categories', function (Blueprint $table) {
            $table->decimal('commission_rate', 5, 2)->default(20.00)->after('is_active'); // platform commission %
            $table->integer('return_window_days')->default(7)->after('commission_rate');
            $table->string('return_type', 20)->default('full_return')->after('return_window_days'); // full_return, replacement_only, no_return
            $table->boolean('returnable')->default(true)->after('return_type');
            $table->string('hsn_code_prefix', 6)->nullable()->after('returnable'); // for auto-suggest
            $table->decimal('default_gst_rate', 5, 2)->default(18.00)->after('hsn_code_prefix');
            $table->text('description')->nullable()->after('name');
            $table->string('image', 500)->nullable()->after('description');
            $table->unsignedBigInteger('parent_id')->nullable()->after('id'); // subcategories
        });

        // ──────────────────────────────────────────────────
        // 10. ALTER tbl_product_orders — Real money + shipping
        // ──────────────────────────────────────────────────
        Schema::table('tbl_product_orders', function (Blueprint $table) {
            // Payment
            $table->unsignedBigInteger('payment_transaction_id')->nullable()->after('transaction_id');
            $table->bigInteger('total_amount_paise')->default(0)->after('total_coins');
            $table->bigInteger('shipping_charge_paise')->default(0)->after('total_amount_paise');
            $table->bigInteger('gst_amount_paise')->default(0)->after('shipping_charge_paise');

            // Platform financials
            $table->decimal('platform_commission_rate', 5, 2)->nullable()->after('gst_amount_paise');
            $table->bigInteger('platform_commission_paise')->default(0)->after('platform_commission_rate');
            $table->bigInteger('tcs_amount_paise')->default(0)->after('platform_commission_paise');
            $table->bigInteger('tds_amount_paise')->default(0)->after('tcs_amount_paise');
            $table->bigInteger('seller_net_amount_paise')->default(0)->after('tds_amount_paise');

            // Payment & shipping method
            $table->string('payment_method', 15)->default('prepaid')->after('seller_net_amount_paise'); // prepaid, cod
            $table->string('shipping_method', 20)->nullable()->after('payment_method'); // self, shiprocket, delhivery

            // Shiprocket integration
            $table->string('shiprocket_order_id', 30)->nullable()->after('shipping_method');
            $table->string('shiprocket_shipment_id', 30)->nullable()->after('shiprocket_order_id');
            $table->string('awb_code', 50)->nullable()->after('shiprocket_shipment_id');
            $table->string('courier_name', 50)->nullable()->after('awb_code');
            $table->string('shipping_label_url', 500)->nullable()->after('courier_name');

            // Delivery tracking
            $table->date('estimated_delivery_date')->nullable()->after('shipping_label_url');
            $table->timestampTz('delivered_at')->nullable()->after('estimated_delivery_date');
            $table->timestampTz('return_window_expires_at')->nullable()->after('delivered_at');

            // Address
            $table->unsignedBigInteger('shipping_address_id')->nullable()->after('shipping_address');

            // Invoice
            $table->string('invoice_number', 50)->nullable()->after('return_window_expires_at');
            $table->string('invoice_url', 500)->nullable()->after('invoice_number');

            // Payout reference
            $table->unsignedBigInteger('payout_id')->nullable()->after('invoice_url');
            $table->boolean('payout_eligible')->default(false)->after('payout_id'); // true after return window expires
        });

        // ──────────────────────────────────────────────────
        // 11. ALTER tbl_order_items — Variant + INR price
        // ──────────────────────────────────────────────────
        Schema::table('tbl_order_items', function (Blueprint $table) {
            $table->unsignedBigInteger('variant_id')->nullable()->after('product_id');
            $table->bigInteger('price_paise')->default(0)->after('price_coins');
            $table->string('variant_label', 100)->nullable()->after('price_paise'); // "Size: M, Color: Red"
        });

        // ──────────────────────────────────────────────────
        // 12. ALTER tbl_cart_items — Variant support
        // ──────────────────────────────────────────────────
        Schema::table('tbl_cart_items', function (Blueprint $table) {
            $table->unsignedBigInteger('variant_id')->nullable()->after('product_id');
        });

        // Drop old unique constraint and add new one with variant_id
        DB::statement('ALTER TABLE tbl_cart_items DROP CONSTRAINT IF EXISTS tbl_cart_items_user_id_product_id_unique');
        DB::statement('CREATE UNIQUE INDEX uq_cart_user_product_variant ON tbl_cart_items (user_id, product_id, COALESCE(variant_id, 0))');

        // ──────────────────────────────────────────────────
        // 13. ALTER tbl_affiliate_links — Real money earnings
        // ──────────────────────────────────────────────────
        Schema::table('tbl_affiliate_links', function (Blueprint $table) {
            $table->bigInteger('total_earnings_paise')->default(0)->after('total_earnings');
        });

        // ──────────────────────────────────────────────────
        // 14. ALTER tbl_affiliate_earnings — Real money
        // ──────────────────────────────────────────────────
        Schema::table('tbl_affiliate_earnings', function (Blueprint $table) {
            $table->bigInteger('commission_paise')->default(0)->after('commission_coins');
        });

        // ──────────────────────────────────────────────────
        // 15. ALTER tbl_users — Seller & affiliate status flags
        // ──────────────────────────────────────────────────
        Schema::table('tbl_users', function (Blueprint $table) {
            $table->boolean('is_approved_seller')->default(false)->after('is_monetized');
            $table->boolean('is_approved_affiliate')->default(false)->after('is_approved_seller');
            $table->bigInteger('seller_wallet_paise')->default(0)->after('is_approved_affiliate'); // pending balance
            $table->bigInteger('seller_total_earned_paise')->default(0)->after('seller_wallet_paise');
            $table->bigInteger('affiliate_total_earned_paise')->default(0)->after('seller_total_earned_paise');
        });

        // ──────────────────────────────────────────────────
        // 16. MARKETPLACE SETTINGS (add to tbl_settings)
        // ──────────────────────────────────────────────────
        Schema::table('tbl_settings', function (Blueprint $table) {
            // Marketplace toggles
            $table->boolean('marketplace_enabled')->default(false);

            // Commission
            $table->decimal('default_commission_rate', 5, 2)->default(20.00);

            // Tax rates
            $table->decimal('tcs_rate', 5, 2)->default(1.00); // 1% TCS
            $table->decimal('tds_rate', 5, 2)->default(1.00); // 1% TDS
            $table->bigInteger('tds_threshold_paise')->default(50000000); // ₹5,00,000 = 50000000 paise

            // Payout settings
            $table->boolean('auto_payout_enabled')->default(true);
            $table->smallInteger('auto_payout_day')->default(1); // 1=Monday
            $table->bigInteger('min_payout_amount_paise')->default(50000); // ₹500
            $table->integer('payout_hold_days')->default(7); // days after delivery before payout eligible

            // Razorpay
            $table->string('razorpay_key_id', 100)->nullable();
            $table->text('razorpay_key_secret')->nullable(); // encrypted
            $table->boolean('razorpay_enabled')->default(false);

            // Cashfree
            $table->string('cashfree_app_id', 100)->nullable();
            $table->text('cashfree_secret_key')->nullable(); // encrypted
            $table->boolean('cashfree_enabled')->default(false);

            // PhonePe
            $table->string('phonepe_merchant_id', 100)->nullable();
            $table->text('phonepe_salt_key')->nullable(); // encrypted
            $table->smallInteger('phonepe_salt_index')->default(1);
            $table->boolean('phonepe_enabled')->default(false);

            // Shiprocket
            $table->string('shiprocket_email', 150)->nullable();
            $table->text('shiprocket_password')->nullable(); // encrypted
            $table->text('shiprocket_token')->nullable();
            $table->timestampTz('shiprocket_token_expires_at')->nullable();
            $table->boolean('shiprocket_enabled')->default(false);

            // Delhivery
            $table->string('delhivery_api_key', 200)->nullable();
            $table->boolean('delhivery_enabled')->default(false);

            // Affiliate auto-approve criteria
            $table->integer('affiliate_auto_approve_min_followers')->default(1000);
            $table->integer('affiliate_auto_approve_min_posts')->default(10);
            $table->decimal('default_affiliate_commission_rate', 5, 2)->default(10.00);

            // COD
            $table->boolean('cod_enabled')->default(true);
            $table->bigInteger('cod_max_amount_paise')->default(500000); // ₹5000 max COD
        });

        // ──────────────────────────────────────────────────
        // 17. ORDER STATUS HISTORY (for tracking)
        // ──────────────────────────────────────────────────
        Schema::create('tbl_order_status_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->smallInteger('status');
            $table->string('status_label', 50);
            $table->text('description')->nullable();
            $table->string('location', 200)->nullable(); // shipping location
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('order_id')->references('id')->on('tbl_product_orders')->onDelete('cascade');
            $table->index(['order_id', 'created_at']);
        });

        // ──────────────────────────────────────────────────
        // 18. TCS/TDS LEDGER (for compliance reporting)
        // ──────────────────────────────────────────────────
        Schema::create('tbl_tax_ledger', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('seller_id');
            $table->unsignedBigInteger('order_id')->nullable();
            $table->string('tax_type', 5); // tcs, tds
            $table->bigInteger('amount_paise');
            $table->string('financial_year', 10); // 2026-27
            $table->string('month', 7); // 2026-03
            $table->string('description', 255)->nullable();
            $table->smallInteger('status')->default(0); // 0=accrued, 1=deposited
            $table->timestampTz('deposited_at')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('seller_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->index(['seller_id', 'financial_year']);
            $table->index(['tax_type', 'financial_year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_tax_ledger');
        Schema::dropIfExists('tbl_order_status_history');
        Schema::dropIfExists('tbl_shoot_request_messages');
        Schema::dropIfExists('tbl_product_shoot_requests');
        Schema::dropIfExists('tbl_seller_payouts');
        Schema::dropIfExists('tbl_returns');
        Schema::dropIfExists('tbl_payment_transactions');
        Schema::dropIfExists('tbl_product_variants');
        Schema::dropIfExists('tbl_affiliate_applications');
        Schema::dropIfExists('tbl_seller_applications');

        // Reverse table alterations
        Schema::table('tbl_settings', function (Blueprint $table) {
            $table->dropColumn([
                'marketplace_enabled', 'default_commission_rate',
                'tcs_rate', 'tds_rate', 'tds_threshold_paise',
                'auto_payout_enabled', 'auto_payout_day', 'min_payout_amount_paise', 'payout_hold_days',
                'razorpay_key_id', 'razorpay_key_secret', 'razorpay_enabled',
                'cashfree_app_id', 'cashfree_secret_key', 'cashfree_enabled',
                'phonepe_merchant_id', 'phonepe_salt_key', 'phonepe_salt_index', 'phonepe_enabled',
                'shiprocket_email', 'shiprocket_password', 'shiprocket_token', 'shiprocket_token_expires_at', 'shiprocket_enabled',
                'delhivery_api_key', 'delhivery_enabled',
                'affiliate_auto_approve_min_followers', 'affiliate_auto_approve_min_posts', 'default_affiliate_commission_rate',
                'cod_enabled', 'cod_max_amount_paise',
            ]);
        });

        Schema::table('tbl_users', function (Blueprint $table) {
            $table->dropColumn(['is_approved_seller', 'is_approved_affiliate', 'seller_wallet_paise', 'seller_total_earned_paise', 'affiliate_total_earned_paise']);
        });

        Schema::table('tbl_affiliate_earnings', function (Blueprint $table) {
            $table->dropColumn('commission_paise');
        });

        Schema::table('tbl_affiliate_links', function (Blueprint $table) {
            $table->dropColumn('total_earnings_paise');
        });

        DB::statement('DROP INDEX IF EXISTS uq_cart_user_product_variant');
        Schema::table('tbl_cart_items', function (Blueprint $table) {
            $table->dropColumn('variant_id');
            $table->unique(['user_id', 'product_id']);
        });

        Schema::table('tbl_order_items', function (Blueprint $table) {
            $table->dropColumn(['variant_id', 'price_paise', 'variant_label']);
        });

        Schema::table('tbl_product_orders', function (Blueprint $table) {
            $table->dropColumn([
                'payment_transaction_id', 'total_amount_paise', 'shipping_charge_paise', 'gst_amount_paise',
                'platform_commission_rate', 'platform_commission_paise', 'tcs_amount_paise', 'tds_amount_paise', 'seller_net_amount_paise',
                'payment_method', 'shipping_method',
                'shiprocket_order_id', 'shiprocket_shipment_id', 'awb_code', 'courier_name', 'shipping_label_url',
                'estimated_delivery_date', 'delivered_at', 'return_window_expires_at',
                'shipping_address_id', 'invoice_number', 'invoice_url',
                'payout_id', 'payout_eligible',
            ]);
        });

        Schema::table('tbl_product_categories', function (Blueprint $table) {
            $table->dropColumn([
                'commission_rate', 'return_window_days', 'return_type', 'returnable',
                'hsn_code_prefix', 'default_gst_rate', 'description', 'image', 'parent_id',
            ]);
        });

        Schema::table('tbl_products', function (Blueprint $table) {
            $table->dropColumn([
                'price_paise', 'compare_at_price_paise', 'shipping_charge_paise',
                'gst_rate', 'hsn_code', 'weight_grams', 'length_cm', 'breadth_cm', 'height_cm',
                'has_variants', 'sku', 'brand_name', 'min_order_qty', 'max_order_qty',
                'shipping_type', 'cod_available', 'return_window_days_override', 'return_type_override',
                'pickup_location_name',
            ]);
        });
    }
};
