<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HashtagController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\LiveStreamController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\MusicController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PostsController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RestrictionsController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ShareLinkController;
use App\Http\Controllers\PortfolioController;
use App\Http\Controllers\ShopCategoryController;
use App\Http\Controllers\StoryController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ContentController;
use App\Http\Controllers\LiveChannelController;
use App\Http\Controllers\MonetizationController;
use App\Http\Controllers\SeriesController;
use App\Http\Controllers\PaidSeriesController;
use App\Http\Controllers\AdRevenueController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\MarketplaceController;
use App\Http\Controllers\SellerApplicationController;
use App\Http\Controllers\AffiliateApplicationController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\GreenScreenController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Route;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/linkstorage', function () {
    Artisan::call('storage:link');
});

Route::get('/', [LoginController::class, 'login'])->name('/');
Route::post('loginForm', [LoginController::class, 'checkLogin'])->name('loginForm');
Route::get('logout', [LoginController::class, 'logout'])->middleware(['checkLogin'])->name('logout');

Route::post('forgotPasswordForm', [LoginController::class, 'forgotPasswordForm'])->name('forgotPasswordForm');

Route::get('privacy_policy', [SettingsController::class, 'privacy_policy'])->name('privacy_policy');
Route::get('terms_of_uses', [SettingsController::class, 'terms_of_uses'])->name('terms_of_uses');
Route::get('community_guidelines', [SettingsController::class, 'community_guidelines'])->name('community_guidelines');

// Test
Route::get('testingRoute', [SettingsController::class, 'testingRoute'])->name('testingRoute');

// Deeplink (legacy)
Route::get('s/{encryptedId}', [ShareLinkController::class, 'encryptedId'])->name('encryptedId');

// Clean URLs
Route::get('p/{postId}', [ShareLinkController::class, 'viewPost'])->name('viewPost');
Route::get('r/{postId}', [ShareLinkController::class, 'viewReel'])->name('viewReel');
Route::get('u/{slug}', [PortfolioController::class, 'viewPortfolio'])->name('viewPortfolio');

// Embed
Route::get('embed/{postId}', [ShareLinkController::class, 'embedPost'])->name('embedPost');

Route::get('/.well-known/apple-app-site-association', function () {
    $file = public_path('assets/apple-app-site-association');
    if (!File::exists($file)) {
        abort(404, 'File not found');
    }

    return Response::file($file, [
        'Content-Type' => 'application/json', // Force JSON
        'Cache-Control' => 'no-cache, no-store, must-revalidate',
        'Pragma' => 'no-cache',
        'Expires' => '0',
    ]);
});

Route::get('/.well-known/assetlinks.json', function () {
    $file = public_path('assets/assetlinks.json');
    if (!File::exists($file)) {
        abort(404, 'File not found');
    }

    return Response::file($file, [
        'Content-Type' => 'application/json', // Force JSON
        'Cache-Control' => 'no-cache, no-store, must-revalidate',
        'Pragma' => 'no-cache',
        'Expires' => '0',
    ]);
});

Route::middleware(['checkLogin'])->group(function () {
    // Dashboard
    Route::get('dashboard', [DashboardController::class, 'dashboard'])->name('dashboard');
    Route::post('fetchChartData', [DashboardController::class, 'fetchChartData'])->name('fetchChartData');

    // Settings
    Route::get('setting', [SettingsController::class, 'settings'])->name('setting');
    Route::post('saveSettings', [SettingsController::class, 'saveSettings'])->name('saveSettings');

    Route::post('saveContentModerationSettings', [SettingsController::class, 'saveContentModerationSettings'])->name('saveContentModerationSettings');
    Route::post('saveGIFSettings', [SettingsController::class, 'saveGIFSettings'])->name('saveGIFSettings');
    Route::post('saveCameraEffectSettings', [SettingsController::class, 'saveCameraEffectSettings'])->name('saveCameraEffectSettings');
    Route::post('saveBasicSettings', [SettingsController::class, 'saveBasicSettings'])->name('saveBasicSettings');
    Route::post('saveSmtpSettings', [SettingsController::class, 'saveSmtpSettings'])->name('saveSmtpSettings');
    Route::post('saveLimitSettings', [SettingsController::class, 'saveLimitSettings'])->name('saveLimitSettings');
    Route::post('saveLiveStreamSettings', [SettingsController::class, 'saveLiveStreamSettings'])->name('saveLiveStreamSettings');
    Route::post('saveDeeplinkSettings', [SettingsController::class, 'saveDeeplinkSettings'])->name('saveDeeplinkSettings');
    Route::post('androidDeepLinking', [SettingsController::class, 'androidDeepLinking'])->name('androidDeepLinking');
    Route::post('iOSDeepLinking', [SettingsController::class, 'iOSDeepLinking'])->name('iOSDeepLinking');

    Route::post('changePassword', [SettingsController::class, 'changePassword'])->name('changePassword');
    Route::post('admobSettingSave', [SettingsController::class, 'admobSettingSave'])->name('admobSettingSave');
    Route::post('saveMonetizationSettings', [SettingsController::class, 'saveMonetizationSettings'])->name('saveMonetizationSettings');
    Route::post('saveAdNetworkSettings', [SettingsController::class, 'saveAdNetworkSettings'])->name('saveAdNetworkSettings');
    Route::post('saveInstagramSettings', [SettingsController::class, 'saveInstagramSettings'])->name('saveInstagramSettings');
    Route::post('updatePrivacyAndTerms', [SettingsController::class, 'updatePrivacyAndTerms'])->name('updatePrivacyAndTerms');

    Route::post('imageUploadInEditor', [SettingsController::class, 'imageUploadInEditor'])->name('imageUploadInEditor');
    Route::get('changeAndroidAdmobStatus/{value}', [SettingsController::class, 'changeAndroidAdmobStatus'])->name('changeAndroidAdmobStatus');
    Route::get('changeiOSAdmobStatus/{value}', [SettingsController::class, 'changeiOSAdmobStatus'])->name('changeiOSAdmobStatus');

    // Onboarding Screens
    Route::post('addOnBoardingScreen', [SettingsController::class, 'addOnBoardingScreen'])->name('addOnBoardingScreen');
    Route::post('onboardingScreensList', [SettingsController::class, 'onboardingScreensList'])->name('onboardingScreensList');
    Route::post('deleteOnboardingScreen', [SettingsController::class, 'deleteOnboardingScreen'])->name('deleteOnboardingScreen');
    Route::post('updateOnboardingScreen', [SettingsController::class, 'updateOnboardingScreen'])->name('updateOnboardingScreen');
    Route::post('updateOnboardingOrder', [SettingsController::class, 'updateOnboardingOrder'])->name('updateOnboardingOrder');

    // Report Reason & Withdrawal Gateways & User Level

    Route::post('addColorFilter', [SettingsController::class, 'addColorFilter'])->name('addColorFilter');
    Route::post('listColorFilters', [SettingsController::class, 'listColorFilters'])->name('listColorFilters');
    Route::post('deleteColorFilter', [SettingsController::class, 'deleteColorFilter'])->name('deleteColorFilter');
    Route::post('editColorFilter', [SettingsController::class, 'editColorFilter'])->name('editColorFilter');

    Route::post('addFaceSticker', [SettingsController::class, 'addFaceSticker'])->name('addFaceSticker');
    Route::post('listFaceStickers', [SettingsController::class, 'listFaceStickers'])->name('listFaceStickers');
    Route::post('deleteFaceSticker', [SettingsController::class, 'deleteFaceSticker'])->name('deleteFaceSticker');
    Route::post('editFaceSticker', [SettingsController::class, 'editFaceSticker'])->name('editFaceSticker');

    Route::post('addReportReason', [SettingsController::class, 'addReportReason'])->name('addReportReason');
    Route::post('listReportReasons', [SettingsController::class, 'listReportReasons'])->name('listReportReasons');
    Route::post('editReportReason', [SettingsController::class, 'editReportReason'])->name('editReportReason');
    Route::post('deleteReportReason', [SettingsController::class, 'deleteReportReason'])->name('deleteReportReason');

    Route::post('deleteWithdrawalGateway', [SettingsController::class, 'deleteWithdrawalGateway'])->name('deleteWithdrawalGateway');
    Route::post('listWithdrawalGateways', [SettingsController::class, 'listWithdrawalGateways'])->name('listWithdrawalGateways');
    Route::post('editWithdrawalGateway', [SettingsController::class, 'editWithdrawalGateway'])->name('editWithdrawalGateway');
    Route::post('addWithdrawalGateway', [SettingsController::class, 'addWithdrawalGateway'])->name('addWithdrawalGateway');

    Route::post('listUserLevels', [SettingsController::class, 'listUserLevels'])->name('listUserLevels');
    Route::post('addUserLevel', [SettingsController::class, 'addUserLevel'])->name('addUserLevel');
    Route::post('deleteUserLevel', [SettingsController::class, 'deleteUserLevel'])->name('deleteUserLevel');
    Route::post('editUserLevel', [SettingsController::class, 'editUserLevel'])->name('editUserLevel');

    // Users
    Route::get('users', [UserController::class, 'users'])->name('users');
    Route::post('listAllUsers', [UserController::class, 'listAllUsers'])->name('listAllUsers');
    Route::post('listAllModerators', [UserController::class, 'listAllModerators'])->name('listAllModerators');
    Route::post('userFreezeUnfreeze', [UserController::class, 'userFreezeUnfreeze'])->name('userFreezeUnfreeze');

    Route::get('viewUserDetails/{id}', [UserController::class, 'viewUserDetails'])->name('viewUserDetails');
    Route::post('deleteUserLink_Admin', [UserController::class, 'deleteUserLink_Admin'])->name('deleteUserLink_Admin');
    Route::post('changeUserModeratorStatus', [UserController::class, 'changeUserModeratorStatus'])->name('changeUserModeratorStatus');
    Route::post('listUserPosts', [PostsController::class, 'listUserPosts'])->name('listUserPosts');
    Route::post('listUserStories', [StoryController::class, 'listUserStories'])->name('listUserStories');
    Route::post('deleteStory_Admin', [StoryController::class, 'deleteStory_Admin'])->name('deleteStory_Admin');
    Route::post('deletePost_Admin', [PostsController::class, 'deletePost_Admin'])->name('deletePost_Admin');
    Route::get('editUser/{id}', [UserController::class, 'editUser'])->name('editUser');
    Route::post('updateUser', [UserController::class, 'updateUser'])->name('updateUser');
    Route::post('addCoinsToUserWallet_FromAdmin', [WalletController::class, 'addCoinsToUserWallet_FromAdmin'])->name('addCoinsToUserWallet_FromAdmin');

    Route::get('createDummyUser', [UserController::class, 'createDummyUser'])->name('createDummyUser');
    Route::post('addDummyUser', [UserController::class, 'addDummyUser'])->name('addDummyUser');
    Route::post('updateDummyUser', [UserController::class, 'updateUser'])->name('updateDummyUser');
    Route::post('listDummyUsers', [UserController::class, 'listDummyUsers'])->name('listDummyUsers');
    Route::get('editDummyUser/{id}', [UserController::class, 'editDummyUser'])->name('editDummyUser');
    Route::post('deleteDummyUser', [UserController::class, 'deleteDummyUser'])->name('deleteDummyUser');

    // Music
    Route::get('music', [MusicController::class, 'music'])->name('music');
    Route::post('addMusicCategory', [MusicController::class, 'addMusicCategory'])->name('addMusicCategory');
    Route::post('listMusicCategories', [MusicController::class, 'listMusicCategories'])->name('listMusicCategories');
    Route::post('deleteMusicCategory', [MusicController::class, 'deleteMusicCategory'])->name('deleteMusicCategory');
    Route::post('editMusicCategory', [MusicController::class, 'editMusicCategory'])->name('editMusicCategory');
    Route::post('addMusic', [MusicController::class, 'addMusic'])->name('addMusic');
    Route::post('listMusics', [MusicController::class, 'listMusics'])->name('listMusics');
    Route::post('editMusic', [MusicController::class, 'editMusic'])->name('editMusic');
    Route::post('deleteMusic', [MusicController::class, 'deleteMusic'])->name('deleteMusic');

    // withdrawals
    Route::get('withdrawals', [WalletController::class, 'withdrawals'])->name('withdrawals');
    Route::post('listPendingWithdrawals', [WalletController::class, 'listPendingWithdrawals'])->name('listPendingWithdrawals');
    Route::post('listCompletedWithdrawals', [WalletController::class, 'listCompletedWithdrawals'])->name('listCompletedWithdrawals');
    Route::post('listRejectedWithdrawals', [WalletController::class, 'listRejectedWithdrawals'])->name('listRejectedWithdrawals');
    Route::post('completeWithdrawal', [WalletController::class, 'completeWithdrawal'])->name('completeWithdrawal');
    Route::post('rejectWithdrawal', [WalletController::class, 'rejectWithdrawal'])->name('rejectWithdrawal');

    // Coin Packages
    Route::get('coinPackages', [WalletController::class, 'coinPackages'])->name('coinPackages');
    Route::post('addCoinPackage', [WalletController::class, 'addCoinPackage'])->name('addCoinPackage');
    Route::post('deleteCoinPackage', [WalletController::class, 'deleteCoinPackage'])->name('deleteCoinPackage');
    Route::post('editCoinPackage', [WalletController::class, 'editCoinPackage'])->name('editCoinPackage');
    Route::post('changeCoinPackageStatus', [WalletController::class, 'changeCoinPackageStatus'])->name('changeCoinPackageStatus');
    Route::post('listCoinPackages', [WalletController::class, 'listCoinPackages'])->name('listCoinPackages');

    // Dummy Lives
    Route::get('dummyLives', [LiveStreamController::class, 'dummyLives'])->name('dummyLives');
    Route::post('addDummyLive', [LiveStreamController::class, 'addDummyLive'])->name('addDummyLive');
    Route::post('listDummyLives', [LiveStreamController::class, 'listDummyLives'])->name('listDummyLives');
    Route::post('deleteDummyLive', [LiveStreamController::class, 'deleteDummyLive'])->name('deleteDummyLive');
    Route::post('changeDummyLiveStatus', [LiveStreamController::class, 'changeDummyLiveStatus'])->name('changeDummyLiveStatus');
    Route::post('editDummyLive', [LiveStreamController::class, 'editDummyLive'])->name('editDummyLive');

    // restrictions
    Route::get('restrictions', [RestrictionsController::class, 'restrictions'])->name('restrictions');
    Route::post('listUsernameRestrictions', [RestrictionsController::class, 'listUsernameRestrictions'])->name('listUsernameRestrictions');
    Route::post('addUsernameRestriction', [RestrictionsController::class, 'addUsernameRestriction'])->name('addUsernameRestriction');
    Route::post('deleteUsernameRestriction', [RestrictionsController::class, 'deleteUsernameRestriction'])->name('deleteUsernameRestriction');
    Route::post('editUsernameRestriction', [RestrictionsController::class, 'editUsernameRestriction'])->name('editUsernameRestriction');

    // notifications
    Route::get('notifications', [NotificationController::class, 'notifications'])->name('notifications');
    Route::post('listAdminNotifications', [NotificationController::class, 'listAdminNotifications'])->name('listAdminNotifications');
    Route::post('addAdminNotification', [NotificationController::class, 'addAdminNotification'])->name('addAdminNotification');
    Route::post('deleteAdminNotification', [NotificationController::class, 'deleteAdminNotification'])->name('deleteAdminNotification');
    Route::post('editAdminNotification', [NotificationController::class, 'editAdminNotification'])->name('editAdminNotification');
    Route::post('repeatAdminNotification', [NotificationController::class, 'repeatAdminNotification'])->name('repeatAdminNotification');

    // Reports
    Route::get('reports', [ReportController::class, 'reports'])->name('reports');
    Route::post('listPostReports', [ReportController::class, 'listPostReports'])->name('listPostReports');
    Route::post('rejectPostReport', [ReportController::class, 'rejectPostReport'])->name('rejectPostReport');
    Route::post('acceptPostReport', [ReportController::class, 'acceptPostReport'])->name('acceptPostReport');
    Route::post('listUserReports', [ReportController::class, 'listUserReports'])->name('listUserReports');
    Route::post('acceptUserReport', [ReportController::class, 'acceptUserReport'])->name('acceptUserReport');
    Route::post('rejectUserReport', [ReportController::class, 'rejectUserReport'])->name('rejectUserReport');

    // posts
    Route::get('posts', [PostsController::class, 'posts'])->name('posts');
    Route::post('listAllPosts', [PostsController::class, 'listAllPosts'])->name('listAllPosts');
    Route::post('listReelPosts', [PostsController::class, 'listReelPosts'])->name('listReelPosts');
    Route::post('listVideoPosts', [PostsController::class, 'listVideoPosts'])->name('listVideoPosts');
    Route::post('listImagePosts', [PostsController::class, 'listImagePosts'])->name('listImagePosts');
    Route::post('listTextPosts', [PostsController::class, 'listTextPosts'])->name('listTextPosts');
    Route::post('fetchFormattedPostDesc', [PostsController::class, 'fetchFormattedPostDesc'])->name('fetchFormattedPostDesc');

    // Post Details
    Route::get('postDetails/{id}', [PostsController::class, 'postDetails'])->name('postDetails');
    Route::post('listPostComments', [CommentController::class, 'listPostComments'])->name('listPostComments');
    Route::post('listCommentReplies', [CommentController::class, 'listCommentReplies'])->name('listCommentReplies');
    Route::post('deleteCommentReply_Admin', [CommentController::class, 'deleteCommentReply_Admin'])->name('deleteCommentReply_Admin');
    Route::post('deleteComment_Admin', [CommentController::class, 'deleteComment_Admin'])->name('deleteComment_Admin');

    // Hashtags
    Route::get('hashtags', [HashtagController::class, 'hashtags'])->name('hashtags');
    Route::post('listAllHashtags', [HashtagController::class, 'listAllHashtags'])->name('listAllHashtags');
    Route::post('addHashtag_Admin', [HashtagController::class, 'addHashtag_Admin'])->name('addHashtag_Admin');
    Route::post('deleteHashtag', [HashtagController::class, 'deleteHashtag'])->name('deleteHashtag');
    // Hashtag Details
    Route::get('hashtagDetails/{hashtag}', [HashtagController::class, 'hashtagDetails'])->name('hashtagDetails');
    Route::post('listHashtagPosts', [PostsController::class, 'listHashtagPosts'])->name('listHashtagPosts');

    // Gifts
    Route::get('gifts', [WalletController::class, 'gifts'])->name('gifts');
    Route::post('addGift', [WalletController::class, 'addGift'])->name('addGift');
    Route::post('editGift', [WalletController::class, 'editGift'])->name('editGift');
    Route::post('deleteGift', [WalletController::class, 'deleteGift'])->name('deleteGift');

    // Monetization Admin
    Route::post('updateMonetizationStatus', [MonetizationController::class, 'updateMonetizationStatus'])->name('updateMonetizationStatus');
    Route::post('reviewKycDocument', [MonetizationController::class, 'reviewKycDocument'])->name('reviewKycDocument');

    // Content Management — Music Videos, Trailers, News
    Route::get('contentMusic', [PostsController::class, 'contentMusic'])->name('contentMusic');
    Route::post('listMusicVideoPosts', [PostsController::class, 'listMusicVideoPosts'])->name('listMusicVideoPosts');
    Route::get('contentTrailers', [PostsController::class, 'contentTrailers'])->name('contentTrailers');
    Route::post('listTrailerPosts_Content', [PostsController::class, 'listTrailerPosts_Content'])->name('listTrailerPosts_Content');
    Route::get('contentNews', [PostsController::class, 'contentNews'])->name('contentNews');
    Route::post('listNewsPosts_Content', [PostsController::class, 'listNewsPosts_Content'])->name('listNewsPosts_Content');
    Route::post('toggleFeaturedPost', [PostsController::class, 'toggleFeaturedPost'])->name('toggleFeaturedPost');

    // Content Genres & Languages
    Route::get('contentGenres', [ContentController::class, 'contentGenres'])->name('contentGenres');
    Route::post('listContentGenres', [ContentController::class, 'listContentGenres'])->name('listContentGenres');
    Route::post('addContentGenre', [ContentController::class, 'addContentGenre'])->name('addContentGenre');
    Route::post('editContentGenre', [ContentController::class, 'editContentGenre'])->name('editContentGenre');
    Route::post('deleteContentGenre', [ContentController::class, 'deleteContentGenre'])->name('deleteContentGenre');
    Route::get('contentLanguages', [ContentController::class, 'contentLanguages'])->name('contentLanguages');
    Route::post('listContentLanguages', [ContentController::class, 'listContentLanguages'])->name('listContentLanguages');
    Route::post('addContentLanguage', [ContentController::class, 'addContentLanguage'])->name('addContentLanguage');
    Route::post('editContentLanguage', [ContentController::class, 'editContentLanguage'])->name('editContentLanguage');
    Route::post('deleteContentLanguage', [ContentController::class, 'deleteContentLanguage'])->name('deleteContentLanguage');

    // Live TV Channels
    Route::get('liveChannels', [LiveChannelController::class, 'liveChannels'])->name('liveChannels');
    Route::post('listLiveChannels_Admin', [LiveChannelController::class, 'listLiveChannels_Admin'])->name('listLiveChannels_Admin');
    Route::post('addLiveChannel_Admin', [LiveChannelController::class, 'addLiveChannel_Admin'])->name('addLiveChannel_Admin');
    Route::post('editLiveChannel_Admin', [LiveChannelController::class, 'editLiveChannel_Admin'])->name('editLiveChannel_Admin');
    Route::post('deleteLiveChannel_Admin', [LiveChannelController::class, 'deleteLiveChannel_Admin'])->name('deleteLiveChannel_Admin');
    Route::post('toggleLiveChannelStatus', [LiveChannelController::class, 'toggleLiveChannelStatus'])->name('toggleLiveChannelStatus');

    // Templates
    Route::get('templates', [TemplateController::class, 'templates'])->name('templates');
    Route::post('listTemplates', [TemplateController::class, 'listTemplates'])->name('listTemplates');
    Route::post('addTemplate', [TemplateController::class, 'addTemplate'])->name('addTemplate');
    Route::post('editTemplate', [TemplateController::class, 'editTemplate'])->name('editTemplate');
    Route::post('toggleTemplateStatus', [TemplateController::class, 'toggleTemplateStatus'])->name('toggleTemplateStatus');
    Route::post('deleteTemplate', [TemplateController::class, 'deleteTemplate'])->name('deleteTemplate');

    // Green Screen Backgrounds
    Route::get('greenScreenBgs', [GreenScreenController::class, 'greenScreenBgs'])->name('greenScreenBgs');
    Route::post('listGreenScreenBgs', [GreenScreenController::class, 'listGreenScreenBgs'])->name('listGreenScreenBgs');
    Route::post('addGreenScreenBg', [GreenScreenController::class, 'addGreenScreenBg'])->name('addGreenScreenBg');
    Route::post('editGreenScreenBg', [GreenScreenController::class, 'editGreenScreenBg'])->name('editGreenScreenBg');
    Route::post('toggleGreenScreenBgStatus', [GreenScreenController::class, 'toggleGreenScreenBgStatus'])->name('toggleGreenScreenBgStatus');
    Route::post('deleteGreenScreenBg', [GreenScreenController::class, 'deleteGreenScreenBg'])->name('deleteGreenScreenBg');

    // Paid Series / Premium Content
    Route::get('paidSeriesAdmin', [PaidSeriesController::class, 'paidSeriesAdmin'])->name('paidSeriesAdmin');
    Route::post('listPaidSeries_Admin', [PaidSeriesController::class, 'listPaidSeries_Admin'])->name('listPaidSeries_Admin');
    Route::post('updatePaidSeriesStatus', [PaidSeriesController::class, 'updatePaidSeriesStatus'])->name('updatePaidSeriesStatus');
    Route::post('deletePaidSeries_Admin', [PaidSeriesController::class, 'deletePaidSeries_Admin'])->name('deletePaidSeries_Admin');

    // Ad Revenue Share
    Route::get('adRevenueAdmin', [AdRevenueController::class, 'adRevenueAdmin'])->name('adRevenueAdmin');
    Route::post('listAdRevenueEnrollments', [AdRevenueController::class, 'listAdRevenueEnrollments'])->name('listAdRevenueEnrollments');
    Route::post('updateAdRevenueEnrollment', [AdRevenueController::class, 'updateAdRevenueEnrollment'])->name('updateAdRevenueEnrollment');
    Route::post('listAdRevenuePayouts', [AdRevenueController::class, 'listAdRevenuePayouts'])->name('listAdRevenuePayouts');
    Route::post('fetchAdRevenueStats', [AdRevenueController::class, 'fetchAdRevenueStats'])->name('fetchAdRevenueStats');
    Route::post('processMonthlyAdRevenue', [AdRevenueController::class, 'processMonthlyAdRevenue'])->name('processMonthlyAdRevenue');

    // Products / Shop
    Route::get('productsAdmin', [ProductController::class, 'productsAdmin'])->name('productsAdmin');
    Route::post('listProducts_Admin', [ProductController::class, 'listProducts_Admin'])->name('listProducts_Admin');
    Route::post('updateProductStatus', [ProductController::class, 'updateProductStatus'])->name('updateProductStatus');
    Route::post('deleteProduct_Admin', [ProductController::class, 'deleteProduct_Admin'])->name('deleteProduct_Admin');
    Route::get('productCategoriesAdmin', [ProductController::class, 'productCategoriesAdmin'])->name('productCategoriesAdmin');
    Route::post('listProductCategories_Admin', [ProductController::class, 'listProductCategories_Admin'])->name('listProductCategories_Admin');
    Route::post('addProductCategory', [ProductController::class, 'addProductCategory'])->name('addProductCategory');
    Route::post('editProductCategory', [ProductController::class, 'editProductCategory'])->name('editProductCategory');
    Route::post('deleteProductCategory', [ProductController::class, 'deleteProductCategory'])->name('deleteProductCategory');
    Route::get('productOrdersAdmin', [ProductController::class, 'productOrdersAdmin'])->name('productOrdersAdmin');
    Route::post('listProductOrders_Admin', [ProductController::class, 'listProductOrders_Admin'])->name('listProductOrders_Admin');

    // Marketplace
    Route::get('marketplaceAdmin', [MarketplaceController::class, 'marketplaceAdmin'])->name('marketplaceAdmin');
    Route::post('listCampaigns_Admin', [MarketplaceController::class, 'listCampaigns_Admin'])->name('listCampaigns_Admin');
    Route::post('deleteCampaign_Admin', [MarketplaceController::class, 'deleteCampaign_Admin'])->name('deleteCampaign_Admin');

    // Seller Applications (Admin)
    Route::get('sellerApplicationsAdmin', [SellerApplicationController::class, 'sellerApplicationsAdmin'])->name('sellerApplicationsAdmin');
    Route::post('listSellerApplications_Admin', [SellerApplicationController::class, 'listApplications_Admin'])->name('listSellerApplications_Admin');
    Route::post('approveSellerApplication', [SellerApplicationController::class, 'approveApplication'])->name('approveSellerApplication');
    Route::post('rejectSellerApplication', [SellerApplicationController::class, 'rejectApplication'])->name('rejectSellerApplication');
    Route::post('suspendSeller', [SellerApplicationController::class, 'suspendSeller'])->name('suspendSeller');
    Route::post('fetchSellerApplicationDetail', [SellerApplicationController::class, 'fetchApplicationDetail'])->name('fetchSellerApplicationDetail');

    // Affiliate Applications (Admin)
    Route::get('affiliateApplicationsAdmin', [AffiliateApplicationController::class, 'affiliateApplicationsAdmin'])->name('affiliateApplicationsAdmin');
    Route::post('listAffiliateApplications_Admin', [AffiliateApplicationController::class, 'listApplications_Admin'])->name('listAffiliateApplications_Admin');
    Route::post('approveAffiliateApplication', [AffiliateApplicationController::class, 'approveApplication'])->name('approveAffiliateApplication');
    Route::post('rejectAffiliateApplication', [AffiliateApplicationController::class, 'rejectApplication'])->name('rejectAffiliateApplication');

    // Returns (Admin)
    Route::post('updateReturnStatus_Admin', [\App\Http\Controllers\ReturnController::class, 'updateReturnStatus_Admin'])->name('updateReturnStatus_Admin');

    // Short Stories / Series
    Route::get('seriesAdmin', [SeriesController::class, 'seriesAdmin'])->name('seriesAdmin');
    Route::post('listSeries_Admin', [SeriesController::class, 'listSeries_Admin'])->name('listSeries_Admin');
    Route::post('updateSeriesStatus', [SeriesController::class, 'updateSeriesStatus'])->name('updateSeriesStatus');
    Route::post('deleteSeries_Admin', [SeriesController::class, 'deleteSeries_Admin'])->name('deleteSeries_Admin');

    // Platform Analytics
    Route::get('analytics', [AnalyticsController::class, 'analyticsOverview'])->name('analytics');
    Route::get('fetchAnalyticsOverview', [AnalyticsController::class, 'fetchAnalyticsOverview'])->name('fetchAnalyticsOverview');
    Route::get('fetchAnalyticsEngagement', [AnalyticsController::class, 'fetchAnalyticsEngagement'])->name('fetchAnalyticsEngagement');
    Route::get('fetchAnalyticsTopPosts', [AnalyticsController::class, 'fetchAnalyticsTopPosts'])->name('fetchAnalyticsTopPosts');
    Route::get('fetchAnalyticsTopUsers', [AnalyticsController::class, 'fetchAnalyticsTopUsers'])->name('fetchAnalyticsTopUsers');
    Route::get('fetchAnalyticsDevices', [AnalyticsController::class, 'fetchAnalyticsDevices'])->name('fetchAnalyticsDevices');
    Route::get('fetchAnalyticsLocations', [AnalyticsController::class, 'fetchAnalyticsLocations'])->name('fetchAnalyticsLocations');

    // App Languages
    Route::get('languages', [LanguageController::class, 'languages'])->name('language/index');
    Route::post('languageList', [LanguageController::class, 'languageList'])->name('languageList');
    Route::post('addLanguage', [LanguageController::class, 'addLanguage'])->name('addLanguage');
    Route::post('updateLanguage', [LanguageController::class, 'updateLanguage'])->name('updateLanguage');
    Route::post('deleteLanguage', [LanguageController::class, 'deleteLanguage'])->name('deleteLanguage');
    Route::post('makeDefaultLanguage', [LanguageController::class, 'makeDefaultLanguage'])->name('makeDefaultLanguage');
    Route::post('languageEnableDisable', [LanguageController::class, 'languageEnableDisable'])->name('languageEnableDisable');
    Route::get('edit_csv/{id}', [LanguageController::class, 'edit_csv'])->name('edit_csv');
});
