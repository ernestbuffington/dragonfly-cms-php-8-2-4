<?php

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromAssignsRector;

//use Rector\Php53\Rector\Variable\ReplaceHttpServerVarsByServerRector;

//use Rector\Php54\Rector\Array_\LongArrayToShortArrayRector; # I ONLY DO THIS FOR CERTAIN FILES #

use Rector\Php55\Rector\FuncCall\PregReplaceEModifierRector;

use Rector\Php56\Rector\FunctionLike\AddDefaultValueForUndefinedVariableRector;

use Rector\Php70\Rector\FuncCall\EregToPregMatchRector;
use Rector\Php70\Rector\List_\EmptyListRector;
use Rector\Php70\Rector\FunctionLike\ExceptionHandlerTypehintRector;
use Rector\Php70\Rector\If_\IfToSpaceshipRector;
use Rector\Php70\Rector\Assign\ListSplitStringRector;
use Rector\Php70\Rector\ClassMethod\Php4ConstructorRector;
use Rector\Php70\Rector\FuncCall\RandomFunctionRector;
use Rector\Php70\Rector\FuncCall\RenameMktimeWithoutArgsToTimeRector;
use Rector\Php70\Rector\Ternary\TernaryToNullCoalescingRector;
use Rector\Php70\Rector\Ternary\TernaryToSpaceshipRector;
use Rector\Php70\Rector\Variable\WrapVariableVariableNameInCurlyBracesRector;

use Rector\Php71\Rector\BinaryOp\BinaryOpBetweenNumberAndStringRector;
use Rector\Php71\Rector\FuncCall\CountOnNullRector;
use Rector\Php71\Rector\BooleanOr\IsIterableRector;
//use Rector\Php71\Rector\List_\ListToArrayDestructRector;
use Rector\Php71\Rector\TryCatch\MultiExceptionCatchRector;
use Rector\Php71\Rector\ClassConst\PublicConstantVisibilityRector;

use Rector\Php72\Rector\FuncCall\CreateFunctionToAnonymousFunctionRector;
use Rector\Php72\Rector\FuncCall\GetClassOnNullRector;
use Rector\Php72\Rector\Assign\ListEachRector;
use Rector\Php72\Rector\Assign\ReplaceEachAssignmentWithKeyCurrentRector;
use Rector\Php72\Rector\FuncCall\StringsAssertNakedRector;
use Rector\Php72\Rector\Unset_\UnsetCastRector;
use Rector\Php72\Rector\While_\WhileEachToForeachRector;

use Rector\Php73\Rector\FuncCall\ArrayKeyFirstLastRector;
use Rector\Php73\Rector\BooleanOr\IsCountableRector;
use Rector\Php73\Rector\FuncCall\JsonThrowOnErrorRector;
use Rector\Php73\Rector\FuncCall\RegexDashEscapeRector;
use Rector\Php73\Rector\FuncCall\SetCookieRector;
use Rector\Php73\Rector\FuncCall\StringifyStrNeedlesRector;

use Rector\Php74\Rector\FuncCall\ArrayKeyExistsOnPropertyRector;
use Rector\Php74\Rector\FuncCall\ArraySpreadInsteadOfArrayMergeRector;
use Rector\Php74\Rector\MethodCall\ChangeReflectionTypeToStringToGetNameRector;
use Rector\Php74\Rector\Closure\ClosureToArrowFunctionRector;
use Rector\Php74\Rector\ArrayDimFetch\CurlyToSquareBracketArrayStringRector;
use Rector\Php74\Rector\StaticCall\ExportToReflectionFunctionRector;
use Rector\Php74\Rector\FuncCall\FilterVarToAddSlashesRector;
use Rector\Php74\Rector\FuncCall\MbStrrposEncodingArgumentPositionRector;
use Rector\Php74\Rector\FuncCall\MoneyFormatToNumberFormatRector;
use Rector\Php74\Rector\Assign\NullCoalescingOperatorRector;
use Rector\Php74\Rector\Ternary\ParenthesizeNestedTernaryRector;
use Rector\Php74\Rector\Double\RealToFloatTypeCastRector;
use Rector\Php74\Rector\Property\RestoreDefaultNullToNullableTypePropertyRector;

use Rector\Php80\Rector\Identical\StrStartsWithRector;

//use Rector\Php81\Rector\FuncCall\NullToStrictStringFuncCallArgRector; # I ONLY DO THIS FOR CERTAIN FILES #

use Rector\Php82\Rector\FuncCall\Utf8DecodeEncodeToMbConvertEncodingRector;

use Rector\MysqlToMysqli\Rector\Assign\MysqlAssignToMysqliRector;
use Rector\MysqlToMysqli\Rector\FuncCall\MysqlFuncCallToMysqliRector;
use Rector\MysqlToMysqli\Rector\FuncCall\MysqlPConnectToMysqliConnectRector;
use Rector\MysqlToMysqli\Rector\FuncCall\MysqlQueryMysqlErrorWithLinkRector;

return static function (RectorConfig $rectorConfig): void {
	$rectorConfig->cacheDirectory('/home/dragonfly/public_html/garbage/rector_cached_files');
    $rectorConfig->containerCacheDirectory('/home/dragonfly/public_html/garbage');
	
	// A. run whole set
    //$rectorConfig->sets([
	//	SetList::PHP_82,
	//	LevelSetList::UP_TO_PHP_82,
    //]);

    // B. or single rule
    $rectorConfig->rule(TypedPropertyFromAssignsRector::class);

    // 53
	//$rectorConfig->rule(ReplaceHttpServerVarsByServerRector::class);
	
	//54
	//$rectorConfig->rule(LongArrayToShortArrayRector::class); # I ONLY DO THIS FOR CERTAIN FILES #

	// 55
	$rectorConfig->rule(PregReplaceEModifierRector::class);

	// 56
	$rectorConfig->rule(AddDefaultValueForUndefinedVariableRector::class);

    // 70
	$rectorConfig->rule(EmptyListRector::class);
    $rectorConfig->rule(EregToPregMatchRector::class);
	$rectorConfig->rule(ExceptionHandlerTypehintRector::class);
	$rectorConfig->rule(IfToSpaceshipRector::class);
	$rectorConfig->rule(ListSplitStringRector::class);
	$rectorConfig->rule(Php4ConstructorRector::class);
	$rectorConfig->rule(RandomFunctionRector::class);
	$rectorConfig->rule(RenameMktimeWithoutArgsToTimeRector::class);
	$rectorConfig->rule(TernaryToNullCoalescingRector::class);
	$rectorConfig->rule(TernaryToSpaceshipRector::class);
	$rectorConfig->rule(WrapVariableVariableNameInCurlyBracesRector::class);
	
	// 71
	$rectorConfig->rule(BinaryOpBetweenNumberAndStringRector::class);
	$rectorConfig->rule(CountOnNullRector::class);
	$rectorConfig->rule(IsIterableRector::class);
	//$rectorConfig->rule(ListToArrayDestructRector::class);
	$rectorConfig->rule(MultiExceptionCatchRector::class);
	$rectorConfig->rule(PublicConstantVisibilityRector::class);
	
	// 72
	$rectorConfig->rule(CreateFunctionToAnonymousFunctionRector::class);
	$rectorConfig->rule(GetClassOnNullRector::class);
	$rectorConfig->rule(ListEachRector::class);
	$rectorConfig->rule(ReplaceEachAssignmentWithKeyCurrentRector::class);
	$rectorConfig->rule(StringsAssertNakedRector::class);
	$rectorConfig->rule(UnsetCastRector::class);
	$rectorConfig->rule(WhileEachToForeachRector::class);
	
	// 73
	$rectorConfig->rule(ArrayKeyFirstLastRector::class);
	$rectorConfig->rule(IsCountableRector::class);
	$rectorConfig->rule(JsonThrowOnErrorRector::class);
	$rectorConfig->rule(RegexDashEscapeRector::class);
	$rectorConfig->rule(SetCookieRector::class);
	$rectorConfig->rule(StringifyStrNeedlesRector::class);
	
	// 74
	$rectorConfig->rule(ArrayKeyExistsOnPropertyRector::class);
	$rectorConfig->rule(ArraySpreadInsteadOfArrayMergeRector::class);
	$rectorConfig->rule(ChangeReflectionTypeToStringToGetNameRector::class);
	$rectorConfig->rule(ClosureToArrowFunctionRector::class);
	$rectorConfig->rule(CurlyToSquareBracketArrayStringRector::class);
	$rectorConfig->rule(ExportToReflectionFunctionRector::class);
	$rectorConfig->rule(FilterVarToAddSlashesRector::class);
	$rectorConfig->rule(MbStrrposEncodingArgumentPositionRector::class);
	$rectorConfig->rule(MoneyFormatToNumberFormatRector::class);
	$rectorConfig->rule(NullCoalescingOperatorRector::class);
	$rectorConfig->rule(ParenthesizeNestedTernaryRector::class);
	$rectorConfig->rule(RealToFloatTypeCastRector::class);
	$rectorConfig->rule(RestoreDefaultNullToNullableTypePropertyRector::class);
	
	// 80 
	$rectorConfig->rule(StrStartsWithRector::class);
	//$rectorConfig->rule(::class);

	// 81
	//$rectorConfig->rule(NullToStrictStringFuncCallArgRector::class);
	
	// 82
	$rectorConfig->rule(Utf8DecodeEncodeToMbConvertEncodingRector::class);
	
	$rectorConfig->rule(MysqlAssignToMysqliRector::class);
	$rectorConfig->rule(MysqlFuncCallToMysqliRector::class);
	$rectorConfig->rule(MysqlPConnectToMysqliConnectRector::class);
	$rectorConfig->rule(MysqlQueryMysqlErrorWithLinkRector::class);
		
    $rectorConfig->paths([
		//////__DIR__ . '/admin/links/adlnk_main.php',
		//////__DIR__ . '/admin/modules/admins.php',
		//////__DIR__ . '/admin/modules/auth.php',
		//////__DIR__ . '/admin/modules/avatars.php',
		//////__DIR__ . '/admin/modules/blocks.php',
		//////__DIR__ . '/admin/modules/cache.php',
		//////__DIR__ . '/admin/modules/cpgmm.php',
		//////__DIR__ . '/admin/modules/database.php',
		//////__DIR__ . '/admin/modules/headlines.php',
		//////__DIR__ . '/admin/modules/index.php',
		//////__DIR__ . '/admin/modules/info.php',
		//////__DIR__ . '/admin/modules/l10n.php',
		//////__DIR__ . '/admin/modules/log.php',
		//////__DIR__ . '/admin/modules/messages.php',
		//////__DIR__ . '/admin/modules/modules.php',
		//////__DIR__ . '/admin/modules/newsletter.php',
		//////__DIR__ . '/admin/modules/packagemanager.php',
		//////__DIR__ . '/admin/modules/ranks.php',
		//////__DIR__ . '/admin/modules/referers.php',
		//////__DIR__ . '/admin/modules/security.php',
		//////__DIR__ . '/admin/modules/settings.php',
		//////__DIR__ . '/admin/modules/smilies.php',
		//////__DIR__ . '/admin/modules/social.php',
		//////__DIR__ . '/admin/modules/users.php',
		//////__DIR__ . '/admin/modules/users_cfg.php',
		//////__DIR__ . '/admin/modules/users_wait.inc',
		//////__DIR__ . '/blocks/block-CPG_Main_Menu.php',
		//////__DIR__ . '/blocks/block-Languages.php',
		//////__DIR__ . '/blocks/block-Newsletter.php',
		//////__DIR__ . '/blocks/block-Preview_theme.php',
		//////__DIR__ . '/blocks/block-Who_where.php',
		//////__DIR__ . '/includes/classes/sqlctrl/mysqli.php',
		//////__DIR__ . '/includes/classes/sqlctrl/pgsql.php',
		//////__DIR__ . '/includes/classes/archive.php',
		//////__DIR__ . '/includes/classes/cache.php',
		//////__DIR__ . '/includes/classes/client.php',
		//////__DIR__ . '/includes/classes/coppermine.php',
		//////__DIR__ . '/includes/classes/cpg_files.php',
		//////__DIR__ . '/includes/classes/cpg_ftp.php',
		//////__DIR__ . '/includes/classes/cpg_ftpfake.php',
		//////__DIR__ . '/includes/classes/cpg_member.php',
		//////__DIR__ . '/includes/classes/filter.php',
		//////__DIR__ . '/includes/classes/l10ntime.php',
		//////__DIR__ . '/includes/classes/security.php',
		//////__DIR__ . '/includes/classes/sqlctrl.php',
		//////__DIR__ . '/includes/classes/synfeed.php',
		//////__DIR__ . '/includes/classes/url.php',
		//////__DIR__ . '/iinstall/step1.php',
		//////__DIR__ . '/iinstall/step2.php',
		//////__DIR__ . '/iinstall/step3.php',
		//////__DIR__ . '/iinstall/step4.php',
		//////__DIR__ . '/iinstall/step5.php',
		//////__DIR__ . '/modules/contact/index.php',
		//////__DIR__ . '/modules/coppermine/.php',
		//////__DIR__ . '/modules/coppermine/admin/adlinks.inc',
		//////__DIR__ . '/modules/coppermine/admin/adwait.inc',
		//////__DIR__ . '/modules/coppermine/admin/categories.php',
		//////__DIR__ . '/modules/coppermine/admin/groups.php',
		//////__DIR__ . '/modules/coppermine/admin/index.inc',
		//////__DIR__ . '/modules/coppermine/admin/reviewcom.php',
		//////__DIR__ . '/modules/coppermine/admin/searchnew.php',
		//////__DIR__ . '/modules/coppermine/admin/users.php',
		//////__DIR__ . '/modules/coppermine/blocks/center-last_pictures_thumb.php',
		//////__DIR__ . '/modules/coppermine/blocks/center-random_pictures.php',
		//////__DIR__ . '/modules/coppermine/blocks/center-scroll-last_pictures.php',
		//////__DIR__ . '/modules/coppermine/blocks/center-scroll-last_pictures_thumb.php',
		//////__DIR__ . '/modules/coppermine/blocks/center-scroll-random_pictures.php',
		//////__DIR__ . '/modules/coppermine/blocks/center-scroll-top_rate_pictures.php',
		//////__DIR__ . '/modules/coppermine/blocks/center-scroll-top_view_pictures.php',
		//////__DIR__ . '/modules/coppermine/blocks/center-top_rate_pictures.php',
		//////__DIR__ . '/modules/coppermine/blocks/last_comments.php',
		//////__DIR__ . '/modules/coppermine/blocks/last_pictures_thumb.php',
		//////__DIR__ . '/modules/coppermine/blocks/random_pictures.php',
		//////__DIR__ . '/modules/coppermine/blocks/scroll-last_comments.php',
		//////__DIR__ . '/modules/coppermine/blocks/scroll-last_pictures_thumb.php',
		//////__DIR__ . '/modules/coppermine/blocks/scroll-least_view_pictures.php',
		//////__DIR__ . '/modules/coppermine/blocks/scroll-random_pictures.php',
		//////__DIR__ . '/modules/coppermine/blocks/scroll-top_rate_pictures.php',
		//////__DIR__ . '/modules/coppermine/blocks/scroll-top_view_pictures.php',
		//////__DIR__ . '/modules/coppermine/blocks/stats.php',
		//////__DIR__ . '/modules/coppermine/blocks/top_rate_pictures.php',
		//////__DIR__ . '/modules/coppermine/include/help-english.inc',
		//////__DIR__ . '/modules/coppermine/include/load.inc',
		//////__DIR__ . '/modules/coppermine/install/cpg_inst.php',
		//////__DIR__ . '/modules/coppermine/addpic.php',
		//////__DIR__ . '/modules/coppermine/albmgr.php',
		//////__DIR__ . '/modules/coppermine/db_input.php',
		//////__DIR__ . '/modules/coppermine/delete.php',
		//////__DIR__ . '/modules/coppermine/displayimage.php',
		//////__DIR__ . '/modules/coppermine/displayimagepopup.php',
		//////__DIR__ . '/modules/coppermine/ecard.php',
		//////__DIR__ . '/modules/coppermine/editOnePic.php',
		//////__DIR__ . '/modules/coppermine/editpics.php',
		//////__DIR__ . '/modules/coppermine/help.php',
		//////__DIR__ . '/modules/coppermine/index.php',
		//////__DIR__ . '/modules/coppermine/profile.php',
		//////__DIR__ . '/modules/coppermine/ratepic.php',
		//////__DIR__ . '/modules/coppermine/search.inc',
		//////__DIR__ . '/modules/coppermine/search.php',
		//////__DIR__ . '/modules/coppermine/thumbnails.php',
		//////__DIR__ . '/modules/coppermine/upload.php',
		//////__DIR__ . '/modules/coppermine/users.php',
		//////__DIR__ . '/modules/Forums/admin/adlinks.inc',
		//////__DIR__ . '/modules/Forums/admin/attach_cp.php',
		//////__DIR__ . '/modules/Forums/admin/attachments.php',
		//////__DIR__ . '/modules/Forums/admin/config.php',
		//////__DIR__ . '/modules/Forums/admin/extensions.php',
		//////__DIR__ . '/modules/Forums/admin/forum_archive.php',
		//////__DIR__ . '/modules/Forums/admin/forum_prune.php',
		//////__DIR__ . '/modules/Forums/admin/forumauth.php',
		//////__DIR__ . '/modules/Forums/admin/forums.php',
		//////__DIR__ . '/modules/Forums/admin/index.inc',
		//////__DIR__ . '/modules/Forums/admin/topic_icons.php',
		//////__DIR__ . '/modules/Forums/admin/ug_auth.php',
		//////__DIR__ . '/modules/Forums/admin/user_forums.php',
		//////__DIR__ . '/modules/Forums/admin/words.php',
		//////__DIR__ . '/modules/Forums/blocks/recent_topics.php',
        //////__DIR__ . '/modules/Forums/blocks/scroll_last_posts.php',
		//////__DIR__ . '/modules/Forums/blocks/stats.php',
		//////__DIR__ . '/modules/Forums/classes/BoardCache.php',
		//////__DIR__ . '/modules/Forums/install/cpg_inst.php',
		//////__DIR__ . '/modules/Forums/v9/archives.php',
		//////__DIR__ . '/modules/Forums/v9/index.php',
		//////__DIR__ . '/modules/Forums/v9/viewarchive.php',
		//////__DIR__ . '/modules/Forums/v9/viewforum.php',
		//////__DIR__ . '/modules/Forums/v9/viewtopic.php',
		//////__DIR__ . '/modules/Forums/archives.php',
		//////__DIR__ . '/modules/Forums/attach_rules.php',
		//////__DIR__ . '/modules/Forums/common.php',
		//////__DIR__ . '/modules/Forums/download.php',
		//////__DIR__ . '/modules/Forums/faq.php',
		//////__DIR__ . '/modules/Forums/feed_rss.inc',
		//////__DIR__ . '/modules/Forums/index.php',
		//////__DIR__ . '/modules/Forums/merge.php',
		//////__DIR__ . '/modules/Forums/modcp.php',
		//////__DIR__ . '/modules/Forums/moderators.php',
		//////__DIR__ . '/modules/Forums/posting.php',
		//////__DIR__ . '/modules/Forums/reputation.php',
		//////__DIR__ . '/modules/Forums/search.php',
		//////__DIR__ . '/modules/Forums/uacp.php',
		//////__DIR__ . '/modules/Forums/userinfo.php',
		//////__DIR__ . '/modules/Forums/viewarchive.php',
		//////__DIR__ . '/modules/Forums/viewforum.php',
		//////__DIR__ . '/modules/Forums/viewtopic.php',
		//////__DIR__ . '/modules/Groups/admin/index.inc',
		//////__DIR__ . '/modules/Groups/blocks/groups.php',
		//////__DIR__ . '/modules/Groups/install/cpg_inst.php',
		//////__DIR__ . '/modules/Groups/index.php',
		//////__DIR__ . '/modules/login/index.php',
		//////__DIR__ . '/modules/News/admin/adlinks.inc',
		//////__DIR__ . '/modules/News/admin/adwait.inc',
		//////__DIR__ . '/modules/News/admin/categories.inc',
		//////__DIR__ . '/modules/News/admin/config.inc',
		//////__DIR__ . '/modules/News/admin/index.inc',
		//////__DIR__ . '/modules/News/admin/submissions.inc',
		//////__DIR__ . '/modules/News/admin/topics.inc',
		//////__DIR__ . '/modules/News/blocks/big_story_of_today.php',
		//////__DIR__ . '/modules/News/blocks/categories.php',
		//////__DIR__ . '/modules/News/blocks/last_5_articles.php',
		//////__DIR__ . '/modules/News/blocks/old_articles.php',
		//////__DIR__ . '/modules/News/blocks/random_headlines.php',
		//////__DIR__ . '/modules/News/install/cpg_inst.php',
		//////__DIR__ . '/modules/News/archive.php',
		//////__DIR__ . '/modules/News/article.php',
		//////__DIR__ . '/modules/News/comment.php',
		//////__DIR__ . '/modules/News/comments.php',
		//////__DIR__ . '/modules/News/feed_rss.inc',
		//////__DIR__ . '/modules/News/index.php',
		//////__DIR__ . '/modules/News/search.php',
		//////__DIR__ . '/modules/News/story.php',
		//////__DIR__ . '/modules/News/submit.php',
		//////__DIR__ . '/modules/Our_Sponsors/admin/adlinks.inc',
		//////__DIR__ . '/modules/Our_Sponsors/admin/adwait.php',
		//////__DIR__ . '/modules/Our_Sponsors/admin/index.php',
		//////__DIR__ . '/modules/Our_Sponsors/blocks/advertising.php',
		//////__DIR__ . '/modules/Our_Sponsors/install/cpg_inst.php',
		//////__DIR__ . '/modules/Our_Sponsors/banner.php',
		//////__DIR__ . '/modules/Our_Sponsors/index.php',
		//////__DIR__ . '/modules/Private_Messages/admin/adlinks.inc',
		//////__DIR__ . '/modules/Private_Messages/admin/index.inc',
		//////__DIR__ . '/modules/Private_Messages/install/cpg_inst.php',
		//////__DIR__ . '/modules/Private_Messages/compose.php',
		//////__DIR__ . '/modules/Private_Messages/delete.php',
		//////__DIR__ . '/modules/Private_Messages/index.php',
		//////__DIR__ . '/modules/Private_Messages/init.inc',
		//////__DIR__ . '/modules/Private_Messages/message.php',
		//////__DIR__ . '/modules/Private_Messages/read.php',
		//////__DIR__ . '/modules/Private_Messages/save.php',
		//////__DIR__ . '/modules/Search/blocks/search.php',
		//////__DIR__ . '/modules/Search/index.php',
		//////__DIR__ . '/modules/Statistics/blocks/total_hits.php',
		//////__DIR__ . '/modules/Statistics/install/cpg_inst.php',
		//////__DIR__ . '/modules/Statistics/counter.php',
		//////__DIR__ . '/modules/Statistics/index.php',
		//////__DIR__ . '/modules/Stories_Archive/index.php',
		//////__DIR__ . '/modules/Surveys/admin/index.inc',
		//////__DIR__ . '/modules/Surveys/blocks/survey.php',
		//////__DIR__ . '/modules/Surveys/comment.php',
		//////__DIR__ . '/modules/Surveys/comments.php',
		//////__DIR__ . '/modules/Surveys/index.php',
		//////__DIR__ . '/modules/Surveys/poll.php',
		//////__DIR__ . '/modules/Tell_a_Friend/index.php',
		//////__DIR__ . '/modules/Top/index.php',
		//////__DIR__ . '/modules/Topics/index.php',
		__DIR__ . '/modules/Your_Account/blocks/User_Info.php',
		//__DIR__ . '/modules/Your_Account/blocks/User_Info_small.php',
		//__DIR__ . '/modules/Your_Account/blocks/userbox.php',
		//__DIR__ . '/modules/Your_Account/install/cpg_inst.php',
		//__DIR__ . '/modules/Your_Account/profile_blocks/comments.php',
		//__DIR__ . '/modules/Your_Account/profile_blocks/groups.php',
		//__DIR__ . '/modules/Your_Account/profile_blocks/news.php',
		//__DIR__ . '/modules/Your_Account/avatars.php',
		//__DIR__ . '/modules/Your_Account/edit_profile.php',
		//__DIR__ . '/modules/Your_Account/functions.php',
		//__DIR__ . '/modules/Your_Account/index.php',
		//__DIR__ . '/modules/Your_Account/register.php',
		//__DIR__ . '/modules/Your_Account/search.php',
		//__DIR__ . '/modules/Your_Account/uploads.php',
		//__DIR__ . '/modules/Your_Account/userinfo.php',
		//__DIR__ . '/rss/forums.php',
		//__DIR__ . '/rss/news.php',
		//__DIR__ . '/rss/news2.php',
		//__DIR__ . '/themes/default/bbcode.inc',
		//__DIR__ . '/themes/default/theme.php',
		//__DIR__ . '/banners.php',
		//__DIR__ . '/cpg_error.php',
		//__DIR__ . '/error.php',
		//__DIR__ . '/footer.php',
		//__DIR__ . '/header.php',
		//__DIR__ . '/index.php',
    ]);

};













