<?php

namespace seraph_accel;

if( !defined( 'ABSPATH' ) )
	exit;

if( defined( 'SERAPH_ACCEL_NO_CACHE' ) )
	return;

if( !defined( 'SERAPH_ACCEL_PLUGIN_DIR' ) ) define( 'SERAPH_ACCEL_PLUGIN_DIR', __DIR__ ); else if( SERAPH_ACCEL_PLUGIN_DIR != __DIR__ ) return;

require_once( __DIR__ . '/common.php' );
require_once( __DIR__ . '/cache_ex.php' );

function _OutImage( $siteId, $settGlob, $sett, $file, $nonce, $aiFileId = null )
{
	if( Gen::StrStartsWith( $file, Gen::GetFileName( WP_CONTENT_DIR ) . '/' ) )
		$pathRoot = Gen::GetFileDir( WP_CONTENT_DIR ) . '/';
	else
		$pathRoot = Gen::SetLastSlash( str_replace( '\\', '/', ABSPATH ) );

	if( Gen::GetNonce( $file, GetSalt() ) != $nonce )
	{
		http_response_code( 403 );
		return;
	}

	$file = $fileOrig =  $pathRoot. $file;
	$fileExt = Gen::GetFileExt( $file );

	if( !in_array( strtolower( $fileExt ), array( 'jpe','jpg','jpeg','png','gif','bmp', 'webp','avif' ) ) )
	{
		http_response_code( 403 );
		return;
	}

	$settImg = Gen::GetArrField( $sett, array( 'contPr', 'img' ), array() );

	if( $aiFileId )
	{
		$aiId = array();
		if( !preg_match( '@^([\\w\\-]+)\\.(\\w+-(\\w+))$@', $aiFileId, $aiId ) )
		{
			http_response_code( 403 );
			return;
		}

		$fileAi = GetCacheDir() . '/s/' . $aiId[ 1 ] . '/ai/' . $aiId[ 2 ] . '.' . $fileExt;
		$aiId = $aiId[ 3 ];

		$fileTm = Gen::FileMTime( $file );
		$fileAiTm = Gen::FileMTime( $fileAi );
		if( $fileAiTm !== false && $fileTm !== false && $fileTm <= $fileAiTm )
			$file = $fileOrig = $fileAi;
		else if( Gen::GetArrField( $settImg, array( 'szAdaptOnDemand' ), false ) )
		{
			if( Gen::GetArrField( $settImg, array( 'szAdaptAsync' ), false ) )
			{
				CachePostPrepareObjEx( 20, $file, ( string )$siteId, 10, array( 'ai' => $aiId ) );
			}
			else
			{
				require_once( __DIR__ . '/content.php' );

				{
					$ctxProcess = &GetContentProcessCtxEx( $_SERVER, $sett, $siteId, '', str_replace( '\\', '/', ABSPATH ), WP_CONTENT_DIR, '', GetCacheDir(), false );
					ContentProcess_InitLocalCbs( $ctxProcess );
					Images_ProcessSrc_SizeAlternatives( $ctxProcess, $file, $sett, true, $aiId );
					unset( $ctxProcess );
				}

				if( file_exists( $fileAi ) )
					$file = $fileOrig = $fileAi;
			}
		}
	}

	$mimeType = Fs::MimeTypeDef;
	$aSkip = array();
	$bNeedCompr = false;
	foreach( array_reverse( array( 'webp','avif' ) ) as $comprType )
	{
		if( !Gen::GetArrField( $settImg, array( $comprType, 'redir' ), false ) )
			continue;

		if( strpos( ($_SERVER[ 'HTTP_ACCEPT' ]??''), 'image/' . $comprType ) === false )
			continue;

		$pathCompr = $file . '.' . $comprType;
		if( !@file_exists( $pathCompr ) )
		{
			$pathCompr .= '.json';
			if( !@file_exists( $pathCompr ) && Gen::GetArrField( $settImg, array( $comprType, 'enable' ), false ) )
				$bNeedCompr = true;
			$aSkip[ $comprType ] = @json_decode( ( string )@file_get_contents( $pathCompr, false, null, 0, 500 ), true );
			continue;
		}

		$file = $pathCompr;
		$mimeType = 'image/' . $comprType;
		break;
	}

	if( $bNeedCompr && Gen::GetArrField( $settImg, array( 'comprAsync' ), false ) )
		CachePostPrepareObjEx( 10, $fileOrig, ( string )$siteId, 10 );

	if( ($sett[ 'hdrTrace' ]??null) )
		@header( 'X-Seraph-Accel-Cache: 2.29.11; state=' . ( $mimeType == Fs::MimeTypeDef ? 'original' : 'preoptimized' ) . '; redir=alt;' . ( $aSkip ? ( '; skip=' . @json_encode( $aSkip ) ) : '' ) . ( $mimeType != Fs::MimeTypeDef ? ( '; sizeOrig=' . @filesize( $fileOrig ) ) : '' ) );

	if( $mimeType != Fs::MimeTypeDef && Gen::GetArrField( $settImg, array( 'redirCacheAdapt' ), false ) )
	{
		@header( 'Location: ' . rtrim( ( string )Net::UrlDeParse( Net::UrlParse( $_SERVER[ 'REQUEST_URI' ] ), 0, array(), array( PHP_URL_PATH ) ), '/' ) . '/' . substr( $file, strlen( Gen::SetLastSlash( str_replace( '\\', '/', ABSPATH ) ) ) ), false );
		http_response_code( 302 );
		return;
	}

	@header( 'Vary: Accept', false );

	Fs::StreamOutFileContent( $file, $mimeType, false, 16384, false, Gen::GetArrField( $sett, array( 'cacheBr', 'enable' ), false ) ? Gen::GetArrField( $sett, array( 'cacheBr', 'timeout' ), 0 ) * 60 : 0, Gen::GetArrField( $settGlob, array( 'cache', 'chkNotMdfSince' ), false ) );
}

global $seraph_accel_g_cacheSkipData;
global $seraph_accel_sites;

ExtCron_Init();

if( !$seraph_accel_sites )
	return;

$hr = _Process( $seraph_accel_sites );

if( $hr == Gen::S_OK || Gen::HrFail( $hr ) )
{
	if( isset( $args[ 'seraph_accel_gp' ] ) || !CacheDoCronAndEndRequest() )
	{

		flush();
		exit();
	}

	return;
}

if( $hr == Gen::S_NOTIMPL )
	return;

if( $hr == Gen::S_IO_PENDING )
{

	ob_start( 'seraph_accel\\_CbContentFinish' );
	ob_start( 'seraph_accel\\_CbContentProcess' );
}
else if( $seraph_accel_g_cacheSkipData )
{

	ob_start( 'seraph_accel\\_CbContentFinishSkip' );
	ob_start( 'seraph_accel\\_CbContentProcess' );
}

function _Process( $sites )
{
	$requestMethod = strtoupper( ($_SERVER[ 'REQUEST_METHOD' ]??'GET') );
	$args = $_GET;

	global $seraph_accel_g_noFo;
	global $seraph_accel_g_prepPrms;
	global $seraph_accel_g_lazyInvTmp;
	global $seraph_accel_g_cacheSkipData;
	global $seraph_accel_g_siteId;
	global $seraph_accel_g_cacheCtxSkip;
	global $seraph_accel_g_simpCacheMode;

	if( ( $requestMethod == 'GET' ) && isset( $_SERVER[ 'HTTP_X_SERAPH_ACCEL_TEST' ] ) )
	{
		if( $idTest = Gen::SanitizeId( substr( $_SERVER[ 'HTTP_X_SERAPH_ACCEL_TEST' ], 0, 64 ) ) )
		{
			@header( 'X-Seraph-Accel-test: ' . $idTest );
			@header( 'ETag: "001122334455667788"' );
		}

		unset( $idTest );
	}

	$seraph_accel_g_prepPrms = CacheExtractPreparePageParams( $args );
	if( $seraph_accel_g_prepPrms !== null )
	{

		BatCache_DontProcessCurRequest( true );

		if( $seraph_accel_g_prepPrms === false )
		{
			http_response_code( 400 );
			return( Gen::E_INVALIDARG );
		}

		@ignore_user_abort( true );
		Gen::SetTimeLimit( 570 );

		if( ($seraph_accel_g_prepPrms[ 'selfTest' ]??null) )
		{
			$seraph_accel_g_cacheSkipData = array( 'skipped', array( 'reason' => 'selfTest' ) );
			return( Gen::S_FALSE );
		}

		if( !ProcessCtlData_Update( ($seraph_accel_g_prepPrms[ 'pc' ]??null), array( 'stage' => 'get' ), false, true ) )
		{
			http_response_code( 599 );
			return( Gen::E_FAIL );
		}

	}

	{
		if( isset( $_SERVER[ 'HTTP_X_SERAPH_ACCEL_POSTPONE_USER_AGENT' ] ) )
		{
			$_SERVER[ 'HTTP_USER_AGENT' ] = $_SERVER[ 'HTTP_X_SERAPH_ACCEL_POSTPONE_USER_AGENT' ];
			unset( $_SERVER[ 'HTTP_X_SERAPH_ACCEL_POSTPONE_USER_AGENT' ] );

		}
	}

	$siteUriRoot = ''; GetContCacheEarlySkipData( $pathOrig, $path, $pathIsDir, $args );
	if( $pathOrig !== null )
	{
		$host = GetRequestHost( $_SERVER );
		$addrSite = $host;

		$seraph_accel_g_siteId = GetCacheSiteIdAdjustPath( $sites, $addrSite, $siteSubId, $path );
		if( $seraph_accel_g_siteId === null )
			$seraph_accel_g_cacheSkipData = array( 'skipped', array( 'reason' => 'siteIdUnk' ) );
		else
			$siteUriRoot = substr( $addrSite, strlen( $host ) );

		unset( $addrSite, $host );
	}

	$settGlob = Plugin::SettGet( Gen::CallFunc( 'seraph_accel_siteSettInlineDetach', array( 'm' ) ) );
	if( $seraph_accel_g_siteId != 'm' )
	{
		if( Wp::IsMultisite() )
			PluginOptions::SetBlogId( GetBlogIdFromSiteId( $seraph_accel_g_siteId ) );
		$sett = Plugin::SettGet( Gen::CallFunc( 'seraph_accel_siteSettInlineDetach', array( $seraph_accel_g_siteId ) ) );
		PluginOptions::UnsetBlogId();
	}
	else
		$sett = $settGlob;

	if( ( $requestMethod == 'GET' ) && isset( $_REQUEST[ 'seraph_accel_gi' ] ) )
	{

		BatCache_DontProcessCurRequest( true );

		$siteId = $seraph_accel_g_siteId;

		$file = $_REQUEST[ 'seraph_accel_gi' ];
		if( isset( $_REQUEST[ 'intrnl' ] ) )
		{
			$siteIdProbe = Gen::SanitizeId( $_REQUEST[ 'intrnl' ] );
			foreach( $sites as $addrSite => $id )
			{
				if( $id != $siteIdProbe )
					continue;

				$siteId = $id;
				$siteUriRoot = substr( $addrSite, strlen( GetRequestHost( $_SERVER ) ) );

				$sett = Plugin::SettGet( Gen::CallFunc( 'seraph_accel_siteSettInlineDetach', array( $siteId ) ) );

				break;
			}

			unset( $siteIdProbe, $addrSite, $id );
		}
		else
			$file = $siteUriRoot . '/' . $file;

		$file = VirtUriPath2Real( $file, GetVirtUriPathsFromSett( $sett ) );
		$file = substr( $file, strlen( $siteUriRoot ) + 1 );

		_OutImage( $siteId, $settGlob, $sett, $file, Gen::SanitizeId( ($_REQUEST[ 'n' ]??null) ), Gen::SanitizeId( ($_REQUEST[ 'ai' ]??null) ) );
		exit();
	}

	if( ( $requestMethod == 'GET' ) && isset( $_REQUEST[ 'seraph_accel_gci' ] ) )
	{
		_OutImage( $seraph_accel_g_siteId, $settGlob, $sett, $_REQUEST[ 'seraph_accel_gci' ], Gen::SanitizeId( ($_REQUEST[ 'n' ]??null) ) );
		exit();
	}

	$settCache = Gen::GetArrField( $sett, array( 'cache' ), array() );

	$timeoutCln = Gen::GetArrField( $settCache, array( 'timeoutCln' ), 0 ) * 60;
	$timeout = ($settCache[ 'updByTimeout' ]??true) ? ( Gen::GetArrField( $settCache, array( 'timeout' ), 0 ) * 60 ) : 0;

	$lazyInv = ($settCache[ 'lazyInv' ]??null);

	InitTimeoutClnForWpNonce( $settCache );

	if( ( $requestMethod == 'GET' ) && isset( $_REQUEST[ 'seraph_accel_gf' ] ) )
	{
		@header( 'X-Robots-Tag: noindex' );

		$seraph_accel_g_simpCacheMode = 'fragments:' . Gen::SanitizeId( $_REQUEST[ 'seraph_accel_gf' ] );

		$timeoutCln = Gen::GetArrField( $settCache, array( 'timeoutFrCln' ), 0 );
		$timeout = Gen::GetArrField( $settCache, array( 'timeoutFr' ), 0 );

		$lazyInv = ($settCache[ 'lazyInvFr' ]??null);

		Net::CurRequestRemoveArgs( $args, array( 'seraph_accel_gf' ) );
		if( !$timeout )
		{
			$seraph_accel_g_cacheSkipData = array( 'revalidated', array( 'reason' => 'timeoutZero' ) );
			return( Gen::S_FALSE );
		}

	}

	if( $seraph_accel_g_simpCacheMode === null && Gen::GetArrField( $settCache, array( 'ctxSkip' ), false ) )
		$seraph_accel_g_cacheCtxSkip = true;

	$idSubPart = ( $requestMethod == 'GET' ) ? Gen::SanitizeId( ($_REQUEST[ 'seraph_accel_gp' ]??null), null ) : null;
	if( $idSubPart )
	{
		@header( 'X-Robots-Tag: noindex' );
		Net::CurRequestRemoveArgs( $args, array( 'seraph_accel_gp' ) );
	}

	$requestMethodCache = 'GET';
	{
		$itemDataFound = null;
		if( ( $requestMethod == 'GET' ) && isset( $_REQUEST[ 'seraph_accel_gbnr' ] ) )
		{
			$itemDataFound = array(
				'type' => 'GET', 'mime' => 'text/plain',
				'exclArgsAll' => false, 'exclArgs' => array(),
				'skipArgsEnable' => true, 'skipArgsAll' => false, 'skipArgs' => array( '!seraph_accel_gbnr' ),
				'timeoutCln' => 60 * 60 * 24, 'timeout' => 60, 'lazyInv' => true,
			);
		}
		else
		{
			$requestURICheck = null;
			foreach( Gen::GetArrField( $settCache, array( 'data', 'items' ), array() ) as $itemData )
			{
				if( !($itemData[ 'enable' ]??null) )
					continue;

				if( $requestMethod != ($itemData[ 'type' ]??'GET') )
					continue;

				$found = false;
				foreach( ExprConditionsSet_Parse( ($itemData[ 'pattern' ]??'') ) as $e )
				{
					if( $requestURICheck === null )
					{
						$requestURICheck = $_SERVER[ 'REQUEST_URI' ];

						if( $requestMethod == 'POST' )
						{
							AddCurPostArgs( $args );
							$requestURICheck = Net::UrlAddArgs( $requestURICheck, $args );
						}
					}

					$val = false;
					if( IsStrRegExp( $e[ 'expr' ] ) )
					{
						if( @preg_match( $e[ 'expr' ], $requestURICheck ) )
							$val = true;
					}
					else if( strpos( $requestURICheck, $e[ 'expr' ] ) !== false )
						$val = true;

					if( !ExprConditionsSet_ItemOp( $e, $val ) )
					{
						$found = false;
						break;
					}

					$found = true;
				}

				if( $found )
				{
					$itemDataFound = $itemData;
					break;
				}
			}
		}

		if( $itemDataFound )
		{
			$seraph_accel_g_simpCacheMode = 'data:' . Fs::GetFileTypeFromMimeContentType( ($itemDataFound[ 'mime' ]??''), 'bin' );

			foreach( array( 'exclArgsAll', 'exclArgs', 'skipArgsEnable', 'skipArgsAll', 'skipArgs' ) as $fld )
				Gen::SetArrField( $settCache, array( $fld ), Gen::GetArrField( $itemDataFound, array( $fld ) ) );
			$requestMethodCache = ($itemDataFound[ 'type' ]??'GET');
			$timeoutCln = Gen::GetArrField( $itemDataFound, array( 'timeoutCln' ), 0 );
			$timeout = Gen::GetArrField( $itemDataFound, array( 'timeout' ), 0 );

			$lazyInv = ($itemDataFound[ 'lazyInv' ]??null);

			if( $pathOrig !== null && $seraph_accel_g_cacheSkipData )
				$seraph_accel_g_cacheSkipData = null;
		}

		unset( $requestURICheck, $itemData, $itemDataFound, $found, $val );
	}

	if( $requestMethod != $requestMethodCache )
	{
		if( $requestMethod != 'GET' )
			$seraph_accel_g_cacheSkipData = null;
		return( Gen::S_FALSE );
	}

	if( $seraph_accel_g_cacheSkipData )
	{

		BatCache_DontProcessCurRequest();

		_ProcessOutHdrTrace( $sett, true, true, $seraph_accel_g_cacheSkipData[ 0 ], ($seraph_accel_g_cacheSkipData[ 1 ]??null) );
		if( $seraph_accel_g_prepPrms !== null )
			ProcessCtlData_Update( ($seraph_accel_g_prepPrms[ 'pc' ]??null), array_merge( array( 'finish' => true, 'skip' => Gen::GetArrField( ($seraph_accel_g_cacheSkipData[ 1 ]??null), array( 'reason' ), '' ) ), ($sett[ 'debugInfo' ]??null) ? array( 'infos' => array( LocId::Pack( 'SrvArgs' ) => PackKvArrInfo( $_SERVER ) ) ) : array() ), false, false );
		return( Gen::S_NOTIMPL );
	}

	if( GetContentProcessorForce( $sett ) !== null )
	{

		BatCache_DontProcessCurRequest();

		$seraph_accel_g_cacheSkipData = array( 'skipped', array( 'reason' => 'debugContProcForce' ) );

		return( Gen::S_FALSE );
	}

	{
		$exclStatus = ContProcGetExclStatus( $seraph_accel_g_siteId, $settCache, $path, $pathOrig, $pathIsDir, $args, $varsOut, true, $seraph_accel_g_prepPrms === null );
		if( $exclStatus )
		{

			BatCache_DontProcessCurRequest();

			$seraph_accel_g_cacheSkipData = array( 'skipped', array( 'reason' => $exclStatus ) );

			if( Gen::StrStartsWith( $exclStatus, 'excl' ) )
			{

				$debugData = ($seraph_accel_g_cacheSkipData[ 1 ]??null);
				if( ($sett[ 'debugInfo' ]??null) )
					$debugData[ 'args' ] = $args;

				_ProcessOutHdrTrace( $sett, true, true, $seraph_accel_g_cacheSkipData[ 0 ], $debugData );
				if( $seraph_accel_g_prepPrms !== null )
					ProcessCtlData_Update( ($seraph_accel_g_prepPrms[ 'pc' ]??null), array_merge( array( 'finish' => true, 'skip' => $exclStatus ), ($sett[ 'debugInfo' ]??null) ? array( 'infos' => array( LocId::Pack( 'SrvArgs' ) => PackKvArrInfo( $_SERVER ) ) ) : array() ), false, false );
				return( Gen::S_NOTIMPL );
			}

			return( Gen::S_FALSE );
		}

		extract( $varsOut );
		Net::CurRequestRemoveArgs( $args, $aArgRemove );
		unset( $varsOut, $exclStatus, $aArgRemove );
	}

	global $seraph_accel_g_dscFile;
	global $seraph_accel_g_dscFilePending;
	global $seraph_accel_g_dataPath;
	global $seraph_accel_g_prepOrigContHashPrev;
	global $seraph_accel_g_ctxCache;

	$seraph_accel_g_ctxCache = new AnyObj();

	$sessId = $userId ? ($sessInfo[ 'userSessId' ]??null) : ($sessInfo[ 'sessId' ]??null);
	$aViewCookieMatched = array();
	$viewId = GetCacheViewId( $seraph_accel_g_ctxCache, $settCache, $userAgent, $path, $pathOrig, $args, Gen::StrStartsWith( ( string )$seraph_accel_g_simpCacheMode, 'fragments' ), $aViewCookieMatched );
	$seraph_accel_g_ctxCache -> viewId = $viewId;
	$cacheRootPath = GetCacheDir();
	$siteCacheRootPath = $cacheRootPath . '/s/' . $seraph_accel_g_siteId;
	$seraph_accel_g_ctxCache -> viewPath = GetCacheViewsDir( $siteCacheRootPath, $siteSubId ) . '/' . $viewId;
	$ctxsPath = $seraph_accel_g_ctxCache -> viewPath . '/c';

	if( ($settCache[ 'normAgent' ]??null) )
		add_action( 'template_redirect',
			function()
			{
				if( !is_404() )
					return;

				if( isset( $_SERVER[ 'SERAPH_ACCEL_ORIG_USER_AGENT' ] ) )
					$_SERVER[ 'HTTP_USER_AGENT' ] = $_SERVER[ 'SERAPH_ACCEL_ORIG_USER_AGENT' ];
			}
		, 0 );

	add_filter( 'wp_redirect_status',
		function( $status, $location )
		{
			global $seraph_accel_g_sRedirLocation;
			$seraph_accel_g_sRedirLocation = $location;
			return( $status );
		}
	, 99999, 2 );

	{
		$seraph_accel_g_ctxCache -> userId = $userId;
		if( !$sessId || !$stateCookId || !Gen::GetArrField( $settCache, array( 'ctxSessSep' ), false ) )
		{
			$seraph_accel_g_ctxCache -> userSessId = null;
			$sessId = '@';
		}
		else
			$seraph_accel_g_ctxCache -> userSessId = $sessId;
		$seraph_accel_g_ctxCache -> isUserSess = $seraph_accel_g_ctxCache -> userId || $seraph_accel_g_ctxCache -> userSessId;

		$ctxPathId = $userId . '/s/' . $sessId;

		if( !$seraph_accel_g_cacheCtxSkip && Gen::GetArrField( $settCache, array( 'ctx' ), false ) )
		{
			$_SERVER[ 'HTTP_X_SERAPH_ACCEL_SESSID' ] = $userId . '/' . $sessId;

			if( $seraph_accel_g_ctxCache -> isUserSess )
			{
				$ctxTimeoutCln = Gen::GetArrField( $settCache, array( 'ctxTimeoutCln' ), 0 ) * 60;
				if( $timeoutCln > $ctxTimeoutCln )
					$timeoutCln = $ctxTimeoutCln;
			}
		}

		if( $stateCookId )
			$stateCookId = md5( $stateCookId );
		else
			$stateCookId = '@';

		$ctxPathId .= '/s/' . $stateCookId;
	}

	if( !$seraph_accel_g_ctxCache -> isUserSess || $seraph_accel_g_cacheCtxSkip )
		Net::CurRequestSetCookies( $aViewCookieMatched );

	$objectId = '@';
	$objectType = 'html';
	if( $pathIsDir )
		$objectId .= 'd';
	if( is_string( $seraph_accel_g_simpCacheMode ) )
	{
		if( Gen::StrStartsWith( $seraph_accel_g_simpCacheMode, 'fragments' ) )
			$objectId .= 'f';
		else if( Gen::StrStartsWith( $seraph_accel_g_simpCacheMode, 'data:' ) )
		{

			$objectType = substr( $seraph_accel_g_simpCacheMode, 5 );
		}
	}

	if( $requestMethod == 'POST' )
		$objectId .= '-p';

	if( !empty( $args ) )
	{
		$argsCumulative = '';
		foreach( $args as $argKey => $argVal )
			$argsCumulative .= $argKey . $argVal;

		$objectId = $objectId . '.' . @md5( $argsCumulative );
		unset( $argsCumulative );
	}

	$seraph_accel_g_dataPath = GetCacheDataDir( $siteCacheRootPath );

	$seraph_accel_g_dscFile = $ctxsPath . '/' . $ctxPathId . '/o';
	if( $path )
		$seraph_accel_g_dscFile .= '/' . $path;
	$seraph_accel_g_dscFile .= '/' . $objectId . '.' . $objectType . '.dat';

	if( $seraph_accel_g_prepPrms && !ProcessCtlData_Update( ($seraph_accel_g_prepPrms[ 'pc' ]??null), array( 'dscFile' => $seraph_accel_g_dscFile ) ) )
	{
		http_response_code( 599 );
		return( Gen::E_FAIL );
	}

	if( $idSubPart )
	{
		$idSubPart = str_replace( '_', '.', $idSubPart );
		$subPartType = Gen::GetFileExt( $idSubPart );
		$idSubPart = substr( $idSubPart, 0, -( strlen( $subPartType ) + 1 ) );
		{
			$sepPos = strpos( $idSubPart, '.' );
			if( $sepPos !== false )
				$idSubPart = substr( $idSubPart, $sepPos + 1 );
			unset( $sepPos );
		}

		if( !Gen::StrStartsWith( $idSubPart, '.' ) )
		{
			$dscPart = Gen::GetArrField( CacheReadDsc( $seraph_accel_g_dscFile ), array( 'b', $idSubPart ), array() );
			if( !$dscPart )
			{
				http_response_code( 404 );
				return( Gen::S_OK );
			}

			if( _ProcessOutCachedData( true, $subPartType, $settGlob, $sett, $settCache, $dscPart, Gen::FileMTime( $seraph_accel_g_dscFile ), $tmCur, 'cache', null, true ) !== Gen::S_OK )
			{
				http_response_code( 599 );
				return( Gen::E_FAIL );
			}

			return( Gen::S_OK );
		}

		if( $subPartType != 'html' )
		{
			http_response_code( 400 );
			return( Gen::S_OK );
		}

		$dsc = CacheReadDsc( $seraph_accel_g_dscFile );
		if( !$dsc )
		{
			http_response_code( 404 );
			return( Gen::S_OK );
		}

		_CacheStdHdrs( true, $seraph_accel_g_ctxCache, $settCache );
		if( ($sett[ 'hdrTrace' ]??null) )
		{
			$debugData = array();
			if( isset( $dsc[ 't' ] ) )
				$debugData[ 'cacheTmp' ] = true;
			$debugData[ 'date' ] = gmdate( 'Y-m-d H:i:s', Gen::FileMTime( $seraph_accel_g_dscFile ) );
			$debugData[ 'dscFile' ] = substr( $seraph_accel_g_dscFile, strlen( GetCacheDir() ) );

			_ProcessOutHdrTrace( $sett, true, false, 'cache', $debugData );
		}

		CacheWriteOut( '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>' );

		foreach( Gen::Explode( '.', substr( $idSubPart, 1 ) ) as $idSubPart )
		{
			$dscPart = Gen::GetArrField( $dsc, array( 'b', $idSubPart ), array() );
			if( !$dscPart )
			{
				http_response_code( 404 );
				return( Gen::S_OK );
			}

			$ctxData = CacheDscGetDataCtx( $settCache, $dscPart, '', $seraph_accel_g_dataPath, $tmCur, 'html' );
			if( !$ctxData )
			{
				http_response_code( 599 );
				return( Gen::E_FAIL );
			}

			CacheWriteOut( '<!--seraph_accel_gp=' . $idSubPart . '-->' );
			CacheDscDataOutput( $ctxData );
			CacheWriteOut( '<!--seraph_accel_gp-->' );
		}

		CacheWriteOut( '</body></html>' );
		return( Gen::S_OK );
	}

	$seraph_accel_g_dscFilePending = $seraph_accel_g_dscFile . '.p';

	if( $seraph_accel_g_prepPrms !== null )
	{
		$seraph_accel_g_dscFilePending .= 'p';
        $seraph_accel_g_noFo = true;
	}

	$contProc = Gen::GetArrField( $sett, array( 'contPr', 'enable' ), false );

	$procTmLim = Gen::GetArrField( $settCache, array( 'procTmLim' ), 570 );

	$sessExpiration = ($sessInfo[ 'expiration' ]??null);
	if( !$sessExpiration )
		$sessExpiration = $tmCur;

	$httpCacheControl = strtolower( ($_SERVER[ 'HTTP_CACHE_CONTROL' ]??'') );

	if( $seraph_accel_g_ctxCache -> isUserSess && !($settCache[ 'ctxLazyInv' ]??null) )
		$lazyInv = false;
	$seraph_accel_g_lazyInvTmp = false;

	if( $timeoutCln && $timeout > $timeoutCln )
		$timeout = $timeoutCln;

	$reason = null;
	$dsc = null;
	$isCip = null;

	$dscFileTm = Gen::FileMTime( $seraph_accel_g_dscFile );
	$dscFileTmAge = $tmCur - $dscFileTm;

	if( !$dscFileTm || ( $timeoutCln > 0 && $dscFileTmAge > $timeoutCln && ( $dscFileTm >= 60 ) ) || ( $timeout > 0 ? ( $dscFileTmAge > $timeout ) : ( $dscFileTm < 60 ) ) || ( $tmCur > $sessExpiration ) || ( $seraph_accel_g_ctxCache -> isUserSess && $httpCacheControl == 'no-cache' && Gen::GetArrField( $settCache, array( 'ctxCliRefresh' ), false ) ) )
	{
		$lock = new Lock( 'dl', $cacheRootPath );
		if( !$lock -> Acquire() )
			return( Gen::E_FAIL );

		$dscFileTm = Gen::FileMTime( $seraph_accel_g_dscFile );

		if( $dscFileTm === false )
		{
			$ccs = _CacheContentStart( $tmCur, $procTmLim );
			$lock -> Release();

			if( $ccs )
			{

				$seraph_accel_g_lazyInvTmp = $lazyInv && ($settCache[ 'lazyInvInitTmp' ]??true);
				if( $seraph_accel_g_prepPrms === null && $contProc && $seraph_accel_g_simpCacheMode === null )
				{
					$seraph_accel_g_cacheSkipData = array( 'revalidating-begin', array( 'reason' => 'initial', 'dscFile' => substr( $seraph_accel_g_dscFile, strlen( $cacheRootPath ) ) ) );
					return( Gen::S_FALSE );
				}

				return( Gen::S_IO_PENDING );
			}

			if( $seraph_accel_g_prepPrms !== null )
			{
				ProcessCtlData_Update( ($seraph_accel_g_prepPrms[ 'pc' ]??null), array_merge( array( 'finish' => true, 'skip' => 'alreadyProcessing' ), ($sett[ 'debugInfo' ]??null) ? array( 'infos' => array( LocId::Pack( 'SrvArgs' ) => PackKvArrInfo( $_SERVER ) ) ) : array() ), false, false );
				return( Gen::S_OK );
			}

			if( $seraph_accel_g_simpCacheMode === null )
			{
				$seraph_accel_g_cacheSkipData = array( 'revalidating', array( 'reason' => 'initial', 'dscFile' => substr( $seraph_accel_g_dscFile, strlen( $cacheRootPath ) ) ) );
				return( Gen::S_FALSE );
			}

			return( Gen::S_IO_PENDING );
		}
		else
		{

			$dsc = CacheReadDsc( $seraph_accel_g_dscFile );

			$dscFileTmAge = $tmCur - $dscFileTm;

			if( $dscFileTm === 0 )
			{
				$reason = 'forced';

				if( $dsc && !isset( $dsc[ 't' ] ) )
					$seraph_accel_g_prepOrigContHashPrev = ($dsc[ 'h' ]??null);

			}
			else if( $timeoutCln > 0 && $dscFileTmAge > $timeoutCln && ( $dscFileTm >= 60 ) )
			{
				$reason = 'timeoutClnExpired';

				$lazyInv = false;

			}
			else if( $timeout > 0 ? ( $dscFileTmAge > $timeout ) : ( $dscFileTm < 60 ) )
			{
				$reason = ( $dscFileTm === 5 ) ? 'initial' : ( ( $dscFileTm === 10 ) ? 'forced' : 'timeoutExpired' );

				if( $dsc && !isset( $dsc[ 't' ] ) )
					$seraph_accel_g_prepOrigContHashPrev = ($dsc[ 'h' ]??null);

			}
			else if( $tmCur > $sessExpiration )
				$reason = 'userSessionExpired';
			else if( $seraph_accel_g_ctxCache -> isUserSess && $httpCacheControl == 'no-cache' && Gen::GetArrField( $settCache, array( 'ctxCliRefresh' ), false ) )
				$reason = 'forcedFromClient';

			if( $reason )
			{
				$ccs = _CacheContentStart( $tmCur, $procTmLim );
				if( $ccs )
				{
					if( $dscFileTm === 0 && !@touch( $seraph_accel_g_dscFile, 10 ) )
					{
						$lock -> Release();
						return( Gen::E_FAIL );
					}

					$lock -> Release();

					if( $lazyInv )
					{
						$isCip = false;
						$seraph_accel_g_lazyInvTmp = ( $seraph_accel_g_simpCacheMode !== null ) ? false : ( ( $reason === 'forced' ) ? ($settCache[ 'lazyInvForcedTmp' ]??null) : ($settCache[ 'lazyInvTmp' ]??null) );
					}
					else

					{

						if( $seraph_accel_g_prepPrms === null && $contProc && $seraph_accel_g_simpCacheMode === null )
						{
							$seraph_accel_g_cacheSkipData = array( 'revalidating-begin', array( 'reason' => $reason, 'dscFile' => substr( $seraph_accel_g_dscFile, strlen( $cacheRootPath ) ) ) );
							return( Gen::S_FALSE );
						}

						return( Gen::S_IO_PENDING );
					}
				}
				else
				{
					$lock -> Release();

					$isCip = true;

					if( !$lazyInv )

					{
						if( !( $dsc && isset( $dsc[ 't' ] ) ) && $seraph_accel_g_prepPrms === null )
						{
							if( $seraph_accel_g_simpCacheMode === null )
							{
								$seraph_accel_g_cacheSkipData = array( 'revalidating', array( 'reason' => $reason, 'dscFile' => substr( $seraph_accel_g_dscFile, strlen( $cacheRootPath ) ) ) );
								return( Gen::S_FALSE );
							}

							return( Gen::S_IO_PENDING );
						}
					}
				}
			}
			else
				$lock -> Release();
		}

		unset( $lock );
	}
	else
		$dsc = CacheReadDsc( $seraph_accel_g_dscFile );

	if( $seraph_accel_g_prepPrms === null )
	{

		if( $contProc && ( ( strlen( $userAgent ) > 100 ) || !MatchUserAgentExpressions( $userAgent, Gen::GetArrField( $sett, array( 'bots', 'agents' ), array() ), array( '@^\\^?([\\/\\~\\@\\;\\%\\`\\#])\\.[\\*\\+](?1)\\$?$@'  ) ) ) )
			lfjikztqjqji( $seraph_accel_g_siteId, $tmCur, true );

		$reasonOutputErr = null;
		if( !$dsc )
			$reasonOutputErr = 'brokenDsc';
		else
		{

			$hr = _ProcessOutCachedData( $seraph_accel_g_simpCacheMode === null, null, $settGlob, $sett, $settCache, $dsc, $dscFileTm, $tmCur, $isCip ? 'revalidating' : ( $isCip === false ? 'revalidating-begin' : 'cache' ), $reason, true );
			if( Gen::HrFail( $hr ) )
				return( $hr );

			if( $hr == Gen::S_FALSE )
				$reasonOutputErr = 'brokenData';
		}

		if( $reasonOutputErr )
		{
			$lock = new Lock( 'dl', $cacheRootPath );
			if( !$lock -> Acquire() )
				return( Gen::E_FAIL );

			@touch( $seraph_accel_g_dscFile, 0 );

			if( $isCip === false || _CacheContentStart( $tmCur, $procTmLim ) )
			{
				$lock -> Release();

				if( $contProc && $seraph_accel_g_simpCacheMode === null )
				{
					$seraph_accel_g_cacheSkipData = array( 'revalidating-begin', array( 'reason' => $reasonOutputErr, 'dscFile' => substr( $seraph_accel_g_dscFile, strlen( $cacheRootPath ) ) ) );
					return( Gen::S_FALSE );
				}

				return( Gen::S_IO_PENDING );
			}

			$lock -> Release();

			if( $seraph_accel_g_simpCacheMode === null )
			{
				$seraph_accel_g_cacheSkipData = array( 'revalidating', array( 'reason' => $reasonOutputErr, 'dscFile' => substr( $seraph_accel_g_dscFile, strlen( $cacheRootPath ) ) ) );
				return( Gen::S_FALSE );
			}

			return( Gen::S_IO_PENDING );
		}
	}
	else
		$hr = Gen::S_OK;

	if( $isCip === false )
	{
		if( $seraph_accel_g_prepPrms === null )
		{
			if( $bgEnabled = Gen::CloseCurRequestSessionForContinueBgWork() )
				CacheFem();

			if( $seraph_accel_g_simpCacheMode !== null )
			{
				if( !$bgEnabled )
					return( CacheSetCurRequestToPrepareAsync( null, false, false ) ? Gen::S_OK : Gen::S_FALSE );
			}
			else if( $contProc || !$bgEnabled )
				return( ( ( !($settCache[ 'updByTimeout' ]??null) && !$seraph_accel_g_lazyInvTmp  ) || CacheSetCurRequestToPrepareAsync( $contProc ? $seraph_accel_g_siteId : null, $seraph_accel_g_lazyInvTmp ? ( ($settCache[ 'updByTimeout' ]??null) ? true : 'only' ) : false, $bgEnabled ) ) ? Gen::S_OK : Gen::S_FALSE );

			$seraph_accel_g_noFo = true;
			return( Gen::S_IO_PENDING );
		}

		if( $dscFileTm && !($seraph_accel_g_prepPrms[ 'tmp' ]??null) && $dsc && isset( $dsc[ 't' ] ) )
		{
			$obj = new AnyObj();
			$obj -> cont = false;
			if( is_string( $dsc[ 't' ] ) )
				$obj -> cont = ReadSce( '\\seraph_accel\\CacheCrEx', $seraph_accel_g_dataPath, $settCache, $dsc[ 't' ], 'html' );
			else if( $ctxData = CacheDscGetDataCtx( $settCache, $dsc, '', $seraph_accel_g_dataPath, $tmCur, 'html' ) )
			{
				$obj -> cont = CacheDscDataOutput( $ctxData, false );
				unset( $ctxData );
			}

			if( is_string( $obj -> cont ) )
			{
				{
					$posTmpTimeStamp = strrpos( $obj -> cont, '<!-- seraph-accel-tmpTimeStamp: ', -1 );
					if( $posTmpTimeStamp !== false )
						$obj -> cont = substr( $obj -> cont, 0, $posTmpTimeStamp );
				}

				$obj -> cb = function( $obj )
				{

					$obj -> cont = OnEarlyContentComplete( $obj -> cont );
					echo( _CbContentFinish( $obj -> cont ) );

					exit();
				};

				if( $obj -> cont )
				{

					_CacheStdHdrs( $seraph_accel_g_simpCacheMode === null, $seraph_accel_g_ctxCache, $settCache );
					add_action( 'wp_loaded', array( $obj, 'cb' ), -999999  );
					add_action( 'plugins_loaded',
						function()
						{
							Wp::RemoveFilters( 'plugins_loaded', 'linguiseFirstHook' );
						}
					, -999999 );
					return( Gen::S_FALSE );
				}
			}
		}

		$seraph_accel_g_noFo = true;
		return( Gen::S_IO_PENDING );
	}

	if( $seraph_accel_g_prepPrms !== null )
	{
		ProcessCtlData_Update( ($seraph_accel_g_prepPrms[ 'pc' ]??null), array_merge( array( 'finish' => true, 'skip' => $isCip ? 'alreadyProcessing' : 'alreadyProcessed' ), ($sett[ 'debugInfo' ]??null) ? array( 'infos' => array( LocId::Pack( 'SrvArgs' ) => PackKvArrInfo( $_SERVER ) ) ) : array() ), false, false );
		return( $hr );
	}

	return( $hr );
}

