<?php

namespace seraph_accel;

if( !defined( 'ABSPATH' ) )
	exit;

function _CacheStdHdrs( $allowExtCache, $ctxCache, $settCache )
{
	if( $allowExtCache && ( $ctxCache -> viewCompatId || $ctxCache -> isUserSess || !($settCache[ 'srv' ]??null) ) )
		$allowExtCache = false;

	if( $allowExtCache )
	{
		@header( 'Cache-Control: public, max-age=' . Gen::GetArrField( $settCache, array( 'srvShrdTtl' ), 3600 ) . ', s-maxage=' . Gen::GetArrField( $settCache, array( 'srvShrdTtl' ), 3600 ) );
	}
	else
	{
		@header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
		@header( 'Pragma: no-cache' );
	}

	@header( 'Expires: Wed, 11 Jan 1984 05:00:00 GMT' );
	@header( 'Content-Type: text/html; charset=UTF-8' );
	@header( 'Vary: Accept-Encoding', false );
}

function _ProcessOutHdrTrace( $sett, $bHdr, $bLog, $state, $data = null, $dscFile = null )
{
	if( $bHdr && !($sett[ 'hdrTrace' ]??null) )
		$bHdr = false;

	$userAgent = null;
	if( $bLog )
		$userAgent = ( isset( $_SERVER[ 'SERAPH_ACCEL_ORIG_USER_AGENT' ] ) ? $_SERVER[ 'SERAPH_ACCEL_ORIG_USER_AGENT' ] : ($_SERVER[ 'HTTP_USER_AGENT' ]??'') );

	if( $bLog && ( !($sett[ 'log' ]??null) || !($sett[ 'logScope' ][ 'request' ]??null) ) )
		$bLog = false;
	if( $bLog && $state === 'skipped' )
	{
		if( !($sett[ 'logScope' ][ 'requestSkipped' ]??null) )
			$bLog = false;
		else if( !($sett[ 'logScope' ][ 'requestSkippedAdmin' ]??null) && Gen::GetArrField( $data, array( 'reason' ) ) === 'admin' )
			$bLog = false;
	}
	if( $bLog && !($sett[ 'logScope' ][ 'requestBots' ]??null) && MatchUserAgentExpressions( strtolower( $userAgent ), Gen::GetArrField( $sett, array( 'bots', 'agents' ), array() ) ) )
		$bLog = false;

	$debugInfo = ' state=' . $state . ';';
	if( $dscFile )
		$debugInfo .= ' dscFile="' . substr( $dscFile, strlen( GetCacheDir() ) ) . '";';

	if( is_array( $data ) )
		foreach( $data as $dataK => $dataV )
		{
			$v = '';
			switch( gettype( $dataV ) )
			{
			case 'array':		$v = @json_encode( $dataV, JSON_INVALID_UTF8_IGNORE ); break;
			case 'string':		$v = '"' . $dataV . '"'; break;
			case 'boolean':		$v = $dataV ? 'true' : 'false'; break;
			default:			$v .= $dataV; break;
			}

			$debugInfo .= ' ' . $dataK . '=' . $v . ';';
		}

	if( $bHdr )
		@header( 'X-Seraph-Accel-Cache: 2.29.11;' . $debugInfo );

	if( $bLog )
	{
		$txt = $debugInfo . ' URL: ' . GetCurRequestUrl() . '; Agent: ' . $userAgent . '; IP: ' . ($_SERVER[ 'REMOTE_ADDR' ]??'<UNK>');

		LogWrite( $txt, Ui::MsgInfo, 'HTTP trace' );
	}
}

function _ProcessOutCachedData( $allowExtCache, $objSubType, $settGlob, $sett, $settCache, $dsc, $dscFileTm, $tmCur, $stateValidate, $reason, $out, &$output = null )
{
	global $seraph_accel_g_dscFile;
	global $seraph_accel_g_dscFilePending;
	global $seraph_accel_g_dataPath;
	global $seraph_accel_g_ctxCache;
	global $seraph_accel_g_simpCacheMode;

	if( $objSubType === null && is_string( $seraph_accel_g_simpCacheMode ) && Gen::StrStartsWith( ( string )$seraph_accel_g_simpCacheMode, 'data:' ) )
		$objSubType = substr( $seraph_accel_g_simpCacheMode, 5 );

	$bNotMdf = false;

	if( Gen::GetArrField( $settGlob, array( 'cache', 'chkNotMdfSince' ), false ) )
	{
		$hash = null;
		$tmLm = $dscFileTm;
		if( $tmLm < 60 )
		{
			$tmLm = Gen::FileMTime( $seraph_accel_g_dscFilePending );
			if( $tmLm === false )
				$tmLm = $tmCur;
		}

		{
			$tm2 = ( int )Gen::GetArrField( $settGlob, array( '_LM', 'cache', 'chkNotMdfSince' ) );
			if( $tmLm < $tm2 )
				$tmLm = $tm2;
			unset( $tm2 );
		}

		if( isset( $dsc[ 'h' ] ) )
		{
			$hash = $dsc[ 'h' ];
			foreach( ( array )($dsc[ 'p' ]??null) as $oiCi )
				$hash .= GetCacheCh( $oiCi, true );
			$hash = md5( $hash );
		}

		if( isset( $_SERVER[ 'HTTP_IF_NONE_MATCH' ] ) && $hash )
			$bNotMdf = trim( $_SERVER[ 'HTTP_IF_NONE_MATCH' ], " \t\n\r\0\x0B\"" ) == $hash;
		else if( isset( $_SERVER[ 'HTTP_IF_MODIFIED_SINCE' ] ) )
		{
			$tmIfMdfSince = Net::GetTimeFromHdrVal( $_SERVER[ 'HTTP_IF_MODIFIED_SINCE' ] );

			if( $seraph_accel_g_ctxCache -> viewStateId )
			{
				$tmLm = ( $tmCur == $tmLm ) ? ( $tmCur - 1 ) : $tmCur;
				$bNotMdf = ( $tmIfMdfSince == $tmLm );
			}
			else
				$bNotMdf = ( $tmIfMdfSince >= $tmLm );
		}
	}

	if( $bNotMdf )
	{
		http_response_code( 304 );
		$ctxData = null;
	}
	else
	{
		$encoding = '';

		$acceptEncodings = array_map( 'trim', explode( ',', strtolower( ($_SERVER[ 'HTTP_ACCEPT_ENCODING' ]??'') ) ) );
		{
			$acceptEncodingsRaw = $acceptEncodings;
			$acceptEncodings = array();
			foreach( $acceptEncodingsRaw as $acceptEncodingRaw )
			{
				$parts = array_map( 'trim', explode( ';', $acceptEncodingRaw ) );
				if( count( $parts ) )
				{
					$parts = $parts[ 0 ];
					if( $parts != 'br' || IsBrotliAvailable() )
						$acceptEncodings[ $parts ] = true;
				}
			}

			unset( $parts );
			unset( $acceptEncodingsRaw );
			unset( $acceptEncodingRaw );
		}

		$encs = Gen::GetArrField( $settCache, array( 'encs' ), array() );

		{
			foreach( $encs as $enc )
			{
				if( $enc === '' )
					continue;

				if( ($acceptEncodings[ $enc ]??null) )
				{
					$encoding = $enc;
					break;
				}
			}
		}
		unset( $encs );
		unset( $acceptEncodings );

		if( $encoding === 'compress' )
			$encoding = '';

		if( !$out )
			$encoding = '';

		$ctxData = CacheDscGetDataCtx( $settCache, $dsc, $encoding, $seraph_accel_g_dataPath, $tmCur, $objSubType === null ? 'html' : $objSubType );
		if( !$ctxData || ( $objSubType === null && !CacheDscValidateDepsData( $sett, $dsc, $seraph_accel_g_dataPath ) ) )
		{

			@unlink( $seraph_accel_g_dscFile );

			return( Gen::S_FALSE );
		}

		if( !defined( 'SERAPH_ACCEL_ADVCACHE_COMP' ) )
		{
			if( $encoding )
			{
				@ini_set( 'zlib.output_compression', 'Off' );
				@ini_set( 'brotli.output_compression', 'Off' );
			}

			if( Gen::GetArrField( $settGlob, array( 'cache', 'cntLen' ), false ) && $ctxData[ 'contentLen' ] !== null )
				@header( 'Content-Length: '. $ctxData[ 'contentLen' ] );

			if( $encoding )
				@header( 'Content-Encoding: ' . $encoding );
		}
	}

	if( $objSubType === null )
		_CacheStdHdrs( $allowExtCache, $seraph_accel_g_ctxCache, $settCache );
	else
	{
		switch( $objSubType )
		{
		case 'css':		$objSubType = 'text/css; charset=UTF-8'; break;
		case 'js':		$objSubType = 'application/javascript; charset=UTF-8'; break;
		case 'json':	$objSubType = 'application/json; charset=UTF-8'; break;
		case 'xml':		$objSubType = 'text/xml; charset=UTF-8'; break;
		case 'txt':		$objSubType = 'text/plain; charset=UTF-8'; break;
		case 'bin':		$objSubType = 'application/octet-stream'; break;
		case 'rss':		$objSubType = 'application/rss+xml; charset=UTF-8'; break;
		default:		$objSubType = 'text/html; charset=UTF-8'; break;
		}

		@header( 'Content-Type: ' . $objSubType );
	}

	if( Gen::GetArrField( $settGlob, array( 'cache', 'chkNotMdfSince' ), false ) )
	{
		if( $hash )
			@header( 'ETag: "' . $hash . '"' );
		@header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s', $tmLm ) . ' GMT' );
	}

	foreach( Gen::GetArrField( $dsc, array( 'hd' ), array() ) as $hdr )
		@header( $hdr );

	if( ($sett[ 'hdrTrace' ]??null) || ( $objSubType === null && ($sett[ 'log' ]??null) && ($sett[ 'logScope' ][ 'request' ]??null) ) )
	{
		$debugData = array();
		if( $reason )
			$debugData[ 'reason' ] = $reason;
		if( isset( $dsc[ 't' ] ) )
			$debugData[ 'cacheTmp' ] = true;
		$debugData[ 'date' ] = gmdate( 'Y-m-d H:i:s', $dscFileTm );
		$debugData[ 'dscFile' ] = substr( $seraph_accel_g_dscFile, strlen( GetCacheDir() ) );

		if( $ctxData )
			$debugData = array_merge( $debugData, array_filter( $ctxData, function( $k ) { return( in_array( $k, array( 'encoding', 'recompress', 'compressedEncoding', 'sizeRaw', 'size' ) ) ); }, ARRAY_FILTER_USE_KEY ), array( 'parts' => count( $ctxData[ 'oiFs' ] ) ),
				($sett[ 'debugInfo' ]??null) ? array( 'PLG_DIR' => __DIR__, '_SERVER' => $_SERVER ) : array()
			);

		_ProcessOutHdrTrace( $sett, true, $objSubType === null, $stateValidate, $debugData );
	}

	if( $bNotMdf )
		return( Gen::S_OK );

	$output = CacheDscDataOutput( $ctxData, $out );
	if( $output !== false )
		return( Gen::S_OK );

	@unlink( $seraph_accel_g_dscFile );
	return( Gen::E_FAIL );
}

function _GetCcf( $settCache, $oiCi, $encoding, $dataPath, $tmUpdate, $type, $dataComprExts )
{
	$ext = _GetDataFileEncExt( $encoding, true );
	if( $ext === null || !in_array( $ext, $dataComprExts ) )
		return( null );

	if( $type != 'html' )
		$dataPath .= '/' . $type;

	$oiCf = CacheCgf( $settCache, $dataPath, $oiCi, $type, $ext );

	CacheCts( $oiCf, $tmUpdate );
	return( array( 'path' => $oiCf, 'fmt' => $ext ) );
}

function _GetCcf2( $oiCfn, $encoding, $dataPath, $tmUpdate, $dataComprExts )
{
	$ext = _GetDataFileEncExt( $encoding, true );
	if( $ext === null || !in_array( $ext, $dataComprExts ) )
		return( null );

	$oiCf = $dataPath . '/' . $oiCfn . $ext;

	CacheCts( $oiCf, $tmUpdate );
	return( array( 'path' => $oiCf, 'fmt' => $ext ) );
}

function _GetCfc( $oiCf, $out = false )
{
	if( !$out )
		return( Gen::FileGetContents( $oiCf[ 'path' ] ) );

	$file = @fopen( $oiCf[ 'path' ], 'rb' );
	if( !$file )
		return( false );

	while( !@feof( $file ) && ( @connection_status() == 0 ) )
		CacheWriteOut( @fread( $file, 0x10000 ) );

	return( true );
}

function CacheDscGetDataCtxFirstFile( $settCache, $oiCi, &$ctxData, $dataPath, $tmUpdate, $type, $dataComprExts )
{
	$encoding = $ctxData[ 'encoding' ];

	$oiCf = _GetCcf( $settCache, $oiCi, $encoding, $dataPath, $tmUpdate, $type, $dataComprExts );
	if( $oiCf )
	{
		$ctxData[ 'compressedEncoding' ] = $encoding;
		return( $oiCf );
	}

	$ctxData[ 'recompress' ] = true;

	$encodings = array( '', 'gzip', 'deflate', 'compress', 'br' );
	if( !in_array( $encoding, $encodings ) )
		return( null );

	foreach( $encodings as $encoding )
	{
		$oiCf = _GetCcf( $settCache, $oiCi, $encoding, $dataPath, $tmUpdate, $type, $dataComprExts );
		if( $oiCf )
		{
			$ctxData[ 'compressedEncoding' ] = $encoding;
			return( $oiCf );
		}
	}

	return( null );
}

function CacheDscGetDataCtx( $settCache, $dsc, $encoding, $dataPath, $tmUpdate, $type )
{
	$oiCs = ($dsc[ 'p' ]??null);
	if( !is_array( $oiCs )  )
	{

		return( null );
	}

	$dataComprExts = Gen::GetArrField( $settCache, array( 'dataCompr' ), array() );
	if( empty( $dataComprExts ) )
		$dataComprExts[] = '';
	foreach( $dataComprExts as &$dataComprExt )
		$dataComprExt = _GetDataFileComprExt( $dataComprExt );

	$ctxData = array( 'encoding' => $encoding, 'recompress' => false, 'oiFs' => array() );
	if( $oiCs )
	{
		$oiCi = $oiCs[ 0 ];

		$oiCf = CacheDscGetDataCtxFirstFile( $settCache, $oiCi, $ctxData, $dataPath, $tmUpdate, $type, $dataComprExts );
		if( !$oiCf )
		{

			return( null );
		}

		$ctxData[ 'fmt' ] = $oiCf[ 'fmt' ];
	}
	else
		$ctxData[ 'fmt' ] = '';

	$fmt = $ctxData[ 'fmt' ];

	if( !$ctxData[ 'recompress' ] )
	{
		switch( $encoding )
		{
		case 'deflate':
			if( $fmt != '.deflu' )
				$ctxData[ 'recompress' ] = true;
			break;

		case 'compress':
			if( $fmt != '.deflu' )
				$ctxData[ 'recompress' ] = true;
			break;

		case 'gzip':
			if( $fmt != '.deflu' && count( $oiCs ) > 1 )
				$ctxData[ 'recompress' ] = true;
			break;

		case 'br':
			if( $fmt != '.brua' && count( $oiCs ) > 1 )
				$ctxData[ 'recompress' ] = true;
			break;

		}
	}

	if( defined( 'SERAPH_ACCEL_ADVCACHE_COMP' ) )
	{
		$ctxData[ 'recompress' ] = true;
		$encoding = '';
	}

	$recompress = $ctxData[ 'recompress' ];

	$size = 0;
	$contentLen = 0;
	$sizeRaw = 0;
	$content = '';

	for( $i = 0; $i < count( $oiCs ); $i++ )
	{
		if( $i )
		{
			$oiCi = $oiCs[ $i ];

			$oiCf = _GetCcf( $settCache, $oiCi, $ctxData[ 'compressedEncoding' ], $dataPath, $tmUpdate, $type, $dataComprExts );
			if( !$oiCf )
			{

				return( null );
			}
		}

		$ctxData[ 'oiFs' ][] = $oiCf;
		$oiCos = GetCacheCos( $oiCi );
		$size += $oiCos;

		if( $recompress )
		{
			$oiCd = _GetCfc( $oiCf );
			if( $oiCd === false || !CacheCvs( strlen( $oiCd ), $oiCos ) )
			{

				return( null );
			}

			$sizeRaw += strlen( $oiCd );

			switch( $fmt )
			{
			case '.gz':				$oiCd = @gzdecode( $oiCd ); break;
			case '.deflu':		$oiCd = @gzinflate( $oiCd . "\x03\0" ); break;
			case '.br':				$oiCd = Gen::CallFunc( 'brotli_uncompress', array( $oiCd ), false ); break;
			case '.brua':		$oiCd = Gen::CallFunc( 'brotli_uncompress', array( "\x6b\x00" . $oiCd . "\x03" ), false ); break;
			}

			if( $oiCd === false )
			{

				return( null );
			}

			$content .= $oiCd;
		}
		else
		{
			$oiCfs = @filesize( $oiCf[ 'path' ] );
			if( !CacheCvs( $oiCfs, $oiCos ) )
			{

				return( null );
			}
			$contentLen += $oiCfs;
		}

	}

	if( !$recompress )
	{
		switch( $encoding )
		{
		case 'deflate':
			if( $fmt == '.deflu' )
				$contentLen += 2;
			break;

		case 'compress':
			if( $fmt == '.deflu' )
				$contentLen += 2 + 2 + 4;
			break;

		case 'gzip':
			if( $fmt == '.deflu' )
				$contentLen += 10 + 2 + 4 + 4;
			break;

		case 'br':
			if( $fmt == '.brua' )
				$contentLen += 2 + 1;
			break;
		}

		$sizeRaw = $contentLen;
	}
	else
	{
		switch( $encoding )
		{
		case 'deflate':		$content = @gzdeflate( $content, 6 ); break;
		case 'compress':	$content = @gzcompress( $content, 6 ); break;
		case 'gzip':		$content = @gzencode( $content, 6 ); break;
		case 'br':			$content = Gen::CallFunc( 'brotli_compress', array( $content, 7 ), false ); break;
		}

		if( $content === false )
		{

			return( null );
		}

		$contentLen = strlen( $content );
	}

	$ctxData[ 'content' ] = $content;
	$ctxData[ 'contentLen' ] = $contentLen;
	$ctxData[ 'size' ] = $size;
	$ctxData[ 'sizeRaw' ] = $sizeRaw;
	$ctxData[ 'crc32' ] = $dsc[ 'c' ];
	$ctxData[ 'adler32' ] = $dsc[ 'a' ];
	return( $ctxData );
}

function CacheDscValidateDepsData( $sett, $dsc, $dataPath )
{
	static $g_aaCheckExt = array( 'css' => array( 'css' ), 'js' => array( 'js' ), 'img' => array( 'jpe','jpg','jpeg','png','gif','bmp', 'tiff', 'svg', 'webp','avif'  ) );

	$settCache = Gen::GetArrField( $sett, array( 'cache' ), array() );

	foreach( Gen::GetArrField( $dsc, array( 's' ), array() ) as $childType => $children )
	{
		$aCheckExt = ($g_aaCheckExt[ $childType ]??null);
		if( !$aCheckExt )
			continue;

		$dataPathSubType = $dataPath . '/' . $childType;
		foreach( $children as $childId )
		{
			$found = false;
			foreach( $aCheckExt as $fileExt )
			{
				if( !@file_exists( CacheCgf( $settCache, $dataPathSubType, $childId, $fileExt ) ) )
					continue;

				$found = true;
				break;
			}

			if( !$found )
			{
				if( ($sett[ 'log' ]??null) )
					LogWrite( 'Descriptor child "' . $childType . '" not found: ' . $childId, Ui::MsgErr, 'Errors' );
				return( false );
			}
		}
	}

	foreach( Gen::GetArrField( $dsc, array( 'b' ), array() ) as $idSubPart => $dscPart )
		if( !CacheDscValidateDepsData( $sett, $dscPart, $dataPath ) )
			return( false );

	return( true );
}

function CacheDscDataOutput( $ctxData, $out = true )
{
	$iubyvadkxs = $ctxData[ 'oiFs' ];
	$encoding = $ctxData[ 'encoding' ];
	$recompress = $ctxData[ 'recompress' ];
	$fmt = $ctxData[ 'fmt' ];

	if( $recompress )
	{
		$content = $ctxData[ 'content' ];
		if( !$out )
			return( $content );

		CacheWriteOut( $content );
		return( true );
	}

	$content = '';

	switch( $encoding )
	{
	case 'deflate':
		break;

	case 'compress':
		if( $fmt == '.deflu' )
		{
			$oiCd = "\x78\xDA";
			if( $out )
				CacheWriteOut( $oiCd );
			else
				$content .= $oiCd;
		}
		break;

	case 'gzip':
		if( $fmt == '.deflu' )
		{
			$oiCd = "\x1F\x8B\x08\0\0\0\0\0\x02\x0A";
			if( $out )
				CacheWriteOut( $oiCd );
			else
				$content .= $oiCd;
		}
		break;

	case 'br':
		if( $fmt == '.brua' )
		{
			$oiCd = "\x6b\x00";
			if( $out )
				CacheWriteOut( $oiCd );
			else
				$content .= $oiCd;
		}
		break;
	}

	foreach( $iubyvadkxs as $oiCf )
	{

		$oiCd = _GetCfc( $oiCf, $out );
		if( $oiCd === false )
			return( false );

		if( !$out )
			$content .= $oiCd;

	}

	switch( $encoding )
	{
	case 'deflate':
		if( $fmt == '.deflu' )
		{
			$oiCd = "\x03\0";
			if( $out )
				CacheWriteOut( $oiCd );
			else
				$content .= $oiCd;
		}
		break;

	case 'compress':
		if( $fmt == '.deflu' )
		{
			$oiCd = "\x03\0" . $ctxData[ 'adler32' ];
			if( $out )
				CacheWriteOut( $oiCd );
			else
				$content .= $oiCd;
		}
		break;

	case 'gzip':
		if( $fmt == '.deflu' )
		{
			$oiCd = "\x03\0" . $ctxData[ 'crc32' ] . pack( 'V', $ctxData[ 'size' ] );
			if( $out )
				CacheWriteOut( $oiCd );
			else
				$content .= $oiCd;
		}
		break;

	case 'br':
		if( $fmt == '.brua' )
		{
			$oiCd = "\x03";
			if( $out )
				CacheWriteOut( $oiCd );
			else
				$content .= $oiCd;
		}
		break;
	}

	if( !$out )
		return( $content );

	CacheWriteOut( $content );
	return( true );
}

function CacheWriteOut( $data )
{

	print( $data );
}

function CacheDscWriteCancel( $dscDel = true, $updTime = false )
{
	global $seraph_accel_g_dscFile;
	global $seraph_accel_g_dscFilePending;

	if( $updTime )
		@touch( $seraph_accel_g_dscFile );

	@unlink( $seraph_accel_g_dscFilePending );
	if( Gen::GetFileExt( $seraph_accel_g_dscFilePending ) == 'pp' )
		@unlink( substr( $seraph_accel_g_dscFilePending, 0, -1 ) );

	if( $dscDel && !$updTime )
	{

		@unlink( $seraph_accel_g_dscFile );
	}
}

function _CacheSetRequestToPrepareAsyncEx( $siteId, $method, $url, $hdrs, $tmp = false )
{

	if( !$siteId )
	{
		$urlProc = ProcessQueueItemCtx::AdjustRequestUrl( $url, Gen::GetCurRequestTime(), array() );

		$asyncMode = OnAsyncTasksPushGetMode();
		if( $asyncMode == 'ec' )
			ExtCron_AddUrlToQueue( $method, $urlProc, $hdrs );
		else

			ProcessQueueItemCtx::MakeRequest( $asyncMode, $method, $urlProc, $hdrs );
		return;
	}

	if( $tmp )
	{
		$urlProc = ProcessQueueItemCtx::AdjustRequestUrl( $url, Gen::GetCurRequestTime(), array( 'tmp' => true ) );

		$asyncMode = OnAsyncTasksPushGetMode();
		if( $asyncMode == 'ec' )
			ExtCron_AddUrlToQueue( $method, $urlProc, $hdrs );
		else

			ProcessQueueItemCtx::MakeRequest( $asyncMode, $method, $urlProc, $hdrs );
	}

	if( $tmp != 'only' && CachePostPreparePageEx( $method, $url, $siteId, 10, null, $hdrs ) )
		CachePushQueueProcessor();
}

function CacheSetCurRequestToPrepareAsync( $siteId, $tmp = false, $bgEnabled = false, $early = true )
{
	global $seraph_accel_g_simpCacheMode;

	$obj = new AnyObj();
	$obj -> method = strtoupper( ($_SERVER[ 'REQUEST_METHOD' ]??'GET') );
	$obj -> url = GetCurRequestUrl();
	if( $obj -> method == 'POST' )
	{
		$aRequestArg = array(); AddCurPostArgs( $aRequestArg );
		$obj -> url = Net::UrlAddArgs( $obj -> url, $aRequestArg );
	}

	$obj -> hdrs = Net::GetRequestHeaders();
	if( isset( $_SERVER[ 'SERAPH_ACCEL_ORIG_USER_AGENT' ] ) )
		$obj -> hdrs[ 'User-Agent' ] = $_SERVER[ 'SERAPH_ACCEL_ORIG_USER_AGENT' ];

	if( Gen::StrStartsWith( ( string )$seraph_accel_g_simpCacheMode, 'fragments' ) )
		$obj -> url = Net::UrlAddArgs( $obj -> url, array( 'seraph_accel_gf' => substr( $seraph_accel_g_simpCacheMode, 10 ) ) );

	if( !$bgEnabled && $siteId && !$tmp && $seraph_accel_g_simpCacheMode === null )
	{
		Gen::MakeDir( $fileTempQueue = GetCacheDir() . '/qt', true );
		if( $fileTempQueue = tempnam( $fileTempQueue, '' ) )
		{
			if( @file_put_contents( $fileTempQueue, @serialize( array( 'u' => $obj -> url, 's' => $siteId, 'p' => 10, 'h' => $obj -> hdrs, 't' => microtime( true ) ) ) ) !== false )
			{

				if( @rename( $fileTempQueue, $fileTempQueue . '.dat' ) )
				{

					return( true );
				}
				else
					@unlink( $fileTempQueue );
			}
			else
				@unlink( $fileTempQueue );
		}
	}

	if( !$early )
	{
		_CacheSetRequestToPrepareAsyncEx( $siteId, $obj -> method, $obj -> url, $obj -> hdrs, $tmp );
		return( false );
	}

	$obj -> siteId = $siteId;
	$obj -> tmp = $tmp;
	$obj -> cb = function( $obj ) { _CacheSetRequestToPrepareAsyncEx( $obj -> siteId, $obj -> method, $obj -> url, $obj -> hdrs, $obj -> tmp ); };
	add_action( 'muplugins_loaded', array( $obj, 'cb' ) , 0 );

	if( Wp::IsCronEnabled() )
		add_action( 'wp_loaded', function() { if( Wp::GetFilters( 'init', 'wp_cron' ) ) wp_cron(); exit(); }, -999999 );
	else
		add_action( 'muplugins_loaded', function() { exit(); }, 1 );

	return( false );
}

function _CacheContentStart( $tmCur, $procTmLim )
{
	global $seraph_accel_g_dscFile;
	global $seraph_accel_g_dscFilePending;

	for( $try = 1; $try <= 2; $try++ )
	{
		$stm = null;
		$hr = Gen::FileOpenWithMakeDir( $stm, $seraph_accel_g_dscFilePending, 'x' );
		if( $stm )
		{
			@fclose( $stm );
			break;
		}

		if( $try == 2 )
			return( false );

		$dscFilePendingTm = Gen::FileMTime( $seraph_accel_g_dscFilePending );
		if( $dscFilePendingTm !== false && ( $tmCur - $dscFilePendingTm < $procTmLim ) )
			return( false );

		@unlink( $seraph_accel_g_dscFilePending );
	}

	return( true );
}

function _CbContentProcess( $content )
{
	if( !function_exists( 'seraph_accel\\OnEarlyContentComplete' ) )
		return( $content );
	return( OnEarlyContentComplete( $content, true ) );
}

function _CbContentFinishSkip( $content )
{
	global $seraph_accel_g_dscFile;
	global $seraph_accel_g_dscFilePending;
	global $seraph_accel_g_dataPath;
	global $seraph_accel_g_cacheSkipData;
	global $seraph_accel_g_prepPrms;
	global $seraph_accel_g_lazyInvTmp;
	global $seraph_accel_g_bPrepContTmpToMain;
	global $seraph_accel_g_prepOrigContHash;
	global $seraph_accel_g_prepOrigCont;
	global $seraph_accel_g_cacheObjChildren;
	global $seraph_accel_g_cacheObjSubs;
	global $seraph_accel_g_siteId;
	global $seraph_accel_g_prepCont;
	global $seraph_accel_g_simpCacheMode;

	$sett = Plugin::SettGet();
	$settGlob = Plugin::SettGetGlobal();
	$settCache = Gen::GetArrField( $sett, array( 'cache' ), array() );

	@ignore_user_abort( true );

	$skipStatus = Gen::GetArrField( ($seraph_accel_g_cacheSkipData[ 1 ]??null), array( 'reason' ), '' );

	if( ($seraph_accel_g_prepPrms[ 'selfTest' ]??null) )
	{
		$content = 'selfTest-' . $seraph_accel_g_prepPrms[ 'selfTest' ];
		sleep( 5 );
	}

	if( $seraph_accel_g_cacheSkipData[ 0 ] === 'revalidating-begin' )
	{
		$beginPrepare = false;
		$updateTmpCache = false;
		$skipStatus = null;

		$cacheSkipData = $seraph_accel_g_cacheSkipData;
		$seraph_accel_g_cacheSkipData = null;
		if( !GetContCacheEarlySkipData( $pathOrig, $path, $pathIsDir, $args ) )
		{
			if( $skipStatus = ContProcGetSkipStatus( $content ) )
			{
				$seraph_accel_g_cacheSkipData = array( 'skipped', array( 'reason' => $skipStatus ) );
				if( $skipStatus == 'httpCode:500' || Gen::StrStartsWith( $skipStatus, 'err:php' ) )
					$beginPrepare = true;
			}
			else
			{
				$seraph_accel_g_cacheSkipData = $cacheSkipData;
				$beginPrepare = true;
				$updateTmpCache = $seraph_accel_g_lazyInvTmp;
			}
		}

		if( $beginPrepare )
		{
			if( $updateTmpCache )
			{
				$seraph_accel_g_dscFilePending = $seraph_accel_g_dscFilePending . 'p';

				$lock = new Lock( 'dl', GetCacheDir() );
				if( $seraph_accel_g_dataPath && $lock -> Acquire() )
				{
					if( _CacheContentStart( Gen::GetCurRequestTime(), Gen::GetArrField( $settCache, array( 'procTmLim' ), 570 ) ) )
					{
						$lock -> Release();

						CacheDscUpdate( $lock, $settCache, $content, $seraph_accel_g_cacheObjChildren, $seraph_accel_g_cacheObjSubs, $seraph_accel_g_dataPath, $seraph_accel_g_bPrepContTmpToMain ? false : true, $seraph_accel_g_prepOrigCont, $seraph_accel_g_prepOrigContHash );
						CacheAdditional_UpdateCurUrl( $settCache, true );
					}
					else
						$lock -> Release();
				}
				unset( $lock );
				$seraph_accel_g_dscFilePending = substr( $seraph_accel_g_dscFilePending, 0, -1 );

				if( Gen::GetArrField( $settCache, array( 'fastTmpOpt' ), false ) && Gen::GetArrField( $settCache, array( 'chunks', 'enable' ) ) )
					ContentMarkSeparateSofter( $content, true );
			}

			if( ($settCache[ 'updByTimeout' ]??null) )
				CacheSetCurRequestToPrepareAsync( $seraph_accel_g_siteId, false, false, false );
		}
		else
		{

			CacheDscWriteCancel( true, $skipStatus === 'notChanged' );
			if( $skipStatus !== 'notChanged' )
				CacheAdditional_UpdateCurUrl( $settCache );
		}
	}

	if( $skipStatus === 'notChanged' )
	{
		$content = '';
		if( $dsc = CacheReadDsc( $seraph_accel_g_dscFile ) )
		{
			$dscFileTm = Gen::FileMTime( $seraph_accel_g_dscFile );
			_ProcessOutCachedData( $seraph_accel_g_simpCacheMode === null, null, $settGlob, $sett, $settCache, $dsc, $dscFileTm, $dscFileTm, 'revalidated', 'notChanged', false, $content );
		}
		else
			_ProcessOutHdrTrace( $sett, true, true, 'skipped', array( 'reason' => 'brokenDsc' ), $seraph_accel_g_dscFile );
	}
	else
		_ProcessOutHdrTrace( $sett, true, true, $seraph_accel_g_cacheSkipData[ 0 ], ($seraph_accel_g_cacheSkipData[ 1 ]??null) );

	if( Gen::GetArrField( $settCache, array( 'chunks', 'enable' ) ) && GetContentProcessorForce( $sett ) )
		ContentMarkSeparateSofter( $content, false );

	if( $seraph_accel_g_prepPrms !== null )
	{
		ProcessCtlData_Update( ($seraph_accel_g_prepPrms[ 'pc' ]??null), array_merge( array( 'finish' => true, 'skip' => $skipStatus ), ($sett[ 'debugInfo' ]??null) ? array( 'infos' => array( LocId::Pack( 'SrvArgs' ) => PackKvArrInfo( $_SERVER ) ) ) : array() ), false, false );

		$httpCode = http_response_code();
		if( $httpCode >= 300 && $httpCode < 400 )
			http_response_code( 200 );
	}

	return( $content );
}

function _CbContentFinish( $content )
{
	return( _CbContentFinishEx( $content ) );
}

function _CbContentFinishEx( $content, $urlCur = null, $serverArgs = null )
{
	global $post;

	global $seraph_accel_g_dscFile;
	global $seraph_accel_g_dscFilePending;
	global $seraph_accel_g_dataPath;
	global $seraph_accel_g_noFo;
	global $seraph_accel_g_cacheObjChildren;
	global $seraph_accel_g_cacheObjSubs;
	global $seraph_accel_g_prepPrms;
	global $seraph_accel_g_prepCont;
	global $seraph_accel_g_bPrepContTmpToMain;
	global $seraph_accel_g_prepOrigContHash;
	global $seraph_accel_g_prepOrigCont;
	global $seraph_accel_g_prepLearnId;
	global $seraph_accel_g_simpCacheMode;
	global $seraph_accel_g_ctxProcess;

	if( $serverArgs === null )
		$serverArgs = $_SERVER;

	$sett = Plugin::SettGet();
	$settGlob = Plugin::SettGetGlobal();
	$settCache = Gen::GetArrField( $sett, array( 'cache' ), array() );

	$skipStatus = ContProcGetSkipStatus( $content );
	if( !$skipStatus && ContentProcess_IsAborted() )
		$skipStatus = 'aborted';

	$asyncMode = OnAsyncTasksPushGetMode( $settGlob );

	if( $skipStatus )
	{
		if( ( $skipStatus == 'noHdrOrBody' || Gen::StrStartsWith( $skipStatus, 'err:contTermEarly' ) )  && ( $asyncMode == 'ec' || ($settGlob[ 'asyncSmpOpt' ]??null) ) )
		{
			if( $urlCur === null )
				$urlCur = GetCurRequestUrl();
			if( !Gen::StrEndsWith( $urlCur, '/' ) )
				$skipStatus = 'httpCode:301:' . rawurlencode( $urlCur . '/' );
		}

		if( $seraph_accel_g_prepPrms !== null )
		{

			if( $skipStatus == 'remote' )
			{

				return( '' );
			}

			$httpCode = http_response_code();
			if( $httpCode >= 300 && $httpCode < 400 )
				http_response_code( 200 );

		}

		if( !$seraph_accel_g_noFo && $skipStatus !== 'notChanged' )
			_ProcessOutHdrTrace( $sett, true, true, 'skipped', array( 'reason' => $skipStatus ) );

		CacheDscWriteCancel( $skipStatus !== 'aborted' && !Gen::StrStartsWith( $skipStatus, 'lrnNeed' ), $skipStatus === 'notChanged' );

		if( $skipStatus !== 'aborted' && !Gen::StrStartsWith( $skipStatus, 'lrnNeed' ) && $skipStatus !== 'notChanged' )
			CacheAdditional_UpdateCurUrl( $settCache, false, $urlCur );

		if( $seraph_accel_g_prepPrms !== null )
			ProcessCtlData_Update( ($seraph_accel_g_prepPrms[ 'pc' ]??null), array_merge( array( 'finish' => true, 'skip' => $skipStatus ), ($sett[ 'debugInfo' ]??null) ? array( 'infos' => array( LocId::Pack( 'SrvArgs' ) => PackKvArrInfo( $serverArgs ) ) ) : array() ), false, false );

		if( Gen::GetArrField( $settCache, array( 'chunks', 'enable' ) ) && GetContentProcessorForce( $sett ) )
			ContentMarkSeparateSofter( $content, false );

		if( $seraph_accel_g_noFo )
			return( '' );

		if( $skipStatus === 'notChanged' )
		{
			$content = '';
			if( $dsc = CacheReadDsc( $seraph_accel_g_dscFile ) )
			{
				$dscFileTm = Gen::FileMTime( $seraph_accel_g_dscFile );
				_ProcessOutCachedData( $seraph_accel_g_simpCacheMode === null, null, $settGlob, $sett, $settCache, $dsc, $dscFileTm, $dscFileTm, 'revalidated', 'notChanged', false, $content );
			}
			else
				_ProcessOutHdrTrace( $sett, true, true, 'skipped', array( 'reason' => 'brokenDsc' ), $seraph_accel_g_dscFile );
		}

		return( $content );
	}

	$lock = new Lock( 'dl', GetCacheDir() );
	$dsc = CacheDscUpdate( $lock, $settCache, $content, $seraph_accel_g_cacheObjChildren, $seraph_accel_g_cacheObjSubs, $seraph_accel_g_dataPath, $seraph_accel_g_bPrepContTmpToMain ? false : Gen::GetArrField( $seraph_accel_g_prepPrms, array( 'tmp' ) ), $seraph_accel_g_prepOrigCont, $seraph_accel_g_prepOrigContHash, $seraph_accel_g_prepLearnId );
	unset( $lock );

	if( !$dsc )
	{
		$skipStatus = 'dscFileUpdateError';

		if( !$seraph_accel_g_noFo )
			_ProcessOutHdrTrace( $sett, true, true, 'skipped', array( 'reason' => $skipStatus ), $seraph_accel_g_dscFile );

		if( $seraph_accel_g_prepPrms !== null )
		{
			if( Gen::LastErrDsc_Is() )
				$skipStatus .= ':' . rawurlencode( Gen::LastErrDsc_Get() );
			ProcessCtlData_Update( ($seraph_accel_g_prepPrms[ 'pc' ]??null), array_merge( array( 'finish' => true, 'skip' => $skipStatus ), ($sett[ 'debugInfo' ]??null) ? array( 'infos' => array( LocId::Pack( 'SrvArgs' ) => PackKvArrInfo( $serverArgs ) ) ) : array() ), false, false );
		}

		return( $content );
	}

	CacheAdditional_UpdateCurUrl( $settCache, true, $urlCur );

	if( $seraph_accel_g_prepPrms !== null )
		ProcessCtlData_Update( ($seraph_accel_g_prepPrms[ 'pc' ]??null), array_merge( array( 'finish' => true, 'warns' => LastWarnDscs_Get() ), ($sett[ 'debugInfo' ]??null) ? array( 'infos' => array( LocId::Pack( 'ProcStat' ) => PackKvArrInfo( ($seraph_accel_g_ctxProcess[ 'stat' ]??null) ), LocId::Pack( 'SrvArgs' ) => PackKvArrInfo( $serverArgs ) ) ) : array() ), false, false );

	if( $seraph_accel_g_noFo )
		return( '' );

	$content = '';
	$dscFileTm = Gen::FileMTime( $seraph_accel_g_dscFile );
	_ProcessOutCachedData( $seraph_accel_g_simpCacheMode === null, null, $settGlob, $sett, $settCache, $dsc, $dscFileTm, $dscFileTm, 'revalidated', null, false, $content );
	return( $content );
}

function GetCacheViewId( $ctxCache, $settCache, $userAgent, $path, $pathOrig, &$args, $bFreshParts = false, &$aCookieMatched = null )
{
	$ctxCache -> viewStateId = '';
	$ctxCache -> viewGeoId = '';

	$type = 'cmn';
	if( ($settCache[ 'normAgent' ]??null) )
	{
		$_SERVER[ 'SERAPH_ACCEL_ORIG_USER_AGENT' ] = ($_SERVER[ 'HTTP_USER_AGENT' ]??'');
		$_SERVER[ 'HTTP_USER_AGENT' ] = 'Mozilla/99999.9 AppleWebKit/9999999.99 (KHTML, like Gecko) Chrome/999999.0.9999.99 Safari/9999999.99 Seraph-Accel-Agent/2.29.11';
	}

	if( ($settCache[ 'views' ]??null) )
	{
		if( $viewsDeviceGrp = GetCacheViewDeviceGrp( $settCache, $userAgent ) )
		{
			$type = ($viewsDeviceGrp[ 'id' ]??null);
			if( ($settCache[ 'normAgent' ]??null) )
				$_SERVER[ 'HTTP_USER_AGENT' ] = GetViewTypeUserAgent( $viewsDeviceGrp );
		}

		$aCurHdr = null;

		$viewsGrps = Gen::GetArrField( $settCache, array( 'viewsGrps' ), array() );
		foreach( $viewsGrps as $viewsGrp )
		{
			if( !($viewsGrp[ 'enable' ]??null) )
				continue;

			if( ($viewsGrp[ 'fr' ]??null) && !$bFreshParts )
				continue;

			if( CheckPathInUriList( Gen::GetArrField( $viewsGrp, array( 'urisExcl' ), array() ), $path, $pathOrig ) )
				continue;

			AccomulateCookiesState( $ctxCache -> viewStateId, $_COOKIE, Gen::GetArrField( $viewsGrp, array( 'cookies' ), array() ), $aCookieMatched );
			AccomulateHdrsState( $ctxCache -> viewStateId, $aCurHdr, Gen::GetArrField( $viewsGrp, array( 'hdrs' ), array() ) );

			$viewsArgs = Gen::GetArrField( $viewsGrp, array( 'args' ), array() );
			foreach( $viewsArgs as $a )
			{
				foreach( $args as $argKey => $argVal )
				{
					if( strpos( $argKey, $a ) === 0 )
					{
						$ctxCache -> viewStateId .= $argKey . $argVal;
						unset( $args[ $argKey ] );
					}
				}
			}
		}

		if( Gen::GetArrField( $settCache, array( 'viewsGeo', 'enable' ) ) )
		{
			$ctxCache -> viewGeoId = ($_SERVER[ 'HTTP_X_SERAPH_ACCEL_GEOID' ]??null);
			if( !is_string( $ctxCache -> viewGeoId ) )
			{
				$ip = Net::GetRequestIp();
				$ctxCache -> viewGeoId = GetViewGeoId( $settCache, $_SERVER, $ip );

				$_SERVER[ 'HTTP_X_SERAPH_ACCEL_GEOID' ] = $ctxCache -> viewGeoId;
				$_SERVER[ 'HTTP_X_SERAPH_ACCEL_GEO_REMOTE_ADDR' ] = $_SERVER[ 'REMOTE_ADDR' ] = $_SERVER[ 'HTTP_X_REAL_IP' ] = $_SERVER[ 'HTTP_X_FORWARDED_FOR' ] = $ip;
			}
			else if( isset( $_SERVER[ 'HTTP_X_SERAPH_ACCEL_GEO_REMOTE_ADDR' ] ) )
				$_SERVER[ 'REMOTE_ADDR' ] = $_SERVER[ 'HTTP_X_REAL_IP' ] = $_SERVER[ 'HTTP_X_FORWARDED_FOR' ] = $_SERVER[ 'HTTP_X_SERAPH_ACCEL_GEO_REMOTE_ADDR' ];
		}
	}

	$ctxCache -> viewType = $type;

	if( strlen( $ctxCache -> viewGeoId ) )
		$type .= '-' . $ctxCache -> viewGeoId;

	{
		$serverArgsTmp = Gen::ArrCopy( $_SERVER ); CorrectRequestScheme( $serverArgsTmp, 'client' );
		if( ($serverArgsTmp[ 'REQUEST_SCHEME' ]??null) == 'http' )
		{
			$type .= '-ns';
			$ctxCache -> viewNonSecure = true;
		}
	}

	$ctxCache -> viewCompatId = ContProcIsCompatView( $settCache, $userAgent );
	if( $ctxCache -> viewCompatId )
	{
		$type .= '-' . $ctxCache -> viewCompatId;

		if( ($settCache[ 'normAgent' ]??null) )
			$_SERVER[ 'HTTP_USER_AGENT' ] = $_SERVER[ 'SERAPH_ACCEL_ORIG_USER_AGENT' ];
	}

	if( strlen( $ctxCache -> viewStateId ) )
	{
		$ctxCache -> viewStateId = md5( $ctxCache -> viewStateId );
		$type .= '-' . $ctxCache -> viewStateId;
	}

	return( $type );
}

