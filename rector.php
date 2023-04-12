<?php

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromAssignsRector;

use Rector\Php52\Rector\Property\VarToPublicPropertyRector;

//use Rector\Php53\Rector\Variable\ReplaceHttpServerVarsByServerRector;
use Rector\Php53\Rector\Ternary\TernaryToElvisRector;

//use Rector\Php54\Rector\Array_\LongArrayToShortArrayRector; # I ONLY DO THIS FOR CERTAIN FILES #
use Rector\Php54\Rector\FuncCall\RemoveReferenceFromCallRector;

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
use Rector\Php70\Rector\StaticCall\StaticCallOnNonStaticToInstanceCallRector;

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
	
	//52 
    $rectorConfig->rule(VarToPublicPropertyRector::class);
    
	// 53
	//$rectorConfig->rule(ReplaceHttpServerVarsByServerRector::class);
	$rectorConfig->rule(TernaryToElvisRector::class);
	
	//54
	//$rectorConfig->rule(LongArrayToShortArrayRector::class); # I ONLY DO THIS FOR CERTAIN FILES #
	$rectorConfig->rule(RemoveReferenceFromCallRector::class);

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
	$rectorConfig->rule(StaticCallOnNonStaticToInstanceCallRector::class);
	
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
		  //////__DIR__ . '/includes/cmsinit.inc', // fuckover file extension renames
		  
		  //////__DIR__ . '/install.php',
		  //////__DIR__ . '/install/data/agents.php', 
		  //////__DIR__ . '/install/data/coppermine.php',
		  //////__DIR__ . '/install/data/core.php',  
		  //////__DIR__ . '/install/data/forums.php',  
		  //////__DIR__ . '/install/tables/coppermine.php',
		  //////__DIR__ . '/install/tables/core.php',
		  //////__DIR__ . '/install/tables/forums.php',
		  //////__DIR__ . '/install/tables/news.php',
		  //////__DIR__ . '/install/tables/surveys.php',
		  		  
		  __DIR__ . '/includes/db/*',		  
		  
		  //////__DIR__ . '/admin/*/*/*',
		  //////__DIR__ . '/admin/*/*',
		  //////__DIR__ . '/admin/*',
		  
		  //////__DIR__ . '/blocks/*',
		  
		  //////__DIR__ . '/includes/*/*/*',
		  //////__DIR__ . '/includes/*/*',
		  //////__DIR__ . '/includes/*',
		  
		  //////__DIR__ . '/includes/classes/*',


		  //////__DIR__ . '/install/*/*/*',
		  //////__DIR__ . '/install/*/*',
		  //////__DIR__ . '/install/*',

		  //////__DIR__ . '/language/*/*/*',
		  //////__DIR__ . '/language/*/*',
		  //////__DIR__ . '/language/*',

		  //////__DIR__ . '/modules/*/*/*',
		  //////__DIR__ . '/modules/*/*',
		  //////__DIR__ . '/modules/*',
		  
		  //////__DIR__ . '/rss/*',

		  //////__DIR__ . '/themes/*/*/*',
		  //////__DIR__ . '/themes/*/*',
		  //////__DIR__ . '/themes/*',
		  
		  //////__DIR__ . '/admin.php',
		  //////__DIR__ . '/banners.php',
		  //////__DIR__ . '/cpg_error.php',
		  //////__DIR__ . '/error.php',
		  //////__DIR__ . '/footer.php',
		  //////__DIR__ . '/index.php',
		  //////__DIR__ . '/install.php',

		
    ]);

};













