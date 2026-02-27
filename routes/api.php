<?php

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

use App\Http\Controllers\BankAccountController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\PlaylistController;
use App\Http\Controllers\BroadcastController;
use App\Http\Controllers\AffiliateApplicationController;
use App\Http\Controllers\AffiliateController;
use App\Http\Controllers\AiChatController;
use App\Http\Controllers\AiContentIdeasController;
use App\Http\Controllers\AiStickerController;
use App\Http\Controllers\AiTranslationController;
use App\Http\Controllers\AiVideoController;
use App\Http\Controllers\AiVoiceController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\BusinessAccountController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\CreatorDashboardController;
use App\Http\Controllers\CronsController;
use App\Http\Controllers\HashtagController;
use App\Http\Controllers\InterestController;
use App\Http\Controllers\LiveChannelController;
use App\Http\Controllers\LiveStreamProductController;
use App\Http\Controllers\LivestreamReplayController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\ModeratorController;
use App\Http\Controllers\SellerApplicationController;
use App\Http\Controllers\SeriesController;
use App\Http\Controllers\ShootRequestController;
use App\Http\Controllers\MusicController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PollController;
use App\Http\Controllers\PostsController;
use App\Http\Controllers\ThreadController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StoryController;
use App\Http\Controllers\StoryHighlightController;
use App\Http\Controllers\StoryInteractionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\MonetizationController;
use App\Http\Controllers\InstagramController;
use App\Http\Controllers\SocialController;
use App\Http\Controllers\CreatorInsightsController;
use App\Http\Controllers\ShareLinkController;
use App\Http\Controllers\CreatorSubscriptionController;
use App\Http\Controllers\TwoFactorController;
use App\Http\Controllers\UserNoteController;
use App\Http\Controllers\MilestoneController;
use App\Http\Controllers\CollaborationController;
use App\Http\Controllers\ScheduledLiveController;
use App\Http\Controllers\PaidSeriesController;
use App\Http\Controllers\AdRevenueController;
use App\Http\Controllers\MarketplaceController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\SharedAccessController;
use App\Http\Controllers\ParentalControlController;
use App\Http\Controllers\LocationReviewController;
use App\Http\Controllers\FriendsMapController;
use App\Http\Controllers\CallController;
use App\Http\Controllers\AccountSessionController;
use App\Http\Controllers\ChallengeController;
use App\Http\Controllers\ContentCalendarController;
use App\Http\Controllers\PortfolioController;
use App\Http\Controllers\ContentModerationController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\GreenScreenController;
use App\Http\Controllers\GrievanceController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ReturnController;
use App\Http\Controllers\AppealController;
use App\Http\Controllers\VastController;
use Illuminate\Support\Facades\Route;

// Group routes with common middleware
Route::middleware('checkHeader')->group(function () {

    // Users
    Route::prefix('user')->group(function () {
        Route::post('logInUser', [UserController::class, 'logInUser'])->middleware('throttle:auth');
        Route::post('logInFakeUser', [UserController::class, 'logInFakeUser'])->middleware('throttle:auth');
        Route::post('logOutUser', [UserController::class, 'logOutUser']);

        // Custom Auth (email/password, Google, Apple)
        Route::post('registerUser', [UserController::class, 'registerUser'])->middleware('throttle:auth');
        Route::post('loginWithEmail', [UserController::class, 'loginWithEmail'])->middleware('throttle:auth');
        Route::post('loginWithGoogle', [UserController::class, 'loginWithGoogle'])->middleware('throttle:auth');
        Route::post('loginWithApple', [UserController::class, 'loginWithApple'])->middleware('throttle:auth');
        Route::post('verifyEmail', [UserController::class, 'verifyEmail'])->middleware('throttle:auth');
        Route::post('resendVerificationCode', [UserController::class, 'resendVerificationCode'])->middleware('throttle:auth');
        Route::post('forgotPassword', [UserController::class, 'forgotPassword'])->middleware('throttle:auth');
        Route::post('verifyResetCode', [UserController::class, 'verifyResetCode'])->middleware('throttle:auth');
        Route::post('resetPassword', [UserController::class, 'resetPassword'])->middleware('throttle:auth');
        Route::post('updateUserDetails', [UserController::class, 'updateUserDetails'])->middleware('authorizeUser');
        Route::post('addUserLink', [UserController::class, 'addUserLink'])->middleware('authorizeUser');
        Route::post('deleteUserLink', [UserController::class, 'deleteUserLink'])->middleware('authorizeUser');
        Route::post('editeUserLink', [UserController::class, 'editeUserLink'])->middleware('authorizeUser');
        Route::post('updateLastUsedAt', [UserController::class, 'updateLastUsedAt'])->middleware('authorizeUser');
        Route::post('checkUsernameAvailability', [UserController::class, 'checkUsernameAvailability'])->middleware('authorizeUser');

        // Login Activity
        Route::post('fetchLoginSessions', [UserController::class, 'fetchLoginSessions'])->middleware('authorizeUser');
        Route::post('logOutSession', [UserController::class, 'logOutSession'])->middleware('authorizeUser');

        // Data Download
        Route::post('requestDataDownload', [UserController::class, 'requestDataDownload'])->middleware('authorizeUser');
        Route::post('fetchDataDownloadRequests', [UserController::class, 'fetchDataDownloadRequests'])->middleware('authorizeUser');
        Route::post('downloadMyData', [UserController::class, 'downloadMyData'])->middleware('authorizeUser');

        // Actions (Block, Follow etc)
        Route::post('blockUser', [UserController::class, 'blockUser'])->middleware('authorizeUser');
        Route::post('unBlockUser', [UserController::class, 'unBlockUser'])->middleware('authorizeUser');

        // Mute
        Route::post('muteUser', [UserController::class, 'muteUser'])->middleware('authorizeUser');
        Route::post('unMuteUser', [UserController::class, 'unMuteUser'])->middleware('authorizeUser');
        Route::post('fetchMyMutedUsers', [UserController::class, 'fetchMyMutedUsers'])->middleware('authorizeUser');

        // Restrict
        Route::post('restrictUser', [UserController::class, 'restrictUser'])->middleware('authorizeUser');
        Route::post('unrestrictUser', [UserController::class, 'unrestrictUser'])->middleware('authorizeUser');
        Route::post('fetchMyRestrictedUsers', [UserController::class, 'fetchMyRestrictedUsers'])->middleware('authorizeUser');

        // Favorites
        Route::post('addToFavorites', [UserController::class, 'addToFavorites'])->middleware('authorizeUser');
        Route::post('removeFromFavorites', [UserController::class, 'removeFromFavorites'])->middleware('authorizeUser');
        Route::post('fetchMyFavorites', [UserController::class, 'fetchMyFavorites'])->middleware('authorizeUser');

        // Close Friends
        Route::post('addCloseFriend', [UserController::class, 'addCloseFriend'])->middleware('authorizeUser');
        Route::post('removeCloseFriend', [UserController::class, 'removeCloseFriend'])->middleware('authorizeUser');
        Route::post('fetchMyCloseFriends', [UserController::class, 'fetchMyCloseFriends'])->middleware('authorizeUser');

        // Hidden Words
        Route::post('addHiddenWord', [UserController::class, 'addHiddenWord'])->middleware('authorizeUser');
        Route::post('removeHiddenWord', [UserController::class, 'removeHiddenWord'])->middleware('authorizeUser');
        Route::post('fetchHiddenWords', [UserController::class, 'fetchHiddenWords'])->middleware('authorizeUser');

        // Fetch
        Route::post('fetchMyBlockedUsers', [UserController::class, 'fetchMyBlockedUsers'])->middleware('authorizeUser');
        Route::post('fetchUserDetails', [UserController::class, 'fetchUserDetails'])->middleware('authorizeUser');

        // Followers
        Route::post('followUser', [UserController::class, 'followUser'])->middleware('authorizeUser');
        Route::post('unFollowUser', [UserController::class, 'unFollowUser'])->middleware('authorizeUser');
        Route::post('fetchUserFollowers', [UserController::class, 'fetchUserFollowers'])->middleware('authorizeUser');
        Route::post('fetchUserFollowings', [UserController::class, 'fetchUserFollowings'])->middleware('authorizeUser');

        Route::post('fetchMyFollowers', [UserController::class, 'fetchMyFollowers'])->middleware('authorizeUser');
        Route::post('fetchMyFollowings', [UserController::class, 'fetchMyFollowings'])->middleware('authorizeUser');

        Route::post('searchUsers', [UserController::class, 'searchUsers'])->middleware('authorizeUser');

        // Delete My Account
        Route::post('deleteMyAccount', [UserController::class, 'deleteMyAccount'])->middleware('authorizeUser');

        // Follow Requests (for private accounts)
        Route::post('fetchFollowRequests', [UserController::class, 'fetchFollowRequests'])->middleware('authorizeUser');
        Route::post('acceptFollowRequest', [UserController::class, 'acceptFollowRequest'])->middleware('authorizeUser');
        Route::post('rejectFollowRequest', [UserController::class, 'rejectFollowRequest'])->middleware('authorizeUser');

    });

    // Two-Factor Authentication
    Route::prefix('2fa')->group(function () {
        Route::post('verifyTOTP', [TwoFactorController::class, 'verifyTOTP'])->middleware('throttle:auth');
        Route::post('verifyBackupCode', [TwoFactorController::class, 'verifyBackupCode'])->middleware('throttle:auth');
        Route::post('setup', [TwoFactorController::class, 'setup2FA'])->middleware('authorizeUser');
        Route::post('confirm', [TwoFactorController::class, 'confirm2FA'])->middleware('authorizeUser');
        Route::post('disable', [TwoFactorController::class, 'disable2FA'])->middleware('authorizeUser');
        Route::post('regenerateBackupCodes', [TwoFactorController::class, 'regenerateBackupCodes'])->middleware('authorizeUser');
        Route::post('status', [TwoFactorController::class, 'get2FAStatus'])->middleware('authorizeUser');
    });

    // Posts
    Route::prefix('post')->group(function () {

        // Add Post
        Route::post('addUserMusic', [PostsController::class, 'addUserMusic'])->middleware('authorizeUser');
        Route::post('addPost_Reel', [PostsController::class, 'addPost_Reel'])->middleware(['authorizeUser', 'throttle:upload']);
        Route::post('addPost_Feed_Video', [PostsController::class, 'addPost_Feed_Video'])->middleware(['authorizeUser', 'throttle:upload']);
        Route::post('addPost_Feed_Image', [PostsController::class, 'addPost_Feed_Image'])->middleware(['authorizeUser', 'throttle:upload']);
        Route::post('addPost_Feed_Text', [PostsController::class, 'addPost_Feed_Text'])->middleware(['authorizeUser', 'throttle:upload']);

        // Musics
        Route::post('serchMusic', [MusicController::class, 'serchMusic'])->middleware('authorizeUser');
        Route::post('fetchMusicExplore', [MusicController::class, 'fetchMusicExplore'])->middleware('authorizeUser');
        Route::post('fetchMusicByCategories', [MusicController::class, 'fetchMusicByCategories'])->middleware('authorizeUser');
        Route::post('fetchSavedMusics', [MusicController::class, 'fetchSavedMusics'])->middleware('authorizeUser');

        // Like, Share, Save
        Route::post('likePost', [PostsController::class, 'likePost'])->middleware('authorizeUser');
        Route::post('disLikePost', [PostsController::class, 'disLikePost'])->middleware('authorizeUser');
        Route::post('increaseViewsCount', [PostsController::class, 'increaseViewsCount'])->middleware('authorizeUser');
        Route::post('increaseShareCount', [PostsController::class, 'increaseShareCount'])->middleware('authorizeUser');
        Route::post('savePost', [PostsController::class, 'savePost'])->middleware('authorizeUser');
        Route::post('unSavePost', [PostsController::class, 'unSavePost'])->middleware('authorizeUser');

        // Duets
        Route::post('fetchDuetsOfPost', [PostsController::class, 'fetchDuetsOfPost'])->middleware('authorizeUser');
        Route::post('fetchDuetCount', [PostsController::class, 'fetchDuetCount'])->middleware('authorizeUser');

        // Stitches
        Route::post('fetchStitchesOfPost', [PostsController::class, 'fetchStitchesOfPost'])->middleware('authorizeUser');

        // Collections
        Route::post('fetchCollections', [PostsController::class, 'fetchCollections'])->middleware('authorizeUser');
        Route::post('createCollection', [PostsController::class, 'createCollection'])->middleware('authorizeUser');
        Route::post('editCollection', [PostsController::class, 'editCollection'])->middleware('authorizeUser');
        Route::post('deleteCollection', [PostsController::class, 'deleteCollection'])->middleware('authorizeUser');
        Route::post('movePostToCollection', [PostsController::class, 'movePostToCollection'])->middleware('authorizeUser');
        Route::post('fetchCollectionPosts', [PostsController::class, 'fetchCollectionPosts'])->middleware('authorizeUser');

        // Shared Collections
        Route::post('shareCollection', [PostsController::class, 'shareCollection'])->middleware('authorizeUser');
        Route::post('respondCollectionInvite', [PostsController::class, 'respondCollectionInvite'])->middleware('authorizeUser');
        Route::post('fetchCollectionInvites', [PostsController::class, 'fetchCollectionInvites'])->middleware('authorizeUser');
        Route::post('fetchCollectionMembers', [PostsController::class, 'fetchCollectionMembers'])->middleware('authorizeUser');
        Route::post('removeCollectionMember', [PostsController::class, 'removeCollectionMember'])->middleware('authorizeUser');
        Route::post('leaveCollection', [PostsController::class, 'leaveCollection'])->middleware('authorizeUser');
        Route::post('savePostToSharedCollection', [PostsController::class, 'savePostToSharedCollection'])->middleware('authorizeUser');
        Route::post('fetchSharedCollections', [PostsController::class, 'fetchSharedCollections'])->middleware('authorizeUser');

        // Comment
        Route::post('addPostComment', [CommentController::class, 'addPostComment'])->middleware('authorizeUser');
        Route::post('fetchCommentById', [CommentController::class, 'fetchCommentById'])->middleware('authorizeUser');
        Route::post('fetchCommentByReplyId', [CommentController::class, 'fetchCommentByReplyId'])->middleware('authorizeUser');
        Route::post('likeComment', [CommentController::class, 'likeComment'])->middleware('authorizeUser');
        Route::post('disLikeComment', [CommentController::class, 'disLikeComment'])->middleware('authorizeUser');
        Route::post('deleteComment', [CommentController::class, 'deleteComment'])->middleware('authorizeUser');
        // Pin/Unpin comment
        Route::post('pinComment', [CommentController::class, 'pinComment'])->middleware('authorizeUser');
        Route::post('unPinComment', [CommentController::class, 'unPinComment'])->middleware('authorizeUser');
        // Pin/Pin Post
        Route::post('pinPost', [PostsController::class, 'pinPost'])->middleware('authorizeUser');
        Route::post('unpinPost', [PostsController::class, 'unpinPost'])->middleware('authorizeUser');
        Route::post('updatePostCaptions', [PostsController::class, 'updatePostCaptions'])->middleware('authorizeUser');

        // Scheduled Posts
        Route::post('fetchScheduledPosts', [PostsController::class, 'fetchScheduledPosts'])->middleware('authorizeUser');
        Route::post('cancelScheduledPost', [PostsController::class, 'cancelScheduledPost'])->middleware('authorizeUser');

        // Not Interested
        Route::post('markNotInterested', [PostsController::class, 'markNotInterested'])->middleware('authorizeUser');
        Route::post('undoNotInterested', [PostsController::class, 'undoNotInterested'])->middleware('authorizeUser');

        // Embed
        Route::post('generateEmbedCode', [PostsController::class, 'generateEmbedCode'])->middleware('authorizeUser');

        // Q&A
        Route::post('askQuestion', [QuestionController::class, 'askQuestion'])->middleware('authorizeUser');
        Route::post('answerQuestion', [QuestionController::class, 'answerQuestion'])->middleware('authorizeUser');
        Route::post('deleteQuestion', [QuestionController::class, 'deleteQuestion'])->middleware('authorizeUser');
        Route::post('toggleHideQuestion', [QuestionController::class, 'toggleHideQuestion'])->middleware('authorizeUser');
        Route::post('togglePinQuestion', [QuestionController::class, 'togglePinQuestion'])->middleware('authorizeUser');
        Route::post('likeQuestion', [QuestionController::class, 'likeQuestion'])->middleware('authorizeUser');
        Route::post('fetchQuestions', [QuestionController::class, 'fetchQuestions'])->middleware('authorizeUser');

        // Comment Reply
        Route::post('replyToComment', [CommentController::class, 'replyToComment'])->middleware('authorizeUser');
        Route::post('deleteCommentReply', [CommentController::class, 'deleteCommentReply'])->middleware('authorizeUser');

        Route::post('fetchPostComments', [CommentController::class, 'fetchPostComments'])->middleware('authorizeUser');
        Route::post('fetchPostCommentReplies', [CommentController::class, 'fetchPostCommentReplies'])->middleware('authorizeUser');
        Route::post('fetchVideoRepliesForComment', [CommentController::class, 'fetchVideoRepliesForComment'])->middleware('authorizeUser');
        // Comment Approval
        Route::post('fetchPendingComments', [CommentController::class, 'fetchPendingComments'])->middleware('authorizeUser');
        Route::post('approveComment', [CommentController::class, 'approveComment'])->middleware('authorizeUser');
        Route::post('rejectComment', [CommentController::class, 'rejectComment'])->middleware('authorizeUser');
        // Creator Like & Top Comments
        Route::post('creatorLikeComment', [CommentController::class, 'creatorLikeComment'])->middleware('authorizeUser');
        Route::post('creatorUnlikeComment', [CommentController::class, 'creatorUnlikeComment'])->middleware('authorizeUser');
        Route::post('fetchTopComments', [CommentController::class, 'fetchTopComments'])->middleware('authorizeUser');

        // Fetch
        Route::post('fetchPostById', [PostsController::class, 'fetchPostById'])->middleware('authorizeUser');
        Route::post('fetchPostsDiscover', [PostsController::class, 'fetchPostsDiscover'])->middleware(['authorizeUser', 'throttle:feed']);
        Route::post('fetchPostsFollowing', [PostsController::class, 'fetchPostsFollowing'])->middleware(['authorizeUser', 'throttle:feed']);
        Route::post('fetchPostsFavorites', [PostsController::class, 'fetchPostsFavorites'])->middleware(['authorizeUser', 'throttle:feed']);
        Route::post('fetchReelPostsByMusic', [PostsController::class, 'fetchReelPostsByMusic'])->middleware(['authorizeUser', 'throttle:feed']);
        Route::post('fetchPostsByHashtag', [PostsController::class, 'fetchPostsByHashtag'])->middleware(['authorizeUser', 'throttle:feed']);
        Route::post('fetchUserPosts', [PostsController::class, 'fetchUserPosts'])->middleware(['authorizeUser', 'throttle:feed']);
        Route::post('fetchSavedPosts', [PostsController::class, 'fetchSavedPosts'])->middleware(['authorizeUser', 'throttle:feed']);
        Route::post('fetchExplorePageData', [PostsController::class, 'fetchExplorePageData'])->middleware(['authorizeUser', 'throttle:feed']);
        Route::post('fetchEnhancedExplore', [PostsController::class, 'fetchEnhancedExplore'])->middleware(['authorizeUser', 'throttle:feed']);
        Route::post('fetchPostsByLocation', [PostsController::class, 'fetchPostsByLocation'])->middleware(['authorizeUser', 'throttle:feed']);
        Route::post('fetchPostsNearBy', [PostsController::class, 'fetchPostsNearBy'])->middleware(['authorizeUser', 'throttle:feed']);
        Route::post('fetchTrendingPosts', [PostsController::class, 'fetchTrendingPosts'])->middleware(['authorizeUser', 'throttle:feed']);
        Route::post('fetchSubscriberOnlyPosts', [PostsController::class, 'fetchSubscriberOnlyPosts'])->middleware(['authorizeUser', 'throttle:feed']);

        // Search
        Route::post('searchPosts', [PostsController::class, 'searchPosts'])->middleware('authorizeUser');
        Route::post('searchPostsFTS', [PostsController::class, 'searchPostsFTS'])->middleware('authorizeUser');
        Route::post('searchHashtags', [HashtagController::class, 'searchHashtags'])->middleware('authorizeUser');

        // Delete Post
        Route::post('deletePost', [PostsController::class, 'deletePost'])->middleware('authorizeUser');

        // Stories
        Route::post('createStory', [StoryController::class, 'createStory'])->middleware('authorizeUser');
        Route::post('viewStory', [StoryController::class, 'viewStory'])->middleware('authorizeUser');
        Route::post('fetchStory', [StoryController::class, 'fetchStory'])->middleware('authorizeUser');
        Route::post('deleteStory', [StoryController::class, 'deleteStory'])->middleware('authorizeUser');
        Route::post('fetchStoryByID', [StoryController::class, 'fetchStoryByID'])->middleware('authorizeUser');


    });

    // Story Highlights
    Route::prefix('highlight')->group(function () {
        Route::post('createHighlight', [StoryHighlightController::class, 'createHighlight'])->middleware('authorizeUser');
        Route::post('fetchHighlights', [StoryHighlightController::class, 'fetchHighlights'])->middleware('authorizeUser');
        Route::post('fetchHighlightById', [StoryHighlightController::class, 'fetchHighlightById'])->middleware('authorizeUser');
        Route::post('updateHighlight', [StoryHighlightController::class, 'updateHighlight'])->middleware('authorizeUser');
        Route::post('deleteHighlight', [StoryHighlightController::class, 'deleteHighlight'])->middleware('authorizeUser');
        Route::post('addStoryToHighlight', [StoryHighlightController::class, 'addStoryToHighlight'])->middleware('authorizeUser');
        Route::post('removeHighlightItem', [StoryHighlightController::class, 'removeHighlightItem'])->middleware('authorizeUser');
        Route::post('reorderHighlights', [StoryHighlightController::class, 'reorderHighlights'])->middleware('authorizeUser');
    });

    // Story Sticker Interactions (Polls & Questions)
    Route::prefix('sticker')->group(function () {
        Route::post('voteOnPoll', [StoryInteractionController::class, 'voteOnPoll'])->middleware('authorizeUser');
        Route::post('fetchPollResults', [StoryInteractionController::class, 'fetchPollResults'])->middleware('authorizeUser');
        Route::post('submitQuestionResponse', [StoryInteractionController::class, 'submitQuestionResponse'])->middleware('authorizeUser');
        Route::post('fetchQuestionResponses', [StoryInteractionController::class, 'fetchQuestionResponses'])->middleware('authorizeUser');
        Route::post('answerQuiz', [StoryInteractionController::class, 'answerQuiz'])->middleware('authorizeUser');
        Route::post('fetchQuizResults', [StoryInteractionController::class, 'fetchQuizResults'])->middleware('authorizeUser');
        Route::post('submitSlider', [StoryInteractionController::class, 'submitSlider'])->middleware('authorizeUser');
        Route::post('fetchSliderResults', [StoryInteractionController::class, 'fetchSliderResults'])->middleware('authorizeUser');
        Route::post('subscribeCountdown', [StoryInteractionController::class, 'subscribeCountdown'])->middleware('authorizeUser');
        Route::post('unsubscribeCountdown', [StoryInteractionController::class, 'unsubscribeCountdown'])->middleware('authorizeUser');
        Route::post('fetchCountdownInfo', [StoryInteractionController::class, 'fetchCountdownInfo'])->middleware('authorizeUser');
        Route::post('createAddYoursChain', [StoryInteractionController::class, 'createAddYoursChain'])->middleware('authorizeUser');
        Route::post('participateInChain', [StoryInteractionController::class, 'participateInChain'])->middleware('authorizeUser');
        Route::post('fetchChainInfo', [StoryInteractionController::class, 'fetchChainInfo'])->middleware('authorizeUser');
    });

    // Notes
    Route::prefix('notes')->group(function () {
        Route::post('createNote', [UserNoteController::class, 'createNote'])->middleware('authorizeUser');
        Route::post('fetchMyNote', [UserNoteController::class, 'fetchMyNote'])->middleware('authorizeUser');
        Route::post('fetchFollowerNotes', [UserNoteController::class, 'fetchFollowerNotes'])->middleware('authorizeUser');
        Route::post('deleteNote', [UserNoteController::class, 'deleteNote'])->middleware('authorizeUser');
    });

    // Misc
    Route::prefix('misc')->group(function () {

        // Gifts
        Route::post('sendGift', [WalletController::class, 'sendGift'])->middleware('authorizeUser');
        Route::post('submitWithdrawalRequest', [WalletController::class, 'submitWithdrawalRequest'])->middleware('authorizeUser');
        Route::post('fetchMyWithdrawalRequest', [WalletController::class, 'fetchMyWithdrawalRequest'])->middleware('authorizeUser');
        Route::post('buyCoins', [WalletController::class, 'buyCoins'])->middleware('authorizeUser');

        // Tipping
        Route::post('sendTip', [WalletController::class, 'sendTip'])->middleware('authorizeUser');
        Route::post('fetchTipAmounts', [WalletController::class, 'fetchTipAmounts'])->middleware('authorizeUser');

        // Creator Tiers
        Route::post('fetchCreatorTiers', [WalletController::class, 'fetchCreatorTiers'])->middleware('authorizeUser');
        Route::post('fetchMyTierStatus', [WalletController::class, 'fetchMyTierStatus'])->middleware('authorizeUser');

        // Report
        Route::post('reportPost', [ReportController::class, 'reportPost'])->middleware('authorizeUser');
        Route::post('reportUser', [ReportController::class, 'reportUser'])->middleware('authorizeUser');

        // Notification
        Route::post('fetchAdminNotifications', [NotificationController::class, 'fetchAdminNotifications'])->middleware('authorizeUser');
        Route::post('pushNotificationToSingleUser', [NotificationController::class, 'pushNotificationToSingleUser'])->middleware('authorizeUser');
        Route::post('fetchActivityNotifications', [NotificationController::class, 'fetchActivityNotifications'])->middleware('authorizeUser');
        Route::post('fetchNotificationsByCategory', [NotificationController::class, 'fetchNotificationsByCategory'])->middleware('authorizeUser');
        Route::post('markNotificationsAsRead', [NotificationController::class, 'markNotificationsAsRead'])->middleware('authorizeUser');
        Route::post('markAllNotificationsAsRead', [NotificationController::class, 'markAllNotificationsAsRead'])->middleware('authorizeUser');
        Route::post('fetchUnreadNotificationCount', [NotificationController::class, 'fetchUnreadNotificationCount'])->middleware('authorizeUser');

    });

    // Poll
    Route::prefix('poll')->group(function () {
        Route::post('createPollPost', [PollController::class, 'createPollPost'])->middleware('authorizeUser');
        Route::post('voteOnPoll', [PollController::class, 'voteOnPoll'])->middleware('authorizeUser');
        Route::post('fetchPollResults', [PollController::class, 'fetchPollResults'])->middleware('authorizeUser');
        Route::post('closePoll', [PollController::class, 'closePoll'])->middleware('authorizeUser');
    });

    // Account Sessions (Multi-Account)
    Route::prefix('account')->group(function () {
        Route::post('fetchDeviceAccounts', [AccountSessionController::class, 'fetchDeviceAccounts'])->middleware('authorizeUser');
        Route::post('switchAccount', [AccountSessionController::class, 'switchAccount'])->middleware('authorizeUser');
        Route::post('removeAccountFromDevice', [AccountSessionController::class, 'removeAccountFromDevice'])->middleware('authorizeUser');
    });

    // Content Calendar
    Route::prefix('calendar')->group(function () {
        Route::post('fetchCalendarEvents', [ContentCalendarController::class, 'fetchCalendarEvents'])->middleware('authorizeUser');
        Route::post('fetchBestTimeToPost', [ContentCalendarController::class, 'fetchBestTimeToPost'])->middleware('authorizeUser');
        Route::post('updateDraftDate', [ContentCalendarController::class, 'updateDraftDate'])->middleware('authorizeUser');
        Route::post('bulkSchedule', [ContentCalendarController::class, 'bulkSchedule'])->middleware('authorizeUser');
    });

    // Thread
    Route::prefix('thread')->group(function () {
        Route::post('createThread', [ThreadController::class, 'createThread'])->middleware('authorizeUser');
        Route::post('addToThread', [ThreadController::class, 'addToThread'])->middleware('authorizeUser');
        Route::post('fetchThread', [ThreadController::class, 'fetchThread'])->middleware('authorizeUser');
        Route::post('quoteRepost', [ThreadController::class, 'quoteRepost'])->middleware('authorizeUser');
    });

    // Moderator
    Route::prefix('moderator')->group(function () {
        Route::post('moderator_freezeUser', [ModeratorController::class, 'moderator_freezeUser'])->middleware('authorizeUser');
        Route::post('moderator_unFreezeUser', [ModeratorController::class, 'moderator_unFreezeUser'])->middleware('authorizeUser');
        Route::post('moderator_deletePost', [ModeratorController::class, 'moderator_deletePost'])->middleware('authorizeUser');
        Route::post('moderator_deleteStory', [ModeratorController::class, 'moderator_deleteStory'])->middleware('authorizeUser');
        Route::post('issueViolation', [ModeratorController::class, 'issueViolation'])->middleware('authorizeUser');
        Route::post('fetchPendingReports', [ModeratorController::class, 'fetchPendingReports'])->middleware('authorizeUser');
        Route::post('resolveReport', [ModeratorController::class, 'resolveReport'])->middleware('authorizeUser');
        Route::post('fetchUserViolations', [ModeratorController::class, 'fetchUserViolations'])->middleware('authorizeUser');
        Route::post('fetchModerationLog', [ModeratorController::class, 'fetchModerationLog'])->middleware('authorizeUser');
        Route::post('checkBannedWords', [ModeratorController::class, 'checkBannedWords'])->middleware('authorizeUser');
        Route::post('fetchModerationStats', [ModeratorController::class, 'fetchModerationStats'])->middleware('authorizeUser');
    });

    // Business Account
    Route::prefix('business')->group(function () {
        Route::post('fetchProfileCategories', [BusinessAccountController::class, 'fetchProfileCategories'])->middleware('authorizeUser');
        Route::post('fetchProfileSubCategories', [BusinessAccountController::class, 'fetchProfileSubCategories'])->middleware('authorizeUser');
        Route::post('convertToBusinessAccount', [BusinessAccountController::class, 'convertToBusinessAccount'])->middleware('authorizeUser');
        Route::post('fetchMyBusinessStatus', [BusinessAccountController::class, 'fetchMyBusinessStatus'])->middleware('authorizeUser');
        Route::post('revertToPersonalAccount', [BusinessAccountController::class, 'revertToPersonalAccount'])->middleware('authorizeUser');
    });

    // Interests
    Route::prefix('interest')->group(function () {
        Route::post('fetchInterests', [InterestController::class, 'fetchInterests'])->middleware('authorizeUser');
        Route::post('updateMyInterests', [InterestController::class, 'updateMyInterests'])->middleware('authorizeUser');
        Route::post('fetchFeedPreferences', [InterestController::class, 'fetchFeedPreferences'])->middleware('authorizeUser');
        Route::post('updateFeedPreference', [InterestController::class, 'updateFeedPreference'])->middleware('authorizeUser');
        Route::post('resetFeed', [InterestController::class, 'resetFeed'])->middleware('authorizeUser');
        Route::post('fetchMyKeywordFilters', [InterestController::class, 'fetchMyKeywordFilters'])->middleware('authorizeUser');
        Route::post('addKeywordFilter', [InterestController::class, 'addKeywordFilter'])->middleware('authorizeUser');
        Route::post('removeKeywordFilter', [InterestController::class, 'removeKeywordFilter'])->middleware('authorizeUser');
    });

    // Settings Routes
    Route::prefix('settings')->group(function () {
        Route::post('fetchSettings', [SettingsController::class, 'fetchSettings']);
        Route::post('uploadFileGivePath', [SettingsController::class, 'uploadFileGivePath'])->middleware('authorizeUser');
        Route::post('deleteFile', [SettingsController::class, 'deleteFile'])->middleware('authorizeUser');
    });

    // Instagram Import
    Route::prefix('instagram')->group(function () {
        Route::post('connect', [InstagramController::class, 'handleOAuthCallback'])->middleware('authorizeUser');
        Route::post('disconnect', [InstagramController::class, 'disconnect'])->middleware('authorizeUser');
        Route::post('fetchMedia', [InstagramController::class, 'fetchMedia'])->middleware('authorizeUser');
        Route::post('importVideo', [InstagramController::class, 'importVideo'])->middleware('authorizeUser');
        Route::post('importBulk', [InstagramController::class, 'importBulk'])->middleware('authorizeUser');
        Route::post('getConnectionStatus', [InstagramController::class, 'getConnectionStatus'])->middleware('authorizeUser');
        Route::post('toggleAutoSync', [InstagramController::class, 'toggleAutoSync'])->middleware('authorizeUser');
        Route::post('getImportHistory', [InstagramController::class, 'getImportHistory'])->middleware('authorizeUser');
    });

    // Monetization
    Route::prefix('monetization')->group(function () {
        Route::post('fetchMonetizationStatus', [MonetizationController::class, 'fetchMonetizationStatus'])->middleware('authorizeUser');
        Route::post('applyForMonetization', [MonetizationController::class, 'applyForMonetization'])->middleware('authorizeUser');
        Route::post('submitKycDocument', [MonetizationController::class, 'submitKycDocument'])->middleware('authorizeUser');
        Route::post('fetchEarningsSummary', [MonetizationController::class, 'fetchEarningsSummary'])->middleware('authorizeUser');
        Route::post('fetchTransactionHistory', [MonetizationController::class, 'fetchTransactionHistory'])->middleware('authorizeUser');
        Route::post('claimRewardedAd', [MonetizationController::class, 'claimRewardedAd'])->middleware('authorizeUser');
    });

    // Ad Revenue Share
    Route::prefix('adRevenue')->group(function () {
        Route::post('logAdImpression', [AdRevenueController::class, 'logAdImpression'])->middleware('authorizeUser');
        Route::post('enrollInAdRevenueShare', [AdRevenueController::class, 'enrollInAdRevenueShare'])->middleware('authorizeUser');
        Route::post('fetchAdRevenueStatus', [AdRevenueController::class, 'fetchAdRevenueStatus'])->middleware('authorizeUser');
        Route::post('fetchAdRevenueSummary', [AdRevenueController::class, 'fetchAdRevenueSummary'])->middleware('authorizeUser');
    });

    // Content (Music Videos, Trailers, News, Short Stories)
    Route::prefix('content')->group(function () {
        Route::post('fetchContentByType', [PostsController::class, 'fetchContentByType'])->middleware('authorizeUser');
        Route::post('fetchContentGenres', [PostsController::class, 'fetchContentGenres'])->middleware('authorizeUser');
        Route::post('fetchContentLanguages', [PostsController::class, 'fetchContentLanguages'])->middleware('authorizeUser');
        Route::post('fetchLinkedPost', [PostsController::class, 'fetchLinkedPost'])->middleware('authorizeUser');
        Route::post('addPost_MusicVideo', [PostsController::class, 'addPost_MusicVideo'])->middleware(['authorizeUser', 'throttle:upload']);
        Route::post('addPost_Trailer', [PostsController::class, 'addPost_Trailer'])->middleware(['authorizeUser', 'throttle:upload']);
        Route::post('addPost_News', [PostsController::class, 'addPost_News'])->middleware(['authorizeUser', 'throttle:upload']);
    });

    // Live TV Channels
    Route::prefix('livetv')->group(function () {
        Route::post('fetchLiveChannels', [LiveChannelController::class, 'fetchLiveChannels'])->middleware('authorizeUser');
        Route::post('addLiveChannel', [LiveChannelController::class, 'addLiveChannel'])->middleware('authorizeUser');
        Route::post('updateLiveChannel', [LiveChannelController::class, 'updateLiveChannel'])->middleware('authorizeUser');
        Route::post('deleteLiveChannel', [LiveChannelController::class, 'deleteLiveChannel'])->middleware('authorizeUser');
    });

    // Scheduled Lives
    Route::prefix('scheduledLive')->group(function () {
        Route::post('create', [ScheduledLiveController::class, 'createScheduledLive'])->middleware('authorizeUser');
        Route::post('fetch', [ScheduledLiveController::class, 'fetchScheduledLives'])->middleware('authorizeUser');
        Route::post('fetchMine', [ScheduledLiveController::class, 'fetchMyScheduledLives'])->middleware('authorizeUser');
        Route::post('toggleReminder', [ScheduledLiveController::class, 'toggleReminder'])->middleware('authorizeUser');
        Route::post('cancel', [ScheduledLiveController::class, 'cancelScheduledLive'])->middleware('authorizeUser');
        Route::post('update', [ScheduledLiveController::class, 'updateScheduledLive'])->middleware('authorizeUser');
    });

    // Short Stories / Series
    Route::prefix('series')->group(function () {
        Route::post('fetchSeries', [SeriesController::class, 'fetchSeries'])->middleware('authorizeUser');
        Route::post('fetchSeriesEpisodes', [SeriesController::class, 'fetchSeriesEpisodes'])->middleware('authorizeUser');
        Route::post('createSeries', [SeriesController::class, 'createSeries'])->middleware('authorizeUser');
    });

    // Social (Repost, Trending, Online Status, Reactions)
    Route::prefix('social')->group(function () {
        Route::post('repostPost', [SocialController::class, 'repostPost'])->middleware('authorizeUser');
        Route::post('undoRepost', [SocialController::class, 'undoRepost'])->middleware('authorizeUser');
        Route::post('fetchUserReposts', [SocialController::class, 'fetchUserReposts'])->middleware('authorizeUser');
        Route::post('fetchTrendingHashtags', [SocialController::class, 'fetchTrendingHashtags'])->middleware('authorizeUser');
        Route::post('fetchUsersOnlineStatus', [SocialController::class, 'fetchUsersOnlineStatus'])->middleware('authorizeUser');
        Route::post('reactToComment', [SocialController::class, 'reactToComment'])->middleware('authorizeUser');
        Route::post('fetchCommentReactions', [SocialController::class, 'fetchCommentReactions'])->middleware('authorizeUser');
    });

    // Creator Dashboard & Analytics
    Route::prefix('creator')->group(function () {
        Route::post('fetchCreatorDashboard', [CreatorDashboardController::class, 'fetchCreatorDashboard'])->middleware('authorizeUser');
        Route::post('fetchPostAnalytics', [CreatorDashboardController::class, 'fetchPostAnalytics'])->middleware('authorizeUser');
        Route::post('fetchAudienceInsights', [CreatorDashboardController::class, 'fetchAudienceInsights'])->middleware('authorizeUser');
        Route::post('fetchSearchInsights', [CreatorDashboardController::class, 'fetchSearchInsights'])->middleware('authorizeUser');
    });

    // Creator AI Insights
    Route::prefix('creatorInsights')->group(function () {
        Route::post('generateInsights', [CreatorInsightsController::class, 'generateInsights'])->middleware('authorizeUser');
        Route::post('fetchInsights', [CreatorInsightsController::class, 'fetchInsights'])->middleware('authorizeUser');
        Route::post('markInsightRead', [CreatorInsightsController::class, 'markInsightRead'])->middleware('authorizeUser');
        Route::post('fetchTrendingTopics', [CreatorInsightsController::class, 'fetchTrendingTopics'])->middleware('authorizeUser');
    });

    // Sharing
    Route::post('sharing/generateShareableCard', [ShareLinkController::class, 'generateShareableCard'])->middleware('authorizeUser');

    // Broadcast Channels
    Route::prefix('broadcast')->group(function () {
        Route::post('createChannel', [BroadcastController::class, 'createChannel'])->middleware('authorizeUser');
        Route::post('updateChannel', [BroadcastController::class, 'updateChannel'])->middleware('authorizeUser');
        Route::post('deleteChannel', [BroadcastController::class, 'deleteChannel'])->middleware('authorizeUser');
        Route::post('joinChannel', [BroadcastController::class, 'joinChannel'])->middleware('authorizeUser');
        Route::post('leaveChannel', [BroadcastController::class, 'leaveChannel'])->middleware('authorizeUser');
        Route::post('toggleMute', [BroadcastController::class, 'toggleMute'])->middleware('authorizeUser');
        Route::post('fetchMyChannels', [BroadcastController::class, 'fetchMyChannels'])->middleware('authorizeUser');
        Route::post('fetchUserChannels', [BroadcastController::class, 'fetchUserChannels'])->middleware('authorizeUser');
        Route::post('fetchChannelDetails', [BroadcastController::class, 'fetchChannelDetails'])->middleware('authorizeUser');
        Route::post('fetchChannelMembers', [BroadcastController::class, 'fetchChannelMembers'])->middleware('authorizeUser');
        Route::post('searchChannels', [BroadcastController::class, 'searchChannels'])->middleware('authorizeUser');
    });

    // Creator Subscriptions
    Route::prefix('subscription')->group(function () {
        Route::post('enableSubscriptions', [CreatorSubscriptionController::class, 'enableSubscriptions'])->middleware('authorizeUser');
        Route::post('disableSubscriptions', [CreatorSubscriptionController::class, 'disableSubscriptions'])->middleware('authorizeUser');
        Route::post('createTier', [CreatorSubscriptionController::class, 'createTier'])->middleware('authorizeUser');
        Route::post('updateTier', [CreatorSubscriptionController::class, 'updateTier'])->middleware('authorizeUser');
        Route::post('deleteTier', [CreatorSubscriptionController::class, 'deleteTier'])->middleware('authorizeUser');
        Route::post('fetchTiers', [CreatorSubscriptionController::class, 'fetchTiers'])->middleware('authorizeUser');
        Route::post('subscribe', [CreatorSubscriptionController::class, 'subscribe'])->middleware('authorizeUser');
        Route::post('cancelSubscription', [CreatorSubscriptionController::class, 'cancelSubscription'])->middleware('authorizeUser');
        Route::post('fetchMySubscriptions', [CreatorSubscriptionController::class, 'fetchMySubscriptions'])->middleware('authorizeUser');
        Route::post('fetchMySubscribers', [CreatorSubscriptionController::class, 'fetchMySubscribers'])->middleware('authorizeUser');
        Route::post('checkSubscription', [CreatorSubscriptionController::class, 'checkSubscription'])->middleware('authorizeUser');
    });

    // Playlists
    Route::prefix('playlist')->group(function () {
        Route::post('fetchUserPlaylists', [PlaylistController::class, 'fetchUserPlaylists'])->middleware('authorizeUser');
        Route::post('createPlaylist', [PlaylistController::class, 'createPlaylist'])->middleware('authorizeUser');
        Route::post('updatePlaylist', [PlaylistController::class, 'updatePlaylist'])->middleware('authorizeUser');
        Route::post('deletePlaylist', [PlaylistController::class, 'deletePlaylist'])->middleware('authorizeUser');
        Route::post('addPostToPlaylist', [PlaylistController::class, 'addPostToPlaylist'])->middleware('authorizeUser');
        Route::post('removePostFromPlaylist', [PlaylistController::class, 'removePostFromPlaylist'])->middleware('authorizeUser');
        Route::post('fetchPlaylistPosts', [PlaylistController::class, 'fetchPlaylistPosts'])->middleware('authorizeUser');
        Route::post('reorderPlaylistPosts', [PlaylistController::class, 'reorderPlaylistPosts'])->middleware('authorizeUser');
    });

    // Creator Milestones
    Route::prefix('milestone')->group(function () {
        Route::post('fetchMyMilestones', [MilestoneController::class, 'fetchMyMilestones'])->middleware('authorizeUser');
        Route::post('checkMilestones', [MilestoneController::class, 'checkMilestones'])->middleware('authorizeUser');
        Route::post('markMilestoneSeen', [MilestoneController::class, 'markMilestoneSeen'])->middleware('authorizeUser');
        Route::post('markMilestoneShared', [MilestoneController::class, 'markMilestoneShared'])->middleware('authorizeUser');
    });

    // Challenges
    Route::prefix('challenge')->group(function () {
        Route::post('createChallenge', [ChallengeController::class, 'createChallenge'])->middleware('authorizeUser');
        Route::post('fetchChallenges', [ChallengeController::class, 'fetchChallenges'])->middleware('authorizeUser');
        Route::post('fetchChallengeById', [ChallengeController::class, 'fetchChallengeById'])->middleware('authorizeUser');
        Route::post('enterChallenge', [ChallengeController::class, 'enterChallenge'])->middleware('authorizeUser');
        Route::post('fetchEntries', [ChallengeController::class, 'fetchEntries'])->middleware('authorizeUser');
        Route::post('fetchLeaderboard', [ChallengeController::class, 'fetchLeaderboard'])->middleware('authorizeUser');
        Route::post('endChallenge', [ChallengeController::class, 'endChallenge'])->middleware('authorizeUser');
        Route::post('awardPrizes', [ChallengeController::class, 'awardPrizes'])->middleware('authorizeUser');
    });

    // Collaborative Posts
    Route::prefix('collab')->group(function () {
        Route::post('inviteCollaborator', [CollaborationController::class, 'inviteCollaborator'])->middleware('authorizeUser');
        Route::post('respondToInvite', [CollaborationController::class, 'respondToInvite'])->middleware('authorizeUser');
        Route::post('fetchPendingInvites', [CollaborationController::class, 'fetchPendingInvites'])->middleware('authorizeUser');
        Route::post('fetchPostCollaborators', [CollaborationController::class, 'fetchPostCollaborators'])->middleware('authorizeUser');
        Route::post('removeCollaborator', [CollaborationController::class, 'removeCollaborator'])->middleware('authorizeUser');
    });

    // Paid Series / Premium Content
    Route::prefix('paidSeries')->group(function () {
        Route::post('create', [PaidSeriesController::class, 'createPaidSeries'])->middleware('authorizeUser');
        Route::post('update', [PaidSeriesController::class, 'updatePaidSeries'])->middleware('authorizeUser');
        Route::post('delete', [PaidSeriesController::class, 'deletePaidSeries'])->middleware('authorizeUser');
        Route::post('addVideo', [PaidSeriesController::class, 'addVideoToSeries'])->middleware('authorizeUser');
        Route::post('removeVideo', [PaidSeriesController::class, 'removeVideoFromSeries'])->middleware('authorizeUser');
        Route::post('reorderVideos', [PaidSeriesController::class, 'reorderSeriesVideos'])->middleware('authorizeUser');
        Route::post('fetch', [PaidSeriesController::class, 'fetchPaidSeries'])->middleware('authorizeUser');
        Route::post('fetchMine', [PaidSeriesController::class, 'fetchMyPaidSeries'])->middleware('authorizeUser');
        Route::post('fetchVideos', [PaidSeriesController::class, 'fetchSeriesVideos'])->middleware('authorizeUser');
        Route::post('purchase', [PaidSeriesController::class, 'purchaseSeries'])->middleware('authorizeUser');
        Route::post('fetchMyPurchases', [PaidSeriesController::class, 'fetchMyPurchases'])->middleware('authorizeUser');
    });

    // Products / Shop
    Route::prefix('products')->group(function () {
        Route::post('create', [ProductController::class, 'createProduct'])->middleware('authorizeUser');
        Route::post('update', [ProductController::class, 'updateProduct'])->middleware('authorizeUser');
        Route::post('delete', [ProductController::class, 'deleteProduct'])->middleware('authorizeUser');
        Route::post('fetch', [ProductController::class, 'fetchProducts'])->middleware('authorizeUser');
        Route::post('fetchMine', [ProductController::class, 'fetchMyProducts'])->middleware('authorizeUser');
        Route::post('fetchById', [ProductController::class, 'fetchProductById'])->middleware('authorizeUser');
        Route::post('purchase', [ProductController::class, 'purchaseProduct'])->middleware('authorizeUser');
        Route::post('fetchMyOrders', [ProductController::class, 'fetchMyOrders'])->middleware('authorizeUser');
        Route::post('fetchSellerOrders', [ProductController::class, 'fetchSellerOrders'])->middleware('authorizeUser');
        Route::post('updateOrderStatus', [ProductController::class, 'updateOrderStatus'])->middleware('authorizeUser');
        Route::post('submitReview', [ProductController::class, 'submitReview'])->middleware('authorizeUser');
        Route::post('fetchReviews', [ProductController::class, 'fetchProductReviews'])->middleware('authorizeUser');
        Route::post('fetchCategories', [ProductController::class, 'fetchProductCategories'])->middleware('authorizeUser');
        Route::post('tagProducts', [ProductController::class, 'tagProducts'])->middleware('authorizeUser');
        Route::post('untagProduct', [ProductController::class, 'untagProduct'])->middleware('authorizeUser');
        Route::post('fetchPostProductTags', [ProductController::class, 'fetchPostProductTags'])->middleware('authorizeUser');
        // Marketplace enhancements
        Route::post('searchProducts', [ProductController::class, 'searchProducts'])->middleware('authorizeUser');
        Route::post('fetchFeaturedProducts', [ProductController::class, 'fetchFeaturedProducts'])->middleware('authorizeUser');
        Route::post('fetchProductTagsInReel', [ProductController::class, 'fetchProductTagsInReel'])->middleware('authorizeUser');
        Route::post('tagProductsEnhanced', [ProductController::class, 'tagProductsEnhanced'])->middleware('authorizeUser');
        Route::post('fetchSellerProducts', [ProductController::class, 'fetchSellerProducts'])->middleware('authorizeUser');
    });

    // Affiliate Program
    Route::prefix('affiliate')->group(function () {
        Route::post('fetchProducts', [AffiliateController::class, 'fetchAffiliateProducts'])->middleware('authorizeUser');
        Route::post('createLink', [AffiliateController::class, 'createAffiliateLink'])->middleware('authorizeUser');
        Route::post('removeLink', [AffiliateController::class, 'removeAffiliateLink'])->middleware('authorizeUser');
        Route::post('fetchMyLinks', [AffiliateController::class, 'fetchMyAffiliateLinks'])->middleware('authorizeUser');
        Route::post('fetchEarnings', [AffiliateController::class, 'fetchAffiliateEarnings'])->middleware('authorizeUser');
        Route::post('fetchDashboard', [AffiliateController::class, 'fetchAffiliateDashboard'])->middleware('authorizeUser');
        Route::post('trackClick', [AffiliateController::class, 'trackAffiliateClick'])->middleware('authorizeUser');
    });

    // Team / Shared Access
    Route::prefix('team')->group(function () {
        Route::post('invite', [SharedAccessController::class, 'inviteTeamMember'])->middleware('authorizeUser');
        Route::post('respond', [SharedAccessController::class, 'respondToTeamInvite'])->middleware('authorizeUser');
        Route::post('fetchMembers', [SharedAccessController::class, 'fetchMyTeamMembers'])->middleware('authorizeUser');
        Route::post('fetchManagedAccounts', [SharedAccessController::class, 'fetchManagedAccounts'])->middleware('authorizeUser');
        Route::post('fetchInvites', [SharedAccessController::class, 'fetchTeamInvites'])->middleware('authorizeUser');
        Route::post('updateMember', [SharedAccessController::class, 'updateTeamMember'])->middleware('authorizeUser');
        Route::post('removeMember', [SharedAccessController::class, 'removeTeamMember'])->middleware('authorizeUser');
        Route::post('leave', [SharedAccessController::class, 'leaveTeam'])->middleware('authorizeUser');
    });

    // Marketplace
    Route::prefix('marketplace')->group(function () {
        Route::post('createCampaign', [MarketplaceController::class, 'createCampaign'])->middleware('authorizeUser');
        Route::post('updateCampaign', [MarketplaceController::class, 'updateCampaign'])->middleware('authorizeUser');
        Route::post('deleteCampaign', [MarketplaceController::class, 'deleteCampaign'])->middleware('authorizeUser');
        Route::post('fetchCampaigns', [MarketplaceController::class, 'fetchCampaigns'])->middleware('authorizeUser');
        Route::post('fetchMyCampaigns', [MarketplaceController::class, 'fetchMyCampaigns'])->middleware('authorizeUser');
        Route::post('fetchCampaignById', [MarketplaceController::class, 'fetchCampaignById'])->middleware('authorizeUser');
        Route::post('applyToCampaign', [MarketplaceController::class, 'applyToCampaign'])->middleware('authorizeUser');
        Route::post('inviteCreator', [MarketplaceController::class, 'inviteCreator'])->middleware('authorizeUser');
        Route::post('respondToProposal', [MarketplaceController::class, 'respondToProposal'])->middleware('authorizeUser');
        Route::post('completeProposal', [MarketplaceController::class, 'completeProposal'])->middleware('authorizeUser');
        Route::post('fetchMyProposals', [MarketplaceController::class, 'fetchMyProposals'])->middleware('authorizeUser');
        Route::post('fetchCampaignProposals', [MarketplaceController::class, 'fetchCampaignProposals'])->middleware('authorizeUser');
    });

    // Parental Controls / Family Pairing
    Route::prefix('family')->group(function () {
        Route::post('generateCode', [ParentalControlController::class, 'generatePairingCode'])->middleware('authorizeUser');
        Route::post('linkWithCode', [ParentalControlController::class, 'linkWithCode'])->middleware('authorizeUser');
        Route::post('unlink', [ParentalControlController::class, 'unlinkAccount'])->middleware('authorizeUser');
        Route::post('updateControls', [ParentalControlController::class, 'updateControls'])->middleware('authorizeUser');
        Route::post('fetchLinkedAccounts', [ParentalControlController::class, 'fetchLinkedAccounts'])->middleware('authorizeUser');
        Route::post('fetchMyControls', [ParentalControlController::class, 'fetchMyControls'])->middleware('authorizeUser');
        Route::post('fetchActivityReport', [ParentalControlController::class, 'fetchActivityReport'])->middleware('authorizeUser');
    });

    // Location Reviews
    Route::prefix('locationReview')->group(function () {
        Route::post('submit', [LocationReviewController::class, 'submitReview'])->middleware('authorizeUser');
        Route::post('fetch', [LocationReviewController::class, 'fetchLocationReviews'])->middleware('authorizeUser');
        Route::post('fetchMy', [LocationReviewController::class, 'fetchMyReviews'])->middleware('authorizeUser');
        Route::post('delete', [LocationReviewController::class, 'deleteReview'])->middleware('authorizeUser');
    });

    // Friends Map
    Route::prefix('friendsMap')->group(function () {
        Route::post('updateLocation', [FriendsMapController::class, 'updateLocation'])->middleware('authorizeUser');
        Route::post('toggleSharing', [FriendsMapController::class, 'toggleSharing'])->middleware('authorizeUser');
        Route::post('fetchMyStatus', [FriendsMapController::class, 'fetchMyStatus'])->middleware('authorizeUser');
        Route::post('fetchFriendsLocations', [FriendsMapController::class, 'fetchFriendsLocations'])->middleware('authorizeUser');
    });

    // Bank Accounts
    Route::prefix('bank')->group(function () {
        Route::post('fetchBankAccounts', [BankAccountController::class, 'fetchBankAccounts'])->middleware('authorizeUser');
        Route::post('addBankAccount', [BankAccountController::class, 'addBankAccount'])->middleware('authorizeUser');
        Route::post('updateBankAccount', [BankAccountController::class, 'updateBankAccount'])->middleware('authorizeUser');
        Route::post('deleteBankAccount', [BankAccountController::class, 'deleteBankAccount'])->middleware('authorizeUser');
        Route::post('setDefaultBankAccount', [BankAccountController::class, 'setDefaultBankAccount'])->middleware('authorizeUser');
    });

    // Calls
    Route::prefix('call')->group(function () {
        Route::post('initiateCall', [CallController::class, 'initiateCall'])->middleware('authorizeUser');
        Route::post('answerCall', [CallController::class, 'answerCall'])->middleware('authorizeUser');
        Route::post('endCall', [CallController::class, 'endCall'])->middleware('authorizeUser');
        Route::post('rejectCall', [CallController::class, 'rejectCall'])->middleware('authorizeUser');
        Route::post('fetchCallHistory', [CallController::class, 'fetchCallHistory'])->middleware('authorizeUser');
        Route::post('generateLiveKitToken', [CallController::class, 'generateLiveKitToken'])->middleware('authorizeUser');
    });

    // Content Moderation
    Route::prefix('contentModeration')->group(function () {
        Route::post('check', [ContentModerationController::class, 'checkContent'])->middleware('authorizeUser');
    });

    // Templates
    Route::prefix('template')->group(function () {
        Route::post('fetchTemplates', [TemplateController::class, 'fetchTemplates'])->middleware('authorizeUser');
        Route::post('fetchTemplateById', [TemplateController::class, 'fetchTemplateById'])->middleware('authorizeUser');
        Route::post('incrementTemplateUse', [TemplateController::class, 'incrementTemplateUse'])->middleware('authorizeUser');
        Route::post('createUserTemplate', [TemplateController::class, 'createUserTemplate'])->middleware('authorizeUser');
        Route::post('fetchTrendingTemplates', [TemplateController::class, 'fetchTrendingTemplates'])->middleware('authorizeUser');
        Route::post('likeTemplate', [TemplateController::class, 'likeTemplate'])->middleware('authorizeUser');
        Route::post('fetchTemplateUsages', [TemplateController::class, 'fetchTemplateUsages'])->middleware('authorizeUser');
    });

    // Green Screen
    Route::prefix('greenScreen')->group(function () {
        Route::post('fetchBackgrounds', [GreenScreenController::class, 'fetchBackgrounds'])->middleware('authorizeUser');
    });

    // Cart & Checkout
    Route::prefix('cart')->group(function () {
        Route::post('fetch', [CartController::class, 'fetchCart'])->middleware('authorizeUser');
        Route::post('add', [CartController::class, 'addToCart'])->middleware('authorizeUser');
        Route::post('update', [CartController::class, 'updateCartItem'])->middleware('authorizeUser');
        Route::post('remove', [CartController::class, 'removeFromCart'])->middleware('authorizeUser');
        Route::post('clear', [CartController::class, 'clearCart'])->middleware('authorizeUser');
        Route::post('checkout', [CartController::class, 'checkout'])->middleware('authorizeUser');
        // Shipping Addresses
        Route::post('fetchAddresses', [CartController::class, 'fetchAddresses'])->middleware('authorizeUser');
        Route::post('addAddress', [CartController::class, 'addAddress'])->middleware('authorizeUser');
        Route::post('editAddress', [CartController::class, 'editAddress'])->middleware('authorizeUser');
        Route::post('deleteAddress', [CartController::class, 'deleteAddress'])->middleware('authorizeUser');
    });

    // Live Shopping
    Route::prefix('liveShopping')->group(function () {
        Route::post('addProduct', [LiveStreamProductController::class, 'addProductToLive'])->middleware('authorizeUser');
        Route::post('removeProduct', [LiveStreamProductController::class, 'removeProductFromLive'])->middleware('authorizeUser');
        Route::post('fetchProducts', [LiveStreamProductController::class, 'fetchLiveProducts'])->middleware('authorizeUser');
        Route::post('pinProduct', [LiveStreamProductController::class, 'pinProduct'])->middleware('authorizeUser');
        Route::post('unpinProduct', [LiveStreamProductController::class, 'unpinProduct'])->middleware('authorizeUser');
        Route::post('addToCart', [LiveStreamProductController::class, 'addToCartFromLive'])->middleware('authorizeUser');
        Route::post('salesMetrics', [LiveStreamProductController::class, 'fetchLiveSalesMetrics'])->middleware('authorizeUser');
    });

    Route::prefix('replays')->group(function () {
        Route::post('save', [LivestreamReplayController::class, 'saveReplay'])->middleware('authorizeUser');
        Route::post('fetchMine', [LivestreamReplayController::class, 'fetchMyReplays'])->middleware('authorizeUser');
        Route::post('fetchUser', [LivestreamReplayController::class, 'fetchUserReplays'])->middleware('authorizeUser');
        Route::post('delete', [LivestreamReplayController::class, 'deleteReplay'])->middleware('authorizeUser');
        Route::post('update', [LivestreamReplayController::class, 'updateReplay'])->middleware('authorizeUser');
        Route::post('view', [LivestreamReplayController::class, 'incrementViewCount'])->middleware('authorizeUser');
    });

    Route::prefix('aiSticker')->group(function () {
        Route::post('generate', [AiStickerController::class, 'generateSticker'])->middleware('authorizeUser');
        Route::post('fetchMine', [AiStickerController::class, 'fetchMyStickers'])->middleware('authorizeUser');
        Route::post('fetchPublic', [AiStickerController::class, 'fetchPublicStickers'])->middleware('authorizeUser');
        Route::post('incrementUse', [AiStickerController::class, 'incrementUseCount'])->middleware('authorizeUser');
        Route::post('delete', [AiStickerController::class, 'deleteSticker'])->middleware('authorizeUser');
    });

    Route::prefix('aiChat')->group(function () {
        Route::post('sendMessage', [AiChatController::class, 'sendMessage'])->middleware('authorizeUser');
        Route::post('fetchHistory', [AiChatController::class, 'fetchHistory'])->middleware('authorizeUser');
        Route::post('fetchSessions', [AiChatController::class, 'fetchSessions'])->middleware('authorizeUser');
        Route::post('clearHistory', [AiChatController::class, 'clearHistory'])->middleware('authorizeUser');
        Route::post('botInfo', [AiChatController::class, 'fetchBotInfo'])->middleware('authorizeUser');
    });

    Route::prefix('aiTranslation')->group(function () {
        Route::post('translateText', [AiTranslationController::class, 'translateText'])->middleware('authorizeUser');
        Route::post('translateCaptions', [AiTranslationController::class, 'translateCaptions'])->middleware('authorizeUser');
    });

    Route::prefix('aiContentIdeas')->group(function () {
        Route::post('generateIdeas', [AiContentIdeasController::class, 'generateIdeas'])->middleware('authorizeUser');
        Route::post('fetchTrendingTopics', [AiContentIdeasController::class, 'fetchTrendingTopics'])->middleware('authorizeUser');
    });

    Route::prefix('aiVideo')->group(function () {
        Route::post('generateFromText', [AiVideoController::class, 'generateFromText'])->middleware('authorizeUser');
        Route::post('generateFromImage', [AiVideoController::class, 'generateFromImage'])->middleware('authorizeUser');
    });

    Route::prefix('aiVoice')->group(function () {
        Route::post('enhanceAudio', [AiVoiceController::class, 'enhanceAudio'])->middleware('authorizeUser');
        Route::post('enhanceVideo', [AiVoiceController::class, 'enhanceVideo'])->middleware('authorizeUser');
        Route::post('transcribeAudio', [AiVoiceController::class, 'transcribeAudio'])->middleware('authorizeUser');
    });

    // Portfolio
    Route::prefix('portfolio')->group(function () {
        Route::post('createOrUpdate', [PortfolioController::class, 'createOrUpdate'])->middleware('authorizeUser');
        Route::post('fetchMine', [PortfolioController::class, 'fetchMine'])->middleware('authorizeUser');
        Route::post('addSection', [PortfolioController::class, 'addSection'])->middleware('authorizeUser');
        Route::post('updateSection', [PortfolioController::class, 'updateSection'])->middleware('authorizeUser');
        Route::post('removeSection', [PortfolioController::class, 'removeSection'])->middleware('authorizeUser');
        Route::post('reorderSections', [PortfolioController::class, 'reorderSections'])->middleware('authorizeUser');
    });

    // Seller Applications (KYC)
    Route::prefix('seller')->group(function () {
        Route::post('submitApplication', [SellerApplicationController::class, 'submitApplication'])->middleware('authorizeUser');
        Route::post('fetchMyApplication', [SellerApplicationController::class, 'fetchMyApplication'])->middleware('authorizeUser');
        Route::post('updateBankDetails', [SellerApplicationController::class, 'updateBankDetails'])->middleware('authorizeUser');
        Route::post('updateBusinessAddress', [SellerApplicationController::class, 'updateBusinessAddress'])->middleware('authorizeUser');
    });

    // Affiliate Applications
    Route::prefix('affiliateApplication')->group(function () {
        Route::post('submit', [AffiliateApplicationController::class, 'submitApplication'])->middleware('authorizeUser');
        Route::post('fetchMine', [AffiliateApplicationController::class, 'fetchMyApplication'])->middleware('authorizeUser');
    });

    // Product Shoot Requests
    Route::prefix('shootRequest')->group(function () {
        Route::post('create', [ShootRequestController::class, 'createRequest'])->middleware('authorizeUser');
        Route::post('respond', [ShootRequestController::class, 'respondToRequest'])->middleware('authorizeUser');
        Route::post('sendMessage', [ShootRequestController::class, 'sendMessage'])->middleware('authorizeUser');
        Route::post('fetchMessages', [ShootRequestController::class, 'fetchMessages'])->middleware('authorizeUser');
        Route::post('fetchMyRequests', [ShootRequestController::class, 'fetchMyRequests'])->middleware('authorizeUser');
        Route::post('updateStatus', [ShootRequestController::class, 'updateRequestStatus'])->middleware('authorizeUser');
    });

    // Payment & Checkout (Real Money)
    Route::prefix('payment')->group(function () {
        Route::post('checkoutSummary', [PaymentController::class, 'getCheckoutSummary'])->middleware('authorizeUser');
        Route::post('initiateCheckout', [PaymentController::class, 'initiateCheckout'])->middleware('authorizeUser');
        Route::post('verify', [PaymentController::class, 'verifyPayment'])->middleware('authorizeUser');
        Route::post('gateways', [PaymentController::class, 'getPaymentGateways'])->middleware('authorizeUser');
        Route::post('sellerEarnings', [PaymentController::class, 'sellerEarnings'])->middleware('authorizeUser');
        Route::post('trackOrder', [PaymentController::class, 'trackOrder'])->middleware('authorizeUser');
        Route::post('shipOrder', [PaymentController::class, 'shipOrder'])->middleware('authorizeUser');
        Route::post('markDelivered', [PaymentController::class, 'markDelivered'])->middleware('authorizeUser');
        Route::post('cancelOrder', [PaymentController::class, 'cancelOrder'])->middleware('authorizeUser');
    });

    // Returns & Refunds
    Route::prefix('returns')->group(function () {
        Route::post('request', [ReturnController::class, 'requestReturn'])->middleware('authorizeUser');
        Route::post('respond', [ReturnController::class, 'respondToReturn'])->middleware('authorizeUser');
        Route::post('fetch', [ReturnController::class, 'fetchReturns'])->middleware('authorizeUser');
        Route::post('inspect', [ReturnController::class, 'inspectReturn'])->middleware('authorizeUser');
    });
});

// Compliance — Grievance & Appeal
Route::middleware('checkHeader')->group(function () {
    Route::prefix('grievance')->group(function () {
        Route::post('submit', [GrievanceController::class, 'submitGrievance'])->middleware('authorizeUser');
        Route::post('list', [GrievanceController::class, 'myGrievances'])->middleware('authorizeUser');
        Route::post('detail', [GrievanceController::class, 'getGrievance'])->middleware('authorizeUser');
        Route::post('respond', [GrievanceController::class, 'addResponse'])->middleware('authorizeUser');
        Route::post('gro-info', [GrievanceController::class, 'getGROInfo']);
    });

    Route::prefix('appeal')->group(function () {
        Route::post('submit', [AppealController::class, 'submitAppeal'])->middleware('authorizeUser');
        Route::post('list', [AppealController::class, 'myAppeals'])->middleware('authorizeUser');
        Route::post('detail', [AppealController::class, 'getAppeal'])->middleware('authorizeUser');
    });
});

// Payment Gateway Webhooks (no auth — verified by signature)
Route::post('payment/razorpay/webhook', [PaymentController::class, 'razorpayWebhook']);
Route::post('payment/phonepe/callback', [PaymentController::class, 'phonepeCallback']);
Route::post('payment/cashfree/callback', [PaymentController::class, 'cashfreeCallback']);

// VAST Ad Proxy (public endpoint — no auth required)
Route::get('vast/fetch', [VastController::class, 'fetch']);

// Misc
Route::prefix('cron')->group(function () {
    Route::get('reGeneratePlaceApiToken', [CronsController::class, 'reGeneratePlaceApiToken']);
    Route::get('deleteExpiredStories', [CronsController::class, 'deleteExpiredStories']);
    Route::get('deleteOldNotifications', [CronsController::class, 'deleteOldNotifications']);
    Route::get('countDailyActiveUsers', [CronsController::class, 'countDailyActiveUsers']);
    Route::get('renewSubscriptions', [CreatorSubscriptionController::class, 'renewExpiredSubscriptions']);

    // Don't use below function on your live environment, This will delete data from the platform. For Author only
    Route::get('cleanDemoAppData', [CronsController::class, 'cleanDemoAppData']);
});

