<?php

namespace App\Models;

final class Constants
{

    const isDeletedNo = 0;
    const isDeletedYes = 1;

    const userTypeAdmin = 1;
    const userTypeUser = 0;

    const isFreeze = 1;
    const isFreezeNot = 0;

    const android = 0;
    const iOS = 1;

    const pushTypeTopic = 'topic';
    const pushTypeToken = 'token';

    const isNotifyYes = 1;

    const credit = 1;
    const debit = 0;

    const withdrawalPending = 0;
    const withdrawalCompleted = 1;
    const withdrawalRejected = 2;

    const postTypeReel = 1;
    const postTypeImage = 2;
    const postTypeVideo = 3;
    const postTypeText = 4;

    // Post visibility levels
    const postVisibilityPublic = 0;
    const postVisibilityFollowers = 1;
    const postVisibilityOnlyMe = 2;

    const storyTypeImage = 0;
    const storyTypeVideo = 1;

    // Story visibility levels
    const storyVisibilityPublic = 0;
    const storyVisibilityCloseFriends = 1;
    const storyVisibilitySubscribers = 2;

    const commentTypeText = 0;
    const commentTypeImage = 1;

    const notify_like_post = 1;
    const notify_comment_post = 2;
    const notify_mention_post = 3;
    const notify_mention_comment = 4;
    const notify_follow_user = 5;
    const notify_gift_user = 6;
    const notify_reply_comment = 7;
    const notify_mention_reply = 8;

    const userDummy = 1;
    const userReal = 0;

    // Account types (all stored in tbl_users.account_type)
    const accountTypePersonal = 0;
    const accountTypeInfluencer = 1;
    const accountTypeBusiness = 2;
    const accountTypeProductionHouse = 3;
    const accountTypeNewsMedia = 4;

    // Business/monetization status
    const businessStatusNotApplied = 0;
    const businessStatusPending = 1;
    const businessStatusApproved = 2;
    const businessStatusRejected = 3;

    // Follow request status
    const followRequestPending = 0;
    const followRequestAccepted = 1;
    const followRequestRejected = 2;

    // Notification types (additional)
    const notify_follow_request = 9;
    const notify_monetization_status = 10;

    // Transaction types
    const txnGiftReceived = 1;
    const txnGiftSent = 2;
    const txnPurchase = 3;
    const txnWithdrawal = 4;
    const txnAdReward = 5;
    const txnAdminCredit = 6;
    const txnRegistrationBonus = 7;
    const txnTipReceived = 8;
    const txnTipSent = 9;
    const txnSubscriptionReceived = 10;
    const txnSubscriptionSent = 11;
    const txnPaidSeriesPurchase = 12;
    const txnPaidSeriesRevenue = 13;
    const txnAdRevenue = 14;
    const txnProductPurchase = 15;
    const txnProductRevenue = 16;
    const txnMarketplacePayout = 17;
    const txnMarketplaceEarning = 18;
    const txnAffiliateEarning = 19;

    // Creator tiers
    const tierNone = 0;
    const tierBronze = 1;
    const tierSilver = 2;
    const tierGold = 3;
    const tierPlatinum = 4;

    const tierLabels = [
        self::tierNone => 'None',
        self::tierBronze => 'Bronze',
        self::tierSilver => 'Silver',
        self::tierGold => 'Gold',
        self::tierPlatinum => 'Platinum',
    ];

    // Notification type for tip
    const notify_tip_received = 11;

    // Notification type for repost
    const notify_repost = 12;

    // Notification type for new subscriber
    const notify_new_subscriber = 13;

    // Notification types for collaboration
    const notify_collab_invite = 14;
    const notify_collab_accepted = 15;

    // Notification types for team/shared access
    const notify_team_invite = 16;
    const notify_team_accepted = 17;

    // Notification type for new exclusive content
    const notify_new_exclusive_content = 19;

    // Notification type for creator liked comment
    const notify_creator_liked_comment = 20;

    // Notification types for calls
    const notify_incoming_call = 18;

    // Call types
    const callTypeVoice = 1;
    const callTypeVideo = 2;

    // Call status
    const callStatusRinging = 0;
    const callStatusAnswered = 1;
    const callStatusEnded = 2;
    const callStatusMissed = 3;
    const callStatusRejected = 4;

    // Post visibility: subscriber-only
    const postVisibilitySubscribers = 3;

    // Post status (stored in tbl_post.post_status)
    const postStatusPublished = 1;
    const postStatusScheduled = 2;
    const postStatusFailed = 3;

    // Content types (stored in tbl_post.content_type)
    const contentTypeNormal = 0;
    const contentTypeMusicVideo = 1;
    const contentTypeTrailer = 2;
    const contentTypeNews = 3;
    const contentTypeShortStory = 4;

    // Content type to allowed account types mapping
    const contentTypeAccountMap = [
        self::contentTypeMusicVideo => [self::accountTypeProductionHouse],
        self::contentTypeTrailer => [self::accountTypeProductionHouse],
        self::contentTypeNews => [self::accountTypeNewsMedia],
        self::contentTypeShortStory => [self::accountTypeProductionHouse, self::accountTypeNewsMedia],
    ];

    // Content type labels
    const contentTypeLabels = [
        self::contentTypeNormal => 'Normal',
        self::contentTypeMusicVideo => 'Music Video',
        self::contentTypeTrailer => 'Trailer',
        self::contentTypeNews => 'News',
        self::contentTypeShortStory => 'Short Story',
    ];

    // Challenge constants
    const notify_challenge_entry = 21;
    const notify_challenge_winner = 22;
    const notify_poll_results = 23;

    const challengeTypeCommunity = 0;
    const challengeTypeBrand = 1;

    const challengeStatusActive = 1;
    const challengeStatusEnded = 2;
    const challengeStatusJudging = 3;
    const challengeStatusCompleted = 4;

    const coinTransactionChallengeReward = 20;

    const userPublicFields= 'id,username,fullname,bio,profile_photo,is_verify,device,device_token,app_language,notify_post_like,notify_post_comment,notify_follow,notify_mention,notify_gift_received,notify_chat,coin_collected_lifetime,total_post_likes_count,following_count,follower_count,receive_message,account_type,is_private,profile_category_id,profile_sub_category_id';

    const postsWithArray = ['images','music','user.links','user.stories','user:'.Constants::userPublicFields,'duetSource:id,user_id,video,thumbnail,description','duetSource.user:'.Constants::userPublicFields,'stitchSource:id,user_id,video,thumbnail,description','stitchSource.user:'.Constants::userPublicFields,'collaborators.user:'.Constants::userPublicFields,'productTags.product:id,name,price_coins,images,seller_id','productTags.product.seller:'.Constants::userPublicFields];

}
