<?php



namespace seraph_accel;



if( !defined( 'ABSPATH' ) )

	exit;



require( __DIR__ . '/htmlparser.php' );

require( __DIR__ . '/content_img.php' );

require( __DIR__ . '/content_js.php' );

require( __DIR__ . '/content_css.php' );

require( __DIR__ . '/content_frm.php' );



spl_autoload_register(

	function( $class )

	{

		if( strpos( $class, 'seraph_accel\\DomElementEx' ) === 0 || strpos( $class, 'seraph_accel\\XPathEx_MatchAll' ) === 0 || strpos( $class, 'seraph_accel\\LazyCont_XpathExtFunc_FollowingSiblingUpToParent_Iterator' ) === 0 )

			@include_once( __DIR__ . '/content_ex.php' );

		if( strpos( $class, 'seraph_accel\\CssToXPathNormalizedAttributeMatchingExtension' ) === 0 || strpos( $class, 'seraph_accel\\CssToXPathHtmlExtension' ) === 0 || strpos( $class, 'seraph_accel\\CssSelFs' ) === 0 )

			@include_once( __DIR__ . '/content_css_ex.php' );

		if( strpos( $class, 'seraph_accel\\Symfony\\Component\\CssSelector\\' ) === 0 )

			@include_once( __DIR__ . '/Cmn/Ext/PHP/symfony-css-selector/' . str_replace( '\\', '/', substr( $class, 43 ) ) . '.php' );



		if( strpos( $class, 'seraph_accel\\tubalmartin\\CssMin' ) === 0 )

			@include_once( __DIR__ . '/Cmn/Ext/PHP/YUI-CSS-compressor-PHP-port/' . str_replace( '\\', '/', substr( $class, 32 ) ) . '.php' );

		if( strpos( $class, 'seraph_accel\\Sabberworm\\CSS' ) === 0 )

			@include_once( __DIR__ . '/Cmn/Ext/PHP/php-css-parser/' . str_replace( '\\', '/', substr( $class, 28 ) ) . '.php' );



		if( strpos( $class, 'seraph_accel\\JSMin\\' ) === 0 )

			@include_once( __DIR__ . '/Cmn/Ext/PHP/jsmin-php/' . str_replace( '\\', '/', substr( $class, 19 ) ) . '.php' );

		if( strpos( $class, 'seraph_accel\\JShrink\\' ) === 0 )

			@include_once( __DIR__ . '/Cmn/Ext/PHP/JShrink/' . str_replace( '\\', '/', substr( $class, 21 ) ) . '.php' );

	}

);



function ContentDisableIndexingEx( $buffer )

{

	$posHead = Ui::TagBeginGetPos( array( 'head', 'HEAD' ), $buffer );

	if( $posHead )

		$buffer = substr( $buffer, 0, $posHead[ 1 ] ) . Ui::TagOpen( 'meta', array( 'name' => 'robots', 'content' => 'noindex' ) ) . substr( $buffer, $posHead[ 1 ] );

	return( $buffer );

}



function ContentDisableIndexing()

{

	ob_start( 'seraph_accel\\ContentDisableIndexingEx' );

}



function InitContentProcessor( $sett )

{



	add_action( 'init', 'seraph_accel\\_InitContentProcessor', 0 );

}



function _ContentProcessor_TmpCont_SettImg_Adjust( &$settImg )

{



	Gen::SetArrField( $settImg, array( 'inlSml' ), false );

	Gen::SetArrField( $settImg, array( 'deinlLrg' ), false );



}



function _InitContentProcessor()

{

	global $seraph_accel_g_prepPrms;

	global $seraph_accel_g_prepCont;

	global $seraph_accel_g_simpCacheMode;



	$siteId = GetSiteId();

	$sett = Plugin::SettGet();

	$settContPr = Gen::GetArrField( $sett, array( 'contPr' ), array() );

	$tmCur = Gen::GetCurRequestTime();



	$seraph_accel_g_prepCont = false;



	$prepContSpecStage = 'full';

	if( is_string( $seraph_accel_g_simpCacheMode ) )

	{

		if( Gen::StrStartsWith( $seraph_accel_g_simpCacheMode, 'fragments' ) )

			$prepContSpecStage = 'fragments';

		else if( Gen::StrStartsWith( $seraph_accel_g_simpCacheMode, 'data:' ) )

			return;

	}



	$settContPrOverride = GetContentProcessorForce( $sett );



	if( ($_REQUEST[ 'seraph_accel_at' ]??null) == 'ORC' )

	{

	}

	else if( !$settContPrOverride )

	{

		if( $cacheSkipData = GetContCacheEarlySkipData( $pathOrig, $path, $pathIsDir, $args ) )

		{

			if( $cacheSkipData[ 0 ] == 'revalidating-begin' )

				$prepContSpecStage = 'tmp';

			else if( $seraph_accel_g_prepPrms !== null && ($seraph_accel_g_prepPrms[ 'selfTest' ]??null) )

			{

			}

			else if( !Gen::StrStartsWith( ( string )$seraph_accel_g_simpCacheMode, 'fragments' ) )

			{



				BatCache_DontProcessCurRequest();



				return;

			}



			unset( $cacheSkipData );

		}



		if( $seraph_accel_g_prepPrms !== null && ($seraph_accel_g_prepPrms[ 'tmp' ]??null) )

			$prepContSpecStage = 'tmpLong';



		$settCache = Gen::GetArrField( $sett, array( 'cache' ), array() );



		if( ($settCache[ 'enable' ]??null) && !function_exists( 'seraph_accel_siteSettInlineDetach' ) )

			return;



		if( ContProcGetExclStatus( $siteId, $settCache, $path, $pathOrig, $pathIsDir, $_GET, $varsOut, false, !($settCache[ 'enable' ]??null) ) )

			return;



		unset( $varsOut );



		if( Gen::GetArrField( $settContPr, array( 'enable' ), false )&& PluginLic::CheckFeature() != Gen::S_OK && lfjikztqjqji( $siteId, $tmCur, false ) )

			return;



	}

	else

	{

		add_action( 'wp_loaded',

			function()

			{

				if( ($_REQUEST[ 'd' ]??null) == 'phpinfo' )

				{

					phpinfo();

					exit;

				}



				if( ($_REQUEST[ 'd' ]??null) == 'opcache_reset' )

				{



					echo( '<pre>PluginRe::OpCacheReset(), dir \'' . ( string )PluginRe::GetOpCacheDir() . '\': ' . sprintf( '0x%08X', PluginRe::OpCacheReset() ) . '</pre>' );



					if( function_exists( 'opcache_reset' ) )

						echo( '<pre>opcache_reset(): ' . ( @opcache_reset() ? 'OK' : 'FALSE' ) . '</pre>' );

					else

						echo( '<pre>opcache_reset(): doesnt exist</pre>' );



					exit;

				}



				if( ($_REQUEST[ 'd' ]??null) == 'delcache' )

				{

					$hr = Gen::DelDir( GetCacheDir(), false );

					echo( '<pre>res: ' . sprintf( '0x%08X', $hr ) . '</pre>' );

					exit;

				}



				if( ($_REQUEST[ 'd' ]??null) == 'info' )

				{

					$aTestRes = array();

					$aTestRes[ 'roots' ][ 'siteRootUrl' ] = Wp::GetSiteRootUrl();

					$aTestRes[ 'roots' ][ 'siteRootUrl-base' ] = Wp::GetSiteRootUrl( '', 'base' );

					$aTestRes[ 'roots' ][ 'siteWpRoot1' ] = Wp::GetSiteWpRootUrl( '', null, true );

					$aTestRes[ 'roots' ][ 'siteWpRoot2' ] = Wp::GetSiteWpRootUrl();



					$aTestRes[ 're' ][ 'launcher' ] = PluginRe::_GetPhpLauncher();

					$aTestRes[ 're' ][ 'phpExtensionDir' ] = ini_get( 'extension_dir' );



					$aTestRes[ 'defines' ][ 'SERAPH_ACCEL_CACHE_DIR' ] = Gen::Constant( 'SERAPH_ACCEL_CACHE_DIR' );

					$aTestRes[ 'defines' ][ 'SERAPH_ACCEL_SALT' ] = Gen::Constant( 'SERAPH_ACCEL_SALT' );

					$aTestRes[ 'defines' ][ 'SERAPH_SECRET_KEY' ] = Gen::Constant( 'SERAPH_SECRET_KEY' );

					$aTestRes[ 'defines' ][ 'SERAPH_ACCEL_SITEROOT_DIR' ] = Gen::Constant( 'SERAPH_ACCEL_SITEROOT_DIR' );

					$aTestRes[ 'defines' ][ 'SERAPH_ACCEL_ALT_ROOTS' ] = Gen::Constant( 'SERAPH_ACCEL_ALT_ROOTS' );

					$aTestRes[ 'defines' ][ 'NONCE_SALT' ] = Gen::Constant( 'NONCE_SALT' );

					$aTestRes[ 'defines' ][ 'WP_CACHE' ] = Gen::Constant( 'WP_CACHE' );



					$aTestRes[ 'ABSPATH' ][ 'path' ] = ABSPATH;

					$aTestRes[ 'ABSPATH' ][ 'isWritabble' ] = @is_writable( $aTestRes[ 'ABSPATH' ][ 'path' ] );



					$aTestRes[ 'WP_CONTENT_DIR' ][ 'path' ] = WP_CONTENT_DIR;

					$aTestRes[ 'WP_CONTENT_DIR' ][ 'isWritabble' ] = @is_writable( $aTestRes[ 'WP_CONTENT_DIR' ][ 'path' ] );



					$aTestRes[ 'WP_TEMP_DIR' ][ 'path' ] = Gen::Constant( 'WP_TEMP_DIR' );

					$aTestRes[ 'WP_TEMP_DIR' ][ 'isWritabble' ] = $aTestRes[ 'WP_TEMP_DIR' ][ 'path' ] ? @is_writable( $aTestRes[ 'WP_TEMP_DIR' ][ 'path' ] ) : false;



					$aTestRes[ 'temp_dir' ][ 'path' ] = Gen::GetTempDir();

					$aTestRes[ 'temp_dir' ][ 'isWritabble' ] = @is_writable( $aTestRes[ 'temp_dir' ][ 'path' ] );



					$aTestRes[ 'htaccess' ][ 'file' ] = Gen::HtAccess_GetFilePath();

					$aTestRes[ 'htaccess' ][ 'isWritabble' ] = @is_writable( $aTestRes[ 'htaccess' ][ 'file' ] );



					$aTestRes[ 'wp-config' ][ 'file' ] = Wp::GetConfigFilePath();

					$aTestRes[ 'wp-config' ][ 'isWritabble' ] = @is_writable( $aTestRes[ 'wp-config' ][ 'file' ] );



					$aTestRes[ 'advanced-cache' ][ 'file' ] = WP_CONTENT_DIR . '/advanced-cache.php';

					$aTestRes[ 'advanced-cache' ][ 'isWritabble' ] = @is_writable( $aTestRes[ 'advanced-cache' ][ 'file' ] );



					$aTestRes[ 'ctx' ] = GetContentProcessCtx( $_SERVER, Plugin::SettGet() );

					unset( $aTestRes[ 'ctx' ][ 'cbs' ] );



					$aContBlock = array(

						'General'							=> Ui::Tag( 'pre', htmlentities( str_replace( '\\/', '/', json_encode( $aTestRes, JSON_PRETTY_PRINT ) ), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401 ) ),

						'Post types'						=> Ui::Tag( 'pre', htmlentities( str_replace( '\\/', '/', json_encode( get_post_types( array(), 'objects' ), JSON_PRETTY_PRINT ) ), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401 ) ),

						'Taxonomies'						=> Ui::Tag( 'pre', htmlentities( str_replace( '\\/', '/', json_encode( get_taxonomies( array(), 'objects' ), JSON_PRETTY_PRINT ) ), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401 ) ),

						'Content of \'.htaccess\''			=> Ui::Tag( 'pre', htmlentities( ( string )Gen::FileGetContents( $aTestRes[ 'htaccess' ][ 'file' ] ) ) ),

						'Content of \'wp-config.php\''		=> Ui::Tag( 'pre', htmlentities( ( string )Gen::FileGetContents( $aTestRes[ 'wp-config' ][ 'file' ] ) ) ),

						'Content of \'advanced-cache.php\''	=> Ui::Tag( 'pre', htmlentities( ( string )Gen::FileGetContents( $aTestRes[ 'advanced-cache' ][ 'file' ] ) ) ),



					);



					$fnName2Slug = function( $v )

					{

						return( preg_replace( '@\W@', '_', strtolower( $v ) ) );

					};



					echo( Ui::Tag( 'style', 'pre{tab-size:4;}h2{margin-top:2em;}' ) );



					echo( Ui::Tag( 'h1', 'Info' ) );

					foreach( $aContBlock as $contBlockName => $contBlockCont )

						echo( Ui::Link( $contBlockName, '#' . $fnName2Slug( $contBlockName ) ) . '<br>' );



					foreach( $aContBlock as $contBlockName => $contBlockCont )

						echo( Ui::Tag( 'h2', $contBlockName, array( 'id' => $fnName2Slug( $contBlockName ) ) ) . $contBlockCont );



					exit;

				}

			}

		, 99999 );



		ApplyContentProcessorForceSett( $sett, $settContPrOverride );

		Plugin::SettSet( $sett, true );

	}



	if( $seraph_accel_g_prepPrms !== null )

		Wp::RemoveFilters( 'init', 'wp_cron' );



	$seraph_accel_g_prepCont = $prepContSpecStage;



	global $seraph_accel_g_ahuddqrText;



	if( PluginLic::CheckFeature() != Gen::S_OK )

	{

		$seraph_accel_g_ahuddqrText = array(

			'Title' => Wp::GetLocString( 'Seraphinite Accelerator', null, 'seraphinite-accelerator' ),

			'Descr' => Wp::GetLocString( 'Turns on site high speed to be attractive for people and search engines.', null, 'seraphinite-accelerator' ),

			'BannerText_%s' => __( 'BannerText_%s', 'seraphinite-accelerator' ),

		);

	}



	{

		if( defined( 'EZOIC__PLUGIN_NAME' ) )

		{

			Wp::RemoveFilters( 'shutdown', array( 'Ezoic_Namespace\\Ezoic_Integration_Public', 'ez_buffer_end' ) );

			Wp::RemoveFilters( 'shutdown', array( 'Ezoic_Namespace\\Ezoic_Wp_Integration', 'ez_buffer_end' ) );



			add_filter( 'seraph_accel_content_pre',

				function( $buffer )

				{

					if( !Gen::DoesFuncExist( '\\Ezoic_Namespace\\Ezoic_Integration_WP_Request::get_content_response_from_ezoic' ) || !Gen::DoesFuncExist( '\\Ezoic_Namespace\\Ezoic_Integration_WP_Response::handle_ezoic_response' ) )

						return( $buffer );



					$ez_request = new \Ezoic_Namespace\Ezoic_Integration_WP_Request();

					$ez_response = new \Ezoic_Namespace\Ezoic_Integration_WP_Response();

					return( $ez_response -> handle_ezoic_response( $buffer, $ez_request -> get_content_response_from_ezoic( $buffer ) ) );

				}

			);

		}



		if( defined( 'HMWP_VERSION' ) )

		{

			$model = Gen::GetArrField( Wp::GetFilters( 'plugins_url', array( 'HMWP_Models_Rewrite', 'plugin_url' ) ), array( 0, 'f', 0 ) );

			if( $model && Gen::DoesFuncExist( 'HMWP_Models_Rewrite::find_replace' ) )

			{

				$ctx = new AnyObj();

				$ctx -> model = $model;

				$ctx -> cbAdjustSepCont =

					function( $ctx, $content, $isFile = true )

					{

						if( !$isFile )

							return( $content );



						$aFlt = Wp::RemoveFilters( 'hmwp_process_find_replace' );

						$content = $ctx -> model -> find_replace( $content );

						Wp::AddFilters( $aFlt );

						return( $content );

					}

				;



				add_filter( 'hmwp_process_buffer', '__return_false', 99999 );

				add_filter( 'hmwp_process_find_replace', '__return_false', 99999 );

				add_filter( 'seraph_accel_content', array( $ctx, 'cbAdjustSepCont' ) );

				add_filter( 'seraph_accel_css_content', array( $ctx, 'cbAdjustSepCont' ), 10, 2 );

				add_filter( 'seraph_accel_js_content', array( $ctx, 'cbAdjustSepCont' ), 10, 2 );

				add_filter( 'seraph_accel_html_content', array( $ctx, 'cbAdjustSepCont' ), 10, 2 );



			}

			unset( $model, $ctx );

		}



		if( Gen::DoesFuncExist( '\\WPH::proces_html_buffer' ) )

		{

			add_filter( 'wph/components/css_combine_code',     function( $ignore ) { return false; }, 99999 );

			add_filter( 'wph/components/js_combine_code',     function( $ignore ) { return false; }, 99999 );



			add_filter( 'wp-hide/ignore_ob_start_callback',     function( $ignore ) { return true; }, 99999 );



			$ctx = new AnyObj();

			$ctx -> cbAdjustSepCont =

				function( $ctx, $content, $isFile = true )

				{

					global $wph;



					if( !$isFile || !$wph || ($wph -> ob_callback_late??null) )

						return( $content );



					$content = $wph -> proces_html_buffer( $content );

					return( $content );

				}

			;



			add_filter( 'seraph_accel_content', array( $ctx, 'cbAdjustSepCont' ) );

			add_filter( 'seraph_accel_css_content', array( $ctx, 'cbAdjustSepCont' ), 10, 2 );

			add_filter( 'seraph_accel_js_content', array( $ctx, 'cbAdjustSepCont' ), 10, 2 );

			add_filter( 'seraph_accel_html_content', array( $ctx, 'cbAdjustSepCont' ), 10, 2 );



			unset( $ctx );

		}



		if( class_exists( 'WebpConverter\\WebpConverter' ) )

		{

			add_action( 'init', function() { Wp::RemoveFilters( 'init', array( 'WebpConverter\\Loader\\HtaccessBypassingLoader', 'start_buffering' ) ); }, 1 );



		}



		if( defined( 'WPSHIELD_CPP_PATH' ) )

		{

			Wp::RemoveFilters( 'init', array( 'WPShield\\Plugin\\ContentProtectorPro\\ContentProtectorSetup', 'buffer_start' ) );

			Wp::RemoveFilters( 'shutdown', array( 'WPShield\\Plugin\\ContentProtectorPro\\ContentProtectorSetup', 'buffer_end' ) );

			add_filter( 'seraph_accel_content', function( $content ) { return( apply_filters( 'wpshield/content-protector-pro/buffer/end/content', $content ) ); } );

		}



		if( function_exists( 'jet_elements' ) )

			Wp::RemoveFilters( 'init', array( 'Jet_Elements_Download_Handler', 'process_download' ) );

	}



	{



		if( Gen::GetArrField( $settContPr, array( 'img', 'lazy', 'load' ), false, '/' ) )

			add_filter( 'wp_lazy_loading_enabled', function( $default, $tag_name ) { return( ( $tag_name == 'img' || $tag_name == 'picture' ) ? false : $default ); }, 10, 2 );

		if( Gen::GetArrField( $settContPr, array( 'frm', 'lazy', 'enable' ), false, '/' ) )

			add_filter( 'wp_lazy_loading_enabled', function( $default, $tag_name ) { return( ( $tag_name == 'iframe' ) ? false : $default ); }, 10, 2 );

	}



	$settImg = Gen::GetArrField( $settContPr, array( 'img' ), array() );

	$settCdn = Gen::GetArrField( $settContPr, array( 'cdn' ), array() );



	if( Gen::GetArrField( $settImg, array( 'sysFlt' ), false ) && ( Gen::GetArrField( $settImg, array( 'srcAddLm' ), false ) || Gen::GetArrField( $settCdn, array( 'enable' ), false ) ) )

	{



		add_filter( 'wp_get_attachment_url',

			function( $url, $post_id )

			{

				if( !$url )

					return( $url );



				$sett = Plugin::SettGet();

				$settCache = Gen::GetArrField( $sett, array( 'cache' ), array() );

				$settContPr = Gen::GetArrField( $sett, array( 'contPr' ), array() );

				$settImg = Gen::GetArrField( $settContPr, array( 'img' ), array() );

				$settCdn = Gen::GetArrField( $settContPr, array( 'cdn' ), array() );



				_ContentProcessor_TmpCont_SettImg_Adjust( $settImg );



				$ctxProcess = &GetContentProcessCtx( $_SERVER, $sett );



				$url = new ImgSrc( $ctxProcess, $url );

				Images_ProcessSrc( $ctxProcess, $url, $settCache, $settImg, $settCdn );

				return( $url -> src );

			}

		, 9999, 2 );



	    add_filter( 'wp_get_attachment_image_src',

	        function( $image, $attachment_id, $size, $icon )

	        {

	            if( !is_array( $image ) )

					return( $image );



				$src = ($image[ 0 ]??null);

	            if( !$src )

					return( $image );



				$sett = Plugin::SettGet();

				$settCache = Gen::GetArrField( $sett, array( 'cache' ), array() );

				$settContPr = Gen::GetArrField( $sett, array( 'contPr' ), array() );

				$settImg = Gen::GetArrField( $settContPr, array( 'img' ), array() );

				$settCdn = Gen::GetArrField( $settContPr, array( 'cdn' ), array() );



				_ContentProcessor_TmpCont_SettImg_Adjust( $settImg );



				$ctxProcess = &GetContentProcessCtx( $_SERVER, $sett );



				$src = new ImgSrc( $ctxProcess, $src );

	            if( Images_ProcessSrc( $ctxProcess, $src, $settCache, $settImg, $settCdn ) )

	                $image[ 0 ] = $src -> src;



	            return( $image );

	        }

	    , 9999, 4 );



	    add_filter( 'wp_calculate_image_srcset',

	        function( $sources, $size_array, $image_src, $image_meta, $attachment_id )

	        {

	            if( !is_array( $sources ) )

	                return( $sources );



				$sett = Plugin::SettGet();

				$settCache = Gen::GetArrField( $sett, array( 'cache' ), array() );

				$settContPr = Gen::GetArrField( $sett, array( 'contPr' ), array() );

				$settImg = Gen::GetArrField( $settContPr, array( 'img' ), array() );

				$settCdn = Gen::GetArrField( $settContPr, array( 'cdn' ), array() );



				_ContentProcessor_TmpCont_SettImg_Adjust( $settImg );



				$ctxProcess = &GetContentProcessCtx( $_SERVER, $sett );



	            foreach( $sources as &$source )

	            {

	                if( !is_array( $source ) )

	                    continue;



					$src = ($source[ 'url' ]??null);

	                if( !$src )

	                    continue;



					$src = new ImgSrc( $ctxProcess, $src );

	                if( Images_ProcessSrc( $ctxProcess, $src, $settCache, $settImg, $settCdn ) )

	                    $source[ 'url' ] = $src -> src;

	            }



	            return( $sources );

	        }

	    , 9999, 5 );

	}



	if( ($settContPr[ 'enable' ]??null) && !Gen::GetArrField( $sett, array( 'emojiIcons' ), true, '/' ) )

		add_action( 'wp_loaded',

			function()

			{

				remove_action( 'wp_head', 'print_emoji_detection_script', 7 );

				remove_action( 'wp_print_styles', 'print_emoji_styles' );

				remove_filter( 'the_content_feed', 'wp_staticize_emoji' );

				remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );

				remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );

				add_filter( 'emoji_svg_url', '__return_false' );



			}

	);



	if( Gen::GetArrField( $settContPr, array( 'rc', 'thmFltsm' ), false ) && function_exists( 'flatsome_text_box'  ) )

	{

		add_action( 'wp_loaded',

			function()

			{

				$dir = dirname( ( string )Gen::GetFuncFile( 'flatsome_text_box' ) );

				if( !$dir )

					return;



				global $shortcode_tags;



				static $g_aTag2IdPrefix = array(

					'accordion-item' => 'accordion-',

					'blog_posts' => 'row-',

					'gap' => 'gap-',

					'map' => 'map-',

					'page_header' => 'page-header-',

					'ux_product_categories' => 'cats-',

					'ux_product_categories_grid' => 'cats-',

					'ux_product_flip' => 'product-flip-',

					'col' => 'col-',

					'col_inner' => 'col-',

					'col_inner_1' => 'col-',

					'col_inner_2' => 'col-',

					'row' => 'row-',

					'row_inner' => 'row-',

					'row_inner_1' => 'row-',

					'row_inner_2' => 'row-',

					'background' => 'section_',

					'section' => 'section_',

					'section_inner' => 'section_',

					'tabgroup' => 'panel-',

					'tabgroup_vertical' => 'panel-',

					'text_box' => 'text-box-',

					'title' => 'title-',

					'ux_banner' => 'banner-',

					'ux_banner_grid' => 'banner-grid-',

					'ux_gallery' => 'gallery-',

					'ux_image' => 'image_',

					'ux_instagram_feed' => 'instagram-',

					'ux_pages' => 'pages-',

					'ux_bestseller_products' => 'product-grid-',

					'ux_featured_products' => 'product-grid-',

					'ux_sale_products' => 'product-grid-',

					'ux_latest_products' => 'product-grid-',

					'ux_custom_products' => 'product-grid-',

					'product_lookbook' => 'product-grid-',

					'products_pinterest_style' => 'product-grid-',

					'ux_products' => 'product-grid-',

					'ux_slider' => 'slider-',

					'ux_text' => 'text-',

					'ux_countdown' => 'timer-',



				);



				$data = new AnyObj();

				$data -> idxs = array();



				foreach( $shortcode_tags as $tag => $cb )

				{

					if( !is_string( $cb ) )

						continue;



					if( !Gen::StrStartsWith( ( string )Gen::GetFuncFile( $cb ), $dir ) )

						continue;



					$hook = new AnyObj();



					$hook -> idPrefix = ($g_aTag2IdPrefix[ $tag ]??null);

					if( !$hook -> idPrefix )

						continue;



					$hook -> data = $data;

					$hook -> cbPrev = $cb;

					$hook -> cb =

						function( $hook, $attrs, $content, $tag )

						{

							$content = call_user_func( $hook -> cbPrev, $attrs, $content, $tag );



							if( preg_match( '@\\sid\\s*=\\s*["\'](' . $hook -> idPrefix . ')(\\d+)@', $content, $m ) )

							{

								$idx = &$hook -> data -> idxs[ $m[ 1 ] ];



								$id = $m[ 1 ] . 'a' . ( ++$idx );

								$content = str_replace( $m[ 1 ] . $m[ 2 ], $id, $content );



							}



							return( $content );

						}

					;



					$shortcode_tags[ $tag ] = array( $hook, 'cb' );

				}

			}

		);

	}

}



function OnEarlyContentComplete( $buffer, $tmpUpdate = false )

{



	global $seraph_accel_g_prepCont;

	global $seraph_accel_g_prepPrms;

	global $seraph_accel_g_lazyInvTmp;

	global $seraph_accel_g_contProcGetSkipStatus;

	global $seraph_accel_g_simpCacheMode;



	if( $seraph_accel_g_prepCont === null && ( defined( 'LINGUISE_SCRIPT_TRANSLATION' )  ) )

		_InitContentProcessor();



	if( !$seraph_accel_g_prepCont )

	{

		if( $seraph_accel_g_prepCont === null && $seraph_accel_g_prepPrms !== null )

		{

			$seraph_accel_g_contProcGetSkipStatus = null;

			ContProcGetSkipStatus( $buffer );

			if( ( !$seraph_accel_g_contProcGetSkipStatus || $seraph_accel_g_contProcGetSkipStatus == 'noHdrOrBody' ) && !( is_string( $seraph_accel_g_simpCacheMode ) && Gen::StrStartsWith( ( string )$seraph_accel_g_simpCacheMode, 'data:' ) ) )

				$seraph_accel_g_contProcGetSkipStatus = 'err:contTermEarly:' . rawurlencode( Gen::GetCallStack( DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS ) );

		}



		return( apply_filters( 'seraph_accel_html_content', $buffer, true ) );

	}



	if( !IsHtml( $buffer ) )

	{



		return( $buffer );

	}



	$skipStatus = ContProcGetSkipStatus( $buffer );

	if( $skipStatus )

		return( apply_filters( 'seraph_accel_html_content', $buffer, true ) );



	$sett = Plugin::SettGet();

	if( Wp::IsMultisite() )

	{

		$settCacheGlobal = Gen::GetArrField( Plugin::SettGetGlobal(), array( 'cache' ), array() );

		foreach( array( array( 'cache', 'procWorkInt' ), array( 'cache', 'procPauseInt' ) ) as $fldPath )

			Gen::SetArrField( $sett, $fldPath, Gen::GetArrField( $settCacheGlobal, $fldPath ) );

		unset( $fldPath, $settCacheGlobal );

	}



	$settCache = Gen::GetArrField( $sett, array( 'cache' ), array() );

	$settContPr = Gen::GetArrField( $sett, array( 'contPr' ), array() );



	$buffer = apply_filters( 'seraph_accel_content_pre', ContentPreProcess( $buffer, Gen::GetArrField( $settContPr, array( 'rc' ), array() ), GetCurRequestUrl() ) );



	global $seraph_accel_g_dataPath;

	global $seraph_accel_g_prepOrigContHashPrev;

	global $seraph_accel_g_prepOrigContHash;

	global $seraph_accel_g_prepOrigCont;

	global $seraph_accel_g_bPrepContTmpToMain;

	global $seraph_accel_g_cacheObjChildren;

	global $seraph_accel_g_cacheObjSubs;

	global $seraph_accel_g_ctxProcess;

	global $seraph_accel_g_ahuddqrText;



	{

		$dataForChecksum = $buffer;

		foreach( GetCurHdrsToStoreInCache( $settCache ) as $hdr )

			$dataForChecksum .= $hdr;

		$dataForChecksum .= @json_encode( $settContPr ) . @json_encode( $settCache ) . ( $seraph_accel_g_ahuddqrText ? 'b' : '' );



		$seraph_accel_g_prepOrigContHash = md5( $dataForChecksum, true );

		if( $seraph_accel_g_prepOrigContHash === $seraph_accel_g_prepOrigContHashPrev && !( $seraph_accel_g_prepPrms !== null && isset( $seraph_accel_g_prepPrms[ 'lrn' ] ) ) )

		{

			$seraph_accel_g_contProcGetSkipStatus = 'notChanged';

			return( $buffer );

		}



		unset( $dataForChecksum );

	}



	if( Gen::StrStartsWith( $seraph_accel_g_prepCont, 'tmp' ) )

	{

		if( $seraph_accel_g_lazyInvTmp && Gen::GetArrField( $settCache, array( 'fastTmpOpt' ), false ) )

		{

			$seraph_accel_g_prepOrigCont = $buffer;



			$buffer = _EarlyContentComplete( $buffer, 1 | ( $seraph_accel_g_prepCont == 'tmpLong' ? 2 : 0 ), $sett, $settCache, $settContPr, $skipStatus );



			if( !$seraph_accel_g_ctxProcess[ 'modeReq' ] )

				$seraph_accel_g_bPrepContTmpToMain = true;



			if( !$skipStatus )

				$skipStatus = 'ok';



			if( ($sett[ 'hdrTrace' ]??null) )

			{

				if( !headers_sent() )

					header( 'X-Seraph-Accel-Content: 2.29.11; status=' . $skipStatus . ', stat=' . _TraceContStat( $seraph_accel_g_ctxProcess[ 'stat' ] ) );



			}

		}



		return( apply_filters( 'seraph_accel_content', $buffer ) );

	}



	$bPrepContTmpToMain = false;

	$skipStatus = null;



	if( $tmpUpdate && $seraph_accel_g_prepCont != 'fragments' )

	{

		if( $seraph_accel_g_prepPrms !== null && $seraph_accel_g_lazyInvTmp && !Gen::GetArrField( $settCache, array( 'fastTmpOpt' ), false )  )

		{

			$bufferTmp = $buffer;

			$ctxProcessCur = $seraph_accel_g_ctxProcess;

			if( Gen::GetArrField( $settCache, array( 'fastTmpOpt' ), false ) )

			{

				$seraph_accel_g_prepOrigCont = $bufferTmp;



				$prepPrmsCur = $seraph_accel_g_prepPrms;



				$bufferTmp = _EarlyContentComplete( $bufferTmp, 1 | 2, $sett, $settCache, $settContPr, $skipStatus );



				if( !$seraph_accel_g_ctxProcess[ 'modeReq' ] )

					$bPrepContTmpToMain = true;



				$seraph_accel_g_prepPrms = $prepPrmsCur;



				unset( $prepPrmsCur );

			}



			$bufferTmp = apply_filters( 'seraph_accel_content', $bufferTmp );



			if( $skipStatus )

				return( $bufferTmp );



			if( !$bPrepContTmpToMain )

			{

				$lock = new Lock( 'dl', GetCacheDir() );

				CacheDscUpdate( $lock, $settCache, $bufferTmp, $seraph_accel_g_cacheObjChildren, $seraph_accel_g_cacheObjSubs, $seraph_accel_g_dataPath, 'u', $seraph_accel_g_prepOrigCont, $seraph_accel_g_prepOrigContHash );

				unset( $lock );



				CacheAdditional_UpdateCurUrl( $settCache, true );



				$seraph_accel_g_cacheObjChildren = $seraph_accel_g_cacheObjSubs = null;

				$seraph_accel_g_ctxProcess = $ctxProcessCur;

			}

			else

			{

				$buffer = $bufferTmp;

			}



			unset( $bufferTmp, $ctxProcessCur );

		}

	}



	if( !$bPrepContTmpToMain )

	{

		$buffer = _EarlyContentComplete( $buffer, $seraph_accel_g_prepCont == 'fragments' ? 256 : ( 1 | 2 | 4 ), $sett, $settCache, $settContPr, $skipStatus );

		$buffer = apply_filters( 'seraph_accel_content', $buffer );

	}



	if( !$skipStatus )

		$skipStatus = 'ok';



	if( ($sett[ 'hdrTrace' ]??null) )

	{

		if( !headers_sent() )

			header( 'X-Seraph-Accel-Content: 2.29.11; status=' . $skipStatus . ', stat=' . _TraceContStat( $seraph_accel_g_ctxProcess[ 'stat' ] ) );



	}



	return( $buffer );

}



function _TraceContStat( $aInfo )

{

	$stateDsc = '';



	foreach( $aInfo as $infoKey => $infoVal )

	{



		if( $stateDsc )

			$stateDsc .= ", ";

		$stateDsc .= $infoKey . '=';



		$stateDsc .= LocId::UnPack( $infoVal,

			function( $id, $comp )

			{

				switch( $id )

				{

				case 'TimeDurSec_%1$s': $id = '%1$ss'; break;

				}



				return( $id );

			}

		);

	}



	return( '{' . $stateDsc . '}' );

}



function _EarlyContentComplete( $buffer, $mode, $sett, $settCache, $settContPr, &$skipStatus = null )

{

	global $seraph_accel_g_contProcGetSkipStatus;



	{

		$memLim = Gen::GetArrField( $settCache, array( 'procMemLim' ), 0 );



		$memLimCur = wp_convert_hr_to_bytes( @ini_get( 'memory_limit' ) ) / 1024 / 1024;



		if( $memLimCur < $memLim )

		{



			@ini_set( 'memory_limit', ( string )$memLim . 'M' );



		}



		unset( $memLim );

		unset( $memLimCur );

	}



	$ctxProcess = &GetContentProcessCtx( $_SERVER, $sett );

	$ctxProcess[ 'mode' ] = $mode;



	if( ($settCache[ 'enable' ]??null) && Gen::GetArrField( $settCache, array( 'chunks', 'enable' ), false ) )

		$ctxProcess[ 'chunksEnabled' ] = true;



	$skipStatus = null;



	$errorReportingPrevLevel = @error_reporting( E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR );

	$encPrev = ContentParseStrIntEncodingCorrect();

	$buffer = ContentProcess( $ctxProcess, $sett, $settCache, $settContPr, $buffer, $skipStatus );

	ContentParseStrIntEncodingRestore( $encPrev );

	@error_reporting( $errorReportingPrevLevel );



	if( !( $ctxProcess[ 'mode' ] & 2 ) )

		$ctxProcess[ 'modeReq' ] |= 2;



	if( $skipStatus )

	{

		$seraph_accel_g_contProcGetSkipStatus = $skipStatus;

		if( Gen::LastErrDsc_Is() )

			$seraph_accel_g_contProcGetSkipStatus .= ':' . rawurlencode( Gen::LastErrDsc_Get() );

	}



	return( $buffer );

}



function ContentProcess_IsItemInFragments( $ctxProcess, $item, $cbCmp = null )

{

	if( !( $ctxProcess[ 'mode' ] & 256 ) )

		return( true );



	foreach( $ctxProcess[ 'fragments' ] as $itemFragment )

		if( HtmlNd::DoesContain( $itemFragment, $item ) )

			return( $cbCmp ? @call_user_func( $cbCmp, $itemFragment, $item ) : true );



	return( false );

}



function ContentProcess_GetCurRelatedUri( $ctxProcess, $args )

{

	$requestPath = ParseContCachePathArgs( $ctxProcess[ 'serverArgs' ], $requestArgs );

	return( Net::UrlAddArgsEx( $ctxProcess[ 'ndHeadBase' ] ? $requestPath : '', array_merge( $requestArgs, $args ) ) );

}



function ContentProcess_GetGetPartUri( $ctxProcess, $id )

{

	return( ContentProcess_GetCurRelatedUri( $ctxProcess, array( 'seraph_accel_gp' => ( string )Gen::GetCurRequestTime( $ctxProcess[ 'serverArgs' ] ) . '_' . str_replace( '.', '_', $id ) ) ) );

}



function ContentPreProcess( $buffer, $settRc, $curUrl, $test = false )

{



	if( Gen::GetArrField( $settRc, array( 'gglTrn' ), false ) && ( $test || Gen::DoesFuncExist( 'GTranslate::activate'  ) ) )

	{

		$aIds = array();

		$offs = 0;

		while( preg_match( '@id=([\'"])gt-wrapper-(\\d+)(?1)@S', $buffer, $m, PREG_OFFSET_CAPTURE, $offs ) )

		{

			$offs = $m[ 0 ][ 1 ] + strlen( $m[ 0 ][ 0 ] );

			$aIds[] = $m[ 2 ][ 0 ];

		}



		$unique_id_base = substr( Wp::GetSiteId(), 0, 8 );



		foreach( $aIds as $i => $id )

		{

			$unique_id = $unique_id_base . '-' . ( $i + 1 );

			$buffer = preg_replace( '@gt-wrapper-' . $id . '@', 'gt-wrapper-' . $unique_id, $buffer );

			$buffer = preg_replace( '@gt_widget_script_' . $id . '@', 'gt_widget_script_' . $unique_id, $buffer );

			$buffer = preg_replace( '@data-gt-widget-id=([\'"])' . $id . '(?1)@', 'data-gt-widget-id=${1}' . $unique_id . '${1}', $buffer );

			$buffer = preg_replace( '@\\.gtranslateSettings\\[\\\'' . $id . '@', '.gtranslateSettings[\'' . $unique_id, $buffer );

		}

	}



	if( Gen::GetArrField( $settRc, array( 'aksmtAs' ), false ) && ( $test || defined( 'AKISMET_VERSION' ) ) )

	{

		$buffer = preg_replace_callback( '@id="ak_js_\\d+"\\s+name="ak_js"\\s+value="(\\d+)@i',

			function( $m )

			{

				return( substr( $m[ 0 ], 0, -strlen( $m[ 1 ] ) ) . '0' );

			}

		, $buffer );

	}



	if( Gen::GetArrField( $settRc, array( 'advWooSrch' ), false ) )

	{

		$aIds = array();

		$offs = 0;

		while( preg_match( '@class=([\'"])aws-search-label(?1)\\sfor=(?1)([^\'"]+)(?1)@S', $buffer, $m, PREG_OFFSET_CAPTURE, $offs ) )

		{

			$offs = $m[ 0 ][ 1 ] + strlen( $m[ 0 ][ 0 ] );

			$aIds[] = $m[ 2 ][ 0 ];

		}



		foreach( $aIds as $i => $id )

			$buffer = preg_replace( '@([\'"])' . $id . '(?1)@', '${1}aws-search-' . ( $i + 1 ) . '${1}', $buffer );

	}



	if( Gen::GetArrField( $settRc, array( 'jetMblMnu' ), false ) )

	{

		$aIds = array();

		$offs = 0;

		while( preg_match( '@id=([\'"])jet-mobile-menu-([\\da-fA-F]+)(?1)@S', $buffer, $m, PREG_OFFSET_CAPTURE, $offs ) )

		{

			$offs = $m[ 0 ][ 1 ] + strlen( $m[ 0 ][ 0 ] );

			$aIds[] = $m[ 2 ][ 0 ];

		}



		foreach( $aIds as $i => $id )

			$buffer = str_replace( array( 'jet-mobile-menu-' . $id, 'menuUniqId&quot;:&quot;' . $id, 'menuUniqId":"' . $id, 'jetMenuMobileWidgetRenderData' . $id ), array( 'jet-mobile-menu-' . ( $i + 1 ), 'menuUniqId&quot;:&quot;' . ( $i + 1 ), 'menuUniqId":"' . ( $i + 1 ), 'jetMenuMobileWidgetRenderData' . ( $i + 1 ) ), $buffer );

	}



	if( Gen::GetArrField( $settRc, array( 'wpelLnk' ), false ) )

	{

		$aIds = array();

		$offs = 0;

		while( preg_match( '@<a[^>]+class=([\'"])(u[A-Fa-f0-9]{32})(?1)\\s+data-wpel-link=[^>]+>@S', $buffer, $m, PREG_OFFSET_CAPTURE, $offs ) )

		{

			$offs = $m[ 0 ][ 1 ] + strlen( $m[ 0 ][ 0 ] );

			$aIds[] = $m[ 2 ][ 0 ];

		}



		foreach( $aIds as $i => $id )

			$buffer = str_replace( array( $id ), array( 'wpel-link-u-' . ( $i + 1 ) ), $buffer );

	}



	if( Gen::GetArrField( $settRc, array( 'cfTrnstl' ), false ) )

	{

		$aIds = array();

		$offs = 0;

		while( preg_match( '@\\sid=([\'"])cf-turnstile-([\\w\\-]+)-(\\d+)(?1)@S', $buffer, $m, PREG_OFFSET_CAPTURE, $offs ) )

		{

			$offs = $m[ 0 ][ 1 ] + strlen( $m[ 0 ][ 0 ] );

			$aIds[] = array( 'prefix' => $m[ 2 ][ 0 ], 'random' => $m[ 3 ][ 0 ] );

		}



		foreach( $aIds as $i => $aId )

			$buffer = str_replace( array( $aId[ 'prefix' ] . '-' . $aId[ 'random' ] ), array( $aId[ 'prefix' ] . '-' . ( $i + 1 ) ), $buffer );

	}



	if( Gen::GetArrField( $settRc, array( 'tagGrpsShfflBx' ), false ) && @preg_match( '@<script\\s[^>]+plugins/tag-groups[^>]+js/shuffle-box@', $buffer ) )

	{

		$aIds = array();

		$offs = 0;

		while( preg_match( '@\\sid=([\'"])tag-groups-shuffle-box-([a-zA-Z\\d]+)(?1)@S', $buffer, $m, PREG_OFFSET_CAPTURE, $offs ) )

		{

			$offs = $m[ 0 ][ 1 ] + strlen( $m[ 0 ][ 0 ] );

			$aIds[] = $m[ 2 ][ 0 ];

		}



		foreach( $aIds as $i => $id )

			$buffer = str_replace( array( 'tag-groups-shuffle-box-' . $id ), array( 'tag-groups-shuffle-box-' . ( $i + 1 ) ), $buffer );

	}



	if( Gen::GetArrField( $settRc, array( 'g5Ere' ), false ) && @preg_match( '@<script\\s[^>]+/plugins/g5-ere/@', $buffer ) )

	{

		$aIds = array();

		$offs = 0;

		while( preg_match( '@\\sdata-prefix=([\'"])(g5ere_[a-zA-Z_]+-)([a-fA-f\\d]+)(?1)@S', $buffer, $m, PREG_OFFSET_CAPTURE, $offs ) )

		{

			$offs = $m[ 0 ][ 1 ] + strlen( $m[ 0 ][ 0 ] );

			$aIds[] = array( 'prefix' => $m[ 2 ][ 0 ], 'random' => $m[ 3 ][ 0 ] );

		}



		foreach( $aIds as $i => $aId )

			$buffer = str_replace( array( $aId[ 'prefix' ] . $aId[ 'random' ] ), array( $aId[ 'prefix' ] . ( $i + 1 ) ), $buffer );

	}



	if( Gen::GetArrField( $settRc, array( 'elmntrTrx' ), false ) && @preg_match( '@<style\\s[^>]+>([^<]*\\.trx_addons_inline_\\d+[^<]*)</style>@', $buffer, $m ) )

	{

		$cont = $m[ 1 ];



		$aIds = array();

		$offs = 0;

		while( preg_match( '@\\.(trx_addons_inline_)(\\d+)@S', $cont, $m, PREG_OFFSET_CAPTURE, $offs ) )

		{

			$offs = $m[ 0 ][ 1 ] + strlen( $m[ 0 ][ 0 ] );

			$aIds[] = array( 'prefix' => $m[ 1 ][ 0 ], 'random' => $m[ 2 ][ 0 ] );

		}



		unset( $cont );



		foreach( $aIds as $i => $aId )

			$buffer = str_replace( array( $aId[ 'prefix' ] . $aId[ 'random' ] ), array( $aId[ 'prefix' ] . ( $i + 1 ) ), $buffer );

	}



	if( Gen::GetArrField( $settRc, array( 'thmXStr' ), false ) && @preg_match( '@<style\\s[^>]*id=[\'"]xstore-inline-css-inline-css[\'"][^>]*>([^<]*)</style>@', $buffer, $m ) )

	{

		$cont = $m[ 1 ];



		$aIds = array();



		$offs = 0;

		while( preg_match( '@\\sid=([\'"])((?:filter|path|banner)-)(\\d+)(?1)@S', $buffer, $m, PREG_OFFSET_CAPTURE, $offs ) )

		{

			$offs = $m[ 0 ][ 1 ] + strlen( $m[ 0 ][ 0 ] );



			$class = $m[ 2 ][ 0 ];

			if( !isset( $aIds[ $class ][ 't' ] ) )

				$aIds[ $class ][ 't' ] = array( 's' => '@(\\W' . preg_quote( $class ) . '){RID}(\\W)@', 'r' => '${1}thmxstr-{NID}${2}' );

			$aIds[ $class ][ 'i' ][] = $m[ 3 ][ 0 ];

		}



		$offs = 0;

		while( preg_match( '@<\\w+\\s[^>]*class=[\'"][^\'"]*\\s((?:slider|menu-list)-)(\\d+)@S', $buffer, $m, PREG_OFFSET_CAPTURE, $offs ) )

		{

			$offs = $m[ 0 ][ 1 ] + strlen( $m[ 0 ][ 0 ] );



			$class = $m[ 1 ][ 0 ];

			if( !isset( $aIds[ $class ][ 't' ] ) )

				$aIds[ $class ][ 't' ] = array( 's' => '@(\\W' . preg_quote( $class ) . '){RID}(\\W)@', 'r' => '${1}thmxstr-{NID}${2}' );

			$aIds[ $class ][ 'i' ][] = $m[ 2 ][ 0 ];

		}



		$offs = 0;

		while( preg_match( '@<\\w+\\s[^>]*class=[\'"][^\'"]*\\s((?:menu-item)-)(\\d+)@S', $buffer, $m, PREG_OFFSET_CAPTURE, $offs ) )

		{

			$offs = $m[ 0 ][ 1 ] + strlen( $m[ 0 ][ 0 ] );



			if( !preg_match( '@\\.' . preg_quote( $m[ 1 ][ 0 ] . $m[ 2 ][ 0 ] ) . '@', $cont ) )

				continue;



			$class = $m[ 1 ][ 0 ];

			if( !isset( $aIds[ $class ][ 't' ] ) )

				$aIds[ $class ][ 't' ] = array( 's' => '@(\\W' . preg_quote( $class ) . '){RID}(\\W)@', 'r' => '${1}thmxstr-{NID}${2}' );

			$aIds[ $class ][ 'i' ][] = $m[ 2 ][ 0 ];

		}



		$offs = 0;

		while( preg_match( '@<\\w+\\s[^>]*class=[\'"][^\'"]*\\s(slider-item-)(\\d+)@S', $buffer, $m, PREG_OFFSET_CAPTURE, $offs ) )

		{

			$offs = $m[ 0 ][ 1 ] + strlen( $m[ 0 ][ 0 ] );



			if( !preg_match( '@\\.' . preg_quote( $m[ 1 ][ 0 ] . $m[ 2 ][ 0 ] ) . '@', $cont ) )

				continue;



			$class = $m[ 1 ][ 0 ];

			if( !isset( $aIds[ $class ][ 't' ] ) )

				$aIds[ $class ][ 't' ] = array( 's' => array( '@(\\W' . preg_quote( $class ) . '){RID}(\\W)@', '@(\\Wdata-slide-id=[\'"]){RID}([\'"]\\W)@' ), 'r' => array( '${1}thmxstr-{NID}${2}', '${1}{NID}${2}' ) );

			$aIds[ $class ][ 'i' ][] = $m[ 2 ][ 0 ];

		}



		unset( $cont );



		foreach( $aIds as $aId )

			foreach( $aId[ 'i' ] as $i => $id )

				$buffer = preg_replace( str_replace( '{RID}', preg_quote( $id ), $aId[ 't' ][ 's' ] ), str_replace( '{NID}', ( string )( $i + 1 ), $aId[ 't' ][ 'r' ] ), $buffer );

	}



	if( Gen::GetArrField( $settRc, array( 'wooPrdQnt' ), false ) && @preg_match( '@<body\\s[^>]*class=[\'"][^\'"]*woocommerce@', $buffer, $m ) )

	{

		$aIds = array();



		$offs = 0;

		while( preg_match( '@<input\\s+type="number"[^>]*\\sid=([\'"])(quantity_)([\\da-f\\.]+)(?1)@S', $buffer, $m, PREG_OFFSET_CAPTURE, $offs ) )

		{

			$offs = $m[ 0 ][ 1 ] + strlen( $m[ 0 ][ 0 ] );



			$class = $m[ 2 ][ 0 ];

			if( !isset( $aIds[ $class ][ 't' ] ) )

				$aIds[ $class ][ 't' ] = array( 's' => '@(\\W' . preg_quote( $class ) . '){RID}(\\W)@', 'r' => '${1}wooprd-{NID}${2}' );

			$aIds[ $class ][ 'i' ][] = $m[ 3 ][ 0 ];

		}



		foreach( $aIds as $aId )

			foreach( $aId[ 'i' ] as $i => $id )

				$buffer = preg_replace( str_replace( '{RID}', preg_quote( $id ), $aId[ 't' ][ 's' ] ), str_replace( '{NID}', ( string )( $i + 1 ), $aId[ 't' ][ 'r' ] ), $buffer );

	}



	if( Gen::GetArrField( $settRc, array( 'asClnTlk' ), false ) && @preg_match( '@<script\\s[^>]+id=["\']apbct-public-bundle@', $buffer, $m ) )

	{

		$aIds = array();

		$offs = 0;

		while( preg_match( '@<input\\s[^>]+id=["\'](ct_checkjs_[a-z0-9]+_)(\\w+)@S', $buffer, $m, PREG_OFFSET_CAPTURE, $offs ) )

		{

			$offs = $m[ 0 ][ 1 ] + strlen( $m[ 0 ][ 0 ] );

			$aIds[ $m[ 1 ][ 0 ] ][] = array( 'random' => $m[ 2 ][ 0 ] );

		}



		foreach( $aIds as $prefix => $aIdsE )

			foreach( $aIdsE as $i => $aId )

				$buffer = str_replace( array( $prefix . $aId[ 'random' ] ), array( $prefix . ( $i + 1 ) ), $buffer );

	}



	if( Gen::GetArrField( $settRc, array( 'chbsBkngFrm' ), false ) )

	{

		$aIds = array();

		$offs = 0;

		while( preg_match( '@\\Wid=["\'](chbs_booking_form_)(\\w+)@', $buffer, $m, PREG_OFFSET_CAPTURE, $offs ) )

		{

			$offs = $m[ 0 ][ 1 ] + strlen( $m[ 0 ][ 0 ] );

			$aIds[ $m[ 1 ][ 0 ] ][] = array( 'random' => $m[ 2 ][ 0 ] );

		}



		foreach( $aIds as $prefix => $aIdsE )

			foreach( $aIdsE as $i => $aId )

				$buffer = str_replace( array( $prefix . $aId[ 'random' ] ), array( $prefix . ( $i + 1 ) ), $buffer );

	}



	if( Gen::GetArrField( $settRc, array( 'jegElmntr' ), false ) && @preg_match( '@/plugins/jeg-elementor-kit@', $buffer, $m ) )

	{

		{

			$aIds = array();

			$offs = 0;

			while( preg_match( '@\\Wid=["\'](jkit-[\\w+\\-]+-)([\\da-f]{10,})["\']@', $buffer, $m, PREG_OFFSET_CAPTURE, $offs ) )

			{

				$offs = $m[ 0 ][ 1 ] + strlen( $m[ 0 ][ 0 ] );

				$aIds[ $m[ 1 ][ 0 ] ][] = array( 'random' => $m[ 2 ][ 0 ] );

			}



			foreach( $aIds as $prefix => $aIdsE )

				foreach( $aIdsE as $i => $aId )

					$buffer = str_replace( array( $prefix . $aId[ 'random' ] ), array( $prefix . ( $i + 1 ) ), $buffer );

		}



		{

			$a = array();

			$offs = 0;

			while( preg_match( '@\\sclass=["\']([^"\']+)\\s+(jeg_module_)([\\da-f_]{10,})@', $buffer, $m, PREG_OFFSET_CAPTURE, $offs ) )

			{

				$offs = $m[ 0 ][ 1 ] + strlen( $m[ 0 ][ 0 ] );

				$a[ $m[ 2 ][ 0 ] ][ $m[ 3 ][ 0 ] ] = md5( $m[ 1 ][ 0 ] );

			}



			foreach( $a as $prefix => $aE )

				foreach( $aE as $random => $stable )

					$buffer = str_replace( array( $prefix . $random ), array( $prefix . $stable ), $buffer );

		}

	}



	if( Gen::GetArrField( $settRc, array( 'divi' ), false ) )

	{

		$aIds = array();

		$offs = 0;



		while( preg_match( '@\\Wid=["\'](et_b-header-search-input-)(\\d+)@', $buffer, $m, PREG_OFFSET_CAPTURE, $offs ) )

		{

			$offs = $m[ 0 ][ 1 ] + strlen( $m[ 0 ][ 0 ] );

			$aIds[ $m[ 1 ][ 0 ] ][] = array( 'random' => $m[ 2 ][ 0 ] );

		}



		while( preg_match( '@\\s(et_custom_uniqid_)([0-9a-f]{10,})@', $buffer, $m, PREG_OFFSET_CAPTURE, $offs ) )

		{

			$offs = $m[ 0 ][ 1 ] + strlen( $m[ 0 ][ 0 ] );

			$aIds[ $m[ 1 ][ 0 ] ][] = array( 'random' => $m[ 2 ][ 0 ] );

		}



		foreach( $aIds as $prefix => $aIdsE )

			foreach( $aIdsE as $i => $aId )

				$buffer = str_replace( array( $prefix . $aId[ 'random' ] ), array( $prefix . ( $i + 1 ) ), $buffer );

	}



	if( Gen::GetArrField( $settRc, array( 'pys' ), false ) && @preg_match( '@<script\\s+id=[\'"]pys-js-extra[\'"]>\\s*var\\s+pysOptions\\s*=\\s*{@', $buffer, $m, PREG_OFFSET_CAPTURE ) )

	{

		$posStart = $m[ 0 ][ 1 ] + strlen( $m[ 0 ][ 0 ] ) - 1;

		$pos = Gen::JsonGetEndPos( $posStart, $buffer );

		$m = @json_decode( Gen::JsObjDecl2Json( substr( $buffer, $posStart, $pos - $posStart ) ), true );



		if( $m )

		{

			$m[ 'cache_bypass' ] = '1';



			foreach( Gen::GetArrField( $m, array( 'staticEvents', 'facebook' ), array() ) as $evtId => $aData )

				foreach( $aData as $i => $aDataE )

					if( isset( $aDataE[ 'eventID' ] ) )

					{

						$v = md5( $curUrl . $evtId );

						$vGuid = '';



						$nLvlTotal = 0;

						foreach( array( 8, 4, 4, 4 ) as $nLvl )

						{

							$vGuid .= substr( $v, $nLvlTotal, $nLvl ) . '-';

							$nLvlTotal += $nLvl;

						}

						$vGuid .= substr( $v, $nLvlTotal );



						$m[ 'staticEvents' ][ 'facebook' ][ $evtId ][ $i ][ 'eventID' ] = $vGuid;

					}



			$buffer = substr_replace( $buffer, str_replace( '\\/', '/', json_encode( $m, JSON_PRETTY_PRINT ) ), $posStart, $pos - $posStart );

		}

	}



	return( $buffer );

}



function ContentProcess_PreFetchLocalFiles_ExpandCssCont( $ctxProcess, $cont, $attr = array(), $a = array(), $filePathOwner = null )

{

	_ContentProcess_PreFetchLocalFiles_ExpandItemCssCont( $a, $ctxProcess, $cont, array( 'cssExtract' => true ), array_merge( array( 'cssExtract' => true ), $attr ), $filePathOwner );

	return( $a );

}



function _ContentProcess_PreFetchLocalFiles_ExpandItemCssCont( &$aRes, $ctxProcess, $cont, $aOpMask = array(), $attr = array(), $filePathOwner = null )

{

	$filePathOwnerRequestUrl = null;

	$filePathOwnerRequestDomainUrl = null;

	$filePathOwnerRequestUriPath = null;



	if( $filePathOwner )

	{

		if( Gen::StrStartsWith( $filePathOwner, $ctxProcess[ 'siteContPath' ] ) )

			$filePathOwnerRequestUrl = $ctxProcess[ 'siteDomainUrl' ] . $ctxProcess[ 'siteRootUri' ] . '/' . Gen::GetFileName( $ctxProcess[ 'siteContPath' ] ) . substr( $filePathOwner, strlen( $ctxProcess[ 'siteContPath' ] ) );

		else if( Gen::StrStartsWith( $filePathOwner, $ctxProcess[ 'siteRootPath' ] ) )

			$filePathOwnerRequestUrl = $ctxProcess[ 'siteDomainUrl' ] . $ctxProcess[ 'siteRootUri' ] . substr( $filePathOwner, strlen( $ctxProcess[ 'siteRootPath' ] ) );

		else

			$filePathOwnerRequestUrl = 'https://255.255.255.255/' . $filePathOwner;



		$filePathOwnerRequestDomainUrl = Net::GetSiteAddrFromUrl( $filePathOwnerRequestUrl, true );

		$filePathOwnerRequestUriPath = Gen::GetFileDir( Net::Url2Uri( $filePathOwnerRequestUrl ) );

	}



	foreach( CssExtractImports( $cont ) as $import )

	{

		$import = StyleProcessor::GetFirstImportSimpleAttrs( $ctxProcess, $import, $filePathOwnerRequestUrl );

		if( !$import || !isset( $import[ 'filePath' ] ) )

			continue;



		_ContentProcess_PreFetchLocalFiles_ExpandItem( $aOpMask, $aRes, $ctxProcess, $import[ 'filePath' ], array_merge( $attr, array( 'filePathRoot' => ($import[ 'filePathRoot' ]??null) ) ) );

	}



	foreach( StyleProcessor::CssExtactUrlsFast( $cont ) as $url )

	{

		$imgSrc = new ImgSrc( $ctxProcess, $url );

		$imgSrc -> Init( null, $filePathOwnerRequestDomainUrl, $filePathOwnerRequestUriPath );

		if( $imgSrc -> IsSrcData() )

		{

			if( isset( $attr[ 'cbImgSrcAttrData' ] ) && in_array( Gen::GetFileName( Ui::GetSrcAttrDataType( $url ) ), array( 'jpe','jpg','jpeg','png','gif','bmp', 'webp','avif' ) ) )

				$attr[ 'cbImgSrcAttrData' ]( $imgSrc );

		}

		else if( isset( $imgSrc -> srcInfo[ 'filePath' ] ) )

			_ContentProcess_PreFetchLocalFiles_ExpandItem( $aOpMask, $aRes, $ctxProcess, $imgSrc -> srcInfo[ 'filePath' ], array( 'filePathRoot' => ($imgSrc -> srcInfo[ 'filePathRoot' ]??null) ) );

	}

}



function _ContentProcess_PreFetchLocalFiles_ExpandItem( $aOpMask, &$aRes, $ctxProcess, $filePath, $attr )

{

	if( ($attr[ 'cssExtract' ]??null) && ($aOpMask[ 'cssExtract' ]??null) )

	{

		$c = Gen::FileGetContents( $filePath );

		if( is_string( $c ) )

			_ContentProcess_PreFetchLocalFiles_ExpandItemCssCont( $aRes, $ctxProcess, $c, $aOpMask, $attr, $filePath );



		$attr[ 'c' ] = $c;

	}



	$aRes[ $filePath ] = $attr;

}



function ContentProcess_PreFetchLocalFiles_Expand( $ctxProcess, $a, $aOpMask = array() )

{

	$aRes = array();

	foreach( $a as $filePath => $attr )

	{

		if( strpos( $filePath, '*' ) !== false )

		{

			foreach( @glob( $filePath ) as $file )

			{

				if( isset( $attr[ 'fileNameFilter' ] ) && !preg_match( $attr[ 'fileNameFilter' ], Gen::GetFileName( $file, true ) ) )

					continue;

				_ContentProcess_PreFetchLocalFiles_ExpandItem( $aOpMask, $aRes, $ctxProcess, $file, $attr );

			}

		}

		else

			_ContentProcess_PreFetchLocalFiles_ExpandItem( $aOpMask, $aRes, $ctxProcess, $filePath, $attr );

	}



	return( $aRes );

}



function ContentProcess_PreFetchLocalFiles_AdjustContCompr( &$info, $filePath )

{

	if( is_string( ($info[ 'c' ]??null) ) && in_array( Gen::GetFileExt( $filePath ), array( 'css', 'js', 'json', 'html' ) ) )

	{

		$info[ 'c' ] = @gzencode( $info[ 'c' ] );

		$info[ 'cmpr' ] = true;

	}

	else

		unset( $info[ 'cmpr' ] );

}



function ContentProcess_PreFetchLocalFiles( &$aFile, $ctxProcess, $a, $cont = true, $sizeLim = null )

{

	$a = ContentProcess_PreFetchLocalFiles_Expand( $ctxProcess, $a, array( 'cssExtract' => true ) );

	ContentProcess_PreFetchLocalFilesEx( $aFile, $ctxProcess, $a, $cont, $sizeLim );

}



function ContentProcess_PreFetchLocalFilesEx( &$aFile, $ctxProcess, $a, $cont = true, $sizeLim = null )

{

	foreach( $a as $filePath => $attr )

	{

		if( isset( $aFile[ $filePath ] ) )

			continue;



		$info = array();

		if( file_exists( $filePath ) )

		{

			$info[ 'tm' ] = @filemtime( $filePath );

			$info[ 'sz' ] = @filesize( $filePath );



			if( $info[ 'tm' ] !== false && $info[ 'sz' ] !== false )

			{

				if( ( !$sizeLim || $info[ 'sz' ] <= $sizeLim ) && ($attr[ 'cont' ]??$cont) )

				{

					$info[ 'c' ] = ($attr[ 'c' ]??@file_get_contents( $filePath ));

					if( $info[ 'c' ] === false )

						unset( $info[ 'tm' ], $info[ 'sz' ] );

					else

						$info[ 'sz' ] = strlen( $info[ 'c' ] );

				}

			}

			else

			{

				$info[ 'c' ] = false;

				unset( $info[ 'tm' ], $info[ 'sz' ] );

			}

		}

		else

			$info[ 'c' ] = false;



		if( !isset( $attr[ 'filePathRoot' ] ) )

			$attr[ 'filePathRoot' ] = Gen::GetFileDir( $filePath );



		if( ($info[ 'c' ]??null) === false && !Gen::DoesFileDirExist( $filePath, $attr[ 'filePathRoot' ] ) )

			$info[ 'c' ] = null;



		ContentProcess_PreFetchLocalFiles_AdjustContCompr( $info, $filePath );



		$aFile[ $filePath ] = $info;

	}

}



function ContentProcess_CallCustomMethod( $name, $args )

{

	return( Gen::CallFunc( 'seraph_accel\\_ContentProcessRemote_CustomMethod_' . Gen::SanitizeId( $name ), array( $args ), false ) );

}



function ContentProcess_ItemType( $itemType, $itemData, &$ctxProcess, $sett )

{

	$skipStatus = '';



	$stage = 'images';



	ContentProcess_InitStat( $ctxProcess );

	ContentProcess_StatStageImg_Begin( $ctxProcess, $stage, $tmStat, $aStatDiff );



	if( $itemType == 10 )

	{

		$ctxProcess[ 'cbs' ] -> ReportStage( $stage );



		$file = ($itemData[ 'u' ]??null);

		Images_ProcessSrc_ConvertAll( $ctxProcess, Gen::GetArrField( $sett, array( 'contPr', 'img' ), array() ), null, $file, Images_ProcessSrcEx_FileMTime( $ctxProcess, $file ), false );

	}

	else if( $itemType == 20 )

	{

		$ctxProcess[ 'cbs' ] -> ReportStage( $stage );



		$file = ($itemData[ 'u' ]??null);

		Images_ProcessSrc_SizeAlternatives( $ctxProcess, $file, $sett, ($itemData[ 'crp' ]??null), ($itemData[ 'ai' ]??null) );

	}



	ContentProcess_StatStageImg_End( $ctxProcess, $stage, $tmStat, $aStatDiff );

	ContentProcess_FinalizeStat( $ctxProcess );



	if( Gen::LastErrDsc_Is() )

		$skipStatus = 'err:' . rawurlencode( Gen::LastErrDsc_Get() );



	return( $skipStatus );

}



function ContentProcess( &$ctxProcess, $sett, $settCache, $settContPr, $buffer, &$skipStatus )

{

	$settOrig = $sett;

	$ctxProcessOrig = $ctxProcess;



	Gen::SetTimeLimit( Gen::GetArrField( $settCache, array( 'procTmLim' ), 570 ) );

	Gen::GarbageCollectorEnable( false );



	global $seraph_accel_g_prepPrms;

	global $seraph_accel_g_ctxCache;

	global $seraph_accel_g_prepContIsUserCtx;

	global $seraph_accel_g_prepLearnId;

	global $seraph_accel_g_ahuddqrText;



	if( $ctxProcess[ 'cbs' ] -> GetContentProcessorForce( $sett ) !== null && ($_REQUEST[ 'd' ]??null) == 'orig' )

		return( $buffer );



	ContentProcess_InitStat( $ctxProcess );



	$stage = 'parse'; if( !( $resUpd = $ctxProcess[ 'cbs' ] -> ReportStage( $stage ) ) ) { ContentProcess_FinalizeStat( $ctxProcess ); $skipStatus = ( $resUpd === null ) ? 'aborted' : 'err:internal'; return( $buffer ); }



	{

		$tmStat = microtime( true );



	}



	$bufferNoOpt = $buffer;



	ContentProcess_Replace( $ctxProcess, $settCache, $settContPr, $buffer );

	if( $ctxProcess[ 'cbs' ] -> IsAborted( $ctxProcess, $settCache ) ) { ContentProcess_FinalizeStat( $ctxProcess ); $skipStatus = 'aborted'; return( $buffer ); }



	$norm = Gen::GetArrField( $settContPr, array( 'normalize' ), 0 );

	$doc = GetHtmlDoc( $buffer, $norm, Gen::GetArrField( $settContPr, array( 'min' ), false ), Gen::GetArrField( $settContPr, array( 'cln', 'cmts' ), false ) ? Gen::GetArrField( $settContPr, array( 'cln', 'cmtsExcl' ), array() ) : true );



	ContentProcess_DocReplace( $ctxProcess, $settCache, $settContPr, $doc );

	if( $ctxProcess[ 'cbs' ] -> IsAborted( $ctxProcess, $settCache ) ) { ContentProcess_FinalizeStat( $ctxProcess ); $skipStatus = 'aborted'; return( $buffer ); }



	if( $ctxProcess[ 'cbs' ] -> GetContentProcessorForce( $sett ) !== null )

	{

		if( ($_REQUEST[ 'd' ]??null) == 'origrpl' )

			return( $buffer );

		if( ($_REQUEST[ 'd' ]??null) == 'origparsed' )

			return( HtmlDocDeParse( $doc, $norm ) );

		if( ($_REQUEST[ 'd' ]??null) == 'origparsedstruct' )

			return( '<pre>' . htmlentities( @json_encode( HtmlNd::Dump( $doc ), JSON_PRETTY_PRINT ), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401 ) . '</pre>' );

	}



	{

		$tmStat = microtime( true ) - $tmStat;



		$ctxProcess[ 'stat' ][ ucfirst( $stage ) . '-Duration' ] = LocId::Pack( 'TimeDurSec_%1$s', null, array( sprintf( '%.2F', $tmStat ) ) );

	}



	if( !$doc )

	{

		$skipStatus = 'err:' . $stage;

		return( $buffer );

	}



	if( $ctxProcess[ 'cbs' ] -> IsAborted( $ctxProcess, $settCache ) ) { ContentProcess_FinalizeStat( $ctxProcess ); $skipStatus = 'aborted'; return( $buffer ); }



	$ctxProcess[ 'ndHtml' ] = HtmlNd::FindByTag( $doc, 'html', false );

	$ctxProcess[ 'ndHead' ] = HtmlNd::FindByTag( $ctxProcess[ 'ndHtml' ], 'head', false );

	$ctxProcess[ 'ndHeadBase' ] = HtmlNd::FindByTag( $ctxProcess[ 'ndHead' ], 'base', false );

	$ctxProcess[ 'ndBody' ] = HtmlNd::FindByTag( $ctxProcess[ 'ndHtml' ], 'body', false );



	if( !$ctxProcess[ 'ndHead' ] || !$ctxProcess[ 'ndBody' ] )

	{

		ContentProcess_FinalizeStat( $ctxProcess );

		$skipStatus = 'err:noHdrOrBody';

		return( $buffer );

	}



	if( ( $ctxProcess[ 'mode' ] & 1 ) && ($ctxProcess[ 'debug' ]??null) )

	{

		{

			$item = $doc -> createElement( 'script' );

			$item -> setAttribute( 'seraph-accel-crit', '1' );

			$item -> nodeValue = htmlspecialchars( '

				(function()

				{

					new PerformanceObserver(

						function( entryList )

						{

							for( const entry of entryList.getEntries() )

							{

								console.log( "LCP candidate: ", entry.startTime, entry );

							}

						}

					).observe( { type: "largest-contentful-paint", buffered: true } );

				})();

			' );

			$ctxProcess[ 'ndHead' ] -> appendChild( $item );

		}



		$item = $doc -> createElement( 'script' );

		$item -> setAttribute( 'type', 'text/javascript' );

		$item -> setAttribute( 'id', 'seraph-accel-testLoad' );

		$item -> nodeValue = htmlspecialchars( '

			(function()

			{

				var callsCheck = {};



				function cr( k, fromFunc )

				{

					console.log( "seraph_accel: \\"" + k + "\\" just triggered" + ( fromFunc ? " from \\"" + fromFunc + "\\"" : "" ) );

					if( !callsCheck[ k ] )

						callsCheck[ k ] = { n: 0 };

					return( callsCheck[ k ] );

				}



				document.addEventListener( "DOMContentLoaded",

					function( e )

					{

						cr( "document.DOMContentLoaded" ).n++;

					}

				);

				window.addEventListener( "DOMContentLoaded",

					function( e )

					{

						cr( "window.DOMContentLoaded" ).n++;

					}

				);



				window.addEventListener( "load",

					function( e )

					{

						cr( "window.load" ).n++;

					}

				);



				window.onload =

					function( e )

					{

						cr( "window.onload", arguments.callee.caller.name ).n++;

					}

				;



				jQuery(

					function()

					{

						cr( "jQuery( func... )" ).n++;

					}

				);



				if( parseInt( jQuery.fn.jquery.split( "." )[ 0 ], 10 ) < 3 )

					jQuery( window ).load(

						function( e )

						{

							var o = cr( "jQuery( window ).load()" );



							o.n++;

							if( cr( "jQuery( func... )" ).n < 1 )

								o.err = "too early";

						}

					);

				else

					cr( "jQuery( window ).load()" ).n++;



				var JQCheck = 0;

				jQuery( document ).ready(

					function( $ )

					{

						var o = cr( "jQuery( document ).ready()" );

						o.n++;

						if( !JQCheck )

							o.err = "not async";

					}

				);

				JQCheck = 1;



				jQuery( document ).on( "ready",

					function( $ )

					{

						var o = cr( "jQuery( document ).on( \\"ready\\" )" );

						o.n++;

					}

				);



				setTimeout(

					function()

					{

						var ak =

						[

							"document.DOMContentLoaded",

							"window.DOMContentLoaded",

							"window.load",

							"window.onload",

							"jQuery( func... )",

							"jQuery( window ).load()",

							"jQuery( document ).ready()",

							"jQuery( document ).on( \\"ready\\" )",

						];



						for( var k in ak )

						{

							cr( ak[ k ] );

						}



						for( var k in callsCheck )

						{

							var o = callsCheck[ k ];

							console.log( "seraph_accel: \\"" + k + "\\": " + ( ( o.n == 1 && !o.err ) ? "OK" : ( "ERROR: fired " + o.n + " times" + ( o.err ? ( ", " + o.err ) : "" ) ) ) );

						}

					}

				, 5 * 1000 );

			})();

		' );

		$ctxProcess[ 'ndBody' ] -> appendChild( $item );

	}



	if( $ctxProcess[ 'mode' ] & 1 )

	{

		$xpath = null;

		foreach( Gen::GetArrField( $settCache, array( 'exclConts' ), array() ) as $pattern )

		{

			if( !$xpath )

				$xpath = new \DOMXPath( $doc );



			if( !HtmlNd::FirstOfChildren( @$xpath -> query( $pattern, $doc ) ) )

				continue;



			ContentProcess_FinalizeStat( $ctxProcess );

			$skipStatus = 'exclConts:' . $pattern;

			return( $buffer );

		}

		unset( $xpath );

	}



	if( $skipStatusEngine = ContentProcess_TryRemote( 0, null, $ctxProcess, $ctxProcessOrig, $settOrig, $doc, $bufferNoOpt, array( '*' ) ) )

	{

		ContentProcess_FinalizeStat( $ctxProcess );

		$skipStatus = $skipStatusEngine;

		return( $buffer );

	}



	{

		$xpath = null;

		foreach( Gen::GetArrField( $settContPr, array( 'cln', 'items' ), array() ) as $pattern )

		{

			if( !$xpath )

				$xpath = new \DOMXPath( $doc );



			foreach( HtmlNd::ChildrenAsArr( @$xpath -> query( $pattern, $doc ) ) as $item )

			{

				if( is_a( $item, 'DOMElement' ) )

					$item -> parentNode -> removeChild( $item );

				else if( is_a( $item, 'DOMAttr' ) && $item -> ownerElement )

					$item -> ownerElement -> removeAttributeNode( $item );

			}

		}

		unset( $xpath );

	}



	$ctxProcess[ 'isAMP' ] = $ctxProcess[ 'ndHtml' ] -> hasAttribute( 'amp' );

	$ctxProcess[ 'isRtl' ] = $ctxProcess[ 'ndHtml' ] -> getAttribute( 'dir' ) === 'rtl';



	$ctxProcess[ 'lazyVidCurId' ] = 0;



	$viewId = 'cmn';

	if( $viewsDeviceGrp = GetCacheViewDeviceGrp( $settCache, $ctxProcess[ 'userAgent' ] ) )

		$viewId = ($viewsDeviceGrp[ 'id' ]??null);



	$contGrpRes = ( $ctxProcess[ 'cbs' ] -> GetContentProcessorForce( $sett ) !== null  ) ? array() : ContGrpsGet( $ctxProcess, Gen::GetArrField( $settContPr, array( 'grps' ), array() ), $doc, $viewId, $grpVariationDataId );



	if( $seraph_accel_g_prepPrms !== null && isset( $seraph_accel_g_prepPrms[ 'lrn' ] ) && !isset( $contGrpRes[ 2 ] ) )

	{

		ContentProcess_FinalizeStat( $ctxProcess );

		$skipStatus = 'grpLrnOff';

		return( $buffer );

	}



	if( isset( $contGrpRes[ 1 ] ) )

	{

		$contGrp = $contGrpRes[ 1 ][ 0 ];



		if( !Gen::GetArrField( $contGrp, array( 'contPr', 'enable' ), false ) )

		{

			ContentProcess_FinalizeStat( $ctxProcess );



			return( $bufferNoOpt );

		}



		if( Gen::GetArrField( $contGrp, array( 'contPr', 'cssOvr' ), false ) )

			Gen::ArrSet( $settContPr[ 'css' ], Gen::GetArrField( $contGrp, array( 'contPr', 'css' ), array() ) );



		foreach( array( array( 'nonCrit', 'inl' ), array( 'nonCrit', 'int' ), array( 'nonCrit', 'ext' ), array( 'nonCrit', 'excl' ), array( 'nonCrit', 'items' ) ) as $fldId )

		{

			if( Gen::GetArrField( $contGrp, array( 'contPr', 'jsNonCritScopeOvr' ), false ) )

				Gen::SetArrField( $settContPr[ 'js' ], $fldId, Gen::GetArrField( $contGrp, array_merge( array( 'contPr', 'js' ), $fldId ) ) );

			Gen::UnsetArrField( $contGrp, array_merge( array( 'contPr', 'js' ), $fldId ) );

		}



		if( Gen::GetArrField( $contGrp, array( 'contPr', 'jsOvr' ), false ) )

			Gen::ArrSet( $settContPr[ 'js' ], Gen::GetArrField( $contGrp, array( 'contPr', 'js' ), array() ) );

	}



	if( $seraph_accel_g_prepContIsUserCtx )

	{

		if( !Gen::GetArrField( $settCache, array( 'ctxContPr' ), true ) )

		{

			ContentProcess_FinalizeStat( $ctxProcess );

			return( $buffer );

		}



		Gen::SetArrField( $settContPr, array( 'css', 'nonCrit', 'auto' ), false );

		Gen::SetArrField( $settContPr, array( 'js', 'optLoad' ), false );

	}



	$settCss = Gen::GetArrField( $settContPr, array( 'css' ), array() );

	$settJs = Gen::GetArrField( $settContPr, array( 'js' ), array() );

	$settCdn = Gen::GetArrField( $settContPr, array( 'cdn' ), array() );

	$settImg = Gen::GetArrField( $settContPr, array( 'img' ), array() );

	$settFrm = Gen::GetArrField( $settContPr, array( 'frm' ), array() );

	$settCp = Gen::GetArrField( $settContPr, array( 'cp' ), array() );



	$jsNotCritsDelayTimeout = ( Gen::GetArrField( $settJs, array( 'optLoad' ), false ) && Gen::GetArrField( $settJs, array( 'nonCrit', 'timeout', 'enable' ), false ) ) ? Gen::GetArrField( $settJs, array( 'nonCrit', 'timeout', 'v' ), 0 ) : null;



	$aFreshItemClassApply = array();



	Images_ProcessSrc_SizeAlternatives_InitDir( $ctxProcess, $settImg );



	$ctxProcess[ 'isJsDelayed' ] = false;

	if(



		!($ctxProcess[ 'compatView' ]??null) && !$ctxProcess[ 'isAMP' ] && $jsNotCritsDelayTimeout )

	{

		$ctxProcess[ 'isJsDelayed' ] = true;

	}



	if( $ctxProcess[ 'isJsDelayed' ] )

	{

		$aBodyClasses = array( 'seraph-accel-js-lzl-ing', 'seraph-accel-js-lzl-ing-ani' );

		if( ($settCache[ 'views' ]??null) )

			$aBodyClasses[] = 'seraph-accel-view-' . $viewId;



		HtmlNd::AddRemoveAttrClass( $ctxProcess[ 'ndBody' ], $aBodyClasses );

		unset( $aBodyClasses );

	}



	if( Gen::LastErrDsc_Is() )

	{

		ContentProcess_FinalizeStat( $ctxProcess );

		$skipStatus = 'err:prepare';

		return( $buffer );

	}



	$ctxProcess[ 'fragments' ] = array();

	if( $aItemSelector = Gen::GetArrField( $settContPr, array( 'fresh', 'items' ), array() ) )

	{

		$xpath = new \DOMXPath( $doc );



		foreach( $aItemSelector as $sel )

		{

			$bShowAlways = false;

			if( Gen::StrStartsWith( $sel, 'sa:' ) )

			{

				$sel = substr( $sel, 3 );

				$bShowAlways = true;

			}



			$res = $xpath -> query( $sel );

			if( $res && $res -> length )

			{

				$sel = md5( $sel );

				$aFreshItemClassApply[] = $sel;



				foreach( $res as $item )

				{

					$item -> setAttribute( 'data-lzl-fr', $sel );

					if( $bShowAlways )

						HtmlNd::AddRemoveAttrClass( $item, array( 'lzl-fr-sa' ) );

					$ctxProcess[ 'fragments' ][] = $item;

				}

			}

		}



		unset( $xpath );



		unset( $aItemSelector );

	}



	$stage = 'contParts'; if( !( $resUpd = $ctxProcess[ 'cbs' ] -> ReportStage( $stage ) ) ) { ContentProcess_FinalizeStat( $ctxProcess ); $skipStatus = ( $resUpd === null ) ? 'aborted' : 'err:internal'; return( $buffer ); }



	{

		$tmStat = microtime( true );



	}



	if( !ContParts_Process( $ctxProcess, $doc, $settCache, $settCp, $settImg, $settFrm, $settCdn, $jsNotCritsDelayTimeout ) )

	{

		ContentProcess_FinalizeStat( $ctxProcess );

		$skipStatus = 'err:' . $stage;

		return( $buffer );

	}



	{

		$tmStat = microtime( true ) - $tmStat;



		$ctxProcess[ 'stat' ][ ucfirst( $stage ) . '-Duration' ] = LocId::Pack( 'TimeDurSec_%1$s', null, array( sprintf( '%.2F', $tmStat ) ) );

	}



	if( $ctxProcess[ 'mode' ] & 1 )

	{

		$settHash = GetContProcSettHash( $settContPr );

		$aSkeletonAggr = null;



		if( $ctxProcess[ 'cbs' ] -> GetContentProcessorForce( $sett ) && ($_REQUEST[ 'd' ]??null) == 'learn' )

		{

			$out = Ui::Tag( 'style', 'pre{tab-size:4;}' ) . Ui::Tag( 'h1', 'Self learning information' );



			$contGrpResTmp = ContGrpsGet( $ctxProcess, Gen::GetArrField( $settContPr, array( 'grps' ), array() ), $doc, $viewId, $grpVariationDataIdTmp );

			if( isset( $contGrpResTmp[ 2 ] ) )

			{

				$ctxProcessNull = null;



				$contGrpTmp = $contGrpResTmp[ 2 ][ 0 ];



				$sklCssSelExcl = ($contGrpTmp[ 'sklSrch' ]??null) ? ($contGrpTmp[ 'sklCssSelExcl' ]??null) : null;

				$contSkeletonHash = ($contGrpTmp[ 'sklSrch' ]??null) === 'a' ? $grpVariationDataIdTmp : GetContSkeleton( $ctxProcessNull, $ctxProcess[ 'ndBody' ], Gen::GetArrField( $contGrpTmp, array( 'sklExcl' ), array() ), $sklCssSelExcl );



				$out .= Ui::Tag( 'h2', 'ID' ) . Ui::Tag( 'p', $contGrpResTmp[ 2 ][ 1 ] . '/' . $contSkeletonHash );

				$out .= Ui::Tag( 'h2', 'Tree' ) . Ui::Tag( 'pre', GetContSkeleton( $ctxProcessNull, $ctxProcess[ 'ndBody' ], Gen::GetArrField( $contGrpTmp, array( 'sklExcl' ), array() ), $sklCssSelExcl, 'text', ($contGrpTmp[ 'sklSrch' ]??null) === 'a' ) );

			}

			else

				$out .= 'Not defined';



			return( $out );

		}



		if( ( $seraph_accel_g_prepPrms !== null  ) && isset( $contGrpRes[ 2 ] ) )

		{



			$contGrp = $contGrpRes[ 2 ][ 0 ];



			if( ($contGrp[ 'sklSrch' ]??null) )

			{

				$ctxProcess[ 'docSkeleton' ] = new \DOMDocument();

				$ctxProcess[ 'docSkeleton' ] -> registerNodeClass( 'DOMElement', 'seraph_accel\\DomElementEx' );

				$ctxProcess[ 'sklCssSelExcl' ] = ($contGrp[ 'sklCssSelExcl' ]??null);

			}



			if( ($contGrp[ 'sklSrch' ]??null) === 'a' )

			{

				$contSkeletonHash = $grpVariationDataId;

				$aSkeletonAggr = GetContSkeleton( $ctxProcess, $ctxProcess[ 'ndBody' ], Gen::GetArrField( $contGrp, array( 'sklExcl' ), array() ), ($ctxProcess[ 'sklCssSelExcl' ]??null), 'tree', true );



			}

			else

				$contSkeletonHash = GetContSkeleton( $ctxProcess, $ctxProcess[ 'ndBody' ], Gen::GetArrField( $contGrp, array( 'sklExcl' ), array() ), ($ctxProcess[ 'sklCssSelExcl' ]??null), 'hash', false, ($ctxProcess[ 'docSkeleton' ]??null) );



			$ctxProcess[ 'lrnFile' ] = ( $seraph_accel_g_ctxCache ? $seraph_accel_g_ctxCache -> viewPath : ( '' ) ) . '/l/' . $contGrpRes[ 2 ][ 1 ] . '/' . $contSkeletonHash . '.dat.gz';

			$ctxProcess[ 'lrnDataPath' ] = Gen::GetFileDir( $ctxProcess[ 'dataPath' ] ) . '/l';

			$seraph_accel_g_prepLearnId = $contGrpRes[ 2 ][ 1 ] . '/' . hex2bin( $contSkeletonHash );



			$oLearnInit = new AnyObj();

			$oLearnInit -> lrnId = substr( $ctxProcess[ 'lrnFile' ], strlen( $ctxProcess[ 'siteCacheRootDir' ] ) );

			$oLearnInit -> lrnFile = $ctxProcess[ 'lrnFile' ];

			$bProcessLearning = $ctxProcess[ 'cbs' ] -> Learn_Init( $oLearnInit, $ctxProcess[ 'siteId' ], isset( $seraph_accel_g_prepPrms[ 'lrn' ] ) , $settHash, !!( $ctxProcess[ 'mode' ] & 4 ), $aSkeletonAggr );

			if( $bProcessLearning === false )

			{

				ContentProcess_FinalizeStat( $ctxProcess );

				$skipStatus = $oLearnInit -> skipStatus;

				return( $buffer );

			}



			$ctxProcess[ 'lrnDsc' ] = $oLearnInit -> lrnDsc;



			if( $bProcessLearning )

			{

				if( !( $ctxProcess[ 'mode' ] & 4 ) )

				{

					$ctxProcess[ 'modeReq' ] |= 4;



				}



				if( $seraph_accel_g_prepPrms )

					$seraph_accel_g_prepPrms[ 'lrn' ] = $oLearnInit -> lrnId;



					$ctxProcess[ 'lrn' ] = $seraph_accel_g_prepPrms[ 'lrn' ];



				if( $aSkeletonAggr !== null )

				{

					$aSkeletonAggr = array_merge_recursive( Gen::GetArrField( $ctxProcess[ 'lrnDsc' ], array( 's' ), array() ), $aSkeletonAggr );

					GetContSkeleton_GenNodesFromAgg( ($ctxProcess[ 'docSkeleton' ]??null), $aSkeletonAggr );



					$ctxProcess[ 'lrnDsc' ][ 's' ] = $aSkeletonAggr;

				}

			}

			else

			{



				if( $aSkeletonAggr !== null )

				{

					$aSkeletonAggr = ( array )($ctxProcess[ 'lrnDsc' ][ 's' ]??null);

					GetContSkeleton_GenNodesFromAgg( ($ctxProcess[ 'docSkeleton' ]??null), $aSkeletonAggr );

				}

			}



		}



		unset( $contGrpRes );

	}



	if( ( $ctxProcess[ 'mode' ] & 4 ) && Gen::GetArrField( $sett, array( 'test', 'optDelay' ), false ) )

	{

		$stage = 'optDelay'; if( !( $resUpd = $ctxProcess[ 'cbs' ] -> ReportStage( $stage ) ) ) { ContentProcess_FinalizeStat( $ctxProcess ); _Learn_Abort( $ctxProcess ); $skipStatus = ( $resUpd === null ) ? 'aborted' : 'err:internal'; return( $buffer ); }



		$timeout = Gen::GetArrField( $sett, array( 'test', 'optDelayTimeout' ), 0 ) / 1000;

		while( $timeout )

		{

			if( $ctxProcess[ 'cbs' ] -> IsAborted() ) { ContentProcess_FinalizeStat( $ctxProcess ); _Learn_Abort( $ctxProcess ); $skipStatus = 'aborted'; return( $buffer ); }



			sleep( 5 );

			$timeout = ( $timeout < 5 ) ? 0 : ( $timeout - 5 );

		}

	}



	unset( $bufferNoOpt );



	if( $ctxProcess[ 'mode' ] & 1 )

	{

		$ctxProcess[ 'aCssCrit' ][ '@\\.lzl-fr-ing@' ] = true;



		foreach( $ctxProcess[ 'fragments' ] as $item )

		{

			HtmlNd::AddRemoveAttrClass( $item, array( 'lzl-fr-ing' ) );

			if( ($ctxProcess[ 'chunksEnabled' ]??null) )

				ContentMarkSeparate( $item, false );

		}

	}



	$stage = 'images'; if( !( $resUpd = $ctxProcess[ 'cbs' ] -> ReportStage( $stage ) ) ) { ContentProcess_FinalizeStat( $ctxProcess ); _Learn_Abort( $ctxProcess ); $skipStatus = ( $resUpd === null ) ? 'aborted' : 'err:internal'; return( $buffer ); }



	ContentProcess_StatStageImg_Begin( $ctxProcess, $stage, $tmStat, $aStatDiff );



	if( !Images_Process( $ctxProcess, $doc, $settCache, $settImg, $settCdn ) )

	{

		ContentProcess_FinalizeStat( $ctxProcess );

		_Learn_Abort( $ctxProcess );

		$skipStatus = 'err:' . $stage;

		return( $buffer );

	}



	ContentProcess_StatStageImg_End( $ctxProcess, $stage, $tmStat, $aStatDiff );



	$stage = 'frames'; if( !( $resUpd = $ctxProcess[ 'cbs' ] -> ReportStage( $stage ) ) ) { ContentProcess_FinalizeStat( $ctxProcess ); _Learn_Abort( $ctxProcess ); $skipStatus = ( $resUpd === null ) ? 'aborted' : 'err:internal'; return( $buffer ); }



	{

		$tmStat = microtime( true );



	}



	if( !Frames_Process( $ctxProcess, $doc, $settCache, $settFrm, $settImg, $settCdn, $settJs ) )

	{

		ContentProcess_FinalizeStat( $ctxProcess );

		_Learn_Abort( $ctxProcess );

		$skipStatus = 'err:' . $stage;

		return( $buffer );

	}



	{

		$tmStat = microtime( true ) - $tmStat;



		$ctxProcess[ 'stat' ][ ucfirst( $stage ) . '-Duration' ] = LocId::Pack( 'TimeDurSec_%1$s', null, array( sprintf( '%.2F', $tmStat ) ) );

	}



	if( ( $ctxProcess[ 'mode' ] & 1 ) && $seraph_accel_g_ahuddqrText )

	{



		if( $itemhuddqr = HtmlNd::ParseAndImport( $doc,

			Ui::Tag( 'a',

				Ui::TagOpen( 'img', array( 'src' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAB4AAAAeCAMAAAAM7l6QAAAABGdBTUEAALGPC/xhBQAAAAFzUkdCAK7OHOkAAAAJcEhZcwAAFxIAABcSAWef0lIAAABOUExURUdwTAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAANyQSi4AAAAZdFJOUwAU7Y9RHUj2hSgEu6fGceGYMw17PLDWXGe4ORhvAAAAz0lEQVQoz42S2RaDIAxEQUBkEXCX///RYtVY28TTPHJhEmbC2J+lFuHmGNtpqO0PlC5fFYW5wUWXQ/1xIScOkDvtpel7NYYWeCUPOjpxNes8XBj2g+beSEKTZlOuv6c0FXDLFPJF6G9RC+pTvMctmgpsFQuBcDDnQlzmhMONYrLKho7A7x/Da3M5kuFto5HS69s3QTzdoyWkw+FYh0FzJpowOoDbCOTXMowInoF6hMr8JM0E4BqN4Um6LN1BNb4jLD1Iv+WL2bMkY7RrEve3L/wzFnTO5UlaAAAAAElFTkSuQmCC', 'alt' => ($seraph_accel_g_ahuddqrText[ 'Title' ]??''), 'style' => array_map( $ctxProcess[ 'isAMP' ] ? function( $v ) { return( $v ); } : function( $v ) { return( '' . $v . '!important' ); }, array( 'display' => 'inline-block', 'vertical-align' => 'top', 'position' => 'absolute', 'width' => 'auto', 'height' => 'auto' ) ) ) ) .

				Ui::Tag( 'span', ( string )sprintf( ($seraph_accel_g_ahuddqrText[ 'BannerText_%s' ]??''), ($seraph_accel_g_ahuddqrText[ 'Title' ]??'') ) . Ui::TagOpen( 'br' ) . Ui::Tag( 'span', ($seraph_accel_g_ahuddqrText[ 'Descr' ]??''), array( 'style' => array_map( $ctxProcess[ 'isAMP' ] ? function( $v ) { return( $v ); } : function( $v ) { return( '' . $v . '!important' ); }, array( 'font-size' => '0.7em' ) ) ) ), array( 'style' => array_map( $ctxProcess[ 'isAMP' ] ? function( $v ) { return( $v ); } : function( $v ) { return( '' . $v . '!important' ); }, array( 'display' => 'inline-block', 'text-align' => 'left', 'vertical-align' => 'top', 'font-size' => '16px', 'padding-left' => '36px' ) ) ) ) .

				( !$ctxProcess[ 'isAMP' ] ? Ui::Tag( 'script', '(function(){var c=document.currentScript.parentNode;setTimeout(function(){var x=new window.XMLHttpRequest();x.onload=function(){if(this.status==200&&this.responseText=="f")c.outerHTML="";};x.open("GET","?seraph_accel_gbnr",true);x.send()},0)})()', array( 'seraph-accel-crit' => '1' ) ) : '' )

			, array( 'href' => Plugin::RmtCfgFld_GetLoc( PluginRmtCfg::Get(), 'Links.FrontendBannerUrl' ), 'target' => '_blank', 'style' => array_map( $ctxProcess[ 'isAMP' ] ? function( $v ) { return( $v ); } : function( $v ) { return( '' . $v . '!important' ); }, array( 'display' => 'none', 'clear' => 'both', 'text-align' => 'center', 'position' => 'relative', 'padding' => '0.5em', 'background-color' => 'transparent', 'color' => '#000', 'line-height' => 1 ) ) ) )

		) )

		{

			$ctxProcess[ 'ndBody' ] -> appendChild( $itemhuddqr );

		}



	}



	if( $ctxProcess[ 'mode' ] & 1 )

	{

		$stage = 'styles'; if( !( $resUpd = $ctxProcess[ 'cbs' ] -> ReportStage( $stage ) ) ) { ContentProcess_FinalizeStat( $ctxProcess ); _Learn_Abort( $ctxProcess ); $skipStatus = ( $resUpd === null ) ? 'aborted' : 'err:internal'; return( $buffer ); }



		{

			$tmStat = microtime( true );



			$aStatDiff = array();

			$aStatDiff[ 'v' ][] = ( float )Gen::GetArrField( $ctxProcess[ '_stat' ], $aStatDiff[ 'k' ][] = array( 'imgRead', 'v' ) );

			$aStatDiff[ 'v' ][] = ( float )Gen::GetArrField( $ctxProcess[ '_stat' ], $aStatDiff[ 'k' ][] = array( 'ai', 'v' ) );

			foreach( array( 'webp','avif' ) as $comprType )

				$aStatDiff[ 'v' ][] = ( float )Gen::GetArrField( $ctxProcess[ '_stat' ], $aStatDiff[ 'k' ][] = array( 'compr', $comprType, 'v' ) );

		}



		$lastBodyChild = $ctxProcess[ 'ndBody' ] -> lastChild;



		if( $ctxProcess[ 'isJsDelayed' ] )

		{



			$xpath = new \DOMXPath( $doc );

			$xpath -> registerNamespace( 'php', 'http://php.net/xpath' );

			$xpath -> registerPhpFunctions( array( 'seraph_accel\\_JsClk_XpathExtFunc_ifExistsThenCssSel' ) );



			$ctxProcess[ 'aJsDelay_firstClickDelayExclCssSel' ] = array();



			foreach( array( 'excl' => 'data-lzl-clk-no', 'exclDef' => 'data-lzl-clk-nodef' ) as $settItem => $prop )

			{

				foreach( Gen::GetArrField( $settJs, array( 'clk', $settItem ), array() ) as $e )

				{

					$jsDelay_firstClickDelayExclCssSel = null;

					$items = null;

					$eOrig = $e;



					$exlScope = '*';

					if( preg_match( '@^([a-z,]*):@S', $e, $m ) )

					{

						$m[ 1 ] = array_unique( explode( ',', $m[ 1 ] ) ); sort( $m[ 1 ] );

						$exlScope = ',' . implode( ',', $m[ 1 ] ) . ',';

						$e = substr( $e, strlen( $m[ 0 ] ) );

					}

					unset( $m );



					if( strpos( $e, '/' ) === false )

					{

						if( $settItem == 'excl' )

							$jsDelay_firstClickDelayExclCssSel = $e;

					}

					else

					{

						$e = str_replace( 'ifExistsThenCssSel(', 'php:function("seraph_accel\\_JsClk_XpathExtFunc_ifExistsThenCssSel",', $e );



						$items = @$xpath -> query( $e, $ctxProcess[ 'ndHtml' ] );

						if( !$items )

							continue;



						foreach( $items as $item )

						{

							if( $item instanceof JsClk_ifExistsThenCssSel )

								$jsDelay_firstClickDelayExclCssSel = $item -> cssSel;

							else if( $item instanceof \DOMElement )

							{

								$item -> setAttribute( $prop, $exlScope );

								if( ($ctxProcess[ 'debug' ]??null) )

									$item -> setAttribute( $prop . '-debug-pattern', $eOrig );

							}

						}

					}



					if( is_string( $jsDelay_firstClickDelayExclCssSel ) && strlen( $jsDelay_firstClickDelayExclCssSel ) )

					{

						if( isset( $ctxProcess[ 'aJsDelay_firstClickDelayExclCssSel' ][ $exlScope ] ) )

							$ctxProcess[ 'aJsDelay_firstClickDelayExclCssSel' ][ $exlScope ] .= ',';

						else

							$ctxProcess[ 'aJsDelay_firstClickDelayExclCssSel' ][ $exlScope ] = '';

						$ctxProcess[ 'aJsDelay_firstClickDelayExclCssSel' ][ $exlScope ] .= $jsDelay_firstClickDelayExclCssSel;

					}

				}



				unset( $items, $jsDelay_firstClickDelayExclCssSel );

			}



			unset( $xpath );

		}



		if( $aCustStyles = Gen::GetArrField( $settCss, array( 'custom' ), array() ) )

		{

			foreach( $aCustStyles as $idStyle => $custStyle )

			{

				if( !($custStyle[ 'enable' ]??null) )

					continue;

				if( !($custStyle[ 'noJsDl' ]??null) && !$ctxProcess[ 'isJsDelayed' ] )

					continue;



				$contCustStyles = '';



				$descr = trim( ( string )($custStyle[ 'descr' ]??null) );

				if( strlen( $descr ) )

					$contCustStyles .= "/* " . $descr . " */\n";

				unset( $descr );



				$contCustStyles .= ($custStyle[ 'data' ]??null);



				if( $contCustStyles )

				{

					$item = $doc -> createElement( 'style' );

					if( $ctxProcess[ 'bJsCssAddType' ] )

						$item -> setAttribute( 'type', 'text/css' );

					$item -> setAttribute( 'id', 'seraph-accel-css-custom-' . $idStyle );

					HtmlNd::SetValFromContent( $item, $contCustStyles );

					unset( $contCustStyles );



					$ctxProcess[ 'ndHead' ] -> appendChild( $item );

				}



				unset( $contCustStyles );

			}



			unset( $aCustStyles, $idStyle, $custStyle );

		}



		$ctxProcess[ 'lazyloadStyles' ] = array();

		$ctxProcess[ 'lazyloadStylesNonCritFont' ] = array();



		if( !Styles_Process( $ctxProcess, $sett, $settCache, $settContPr, $settCss, $settImg, $settCdn, $doc ) )

		{

			ContentProcess_FinalizeStat( $ctxProcess );

			_Learn_Abort( $ctxProcess );

			$skipStatus = 'err:' . $stage;

			return( $buffer );

		}



		{

			$tmStat = microtime( true ) - $tmStat;



			foreach( $aStatDiff[ 'v' ] as $i => $v )

				$tmStat -= ( float )Gen::GetArrField( $ctxProcess[ '_stat' ], $aStatDiff[ 'k' ][ $i ] ) - $v;

			unset( $aStatDiff );

			if( $tmStat < 0 ) $tmStat = 0;



			$ctxProcess[ 'stat' ][ ucfirst( $stage ) . '-Duration' ] = LocId::Pack( 'TimeDurSec_%1$s', null, array( sprintf( '%.2F', $tmStat ) ) );



			$nTotal = 0;

			if( isset( $ctxProcess[ '_stat' ][ 'styleEmbCount' ] ) )

			{

				$ctxProcess[ 'stat' ][ ucfirst( $stage ) . '-Embedded' ] = $ctxProcess[ '_stat' ][ 'styleEmbCount' ];

				$nTotal += $ctxProcess[ '_stat' ][ 'styleEmbCount' ];

			}

			if( isset( $ctxProcess[ '_stat' ][ 'styleInlCount' ] ) )

			{

				$ctxProcess[ 'stat' ][ ucfirst( $stage ) . '-Inlined' ] = $ctxProcess[ '_stat' ][ 'styleInlCount' ];

				$nTotal += $ctxProcess[ '_stat' ][ 'styleInlCount' ];

			}

			if( isset( $ctxProcess[ '_stat' ][ 'styleSrcCount' ] ) )

			{

				$ctxProcess[ 'stat' ][ ucfirst( $stage ) . '-Separated' ] = $ctxProcess[ '_stat' ][ 'styleSrcCount' ];

				$nTotal += $ctxProcess[ '_stat' ][ 'styleSrcCount' ];

			}

			$ctxProcess[ 'stat' ][ ucfirst( $stage ) ] = $nTotal;

			unset( $nTotal );

		}

	}



	{

		$stage = 'scripts'; if( !( $resUpd = $ctxProcess[ 'cbs' ] -> ReportStage( $stage ) ) ) { ContentProcess_FinalizeStat( $ctxProcess ); _Learn_Abort( $ctxProcess ); $skipStatus = ( $resUpd === null ) ? 'aborted' : 'err:internal'; return( $buffer ); }



		{

			$tmStat = microtime( true );



		}



		if( !Scripts_Process( $ctxProcess, $sett, $settCache, $settContPr, $settJs, $settCdn, $doc ) )

		{

			ContentProcess_FinalizeStat( $ctxProcess );

			_Learn_Abort( $ctxProcess );

			$skipStatus = 'err:' . $stage;

			return( $buffer );

		}



		if( $ctxProcess[ 'cbs' ] -> IsAborted( $ctxProcess, $settCache ) ) { ContentProcess_FinalizeStat( $ctxProcess ); _Learn_Abort( $ctxProcess ); $skipStatus = 'aborted'; return( $buffer ); }



		{

			$tmStat = microtime( true ) - $tmStat;



			$ctxProcess[ 'stat' ][ ucfirst( $stage ) . '-Duration' ] = LocId::Pack( 'TimeDurSec_%1$s', null, array( sprintf( '%.2F', $tmStat ) ) );



			$nTotal = 0;

			if( isset( $ctxProcess[ '_stat' ][ 'scriptInlCount' ] ) )

			{

				$ctxProcess[ 'stat' ][ ucfirst( $stage ) . '-Inlined' ] = $ctxProcess[ '_stat' ][ 'scriptInlCount' ];

				$nTotal += $ctxProcess[ '_stat' ][ 'scriptInlCount' ];

			}

			if( isset( $ctxProcess[ '_stat' ][ 'scriptSrcCount' ] ) )

			{

				$ctxProcess[ 'stat' ][ ucfirst( $stage ) . '-Separated' ] = $ctxProcess[ '_stat' ][ 'scriptSrcCount' ];

				$nTotal += $ctxProcess[ '_stat' ][ 'scriptSrcCount' ];

			}

			$ctxProcess[ 'stat' ][ ucfirst( $stage ) ] = $nTotal;

			unset( $nTotal );

		}

	}



	if( $ctxProcess[ 'mode' ] & 1 )

	{

		if( !$ctxProcess[ 'isAMP' ] )

		{

			$stage = 'lazyCont'; if( !( $resUpd = $ctxProcess[ 'cbs' ] -> ReportStage( $stage ) ) ) { _Learn_Abort( $ctxProcess ); $skipStatus = ( $resUpd === null ) ? 'aborted' : 'err:internal'; return( $buffer ); }



			{

				$tmStat = microtime( true );



			}



			$bLazyCont = LazyCont_Process( $ctxProcess, $sett, $settCache, $settContPr, $doc, $norm, $jsNotCritsDelayTimeout );

			if( $bLazyCont === false )

			{

				ContentProcess_FinalizeStat( $ctxProcess );

				_Learn_Abort( $ctxProcess );

				$skipStatus = 'err:' . $stage;

				return( $buffer );

			}



			{

				$tmStat = microtime( true ) - $tmStat;



				$ctxProcess[ 'stat' ][ ucfirst( $stage ) . '-Duration' ] = LocId::Pack( 'TimeDurSec_%1$s', null, array( sprintf( '%.2F', $tmStat ) ) );

			}

		}



		HtmlNd::AddRemoveAttrClass( $ctxProcess[ 'ndBody' ], array(), array( 'seraph-accel-js-lzl-ing-ani' ) );



		$stage = 'final'; if( !( $resUpd = $ctxProcess[ 'cbs' ] -> ReportStage( $stage ) ) ) { ContentProcess_FinalizeStat( $ctxProcess ); _Learn_Abort( $ctxProcess ); $skipStatus = ( $resUpd === null ) ? 'aborted' : 'err:internal'; return( $buffer ); }



		{

			$tmStat = microtime( true );



		}



		{

			if( $ctxProcess[ 'lazyloadStylesNonCritFont' ] )

			{



				{

					$item = $doc -> createElement( 'script' );

					if( $ctxProcess[ 'bJsCssAddType' ] )

						$item -> setAttribute( 'type', 'text/javascript' );

					$item -> setAttribute( 'id', 'seraph-accel-css-pre-lzl' );

					HtmlNd::SetValFromContent( $item, Gen::StrReplaceKeyed(

						array(



							'_PRM_URL_FONT_SRC_' => $ctxProcess[ 'lazyloadStylesNonCritFont' ][ 'src' ],

						),

						"(function(b){var a;b.FontFace&&(a=new FontFace(\"_seraph_accel_lzl_probe\",'url(\"_PRM_URL_FONT_SRC_\")',{display:\"optional\"}))&&a.load();b.seraph_accel_iisrbpb={canLoadNormalImmediately:function(){return!a||\"loaded\"==a.status}}})(window)" ) );



					$ctxProcess[ 'ndHead' ] -> appendChild( $item );

					ContentMarkSeparate( $item );

				}

			}



			{



				$cssLzlItems = array();

				foreach( $ctxProcess[ 'lazyloadStyles' ] as $lazyloadStatus => $lazyloadMode )

					$cssLzlItems[ $jsNotCritsDelayTimeout ? $lazyloadMode : '' ][] = 'link[rel=\\"stylesheet/lzl' . ( $lazyloadStatus == 'nonCrit' ? '-nc' : '' ) . '\\"]';



				if( $cssLzlItems )

				{

					$item = $doc -> createElement( 'script' );

					if( $ctxProcess[ 'bJsCssAddType' ] )

						$item -> setAttribute( 'type', 'text/javascript' );

					$item -> setAttribute( 'id', 'seraph-accel-css-lzl' );

					HtmlNd::SetValFromContent( $item, Gen::StrReplaceKeyed(

						array(

							'_PRM_SEL_NORMAL_NON_GROUPED_' => !Gen::GetArrField( $settCss, array( 'groupFont' ) ) ? '1' : '0',

							'_PRM_SEL_NORMAL_' => '"' . implode( ',', ($cssLzlItems[ '' ]??array()) ) . '"',

							'_PRM_SEL_DELAYED_' => '"' . implode( ',', ($cssLzlItems[ 'withScripts' ]??array()) ) . '"',

							'_PRM_LOWPRI_' => $jsNotCritsDelayTimeout ? '1' : '0',

							'_PRM_SYNC_' => ($settCss[ 'bfrJs' ]??null) ? '1' : '0',

							'_PRM_LOAD_NORMAL_WITH_DELAYED_JS_' => Gen::GetArrField( $settCss, array( 'font', 'delayNonCritWithJs' ) ) ? '1' : '0',

						),

						"(function(h,q,m){function c(b,n,f){b=h.querySelectorAll(b);var p=b.length;p?b.forEach(function(e){function k(){f&&(--p||f())}var a=e.cloneNode();a.rel=\"stylesheet\";if(e.hasAttribute(\"href\")){if(f||n)a.onload&&(a.onloadPrev=a.onload),n&&(a.mediaPrev=a.media?a.media:\"all\",a.media=\"print\"),a.onload=function(){this.mediaPrev&&(this.media=this.mediaPrev,this.mediaPrev=void 0);this.onload=this.onloadPrev;this.onloadPrev=void 0;if(this.onload)try{this.onload()}catch(r){}k()},a.onerror=function(){this.onerror=\nvoid 0;k()};e.parentNode.replaceChild(a,e)}else e.parentNode.replaceChild(a,e),k()}):f&&f()}var d=_PRM_SEL_NORMAL_;if(d.length)if(!q.seraph_accel_iisrbpb||seraph_accel_iisrbpb.canLoadNormalImmediately())c(d,!1),_PRM_SEL_NORMAL_NON_GROUPED_&&h.addEventListener(\"DOMContentLoaded\",function(){c(d,!1)},!1);else if(_PRM_LOAD_NORMAL_WITH_DELAYED_JS_)seraph_accel_izrbpb.add(function(b){if(_PRM_SYNC_)return c(d,!1,b),!0;c(d,!1)},4);else if(_PRM_SYNC_){var g=function(){};seraph_accel_izrbpb.add(function(b){if(g)return g=\nb,!0},4);m(function(){c(d,_PRM_LOWPRI_,function(){g();g=void 0})})}else h.addEventListener(\"DOMContentLoaded\",function(){m(function(){c(d,_PRM_LOWPRI_)})},!1);var l=_PRM_SEL_DELAYED_;l.length&&seraph_accel_izrbpb.add(function(b){if(_PRM_SYNC_)return c(l,!1,b),!0;c(l,!1)},4)})(document,window,setTimeout)" ) );



					$ctxProcess[ 'ndHead' ] -> appendChild( $item );

					ContentMarkSeparate( $item );

				}

			}

		}



		if( ($ctxProcess[ 'lazyload' ]??null) || ($ctxProcess[ 'imgAdaptive' ]??null) )

		{

			{

				$itemInsertBefore = null;

				foreach( $ctxProcess[ 'ndHead' ] -> childNodes as $item )

				{

					if( $item -> nodeName == 'style' || ( $item -> nodeName == 'link' && strpos( $item -> getAttribute( 'rel' ), 'stylesheet' ) === 0 ) )

					{

						$itemInsertBefore = $item;

						break;

					}

				}



				{

					$item = $doc -> createElement( 'style' );

					if( $ctxProcess[ 'bJsCssAddType' ] )

						$item -> setAttribute( 'type', 'text/css' );

					$item -> nodeValue = htmlspecialchars( '.lzl{display:none!important;}' );



					$itemParentCont = $doc -> createElement( 'noscript' );

					$itemParentCont -> appendChild( $item );



					$ctxProcess[ 'ndHead' ] -> insertBefore( $itemParentCont, $itemInsertBefore );

					$itemInsertBefore = $itemParentCont -> nextSibling;



					ContentMarkSeparate( $itemParentCont );

				}



				{

					$item = $doc -> createElement( 'style' );

					if( $ctxProcess[ 'bJsCssAddType' ] )

						$item -> setAttribute( 'type', 'text/css' );

					$item -> nodeValue = htmlspecialchars( ( Gen::GetArrField( $settImg, array( 'lazy', 'smoothAppear' ), false ) ? 'img.lzl,img.lzl-ing{opacity:0.01;}img.lzl-ed{transition:opacity .25s ease-in-out;}' : '' ) . ( $bLazyCont ? 'i[data-lzl-nos]{height:10em;display:block}' : '' ) );



					$ctxProcess[ 'ndHead' ] -> insertBefore( $item, $itemInsertBefore );

					$itemInsertBefore = $item -> nextSibling;



					ContentMarkSeparate( $item );

				}



				unset( $itemInsertBefore );

			}



			{

				{



					$cont = '(function(d){var a=d.querySelectorAll("noscript[lzl]");for(var i=0;i<a.length;i++){var c=a[i];c.parentNode.removeChild(c)}})(document)';



					$item = $doc -> createElement( 'script' );

					if( $ctxProcess[ 'bJsCssAddType' ] )

						$item -> setAttribute( 'type', 'text/javascript' );

					HtmlNd::SetValFromContent( $item, $cont );



					$ctxProcess[ 'ndBody' ] -> appendChild( $item );



					ContentMarkSeparate( $item );

				}



				$cont = 'window.lzl_lazysizesConfig={};';



				if( ($ctxProcess[ 'imgAdaptive' ]??null) )

				{



					$bSepStg = Gen::GetArrField( $settImg, array( 'szAdaptAsync' ), false ) || Gen::GetArrField( $settImg, array( 'szAdaptOnDemand' ), false );

					$cont .= str_replace( array( 'COMPILE_FAKE_CROP_SEL_SYMBOL', 'COMPILE_PROCESS_BJS', 'COMPILE_FAKE_DEVICEPIXELRATIO_MIN' ), array( $bSepStg ? 'c' : '@', ($ctxProcess[ 'imgAdaptiveBjs' ]??null) ? '1' : '0', ( string )( Gen::GetArrField( $settImg, array( 'szAdaptDprMin' ), 100 ) / 100 ) ), "(function(m,n){function t(e){if(!e)return[];for(var h=[],c=[e.szOrig[0],2160,1920,1366,992,768,480,360,120],b=0;b<c.length;b++){var a=c[b];if(!(b&&a>=e.szOrig[0])){if((b||!e.nRenderMinRatio)&&e.cx>a)break;if(b){var d=h,g=\"\"+a,f=e.aDim;f&&-1==f.indexOf(g)||d.push(g)}if(e.nRenderMinRatio||e.cxRenderMin)for(d=1;d<c.length;d++)if(f=c[d],!(f>=a)){if(e.nRenderMinRatio&&e.nRenderMinRatio>f/(a/e.szOrig[0]*e.szOrig[1]))break;if(e.cxRenderMin&&e.cxRenderMin>f)break;g=h;f=\"\"+(b?a:\"O\")+\"COMPILE_FAKE_CROP_SEL_SYMBOL\"+\nf;var k=e.aDim;k&&-1==k.indexOf(f)||g.push(f)}}}return h}function u(e,h=!1){if(h||!e.classList.contains(\"lzl\")&&!e.classList.contains(\"lzl-ing\")){try{var c=JSON.parse(e.getAttribute(\"data-ai-img\"))}catch(l){}if(c){var b=c,a=getComputedStyle(e);var d=[e.clientWidth,e.clientHeight];if(0>=d[0]||0>=d[1])d=void 0;else{if(\"y\"==e.getAttribute(\"data-ai-dpr\")){var g=window.devicePixelRatio;g<COMPILE_FAKE_DEVICEPIXELRATIO_MIN&&(g=COMPILE_FAKE_DEVICEPIXELRATIO_MIN);d[0]*=g;d[1]*=g}var f=b.s;if(f[1]){var k=a.getPropertyValue(\"object-fit\");\nb={szOrig:f};g=null;\"contain\"==k?(k=d[0]/d[1],f=f[0]/f[1],b.cx=f>k?d[0]:Math.round(f*d[1])):\"cover\"==k?(k=d[0]/d[1],f=f[0]/f[1],k>f?b.cx=d[0]:(b.cx=Math.round(f*d[1]),g=\"cover\")):b.cx=d[0];g&&(a=a.getPropertyValue(\"object-position\"),a=a.split(\" \")[0],\"50%\"==a&&\"cover\"==g&&(b.nRenderMinRatio=d[0]/d[1]));d=b}else d=void 0}a=t(d);d=c.O;for(b=a.length;0<b;b--)if(g=a[b-1],c.st){if(-1!=c.d.indexOf(g)){d=c.st.replace(\"_SERAPH_ACCEL_AID_\",g);break}}else if(g=c.d[g]){d=g;break}c=\"src\";h&&e.hasAttribute(\"data-lzl-src\")&&\n(c=\"data-lzl-src\");e.getAttribute(c)!=d&&e.setAttribute(c,d)}}}function v(e){var h=t(function(c){var b,a=[void 0,\"::before\",\"::after\"];for(g in a){var d=getComputedStyle(c,a[g]);if(b=d.getPropertyValue(\"--ai-bg-sz\"))break}if(\"fixed\"==d.getPropertyValue(\"background-attachment\"))a=[window.visualViewport.width,window.visualViewport.height];else{a=[c.clientWidth,c.clientHeight];var g=d.getPropertyValue(\"background-origin\");if(\"content-box\"==g||\"padding-box\"==g)a[0]-=parseInt(d.getPropertyValue(\"border-left-width\"),\n10)+parseInt(d.getPropertyValue(\"border-right-width\"),10),a[1]-=parseInt(d.getPropertyValue(\"border-top-width\"),10)+parseInt(d.getPropertyValue(\"border-bottom-width\"),10);\"content-box\"==g&&(a[0]-=parseInt(d.getPropertyValue(\"padding-left\"),10)+parseInt(d.getPropertyValue(\"padding-right\"),10),a[1]-=parseInt(d.getPropertyValue(\"padding-top\"),10)+parseInt(d.getPropertyValue(\"padding-bottom\"),10))}if(!(0>=a[0]||0>=a[1])){\"y\"==c.getAttribute(\"data-ai-dpr\")&&(c=window.devicePixelRatio,c<COMPILE_FAKE_DEVICEPIXELRATIO_MIN&&\n(c=COMPILE_FAKE_DEVICEPIXELRATIO_MIN),a[0]*=c,a[1]*=c);try{var f=JSON.parse(JSON.parse(b))}catch(q){}if(!f){b=b.split(\" \");if(!b[1])return;f=[[[parseInt(b[0],10),parseInt(b[1],10)]]]}b=d.getPropertyValue(\"background-size\").split(\",\").map(function(q){return q.trim()});d=d.getPropertyValue(\"background-position-x\").split(\",\").map(function(q){return q.trim()});for(var k in f){g=f[k];c=b[k];for(var l in g)return f={},b=g[l],b.s&&(f.aDim=b.d,b=b.s),f.szOrig=b,l=null,\"auto\"==c?(f.cx=b[0],l=\"auto\"):\"contain\"==\nc?(c=a[0]/a[1],b=b[0]/b[1],f.cx=b>c?a[0]:Math.round(b*a[1])):\"cover\"==c?(c=a[0]/a[1],b=b[0]/b[1],c>b?f.cx=a[0]:(f.cx=Math.round(b*a[1]),l=\"cover\")):(c=c.split(\" \")[0],c.lastIndexOf(\"%\")==c.length-1?f.cx=Math.round(parseInt(c,10)/100*a[0]):c.lastIndexOf(\"px\")==c.length-2?f.cx=parseInt(c,10):f.cx=999999999),l&&\"50%\"==d[k]&&(\"auto\"==l?f.cxRenderMin=a[0]:\"cover\"==l&&(f.nRenderMinRatio=a[0]/a[1])),f}}}(e));h.length||h.push(\"O\");h=\"-\"+h.join(\"-\")+\"-\";e.getAttribute(\"data-ai-bg\")!=h&&e.setAttribute(\"data-ai-bg\",\nh)}function p(){r||(r=setTimeout(function(){r=void 0;w()},250))}function w(){var e;for(e=0;e<x.length;e++)u(x[e]);for(e=0;e<y.length;e++)v(y[e])}n.lzl_lazysizesConfig.beforeCheckElem=function(e){e.classList.contains(\"ai-img\")&&u(e,!0);e.classList.contains(\"ai-bg\")&&v(e)};var y=m.getElementsByClassName(\"ai-bg\"),x=m.getElementsByClassName(\"ai-img\"),r;m.addEventListener(\"DOMContentLoaded\",w,!1);n.addEventListener(\"hashchange\",p,!0);n.addEventListener(\"resize\",p,!1);n.MutationObserver?(new n.MutationObserver(p)).observe(m.documentElement,\n{childList:!0,subtree:!0,attributes:!0}):(m.documentElement.addEventListener(\"DOMNodeInserted\",p,!0),m.documentElement.addEventListener(\"DOMAttrModified\",p,!0));COMPILE_PROCESS_BJS&&seraph_accel_izrbpb.add(function(){for(var e=m.querySelectorAll(\".ai-img.ai-bjs\"),h=0;h<e.length;h++){var c=e[h];c.classList.remove(\"ai-img\");try{var b=JSON.parse(c.getAttribute(\"data-ai-img\"))}catch(a){}b&&[\"src\",\"data-lzl-src\"].forEach(function(a){c.hasAttribute(a)&&c.getAttribute(a)!=b.O&&c.setAttribute(a,b.O)})}},\n118)})(document,window);" );

				}



				if( $bLazyCont )

				{



					$cont .= str_replace( array( '_URL_GET_CONTPARTS_' ), array( ContentProcess_GetGetPartUri( $ctxProcess, '{id}.html' ) ), ";(function(g,h,r){function t(a,d,b,f){var c=b.getAttribute(\"data-lzl-nos\");if(c){var e=b.getAttribute(\"data-cp\");if(e)a[e]=b;else{if(a=b.getAttribute(\"data-c\"))a=decodeURIComponent(a);else if(c=b.parentNode.querySelector('noscript[data-lzl-nos-c=\"'+c+'\"]'))a=c.textContent,c.parentNode.removeChild(c);b.outerHTML=a;f&&(f.fire(d,\"seraph_accel_lzlNosLoaded\",{},!1,!0),f.fire(h,\"resize\",{},!1,!0))}}}function u(a,d){var b=Object.getOwnPropertyNames(a);if(b.length){d&&m++;var f=new h.XMLHttpRequest;f.open(\"GET\",\n\"_URL_GET_CONTPARTS_\".replace(\"%7Bid%7D\",\"_\"+b.join(\"_\")),!0);f.onload=function(){if(200==this.status){try{for(var c=0,e,k,l;;){e=this.responseText.indexOf(\"\\x3c!--seraph_accel_gp=\",c);if(-1==e)break;c=e+20;e=this.responseText.indexOf(\"--\\x3e\",c);if(-1==e)break;k=parseInt(this.responseText.substring(c,e),10);c=e+3;e=this.responseText.indexOf(\"\\x3c!--seraph_accel_gp--\\x3e\",c);if(-1==e)break;if(l=a[k])l.outerHTML=this.responseText.substring(c,e);c=e+22}}catch(y){}h.lzl_lazySizes.fire(g,\"seraph_accel_lzlNosLoaded\",\n{},!1,!0)}d&&(m--,!m&&n&&(n(),n=void 0))};f.send()}}function p(a,d,b){if(void 0!==d){if(\"string\"!==typeof d)return;var f=d.indexOf(\"#\");if(-1==f)return;f=d.substr(f+1)}if(void 0===f||!a.querySelector('[id=\"'+f+'\"]')){d=h.lzl_lazySizes;for(var c=a.querySelectorAll((b?\".bjs\":\"\")+\"[data-lzl-nos]\"),e={},k=0;k<c.length;k++){var l=c[k];l.classList.remove(\"lzl\");t(e,a,l,d);if(void 0!==f&&a.querySelector('[id=\"'+f+'\"]'))break}u(e,b)}}function v(a,d){var b={};t(b,a.ownerDocument,a,d);u(b,a.classList.contains(\"bjs\"))}\nfunction w(a){p(g,location.href);g.removeEventListener(r,w,{capture:!0,passive:!0});x=!0;setTimeout(function(){var d=h.lzl_lazySizes,b;for(b in q)v(q[b],d);q=void 0},0)}var m=0,n,x,q=[];h.lzl_lazysizesConfig.beforeUnveil=function(a,d){x?v(a,d):a.getAttribute(\"data-lzl-nos\")&&q.push(a)};g.addEventListener(r,w,{capture:!0,passive:!0});g.addEventListener(\"click\",function(a){p(g,a.target.getAttribute(\"href\"))},{capture:!0,passive:!0});g.addEventListener(\"keydown\",function(a){(70==a.keyCode&&(a.ctrlKey||\na.metaKey)||191==a.keyCode)&&p(g)},{capture:!0,passive:!0});seraph_accel_izrbpb.add(function(a){p(g,void 0,!0);if(m)return n=a,!0},5)})(document,window,\"DOMContentLoaded\");" );

				}



				$cont .=

					@file_get_contents( __DIR__ . '/Cmn/Ext/JS/lazysizes/lazysizes' . $ctxProcess[ 'jsMinSuffix' ] . '.js' ) .

					@file_get_contents( __DIR__ . '/Cmn/Ext/JS/lazysizes/plugins/unveilhooks/ls.unveilhooks' . $ctxProcess[ 'jsMinSuffix' ] . '.js' ) .

					'';



				if( ($ctxProcess[ 'lazyloadBjs' ]??null) )

				{



					$cont .= "(function(e){seraph_accel_izrbpb.add(function(){for(var d=e.querySelectorAll(\".bjs[data-lzl-src]:is(.lzl,.lzl-ing):not(.lzl-ed),.bjs[data-lzl-v-src]\"),b,c=0;c<d.length;c++){var a=d[c];a.classList.add(\"lzl-ed\");a.classList.remove(\"lzl\");(b=a.getAttribute(\"data-lzl-v-src\"))?(a.setAttribute(\"allow\",String(a.getAttribute(\"allow\")).replace(\"autoplay\",\"\")),a.setAttribute(\"src\",b),a.removeAttribute(\"data-lzl-v-src\"),a.removeAttribute(\"data-lzl-src\")):(b=a.getAttribute(\"data-lzl-src\"))&&a.setAttribute(\"src\",\nb);(b=a.getAttribute(\"data-lzl-srcset\"))&&a.setAttribute(\"srcset\",b);(b=a.getAttribute(\"data-lzl-sizes\"))&&a.setAttribute(\"sizes\",b)}},120)})(document);";

				}



				$item = $doc -> createElement( 'script' );

				if( $ctxProcess[ 'bJsCssAddType' ] )

					$item -> setAttribute( 'type', 'text/javascript' );

				$item -> setAttribute( 'id', 'seraph-accel-lzl' );



				HtmlNd::SetValFromContent( $item, $cont );



				$ctxProcess[ 'ndHead' ] -> appendChild( $item );



				ContentMarkSeparate( $item );

				unset( $cont );

			}

		}



		if( ($ctxProcess[ 'lazyVid' ]??null) )

		{



			$item = $doc -> createElement( 'script' );

			if( $ctxProcess[ 'bJsCssAddType' ] )

				$item -> setAttribute( 'type', 'text/javascript' );

			$item -> setAttribute( 'id', 'seraph-accel-lzl-v' );

			$item -> setAttribute( 'seraph-accel-crit', '1' );

			HtmlNd::SetValFromContent( $item, "(function(d,f){function g(a,b){(function(c){if(d.YT)c();else{var e=f.createElement(\"script\");e.type=\"text/javascript\";e.src=\"https://www.youtube.com/iframe_api\";e.onload=c;f.head.appendChild(e)}})(function(){d.YT.ready(function(){new d.YT.Player(a,{events:{onReady:function(c){c.target.playVideo()}}});b&&\"string\"==typeof a.src&&(a.src=a.src.replace(\"autoplay=0\",\"autoplay=1\"))})})}d.addEventListener(\"message\",function(a){if(\"string\"==typeof a.data){a=a.data.split(\":\");var b=a[1];a=a[0];if(\"seraph-accel-lzl-v\"==\na&&(a=f.querySelectorAll('iframe[lzl-v][data-id=\"'+b+'\"]'),a.length)){b=0;if(1<a.length){for(;b<a.length;b++){var c=a[b];if(c.offsetWidth||c.offsetHeight||c.getClientRects().length)break}if(b==a.length)return}a=a[b];a.src=a.getAttribute(\"data-lzl-v-src\");a.removeAttribute(\"data-lzl-v-src\");a.removeAttribute(\"data-lzl-src\");\"youtube\"==a.getAttribute(\"data-lzl-v-svc\")&&g(a)}}},!1);d.seraph_accel_youTubeFeedPlayVideo=function(a){a.setAttribute(\"onclick\",\"return false\")}})(window,document)" );

			$ctxProcess[ 'ndBody' ] -> appendChild( $item );



			ContentMarkSeparate( $item, false );

		}



		if( ($settContPr[ 'earlyPaint' ]??null) && !($ctxProcess[ 'compatView' ]??null) && !$ctxProcess[ 'isAMP' ] )

		{



			{

				$item = $doc -> createElement( 'img' );



				$item -> setAttribute( 'style', Ui::GetStyleAttr( array( 'z-index' => -99999, 'position' => 'fixed', 'top' => 0, 'left' => 0, 'margin' => '1px', 'max-width' => 'none!important', 'max-height' => 'none!important', 'width' => '100vw!important', 'height' => '100vh!important' ) ) );

				$item -> setAttribute( 'onload', 'var i=this,d=document;function c(e){d.removeEventListener(e.type,c);setTimeout(function(){i.parentNode.removeChild(i)},250)}d.addEventListener("DOMContentLoaded",c)' );

				$item -> setAttribute( 'src',

					LazyLoad_SrcSubst( $ctxProcess, array( 'cx' => 1000, 'cy' => 1000 ), Gen::GetArrField( $settImg, array( 'lazy', 'plchRast' ), true ) )



				);

				$item -> setAttribute( 'alt', '...' );



				HtmlNd::InsertChild( $ctxProcess[ 'ndBody' ], 0, $item );



			}



		}



		if( ( ($settCache[ 'cron' ]??null) && CacheDoesCronDelayPageLoad() && !$ctxProcess[ 'isAMP' ] )  )

		{

			$urlCron = $ctxProcess[ 'siteRootUri' ] . $ctxProcess[ 'wpRootSubPath' ] . 'wp-cron.php';

			if( $ctxProcess[ 'isAMP' ] )

				$urlCron = $ctxProcess[ 'siteDomainUrl' ] . $urlCron;



			$cont = 'setTimeout(function(){var x=new window.XMLHttpRequest();x.open("GET","' . $urlCron . '",true);x.send()},0)';



			if( $ctxProcess[ 'isAMP' ] )

			{

				$itemAmpScriptMjsTpl = null;

				$itemAmpScriptJsTpl = null;

				$itemAmpMetaScriptSrc = null;



				foreach( $ctxProcess[ 'ndHead' ] -> childNodes as $item )

				{

					if( $item -> nodeType != XML_ELEMENT_NODE )

						continue;



					if( $item -> nodeName == 'script' )

					{

						$m = array();

						if( preg_match( '@//cdn\\.ampproject\\.org/v\\d+/([a-z-]+)-(?:[\\d+\\.]+|latest)\\.(m?)js@', $item -> getAttribute( 'src' ), $m, PREG_OFFSET_CAPTURE ) )

						{

							if( $m[ 2 ][ 0 ] === 'm' )

							{

								if( $itemAmpScriptMjsTpl !== false )

									$itemAmpScriptMjsTpl = ( $m[ 1 ][ 0 ] === 'amp-script' ) ? false : array( 'item' => $item, 'm' => $m );

							}

							else

							{

								if( $itemAmpScriptJsTpl !== false )

									$itemAmpScriptJsTpl = ( $m[ 1 ][ 0 ] === 'amp-script' ) ? false : array( 'item' => $item, 'm' => $m );

							}

						}

					}



					if( !$itemAmpMetaScriptSrc && $item -> nodeName == 'meta' && $item -> getAttribute( 'name' ) == 'amp-script-src' )

						$itemAmpMetaScriptSrc = $item;

				}



				foreach( array( $itemAmpScriptMjsTpl, $itemAmpScriptJsTpl ) as $itemAmpScriptTpl )

				{

					if( !$itemAmpScriptTpl )

						continue;



					$item = $itemAmpScriptTpl[ 'item' ] -> cloneNode( true );

					$item -> setAttribute( 'custom-element', 'amp-script' );

					$src = $item -> getAttribute( 'src' );

					$item -> setAttribute( 'src', substr_replace( $src, 'amp-script', $itemAmpScriptTpl[ 'm' ][ 1 ][ 1 ], strlen( $itemAmpScriptTpl[ 'm' ][ 1 ][ 0 ] ) ) );



					$ctxProcess[ 'ndHead' ] -> appendChild( $item );

				}



				if( !$itemAmpMetaScriptSrc )

				{

					$itemAmpMetaScriptSrc = $doc -> createElement( 'meta' );

					$itemAmpMetaScriptSrc -> setAttribute( 'name', 'amp-script-src' );

					$ctxProcess[ 'ndHead' ] -> appendChild( $itemAmpMetaScriptSrc );

				}



				if( function_exists( 'hash' ) )

					$itemAmpMetaScriptSrc -> setAttribute( 'content', $itemAmpMetaScriptSrc -> getAttribute( 'content' ) . ' sha384-' . str_replace( array( '=', '+', '/' ), array( '', '-', '_' ), base64_encode( hash( 'sha384', $cont, true ) ) ) );



				$item = HtmlNd::Parse( Ui::Tag( 'amp-script', null, array( 'script' => 'seraph-accel-cron', 'layout' => 'fixed', 'height' => '1', 'width' => '1', 'style' => array( 'position' => 'fixed', 'top' => '0', 'left' => '0', 'visibility' => 'hidden' ) ) ) );

				if( $item && $item -> firstChild && ( $item = $doc -> importNode( $item -> firstChild, true ) ) )

					$ctxProcess[ 'ndBody' ] -> appendChild( $item );

			}



			$item = $doc -> createElement( 'script' );

			$item -> setAttribute( 'id', 'seraph-accel-cron' );



			if( $ctxProcess[ 'isAMP' ] )

			{

				$item -> setAttribute( 'type', 'text/plain' );

				$item -> setAttribute( 'target', 'amp-script' );

			}

			else

			{

				if( $ctxProcess[ 'bJsCssAddType' ] )

					$item -> setAttribute( 'type', 'text/javascript' );

			}



			$item -> nodeValue = htmlspecialchars( $cont );

			$ctxProcess[ 'ndBody' ] -> appendChild( $item );

		}



		if( $aFreshItemClassApply )

		{

			if( $ctxProcess[ 'isAMP' ] )

			{



			}

			else

			{

				$item = $doc -> createElement( 'script' );

				if( $ctxProcess[ 'bJsCssAddType' ] )

					$item -> setAttribute( 'type', 'text/javascript' );

				$item -> setAttribute( 'id', 'seraph-accel-freshParts' );



				HtmlNd::SetValFromContent( $item, str_replace( array( '_URL_GET_FRESH_', '_ARRAY_SELECTORS_' ), array( ContentProcess_GetCurRelatedUri( $ctxProcess, array( 'seraph_accel_gf' => '{tm}' ) ), implode( ',', array_map( function( $v ) { return( '"' . $v . '"' ); }, $aFreshItemClassApply ) ) ), "(function(b,l,h){function g(){e&&([_ARRAY_SELECTORS_].forEach(function(a){a='[data-lzl-fr=\"'+a+'\"]';var c=b.querySelectorAll(a+\".lzl-fr-ing\");a=e.querySelectorAll(a+\":not(.lzl-fr-ed)\");for(var d=0;d<c.length;d++)d<a.length&&(c[d].innerHTML=a[d].innerHTML,a[d].classList.add(\"lzl-fr-ed\")),c[d].classList.remove(\"lzl-fr-ing\")}),e.querySelectorAll(\"[data-lzl-fr]:not(.lzl-fr-ed)\").length||(b.removeEventListener(\"seraph_accel_lzlNosLoaded\",g,{capture:!0,passive:!0}),e=void 0))}var f=new l.XMLHttpRequest,\nk=function(){},e;seraph_accel_izrbpb.add(function(a){if(f)return k=a,!0},5);b.addEventListener(\"seraph_accel_lzlNosLoaded\",g,{capture:!0,passive:!0});f.open(\"GET\",\"_URL_GET_FRESH_\".replace(\"%7Btm%7D\",\"\"+Date.now()),!0);f.setRequestHeader(\"Accept\",\"text/html\");f.onload=function(){function a(c=!0){c&&b.removeEventListener(h,a);g();f=void 0;c=b.createEvent(\"Events\");c.initEvent(\"seraph_accel_freshPartsDone\",!0,!1);b.dispatchEvent(c);k()}e=b.implementation.createHTMLDocument(\"\");200==this.status&&(e.documentElement.innerHTML=\nthis.responseText);\"loading\"!=b.readyState?a(!1):b.addEventListener(h,a,!1)};f.send()})(document,window,\"DOMContentLoaded\")" ) );

				$ctxProcess[ 'ndHead' ] -> insertBefore( $item, $ctxProcess[ 'ndHead' ] -> firstChild );

				ContentMarkSeparate( $item );

			}



			{

				$item = $doc -> createElement( 'style' );

				if( $ctxProcess[ 'bJsCssAddType' ] )

					$item -> setAttribute( 'type', 'text/css' );

				$item -> nodeValue = htmlspecialchars( '[data-lzl-fr].lzl-fr-ing:not(.lzl-fr-sa) *{opacity:0;visibility:hidden;}' . ( Gen::GetArrField( $settContPr, array( 'fresh', 'smoothAppear' ), false ) ? '[data-lzl-fr]:not(.lzl-fr-sa, .lzl-fr-ing) *{transition:opacity .25s ease-in-out;}' : '' ) );



				$ctxProcess[ 'ndHead' ] -> appendChild( $item );

				ContentMarkSeparate( $item );

			}

		}



		if( $ctxProcess[ 'isJsDelayed' ] && Gen::GetArrField( $settJs, array( 'prvntDblInit' ), false ) )

		{



			$item = $doc -> createElement( 'script' );

			if( $ctxProcess[ 'bJsCssAddType' ] )

				$item -> setAttribute( 'type', 'text/javascript' );

			$item -> setAttribute( 'id', 'seraph-accel-prvntDblInit' );

			HtmlNd::SetValFromContent( $item, "(function(d,e,k){function f(a){a.seraph_accellzl_el_f=a.addEventListener;a.seraph_accellzl_el_a=[];a.addEventListener=function(c,b,g){a.seraph_accellzl_el_f&&(-1!=k.indexOf(c)&&a.seraph_accellzl_el_a.push({t:c,l:b,o:g}),a.seraph_accellzl_el_f(c,b,g))}}function h(a){for(var c in a.seraph_accellzl_el_a){var b=a.seraph_accellzl_el_a[c];a.removeEventListener(b.t,b.l,b.o)}a.addEventListener=a.seraph_accellzl_el_f;delete a.seraph_accellzl_el_f;delete a.seraph_accellzl_el_a}f(d);f(e);seraph_accel_izrbpb.add(function(){h(e);\nh(d)},5)})(document,window,[\"DOMContentLoaded\",\"load\"])" );

			$ctxProcess[ 'ndHead' ] -> insertBefore( $item, $ctxProcess[ 'ndHead' ] -> firstChild );

		}



		if( !$ctxProcess[ 'isAMP' ] )

		{



			$item = $doc -> createElement( 'script' );

			if( $ctxProcess[ 'bJsCssAddType' ] )

				$item -> setAttribute( 'type', 'text/javascript' );

			HtmlNd::SetValFromContent( $item, "document.seraph_accel_usbpb=document.createElement;seraph_accel_izrbpb={add:function(b,a=10){void 0===this.a[a]&&(this.a[a]=[]);this.a[a].push(b)},a:{}}" );

			$ctxProcess[ 'ndHead' ] -> insertBefore( $item, $ctxProcess[ 'ndHead' ] -> firstChild );

		}



		if( ($ctxProcess[ 'jsDelay' ]??null) )

			Scripts_ProcessAddRtn( $ctxProcess, $sett, $settCache, $settContPr, $settJs, $settCdn, $doc, $ctxProcess[ 'jsDelay' ] );

	}



	{

		$xpath = new \DOMXPath( $doc );

		foreach( array( HtmlNd::FirstOfChildren( $xpath -> query( './meta[@http-equiv="Content-Type"]', $ctxProcess[ 'ndHead' ] ) ), HtmlNd::FirstOfChildren( $xpath -> query( './meta[@charset]', $ctxProcess[ 'ndHead' ] ) ) ) as $item )

			if( $item && $item !== $ctxProcess[ 'ndHead' ] -> firstChild )

				$ctxProcess[ 'ndHead' ] -> insertBefore( $item, $ctxProcess[ 'ndHead' ] -> firstChild );

		unset( $xpath );

	}



	if( ($ctxProcess[ 'chunksEnabled' ]??null) )

	{

		$settChunks = Gen::GetArrField( $settCache, array( 'chunks' ), array() );



		$xpath = new \DOMXPath( $doc );



		foreach( Gen::GetArrField( $settChunks, array( 'seps' ), array() ) as $sep )

		{

			if( !($sep[ 'enable' ]??null) )

				continue;



			$xpathQ = ($sep[ 'sel' ]??null);

			foreach( HtmlNd::ChildrenAsArr( $xpath -> query( $xpathQ, $ctxProcess[ 'ndHtml' ] ) ) as $item )

				ContentMarkSeparate( $item, false, $sep[ 'side' ] );

		}

	}



	if( $ctxProcess[ 'cbs' ] -> IsAborted( $ctxProcess, $settCache ) ) { ContentProcess_FinalizeStat( $ctxProcess ); _Learn_Abort( $ctxProcess ); $skipStatus = 'aborted'; return( $buffer ); }



	if( $ctxProcess[ 'mode' ] & 256 )

	{



		HtmlNd::ClearAllAttrs( $ctxProcess[ 'ndHtml' ] );

		HtmlNd::ClearAllAttrs( $ctxProcess[ 'ndHead' ] );

		HtmlNd::ClearAllAttrs( $ctxProcess[ 'ndBody' ] );



		foreach( HtmlNd::ChildrenAsArr( $ctxProcess[ 'ndHead' ] -> childNodes ) as $item )

		{

			if( $item -> nodeType == XML_ELEMENT_NODE && $item -> nodeName == 'meta' && $item -> hasAttribute( 'http-equiv' ) )

				continue;



			$ctxProcess[ 'ndHead' ] -> removeChild( $item );

		}



		$itemLast = $ctxProcess[ 'ndBody' ] -> lastChild;

		foreach( $ctxProcess[ 'fragments' ] as $item )

		{

			$ctxProcess[ 'ndBody' ] -> appendChild( $item );

			if( ($ctxProcess[ 'chunksEnabled' ]??null) )

				ContentMarkSeparate( $item, false );

		}



		foreach( HtmlNd::ChildrenAsArr( $ctxProcess[ 'ndBody' ] -> childNodes ) as $item )

		{

			$ctxProcess[ 'ndBody' ] -> removeChild( $item );

		  	if( $item === $itemLast )

				break;

		}



		foreach( HtmlNd::ChildrenAsArr( $doc -> getElementsByTagName( 'script' ) ) as $item )

			if( !ContentProcess_IsItemInFragments( $ctxProcess, $item, function( $itemFragment, $item ) { $type = $item -> getAttribute( 'type' ); return( $type == 'o/js-lzl' || $type == 'o/js-lzls' ); } ) )

				$item -> parentNode -> removeChild( $item );

		foreach( HtmlNd::ChildrenAsArr( $doc -> getElementsByTagName( 'noscript' ) ) as $item )

			$item -> parentNode -> removeChild( $item );



		foreach( HtmlNd::ChildrenAsArr( $doc -> getElementsByTagName( 'style' ) ) as $item )

		    $item -> parentNode -> removeChild( $item );

		foreach( HtmlNd::ChildrenAsArr( $doc -> getElementsByTagName( 'link' ) ) as $item )

			if( $item -> getAttribute( 'rel' ) == 'stylesheet' )

				$item -> parentNode -> removeChild( $item );

	}



	global $seraph_accel_g_cacheObjChildren;

	global $seraph_accel_g_cacheObjSubs;

	$seraph_accel_g_cacheObjChildren = DepsExpand( $ctxProcess[ 'deps' ], false );

	$seraph_accel_g_cacheObjSubs = $ctxProcess[ 'subs' ];



	$buffer = HtmlDocDeParse( $doc, $norm );



	if( $ctxProcess[ 'mode' ] & 256 )

		$buffer = ContentDisableIndexingEx( $buffer );



	{

		$tmStat = microtime( true ) - $tmStat;



		$ctxProcess[ 'stat' ][ ucfirst( $stage ) . '-Duration' ] = LocId::Pack( 'TimeDurSec_%1$s', null, array( sprintf( '%.2F', $tmStat ) ) );

	}



	ContentProcess_FinalizeStat( $ctxProcess );



	if( ( $ctxProcess[ 'mode' ] & 4 ) && _Learn_Finish( $ctxProcess, $settHash ) === false )

	{

		$skipStatus = 'err:writeLrnDone';

		return( $buffer );

	}



	return( $buffer );

}



function ContentProcess_InitStat( &$ctxProcess )

{

	$ctxProcess[ '_stat' ][ 'proc' ][ 'v' ] = microtime( true );

	$ctxProcess[ '_stat' ][ 'cpu' ][ 'v' ] = Gen::GetScriptCpuTime();

	$ctxProcess[ '_stat' ][ 'cpuExtProc' ][ 'v' ] = Gen::GetExtProcCpuTime();



	$ctxProcess[ '_stat' ][ 'netExtSz' ][ 'v' ] = 0;

	$ctxProcess[ '_stat' ][ 'netExtCount' ][ 'v' ] = 0;



	$ctxProcess[ '_stat' ][ 'imgRead' ][ 'v' ] = 0;

	$ctxProcess[ '_stat' ][ 'imgReadCount' ][ 'v' ] = 0;

	$ctxProcess[ '_stat' ][ 'ai' ][ 'v' ] = null;

	$ctxProcess[ '_stat' ][ 'aiCount' ][ 'v' ] = null;

	foreach( array( 'webp','avif' ) as $comprType )

	{

		$ctxProcess[ '_stat' ][ 'compr' ][ $comprType ][ 'v' ] = null;

		$ctxProcess[ '_stat' ][ 'comprCount' ][ $comprType ][ 'v' ] = null;

	}



	$ctxProcess[ 'stat' ][ 'Total-Duration' ] = '';

	$ctxProcess[ 'stat' ][ 'Total-Cpu-Duration' ] = '';

}



function ContentProcess_StatStageImg_Begin( &$ctxProcess, $stage, &$tmStat, &$aStatDiff )

{

	$tmStat = microtime( true );



	$aStatDiff = array();

	$aStatDiff[ 'v' ][] = ( float )Gen::GetArrField( $ctxProcess[ '_stat' ], $aStatDiff[ 'k' ][] = array( 'imgRead', 'v' ) );

	$aStatDiff[ 'v' ][] = ( float )Gen::GetArrField( $ctxProcess[ '_stat' ], $aStatDiff[ 'k' ][] = array( 'ai', 'v' ) );

	foreach( array( 'webp','avif' ) as $comprType )

		$aStatDiff[ 'v' ][] = ( float )Gen::GetArrField( $ctxProcess[ '_stat' ], $aStatDiff[ 'k' ][] = array( 'compr', $comprType, 'v' ) );

}



function ContentProcess_StatStageImg_End( &$ctxProcess, $stage, &$tmStat, &$aStatDiff )

{

	$tmStat = microtime( true ) - $tmStat;



	foreach( $aStatDiff[ 'v' ] as $i => $v )

		$tmStat -= ( float )Gen::GetArrField( $ctxProcess[ '_stat' ], $aStatDiff[ 'k' ][ $i ] ) - $v;

	unset( $aStatDiff );

	if( $tmStat < 0 ) $tmStat = 0;



	$ctxProcess[ 'stat' ][ ucfirst( $stage ) . '-Duration' ] = LocId::Pack( 'TimeDurSec_%1$s', null, array( sprintf( '%.2F', $tmStat ) ) );

	$ctxProcess[ 'stat' ][ $ctxProcess[ '_stat' ][ 'imgRead' ][ 'k' ] = ucfirst( $stage ) . '-Reading-Duration' ] = '';

	$ctxProcess[ 'stat' ][ $ctxProcess[ '_stat' ][ 'imgReadCount' ][ 'k' ] = ucfirst( $stage ) . '-Reading' ] = '';

	$ctxProcess[ 'stat' ][ $ctxProcess[ '_stat' ][ 'ai' ][ 'k' ] = ucfirst( $stage ) . '-Adaptation-Duration' ] = '';

	$ctxProcess[ 'stat' ][ $ctxProcess[ '_stat' ][ 'aiCount' ][ 'k' ] = ucfirst( $stage ) . '-Adaptation' ] = '';

	foreach( array( 'webp','avif' ) as $comprType )

	{

		$ctxProcess[ 'stat' ][ $ctxProcess[ '_stat' ][ 'compr' ][ $comprType ][ 'k' ] = ucfirst( $stage ) . '-Compression-' . ucfirst( $comprType ) . '-Duration' ] = '';

		$ctxProcess[ 'stat' ][ $ctxProcess[ '_stat' ][ 'comprCount' ][ $comprType ][ 'k' ] = ucfirst( $stage ) . '-Compression-' . ucfirst( $comprType ) ] = '';

	}

}



function ContentProcess_FinalizeStat( &$ctxProcess )

{

	$ctxProcess[ '_stat' ][ 'proc' ][ 'v' ] = microtime( true ) - $ctxProcess[ '_stat' ][ 'proc' ][ 'v' ];

	$ctxProcess[ '_stat' ][ 'cpu' ][ 'v' ] = ( Gen::GetScriptCpuTime() - $ctxProcess[ '_stat' ][ 'cpu' ][ 'v' ] ) + ( Gen::GetExtProcCpuTime() - $ctxProcess[ '_stat' ][ 'cpuExtProc' ][ 'v' ] );



	if( $ctxProcess[ '_stat' ][ 'cpu' ][ 'v' ] > $ctxProcess[ '_stat' ][ 'proc' ][ 'v' ] )

		$ctxProcess[ '_stat' ][ 'cpu' ][ 'v' ] = $ctxProcess[ '_stat' ][ 'proc' ][ 'v' ];



	$ctxProcess[ 'stat' ][ 'Total-Duration' ] = LocId::Pack( 'TimeDurSec_%1$s', null, array( sprintf( '%.2F', $ctxProcess[ '_stat' ][ 'proc' ][ 'v' ] ) ) );

	$ctxProcess[ 'stat' ][ 'Total-Cpu-Duration' ] = LocId::Pack( 'TimeDurSec_%1$s', null, array( sprintf( '%.2F', $ctxProcess[ '_stat' ][ 'cpu' ][ 'v' ] ) ) );



	if( isset( $ctxProcess[ '_stat' ][ 'imgRead' ][ 'k' ] ) )

	{

		$ctxProcess[ 'stat' ][ $ctxProcess[ '_stat' ][ 'imgRead' ][ 'k' ] ] = LocId::Pack( 'TimeDurSec_%1$s', null, array( sprintf( '%.2F', $ctxProcess[ '_stat' ][ 'imgRead' ][ 'v' ] ) ) );

		$ctxProcess[ 'stat' ][ $ctxProcess[ '_stat' ][ 'imgReadCount' ][ 'k' ] ] = $ctxProcess[ '_stat' ][ 'imgReadCount' ][ 'v' ];

	}



	if( isset( $ctxProcess[ '_stat' ][ 'ai' ][ 'k' ] ) )

	{

		if( isset( $ctxProcess[ '_stat' ][ 'ai' ][ 'v' ] ) )

			$ctxProcess[ 'stat' ][ $ctxProcess[ '_stat' ][ 'ai' ][ 'k' ] ] = LocId::Pack( 'TimeDurSec_%1$s', null, array( sprintf( '%.2F', $ctxProcess[ '_stat' ][ 'ai' ][ 'v' ] ) ) );

		else

			unset( $ctxProcess[ 'stat' ][ $ctxProcess[ '_stat' ][ 'ai' ][ 'k' ] ] );



		if( isset( $ctxProcess[ '_stat' ][ 'aiCount' ][ 'v' ] ) )

			$ctxProcess[ 'stat' ][ $ctxProcess[ '_stat' ][ 'aiCount' ][ 'k' ] ] = $ctxProcess[ '_stat' ][ 'aiCount' ][ 'v' ];

		else

			unset( $ctxProcess[ 'stat' ][ $ctxProcess[ '_stat' ][ 'aiCount' ][ 'k' ] ] );

	}



	foreach( array( 'webp','avif' ) as $comprType )

	{

		if( isset( $ctxProcess[ '_stat' ][ 'compr' ][ $comprType ][ 'k' ] ) )

		{

			if( isset( $ctxProcess[ '_stat' ][ 'compr' ][ $comprType ][ 'v' ] ) )

				$ctxProcess[ 'stat' ][ $ctxProcess[ '_stat' ][ 'compr' ][ $comprType ][ 'k' ] ] = LocId::Pack( 'TimeDurSec_%1$s', null, array( sprintf( '%.2F', $ctxProcess[ '_stat' ][ 'compr' ][ $comprType ][ 'v' ] ) ) );

			else

				unset( $ctxProcess[ 'stat' ][ $ctxProcess[ '_stat' ][ 'compr' ][ $comprType ][ 'k' ] ] );



			if( isset( $ctxProcess[ '_stat' ][ 'comprCount' ][ $comprType ][ 'v' ] ) )

				$ctxProcess[ 'stat' ][ $ctxProcess[ '_stat' ][ 'comprCount' ][ $comprType ][ 'k' ] ] = $ctxProcess[ '_stat' ][ 'comprCount' ][ $comprType ][ 'v' ];

			else

				unset( $ctxProcess[ 'stat' ][ $ctxProcess[ '_stat' ][ 'comprCount' ][ $comprType ][ 'k' ] ] );

		}

	}



	$ctxProcess[ 'stat' ][ 'Ext-Network-Received' ] = size_format( $ctxProcess[ '_stat' ][ 'netExtSz' ][ 'v' ], 1 );

	$ctxProcess[ 'stat' ][ 'Ext-Network-Fetch' ] = $ctxProcess[ '_stat' ][ 'netExtCount' ][ 'v' ];



}



function ContentProcess_GetAlwaysReplacements( $sett, $settContPr )

{

	$a = array();

	foreach( Gen::GetArrField( $settContPr, array( 'rpl', 'items' ), array() ) as $rpl )

	{

		if( !($rpl[ 'enable' ]??null) || !($rpl[ 'alws' ]??null) )

			continue;



		$a[] = $rpl;

	}



	ContParts_GetAlwaysReplacements( $a, $sett, $settContPr );



	return( $a );

}



function ContentProcess_AlwaysReplacements( $a, $settContPr, $buffer )

{

	if( !IsHtml( $buffer ) )

		return( $buffer );



	$bDocRpl = false;



	foreach( $a as $rpl )

	{

		if( ($rpl[ 'sel' ]??null) )

		{

			$bDocRpl = true;

			continue;

		}



		ContentProcess_ReplaceEx( ( string )($rpl[ 'expr' ]??null), ( string )($rpl[ 'data' ]??null), $buffer );

	}



	if( !$bDocRpl )

		return( $buffer );



	$adjusted = null;

	$xpath = null;



	$norm = Gen::GetArrField( $settContPr, array( 'normalize' ), 0 );

	$doc = GetHtmlDoc( $buffer, $norm );

	if( !$doc )

		return( $buffer );



	foreach( $a as $rpl )

	{

		$sel = ( string )($rpl[ 'sel' ]??null);

		if( !$sel )

			continue;



		if( ContentProcess_DocReplaceEx( ( string )($rpl[ 'expr' ]??null), $sel, ( string )($rpl[ 'data' ]??null), $doc, $xpath ) )

			$adjusted = true;

	}



	if( $adjusted )

		$buffer = HtmlDocDeParse( $doc, $norm );



	return( $buffer );

}



function ContentProcess_Replace( $ctxProcess, $settCache, $settContPr, &$buffer )

{

	$ctxAbort = new AnyObj();

	$ctxAbort -> ctxProcess = $ctxProcess;

	$ctxAbort -> settCache = $settCache;

	$ctxAbort -> cb =

		function( $ctxAbort )

		{

			return( $ctxAbort -> ctxProcess[ 'cbs' ] -> IsAborted( $ctxAbort -> ctxProcess, $ctxAbort -> settCache ) );

		};



	foreach( Gen::GetArrField( $settContPr, array( 'rpl', 'items' ), array() ) as $rpl )

	{

		if( !($rpl[ 'enable' ]??null) || ($rpl[ 'alws' ]??null) || ($rpl[ 'sel' ]??null) )

			continue;



		if( ContentProcess_ReplaceEx( ( string )($rpl[ 'expr' ]??null), ( string )($rpl[ 'data' ]??null), $buffer, array( $ctxAbort, 'cb' ) ) === false )

			return;

	}

}



function ContentProcess_DocReplace( $ctxProcess, $settCache, $settContPr, $doc )

{

	$ctxAbort = new AnyObj();

	$ctxAbort -> ctxProcess = $ctxProcess;

	$ctxAbort -> settCache = $settCache;

	$ctxAbort -> cb =

		function( $ctxAbort )

		{

			return( $ctxAbort -> ctxProcess[ 'cbs' ] -> IsAborted( $ctxAbort -> ctxProcess, $ctxAbort -> settCache ) );

		};



	$xpath = null;



	foreach( Gen::GetArrField( $settContPr, array( 'rpl', 'items' ), array() ) as $rpl )

	{

		$sel = ( string )($rpl[ 'sel' ]??null);

		if( !($rpl[ 'enable' ]??null) || ($rpl[ 'alws' ]??null) || !$sel )

			continue;



		if( ContentProcess_DocReplaceEx( ( string )($rpl[ 'expr' ]??null), $sel, ( string )($rpl[ 'data' ]??null), $doc, $xpath, array( $ctxAbort, 'cb' ) ) === false )

			return;

	}

}



function ContentProcess_DocReplaceEx( $expr, $sel, $dataTpl, $doc, &$xpath, $cbIsAborted = null )

{

	if( !$xpath )

		$xpath = new \DOMXPath( $doc );



	$adjusted = null;



	foreach( HtmlNd::ChildrenAsArr( @$xpath -> query( $sel, $doc ) ) as $item )

	{



		$cont = HtmlNd::DeParse( $item );

		$r = ContentProcess_ReplaceEx( $expr, $dataTpl, $cont, $cbIsAborted );

		if( $r === false )

			return( false );



		if( $cbIsAborted && call_user_func( $cbIsAborted ) )

			return( false );



		if( !$r )

			continue;



		foreach( HtmlNd::ParseAndImportAll( $doc, $cont, LIBXML_NONET, $doc -> encoding ? $doc -> encoding : 'UTF-8' ) as $itemRpl )

			$item -> parentNode -> insertBefore( $itemRpl, $item );

		$item -> parentNode -> removeChild( $item );



		$adjusted = true;

	}



	return( $adjusted );

}



function ContentProcess_ReplaceEx( $expr, $dataTpl, &$buffer, $cbIsAborted = null )

{

	$ctx = new AnyObj();

	$ctx -> cbReplTpl =

		function( $ctx, $m )

		{



			if( Gen::StrStartsWith( $m[ 0 ], '\\${' ) )

				return( substr( $m[ 0 ], 1 ) );



			$charKeep = Gen::StrStartsWith( $m[ 0 ], '${' ) ? '' : $m[ 0 ][ 0 ];

			return( $charKeep . ( string )($ctx -> m[ $m[ 1 ] ][ 0 ]??null) );

		};



	$expr = ExprConditionsSet_Parse( $expr );

	if( !count( $expr ) )

		return( null );



	if( !ExprConditionsSet_IsRegExp( $expr ) && ExprConditionsSet_IsTrivial( $expr ) )

	{

		$n = 0;

		$buffer = str_replace( $expr[ 0 ][ 'expr' ], $dataTpl, $buffer, $n );

		return( $n > 0 ? true : null );

	}



	$exprLast = array_splice( $expr, count( $expr ) - 1, 1 )[ 0 ][ 'expr' ];



	if( count( $expr ) )

	{

		$ctxMatch = new AnyObj();

		$ctxMatch -> buffer = $buffer;

		$ctxMatch -> cbMatch =

			function( $ctxMatch, $expr )

			{

				if( IsStrRegExp( $expr ) )

					return( @preg_match( $expr, $ctxMatch -> buffer ) );

				return( false );

			};

		if( !ExprConditionsSet_MatchEx( $expr, array( $ctxMatch, 'cbMatch' ) ) )

		{

			unset( $ctxMatch );

			return( null );

		}

		unset( $ctxMatch );

	}



	$adjusted = null;



	$iPos = 0;

	$ctx -> m = array();

	while( @preg_match( $exprLast, $buffer, $ctx -> m, PREG_OFFSET_CAPTURE, $iPos ) )

	{



		$posRepl = $ctx -> m[ 0 ];



		$bSkip = true;

		foreach( $ctx -> m as $key => $d )

		{

			if( $bSkip )

			{

				$bSkip = false;

				continue;

			}



			if( !is_int( $key ) )

			{

				$bSkip = true;

				continue;

			}



			$posRepl = $d;

		}



		$data = preg_replace_callback( '@.?\\${(\\w+)}@', array( $ctx, 'cbReplTpl' ), $dataTpl );



		$buffer = substr_replace( $buffer, $data, $posRepl[ 1 ], strlen( $posRepl[ 0 ] ) );

		if( count( $ctx -> m ) == 1 )

			$iPos = $posRepl[ 1 ] + strlen( $data );

		else

			$iPos = $ctx -> m[ 0 ][ 1 ] + strlen( $ctx -> m[ 0 ][ 0 ] ) - strlen( $posRepl[ 0 ] ) + strlen( $data );



		$adjusted = true;



		if( $iPos == strlen( $buffer ) )

			break;



		if( $cbIsAborted && call_user_func( $cbIsAborted ) )

			return( false );

	}



	return( $adjusted );

}



function ContNoScriptItemClear( $itemNoScript )

{



	foreach( HtmlNd::ChildrenAsArr( $itemNoScript -> getElementsByTagName( 'noscript' ) ) as $itemCheck )

	{

		if( $itemNoScript === $itemCheck )

			continue;



		if( $itemCheck -> hasAttribute( 'data-lzl-bjs' ) )

			HtmlNd::MoveChildren( $itemCheck -> parentNode, $itemCheck, $itemCheck );



		$itemCheck -> parentNode -> removeChild( $itemCheck );

	}

}



function _XPathEx_MatchEx( $v, &$aPattern )

{

	if( @is_a( $v, 'DOMNode' ) )

		$v = $v -> nodeValue;



	if( is_string( $v ) )

	{

		foreach( $aPattern as $pattern )

			if( @preg_match( $pattern, $v ) )

				return( true );

	}



	return( false );

}



function _XPathEx_Match( $v )

{

	$aPattern = func_get_args();

	array_shift( $aPattern );



	if( is_array( $v ) )

	{

		foreach( $v as $vi )

			if( _XPathEx_MatchEx( $vi, $aPattern ) )

				return( true );

	}

	else if( _XPathEx_MatchEx( $v, $aPattern ) )

		return( true );



	return( null );

}



function _XPathEx_MatchAll( $v )

{

	if( is_array( $v ) )

		return( new XPathEx_MatchAll( $v, false, func_get_args() ) );



	if( is_string( $v ) )

	{

		$aPattern = func_get_args();

		array_shift( $aPattern );



		foreach( $aPattern as $pattern )

			if( @preg_match( $pattern, $v ) )

				return( true );

	}



	return( null );

}



function _XPathEx_MatchAllGlob( $v )

{

	if( is_array( $v ) )

		return( new XPathEx_MatchAll( $v, true, func_get_args() ) );



	return( null );

}



function XPathEx_New( $doc )

{

	$xpath = new \DOMXPath( $doc );

	$xpath -> registerNamespace( 'php', 'http://php.net/xpath' );

	$xpath -> registerPhpFunctions( array( 'seraph_accel\\_XPathEx_Match', 'seraph_accel\\_XPathEx_MatchAll', 'seraph_accel\\_XPathEx_MatchAllGlob' ) );

	return( $xpath );

}



function XPathEx_QueryPrepareExpr( $expression )

{



	$expression = preg_replace( '@matchAll\\(\\s*\\.\\/\\/\\*\\[\\@([\\w]+)\\]/\\@([\\w]+)@', 'php:function("seraph_accel\\_XPathEx_MatchAllGlob",.//*[@${1}][1]/@${2}', $expression );

	$expression = str_replace( 'matchAll(', 'php:function("seraph_accel\\_XPathEx_MatchAll",', $expression );

	$expression = str_replace( 'match(', 'php:function("seraph_accel\\_XPathEx_Match",', $expression );

	return( $expression );

}



function XPathEx_Query( $xpath, $expression, $contextNode = null, $registerNodeNS = true )

{

	$items = @$xpath -> query( XPathEx_QueryPrepareExpr( $expression ), $contextNode, $registerNodeNS );

	return( $items );

}



function ContSkeleton_FltName_PrepPatterns_Plchldrs_Init( $a = null )

{

	global $seraph_accel_g_ContSkeleton_FltName_PrepPatterns_aPlchldr;



	if( $a )

	{

		$seraph_accel_g_ContSkeleton_FltName_PrepPatterns_aPlchldr = $a;

		return;

	}



	if( $seraph_accel_g_ContSkeleton_FltName_PrepPatterns_aPlchldr )

		return;



	$seraph_accel_g_ContSkeleton_FltName_PrepPatterns_aPlchldr = array(

		'ENUM_POSTTYPES_NOTBUILTINVIEWABLESPEC' =>

			function()

			{

				return( implode( '|', array_keys( array_filter( get_post_types( array(), 'objects' ), function( $o ) { return( !($o -> _builtin??false) && ($o -> show_in_nav_menus??false) && is_post_type_viewable( $o -> name ) ); } ) ) ) );

			},



		'ENUM_TAXONOMIES_NOTBUILTIN' =>

			function()

			{

				return( implode( '|', array_keys( array_filter( get_taxonomies( array(), 'objects' ), function( $o ) { return( !($o -> _builtin??false) ); } ) ) ) );

			},



		'POST_SLUG' =>

			function()

			{

				if( $oPost = get_post() )

					return( $oPost -> post_name );

				return( '' );

			},

	);

}



function ContSkeleton_FltName_PrepPatterns( $patterns )

{

	global $seraph_accel_g_ContSkeleton_FltName_PrepPatterns_aPlchldr;



	ContSkeleton_FltName_PrepPatterns_Plchldrs_Init();



	$res = array();

	foreach( $patterns as $pattern )

	{

		$patternPrms = array( 'r' => '\\*' );

		if( preg_match( '@^([\\w,=\\*\\\\]+):[^:]@', $pattern, $m ) )

		{

			$patternPrms = array_merge( $patternPrms, Gen::ParseProps( $m[ 1 ], ',', '=' ) );

			$pattern = substr( $pattern, strlen( $m[ 1 ] ) + 1 );

		}



		foreach( $seraph_accel_g_ContSkeleton_FltName_PrepPatterns_aPlchldr as $id => &$v )

		{

			$plchldr = '(?\'' . $id . '\')';



			if( strpos( $pattern, $plchldr ) === false )

				continue;



			if( !is_string( $v ) )

				$v = ( string )$v();



			$pattern = str_replace( '|' . $plchldr, strlen( $v ) ? ( '|' . $v ) : '', $pattern );

			$pattern = str_replace( $plchldr, $v, $pattern );

		}

		unset( $id, $cb, $plchldr, $v );



		$pattern .= 'S';



		$res[] = array( 'm' => $pattern, 'p' => $patternPrms );

	}



	return( $res );

}



function ContSkeleton_FltName_PrepPatterns_Plchldrs_Exec()

{

	global $seraph_accel_g_ContSkeleton_FltName_PrepPatterns_aPlchldr;



	ContSkeleton_FltName_PrepPatterns_Plchldrs_Init();



	foreach( $seraph_accel_g_ContSkeleton_FltName_PrepPatterns_aPlchldr as $id => &$v )

		if( !is_string( $v ) )

			$v = ( string )$v();

	unset( $v );



	return( $seraph_accel_g_ContSkeleton_FltName_PrepPatterns_aPlchldr );

}



function ContSkeleton_FltName( $patterns, $s, $spaceAround = false )

{

	foreach( $patterns as $pattern )

	{

		if( $spaceAround && strlen( $s ) )

		{

			if( $s[ 0 ] !== ' ' )

				$s = ' ' . $s;

			if( $s[ strlen( $s ) - 1 ] !== ' ' )

				$s = $s . ' ';

		}



		for( $i = 0; $i < 1000; $i++ )

		{

			if( !@preg_match_all( $pattern[ 'm' ], $s, $am, PREG_SET_ORDER | PREG_OFFSET_CAPTURE ) )

				break;



			for( $i = count( $am ); $i > 0; $i-- )

			{

				$m = $am[ $i - 1 ];



				$j = count( $m );

				$jmin = ( $j > 1 ) ? 1 : 0;



				for( ; $j > $jmin; $j-- )

				{

					$mj = $m[ $j - 1 ];

					$s = substr_replace( $s, $pattern[ 'p' ]['r' ], $mj[ 1 ], strlen( $mj[ 0 ] ) );

				}

			}

		}

	}



	return( $s );

}



function _GetContSkeleton_MaskSelector( $v, $bMask = true, $bInt = false )

{



	static $g_aMaskedInt = array( "\x01", "\x02", '\\@', '\\*', '\\:', '\\[', '\\]', '\\+', '\\&' );

	static $g_aMasked = array( '\\.', '\\#', '\\@', '\\*', '\\:', '\\[', '\\]', '\\+', '\\&' );

	static $g_aUnMasked = array( '.', '#', '@', '*', ':', '[', ']', '+', '&' );



	if( $bMask )

		return( str_replace( $g_aUnMasked, $g_aMasked, $v ) );

	return( str_replace( $bInt ? $g_aMaskedInt : $g_aMasked, $g_aUnMasked, $v ) );

}



function _GetContSkeletonHash_GetAttrs( &$aCssCrit, $item, $aExcl )

{

	$contItemTpl = $item -> nodeName;



	if( $item -> attributes )

	{

		foreach( array( 'class', 'id' ) as $attrName )

		{

			$attr = $item -> attributes -> getNamedItem( $attrName );

			if( !$attr )

				continue;



			if( in_array( $attr, $aExcl[ 'a' ], true ) )

			{

				_GetContSkeletonHash_AddCssCrit( $aCssCrit, $item, false, array( $attrName ) );

				continue;

			}



			$v = $attr -> nodeValue;

			if( $attr -> nodeName == 'class' )

				$v = ' ' . implode( ' ', Ui::ParseClassAttr( $v ) ) . ' ';



			$aPattern = array();

			foreach( $aExcl[ 'as' ] as $exclAttrStr )

				if( isset( $exclAttrStr -> attr ) ? ( $attr -> nodeName == $exclAttrStr -> attr ) : in_array( $attr, $exclAttrStr -> aAttr, true ) )

					$aPattern = array_merge( $aPattern, $exclAttrStr -> aPattern );



			if( $aPattern )

				$v = ContSkeleton_FltName( ContSkeleton_FltName_PrepPatterns( $aPattern ), $v, $attr -> nodeName == 'class' );



			switch( $attr -> nodeName )

			{

			case 'class':

				$v = explode( ' ', $v );

				foreach( $v as $vItem )

				{

					$vItem = trim( $vItem );

					if( strlen( $vItem ) )

						$contItemTpl .= '.' . _GetContSkeleton_MaskSelector( $vItem );

				}

				break;



			case 'id':

				$v = trim( $v );

				if( strlen( $v ) )

					$contItemTpl .= '#' . _GetContSkeleton_MaskSelector( $v );

				break;

			}

		}

	}



	$contItemTpl = trim( ContSkeleton_FltName( $aExcl[ 'sel' ], $contItemTpl, true ) );



	$contItemTplTag = _GetContSkeletonHash_GetAttrsParts( $contItemTpl, $contItemTplClasses, $contItemTplId );

	if( $contItemTplClasses )

	{

		$contItemTplClasses = array_unique( $contItemTplClasses );

		sort( $contItemTplClasses );

		$contItemTpl = $contItemTplTag . '.' . implode( '.', _GetContSkeleton_MaskSelector( $contItemTplClasses ) ) . ( $contItemTplId ? ( '#' . implode( '#', _GetContSkeleton_MaskSelector( $contItemTplId ) ) ) : '' );

	}



	return( $contItemTpl );

}



function _GetContSkeletonHash_GetAttrsParts( $contItemTpl, &$classes, &$ids )

{

	$contItemTpl = str_replace( array( '\\.', '\\#' ), array( "\x01", "\x02" ), $contItemTpl );



	$posClasses = strpos( $contItemTpl, '.' );

	$posId = strpos( $contItemTpl, '#' );



	$classes = ( $posClasses !== false ) ? ( $posId !== false ? substr( $contItemTpl, $posClasses, $posId - $posClasses ) : substr( $contItemTpl, $posClasses ) ) : '';

	$classes = strlen( $classes ) ? _GetContSkeleton_MaskSelector( explode( '.', substr( $classes, 1 ) ), false, true ) : array();



	$ids = ( $posId !== false ) ? substr( $contItemTpl, $posId ) : '';

	$ids = strlen( $ids ) ? _GetContSkeleton_MaskSelector( explode( '#', substr( $ids, 1 ) ), false, true ) : array();



	return( _GetContSkeleton_MaskSelector( ( $posClasses !== false ) ? substr( $contItemTpl, 0, $posClasses ) : ( $posId !== false ? substr( $contItemTpl, 0, $posId ) : $contItemTpl ), false, true ) );

}



function _GetContSkeletonHash_AddCssCrit( &$aCssCrit, $item, $bRecurse = true, $aAttrIncl = array() )

{

	if( !$aAttrIncl )

		$aCssCrit[ "@(?:[^\\w\\-\\#\\.]|^)" . preg_quote( $item -> nodeName, '@' ) . "(?:[^\\-\\w]|$)@" ] = true;



	if( !$aAttrIncl || in_array( 'id', $aAttrIncl ) )

		if( strlen( $v = trim( ( string )$item -> getAttribute( 'id' ) ) ) )

			$aCssCrit[ "@#" . preg_quote( _GetContSkeleton_MaskSelector( $v ), '@' ) . "(?:[^\\-\\w]|$)@" ] = true;



	if( !$aAttrIncl || in_array( 'class', $aAttrIncl ) )

		foreach( Ui::ParseClassAttr( ( string )$item -> getAttribute( 'class' ) ) as $v )

			$aCssCrit[ "@\\." . preg_quote( _GetContSkeleton_MaskSelector( $v ), '@' ) . "(?:[^\\-\\w]|$)@" ] = true;



	if( !$bRecurse )

		return;



	foreach( $item -> childNodes as $itemChild )

		if( $itemChild -> nodeType == XML_ELEMENT_NODE )

			_GetContSkeletonHash_AddCssCrit( $aCssCrit, $itemChild );

}



function _GetContSkeletonHash_Enum( &$aCssCrit, &$aParentUniqueItems, $itemParent, $aExcl, $bAgg = false )

{

	if( !$itemParent -> childNodes )

		return;



	foreach( $itemParent -> childNodes as $item )

	{

		if( $item -> nodeType != XML_ELEMENT_NODE )

			continue;



		if( in_array( $item -> nodeName, $aExcl[ 'n' ], true ) || in_array( $item, $aExcl[ 'e' ], true ) )

		{

			_GetContSkeletonHash_AddCssCrit( $aCssCrit, $item );

			continue;

		}



		$aUniqueItems = array();



			_GetContSkeletonHash_Enum( $aCssCrit, $aUniqueItems, $item, $aExcl, $bAgg );



		$contItemTpl = _GetContSkeletonHash_GetAttrs( $aCssCrit, $item, $aExcl );

		if( !strlen( $contItemTpl ) )

		{

			$aParentUniqueItems = array_merge_recursive( $aParentUniqueItems, $aUniqueItems );

			continue;

		}



		if( $bAgg )

		{

			$contItemTpl = _GetContSkeletonHash_GetAttrsParts( $contItemTpl, $contItemTplClasses, $contItemTplIds );



			$aUniqueItems = $aUniqueItems ? array( 'n' => $aUniqueItems ) : array();

			if( $contItemTplClasses )

				$aUniqueItems[ 'c' ] = array_combine( $contItemTplClasses, array_fill( 0, count( $contItemTplClasses ), array() ) );

			if( $contItemTplIds )

				$aUniqueItems[ 'i' ] = array_combine( $contItemTplIds, array_fill( 0, count( $contItemTplIds ), array() ) );

		}



		$aParentUniqueItems = array_merge_recursive( $aParentUniqueItems, array( $contItemTpl => $aUniqueItems ) );

	}

}



function _GetContSkeletonHash_EnumUniqueItems( &$contTpl, $docTpl, $itemParentTpl, &$aParentUniqueItems, $mode, $bAgg, $level = 0 )

{

	ksort( $aParentUniqueItems );



	foreach( $aParentUniqueItems as $contItemTpl => &$aUniqueItems )

	{

		if( $bAgg )

		{

			ksort( $aUniqueItems );



			if( ($aUniqueItems[ 'c' ]??null) )

			{

				ksort( $aUniqueItems[ 'c' ] );

				if( $mode == 'text' )

					$contItemTpl .= '.' . implode( '.', _GetContSkeleton_MaskSelector( array_keys( $aUniqueItems[ 'c' ] ) ) );

			}



			if( ($aUniqueItems[ 'i' ]??null) )

			{

				ksort( $aUniqueItems[ 'i' ] );

				if( $mode == 'text' )

					$contItemTpl .= '#' . implode( '#', _GetContSkeleton_MaskSelector( array_keys( $aUniqueItems[ 'i' ] ) ) );

			}

		}



		$itemTpl = null;

		if( $docTpl )

		{

			$itemTpl = $docTpl -> createElement( _GetContSkeletonHash_GetAttrsParts( $contItemTpl, $contItemTplClasses, $contItemTplId ) );

			if( $contItemTplClasses )

				$itemTpl -> setAttribute( 'class', ' ' . implode( ' ', $contItemTplClasses ) . ' ' );

			if( $contItemTplId )

				$itemTpl -> setAttribute( 'id', implode( '', $contItemTplId ) );

			$itemParentTpl -> appendChild( $itemTpl );

		}



		if( $mode != 'tree' )

		{

			if( $level )

				$contTpl .= $mode == 'text' ? str_repeat( "\t", $level ) : ( string )$level;

			$contTpl .= $contItemTpl;

			if( $mode == 'text' )

				$contTpl .= "\n";

		}



		if( $bAgg && !isset( $aUniqueItems[ 'n' ] ) )

			continue;



		if( $bAgg )

			_GetContSkeletonHash_EnumUniqueItems( $contTpl, $docTpl, $itemTpl, $aUniqueItems[ 'n' ], $mode, $bAgg, $level + 1 );

		else

			_GetContSkeletonHash_EnumUniqueItems( $contTpl, $docTpl, $itemTpl, $aUniqueItems, $mode, $bAgg, $level + 1 );

	}

}



function GetContSkeleton_GenNodesFromAgg( $docTpl, &$aParentUniqueItems )

{

	_GetContSkeleton_GenNodesFromAgg( $docTpl, $docTpl, $aParentUniqueItems );

}



function _GetContSkeleton_GenNodesFromAgg( $docTpl, $itemParentTpl, &$aParentUniqueItems )

{

	ksort( $aParentUniqueItems );



	foreach( $aParentUniqueItems as $contItemTpl => &$aUniqueItems )

	{

		ksort( $aUniqueItems );

		if( ($aUniqueItems[ 'c' ]??null) )

			ksort( $aUniqueItems[ 'c' ] );

		if( ($aUniqueItems[ 'i' ]??null) )

			ksort( $aUniqueItems[ 'i' ] );



		$contItemTplIds = ($aUniqueItems[ 'i' ]??null);

		if( !$contItemTplIds )

			$contItemTplIds = array( null );

		else

			$contItemTplIds = array_keys( $contItemTplIds );



		$classAttr = '';

		if( ($aUniqueItems[ 'c' ]??null) )

			$classAttr = ' ' . implode( ' ', array_keys( $aUniqueItems[ 'c' ] ) ) . ' ';



		foreach( $contItemTplIds as $contItemTplId )

		{

			$itemTpl = $docTpl -> createElement( $contItemTpl );



			if( strlen( $classAttr ) )

				$itemTpl -> setAttribute( 'class', $classAttr );

			if( $contItemTplId !== null )

				$itemTpl -> setAttribute( 'id', $contItemTplId );

			$itemParentTpl -> appendChild( $itemTpl );



			if( isset( $aUniqueItems[ 'n' ] ) )

				_GetContSkeleton_GenNodesFromAgg( $docTpl, $itemTpl, $aUniqueItems[ 'n' ] );

		}

	}

}



function GetContSkeleton( &$ctxProcess, $ndBody, $excls, $exclsCssSel, $mode = 'hash', $bAgg = false, $docTpl = null )

{

	$aExcl = array( 'n' => array(), 'e' => array(), 'a' => array(), 'as' => array(), 'sel' => ContSkeleton_FltName_PrepPatterns( is_array( $exclsCssSel ) ? $exclsCssSel : array() ) );

	{

		$xpath = XPathEx_New( $ndBody -> ownerDocument );



		foreach( $excls as $exclItemPath )

		{

			if( @preg_match( '@^\\.//([\\w\\-]+)$@', $exclItemPath, $m ) )

			{

				$aExcl[ 'n' ][] = $m[ 1 ];

				continue;

			}



			$items = XPathEx_Query( $xpath, $exclItemPath, $ndBody -> parentNode -> parentNode );

			if( !$items )

				continue;



			foreach( $items as $item )

			{

				if( is_a( $item, 'seraph_accel\\XPathEx_MatchAll' ) )

					$aExcl[ 'as' ][] = $item;

				else if( is_a( $item, 'DOMElement' ) )

					$aExcl[ 'e' ][] = $item;

				else if( is_a( $item, 'DOMAttr' ) )

					$aExcl[ 'a' ][] = $item;

			}

		}



		unset( $xpath );

	}



	$aUniqueItems = array();



	if( $ctxProcess !== null )

		$aCssCrit = &$ctxProcess[ 'aCssCrit' ];

	else

		$aCssCrit = array();



	_GetContSkeletonHash_Enum( $aCssCrit, $aUniqueItems, $ndBody -> parentNode -> parentNode, $aExcl, $bAgg );



	$contTpl = '';

	_GetContSkeletonHash_EnumUniqueItems( $contTpl, $docTpl, $docTpl, $aUniqueItems, $mode, $bAgg );



	return( $mode == 'text' ? $contTpl : ( $mode == 'tree' ? $aUniqueItems : md5( str_replace( '\\*', '*', $contTpl ) ) ) );

}



function GetContProcSettHash( $settContPr )

{

	$settContPr = Gen::ArrCopy( $settContPr );



	foreach( Gen::GetArrField( $settContPr, array( 'cp' ), array() ) as $k => $item )

		if( !$item )

			unset( $settContPr[ 'cp' ][ $k ] );



	foreach( Gen::GetArrField( $settContPr, array( 'css', 'custom' ), array() ) as $k => $item )

		if( !($item[ 'enable' ]??null) )

			unset( $settContPr[ 'css' ][ 'custom' ][ $k ] );



	foreach( Gen::GetArrField( $settContPr, array( 'cdn', 'items' ), array() ) as $k => $item )

		if( !($item[ 'enable' ]??null) )

			unset( $settContPr[ 'cdn' ][ 'items' ][ $k ] );



	foreach( Gen::GetArrField( $settContPr, array( 'grps', 'items' ), array() ) as $k => $item )

		if( !($item[ 'enable' ]??null) )

			unset( $settContPr[ 'grps' ][ 'items' ][ $k ] );



	return( md5( @json_encode( $settContPr ), true ) );

}



function Learn_Id2File( $id )

{

	$pos = strpos( $id, '/' );

	if( $pos === false )

		return( null );



	$pos += 1;

	return( substr( $id, 0, $pos ) . bin2hex( substr( $id, $pos ) ) . '.dat.gz' );

}



function Learn_ReadDsc( $lrnFile )

{

	return( Tof_GetFileData( Gen::GetFileDir( $lrnFile ), Gen::GetFileName( $lrnFile ), array( 2,

		function( $data, $vFrom )

		{

			return( $data );

		}

	), true ) );

}



function Learn_KeepNeededData( &$datasDel, &$lrnsGlobDel, $lrnDsc, $lrnDataPath )

{

	$aData = array();

	$aLrnGlob = array();



	Learn_GetNeededData( $aData, $aLrnGlob, $lrnDsc, $lrnDataPath );



	foreach( $aData as $type => $aId )

	{

		foreach( $aId as $id )

		{

			if( $id == '*' )

				unset( $datasDel[ $type ] );

			else

				unset( $datasDel[ $type ][ $id ] );

		}

	}



	foreach( $aLrnGlob as $file )

		unset( $lrnsGlobDel[ $file ] );

}



function Learn_GetNeededData( &$aData, &$aLrnGlob, $lrnDsc, $lrnDataPath )

{

	ScriptsOpt::getLrnNeededData( $aData, $aLrnGlob, $lrnDsc, $lrnDataPath );

	StyleProcessor::getLrnNeededData( $aData, $aLrnGlob, $lrnDsc, $lrnDataPath );

}



function Learn_Init( $oInit, $siteId, $bLrn, $settHash, $bAllowLearn, $aSkeletonAggr = null, $ctlRes = null )

{

	global $seraph_accel_g_prepPrms;



	$lock = new Lock( 'il', GetCacheDir() );



	if( !$lock -> Acquire() )

	{



		$oInit -> skipStatus = 'err:internal';

		return( false );

	}



	$bAdjustQueueItem = false;

	if( $bLrn )

	{



	}

	else if( _Learn_InitEx( $oInit, null, $settHash, $aSkeletonAggr ) )

	{



		$lock -> Release();

		return( null );

	}

	else if( $bAllowLearn )

	{

		$tmLearnStart = Learn_IsStarted( $oInit -> lrnFile );

		if( $tmLearnStart === false )

		{

		}

		else if( ( time() - $tmLearnStart > 60 ) && !Queue_IsPriorFirst( $siteId, -480 ) )

		{



			if( $aSkeletonAggr === null )

				Learn_Clear( $oInit -> lrnFile );

		}

		else

		{

			$lock -> Release();

			$oInit -> skipStatus = 'lrnNeed';

			return( false );

		}



		if( !Learn_Start( $oInit -> lrnFile ) )

		{

			$lock -> Release();

			$oInit -> skipStatus = 'err:writeLrnPending';

			return( false );

		}



		$bAdjustQueueItem = true;



	}



	$oInit -> lrnDsc = Learn_ReadDsc( $oInit -> lrnFile );



	$lock -> Release();



	if( $bAdjustQueueItem && QueueItem_SetSelfLearn( Gen::GetArrField( $ctlRes ? $ctlRes : ProcessCtlData_Get( ($seraph_accel_g_prepPrms[ 'pc' ]??null) ), array( 'queue', 'id' ) ), $siteId, $oInit -> lrnId ) === false )

	{

		$oInit -> skipStatus = 'err:internal';

		return( false );

	}



	return( true );

}



function _Learn_InitEx( $oInit, $lrnDscExt, $settHash, $aSkeletonAggr = null )

{

	$oInit -> lrnDsc = $lrnDscExt ? $lrnDscExt : Learn_ReadDsc( $oInit -> lrnFile );

	if( !$oInit -> lrnDsc )

		return( false );



	if( Gen::GetArrField( $oInit -> lrnDsc, array( 'sh' ) ) !== $settHash )

	{

		unset( $oInit -> lrnDsc );



		if( !$lrnDscExt )

		{

			Learn_Clear( $oInit -> lrnFile, true, false );



		}



		return( false );

	}



	$bOK = true;



	if( $aSkeletonAggr !== null )

	{

		$aSkeletonAggrLrn = Gen::GetArrField( $oInit -> lrnDsc, array( 's' ), array() );

		if( !Gen::ArrContainRecursive( $aSkeletonAggrLrn, $aSkeletonAggr ) )

			$bOK = false;



	}



	return( $bOK );

}



function Learn_IsStarted( $lrnFile )

{

	return( Gen::FileMTime( $lrnFile . '.p' ) );

}



function Learn_Start( $lrnFile )

{

	Gen::MakeDir( Gen::GetFileDir( $lrnFile ), true );

	return( @file_put_contents( $lrnFile . '.p', '' ) !== false );

}



function _Learn_Finish( &$ctxProcess, $settHash )

{



	if( isset( $ctxProcess[ 'lrn' ] ) )

	{

		if( !isset( $ctxProcess[ 'lrnDsc' ] ) )

			$ctxProcess[ 'lrnDsc' ] = array();

		$ctxProcess[ 'lrnDsc' ][ 'sh' ] = $settHash;

	}

	else if( !isset( $ctxProcess[ 'lrnDsc' ] ) )

		return( null );



	return( $ctxProcess[ 'cbs' ] -> Learn_Finish( $ctxProcess ) );

}



function _Learn_Abort( $ctxProcess )

{

	if( ( $ctxProcess[ 'mode' ] & 4 ) && isset( $ctxProcess[ 'lrn' ] ) )

		$ctxProcess[ 'cbs' ] -> Learn_Abort( $ctxProcess[ 'lrnFile' ] );

}



function Learn_Finish( $ctxProcess )

{



	if( isset( $ctxProcess[ 'lrn' ] ) )

	{

		$ok = Gen::HrSucc( Tof_SetFileData( Gen::GetFileDir( $ctxProcess[ 'lrnFile' ] ), Gen::GetFileName( $ctxProcess[ 'lrnFile' ] ), $ctxProcess[ 'lrnDsc' ], 2, true ) );

		Gen::Unlink( $ctxProcess[ 'lrnFile' ] . '.p' );

		return( $ok );

	}



	$lock = new Lock( 'il', GetCacheDir() );



	if( !$lock -> Acquire() )

	{

		Gen::LastErrDsc_Set( $lock -> GetErrDescr() );

		return( false );

	}



	$ok = null;

	if( $lrnDscPrev = Learn_ReadDsc( $ctxProcess[ 'lrnFile' ] ) )

	{

		if( $ok !== false )

		{

			$r = ScriptsOpt::mergeLrnData( $ctxProcess, $ctxProcess[ 'lrnDsc' ], $lrnDscPrev, $ctxProcess[ 'lrnDataPath' ] );

			if( $r )

				$ok = true;

			else if( $r === false )

				$ok = false;

		}



		if( $ok !== false )

		{

			$r = StyleProcessor::mergeLrnData( $ctxProcess, $ctxProcess[ 'lrnDsc' ], $lrnDscPrev, $ctxProcess[ 'lrnDataPath' ] );

			if( $r )

				$ok = true;

			else if( $r === false )

				$ok = false;

		}



		if( $ok === true )

			$ok = Gen::HrSucc( Tof_SetFileData( Gen::GetFileDir( $ctxProcess[ 'lrnFile' ] ), Gen::GetFileName( $ctxProcess[ 'lrnFile' ] ), $ctxProcess[ 'lrnDsc' ], 2, true ) );

	}



	$lock -> Release();

	return( $ok );

}



function Learn_Clear( $lrnFile, $bMain = true, $bPending = true )

{

	if( $bMain )

		Gen::Unlink( $lrnFile );

	if( $bPending )

		Gen::Unlink( $lrnFile . '.p' );

}



function ContUpdateItemIntegrity( $item, $cont )

{

	if( $cont === false )

		return;



	$integrity = trim( $item -> getAttribute( 'integrity' ) );

	if( !$integrity )

		return;



	$algo = strpos( $integrity, '-' );

	if( $algo === false )

		return;

	$algo = substr( $integrity, 0, $algo );



	$hashNew = function_exists( 'hash' ) ? hash( $algo, $cont, true ) : null;

	if( $hashNew )

		$item -> setAttribute( 'integrity', $algo . '-' . base64_encode( $hashNew ) );

	else

		$item -> removeAttribute( 'integrity' );

}



function GetSrcAttrInfoEx( $src )

{

	return( array( 'url' => $src, 'srcWoArgs' => $src, 'args' => array() ) );

}



function GetSrcAttrInfo( $ctxProcess, $requestDomainUrl, $requestUriPath, &$src )

{

	$src = trim( $src );



	if( Ui::IsSrcAttrData( $src ) )

		return( GetSrcAttrInfoEx( $src ) );



	$urlComps = Net::UrlParse( $src, Net::URLPARSE_F_PRESERVEEMPTIES | Net::URLPARSE_F_PATH_FIXFIRSTSLASH );

	if( !$urlComps )

		return( GetSrcAttrInfoEx( $src ) );



	$args = Net::UrlParseQuery( ($urlComps[ 'query' ]??null) );



	$serverArgs = $ctxProcess[ 'serverArgs' ];



	if( isset( $urlComps[ 'host' ] ) )

	{

		if( isset( $urlComps[ 'scheme' ] ) )

		{

			$srcUrlFullness = 4;

			if( $urlComps[ 'scheme' ] != ($serverArgs[ 'REQUEST_SCHEME' ]??null) && ($serverArgs[ 'REQUEST_SCHEME' ]??null) == 'https' )

				$urlComps[ 'scheme' ] = ($serverArgs[ 'REQUEST_SCHEME' ]??null);

		}

		else

		{

			$srcUrlFullness = 3;

			$urlComps[ 'scheme' ] = ($serverArgs[ 'REQUEST_SCHEME' ]??null);

		}

	}

	else

	{

		$srcUrlFullness = 2;



		$requestDomainUrlComps = $requestDomainUrl ? Net::UrlParse( $requestDomainUrl ) : null;

		if( !$requestDomainUrlComps )

		{

			$requestDomainUrlComps = array( 'scheme' => ($serverArgs[ 'REQUEST_SCHEME' ]??null), 'host' => $ctxProcess[ 'host' ] );

			if( ($serverArgs[ 'SERVER_PORT' ]??null) != 80 && ($serverArgs[ 'SERVER_PORT' ]??null) != 443 )

				$requestDomainUrlComps[ 'port' ] = ($serverArgs[ 'SERVER_PORT' ]??null);

		}



		$urlComps[ 'scheme' ] = ($requestDomainUrlComps[ 'scheme' ]??null);

		$urlComps[ 'host' ] = ($requestDomainUrlComps[ 'host' ]??null);

		$urlComps[ 'port' ] = ($requestDomainUrlComps[ 'port' ]??null);



		unset( $requestDomainUrlComps );



		if( ($urlComps[ 'path' ][ 0 ]??null) !== '/' )

		{

			if( $requestUriPath === null )

				$requestUriPath = $ctxProcess[ 'requestUriPath' ];

			$urlComps[ 'path' ] = $requestUriPath . '/' . $urlComps[ 'path' ];

		}

	}



	if( $urlComps[ 'host' ] != $ctxProcess[ 'host' ] || ( isset( $urlComps[ 'port' ] ) && $urlComps[ 'port' ] != ($serverArgs[ 'SERVER_PORT' ]??null) ) )

	{

		$src = Net::UrlDeParse( $urlComps, Net::URLPARSE_F_PRESERVEEMPTIES );

		return( array( 'url' => $src, 'srcWoArgs' => Net::UrlDeParse( $urlComps, Net::URLPARSE_F_PRESERVEEMPTIES, array( PHP_URL_QUERY, PHP_URL_FRAGMENT ) ), 'args' => $args, '#' => ($urlComps[ 'fragment' ]??null), 'ext' => true ) );

	}



	if( stripos( ($urlComps[ 'path' ]??null) . '/', $ctxProcess[ 'siteRootUri' ] . '/' ) !== 0 )

	{

		$src = Net::UrlDeParse( $urlComps, Net::URLPARSE_F_PRESERVEEMPTIES );

		return( array( 'url' => $src, 'srcWoArgs' => Net::UrlDeParse( $urlComps, Net::URLPARSE_F_PRESERVEEMPTIES, array( PHP_URL_QUERY, PHP_URL_FRAGMENT ) ), 'args' => $args, '#' => ($urlComps[ 'fragment' ]??null), 'ext' => true ) );

	}



	if( isset( $urlComps[ 'path' ] ) )

		$urlComps[ 'path' ] = VirtUriPath2Real( $urlComps[ 'path' ], $ctxProcess[ 'aVPth' ] );



	$res = array( 'url' => Net::UrlDeParse( $urlComps, Net::URLPARSE_F_PRESERVEEMPTIES ), 'srcWoArgs' => Net::UrlDeParse( $urlComps, Net::URLPARSE_F_PRESERVEEMPTIES, array( PHP_URL_SCHEME, PHP_URL_USER, PHP_URL_PASS, PHP_URL_HOST, PHP_URL_PORT, PHP_URL_QUERY, PHP_URL_FRAGMENT ) ), 'args' => $args, '#' => ($urlComps[ 'fragment' ]??null), 'srcUrlFullness' => $srcUrlFullness );

	$src = Net::UrlDeParse( $urlComps, Net::URLPARSE_F_PRESERVEEMPTIES, array( PHP_URL_SCHEME, PHP_URL_USER, PHP_URL_PASS, PHP_URL_HOST, PHP_URL_PORT ) );



	$srcRelFile = substr( ($urlComps[ 'path' ]??null), strlen( $ctxProcess[ 'siteRootUri' ] ) );

	if( $srcRelFile )

	{

		if( Gen::StrStartsWith( $srcRelFile, '/' . Gen::GetFileName( $ctxProcess[ 'siteContPath' ] ) . '/' ) )

			$res[ 'filePathRoot' ] = Gen::GetFileDir( $ctxProcess[ 'siteContPath' ] );

		else

			$res[ 'filePathRoot' ] = $ctxProcess[ 'siteRootPath' ];

		$res[ 'filePath' ] = $res[ 'filePathRoot' ] . Gen::GetNormalizedPath( rawurldecode( $srcRelFile ) );

	}



	return( $res );

}



function IsUrlInPartsList( $items, $url )

{

	if( !$url || !$items )

		return( false );



	$url = strtolower( $url );



	foreach( $items as $item )

		if( strpos( $url, $item ) !== false )

			return( true );



	return( false );

}



function IsObjInRegexpList( $list, array $scopes, &$detectedPattern = null )

{

	if( !($scopes[ 'src' ]??null) && !($scopes[ 'id' ]??null) && !($scopes[ 'body' ]??null) )

		return( false );



	if( !isset( $scopes[ 'src' ] ) )

		$scopes[ 'src' ] = '';

	if( !isset( $scopes[ 'id' ] ) )

		$scopes[ 'id' ] = '';



	foreach( $list as $item )

	{

		$isMatched = true;

		foreach( ExprConditionsSet_Parse( $item ) as $itemE )

		{

			$itemScope = array( 'src', 'id', 'body' );

			$posScopeEnd = strpos( $itemE[ 'expr' ], ':' );

			if( $posScopeEnd !== false )

			{

				$posExpBegin = false;

				foreach( array( '/', '~', '@', ';', '%', '`', '#' ) as $expQuote )

				{

					$posExpBegin2 = strpos( $itemE[ 'expr' ], $expQuote );

					if( $posExpBegin2 !== false && ( $posExpBegin === false || $posExpBegin2 < $posExpBegin ) )

						$posExpBegin = $posExpBegin2;

				}



				if( $posExpBegin !== false && $posScopeEnd < $posExpBegin )

				{

					$itemScope = explode( ',', substr( $itemE[ 'expr' ], 0, $posScopeEnd ) );

					$itemE[ 'expr' ] = substr( $itemE[ 'expr' ], $posScopeEnd + 1 );

				}

			}



			$match = false;

			foreach( $itemScope as $scopeCheck )

			{

				if( ($scopes[ $scopeCheck ]??null) === null )

					continue;



				$m = array();

				if( ExprConditionsSet_IsItemOpFullSearch( $itemE ) )

				{

					if( !@preg_match_all( $itemE[ 'expr' ], $scopes[ $scopeCheck ], $m, PREG_SET_ORDER ) )

						$m = array( array( '' ) );

				}

				else

				{

					if( !@preg_match( $itemE[ 'expr' ], $scopes[ $scopeCheck ], $m ) )

						$m = array( '' );

					$m = array( $m );

				}



				foreach( $m as $mi )

				{

					if( count( $mi ) > 1 )

						array_shift( $mi );

					$mi = implode( '', $mi );



					if( ExprConditionsSet_ItemOp( $itemE, $mi ) )

					{

						$match = true;

						break;

					}

				}



				if( $match )

					break;

			}



			if( !$match )

			{

				$isMatched = false;

				break;

			}

		}



		if( $isMatched )

		{

			$detectedPattern = $item;

			return( true );

		}

	}



	return( false );

}



function GetObjSrcCritStatus( $settNonCrit, $critSpec, $specs, $srcInfo, $src, $id, $body = null, &$detectedPattern = null )

{

	if( !IsObjSrcNotCrit( $settNonCrit, $srcInfo, $src, $id, $body, $detectedPattern ) )

		return( true );

	if( $critSpec && IsObjInRegexpList( $critSpec, array( 'src' => $src, 'id' => $id, 'body' => $body ), $detectedPattern ) )

		return( 'critSpec' );

	if( $specs && IsObjInRegexpList( $specs, array( 'src' => $src, 'id' => $id, 'body' => $body ), $detectedPattern ) )

		return( null );

	return( false );

}



function IsObjSrcNotCrit( $settNonCrit, $srcInfo, $src, $id, $body = null, &$detectedPattern = null )

{

	if( $srcInfo )

	{

		if( !($settNonCrit[ 'ext' ]??null) && ($srcInfo[ 'ext' ]??null) )

			return( false );

		if( !($settNonCrit[ 'int' ]??null) )

			return( false );

	}

	else if( !($settNonCrit[ 'inl' ]??null) )

		return( false );



	$inList = IsObjInRegexpList( Gen::GetArrField( $settNonCrit, array( 'items' ), array() ), array( 'src' => $src, 'id' => $id, 'body' => $body ), $detectedPattern );

	return( ($settNonCrit[ 'excl' ]??null) ? !$inList : $inList );

}



function UpdSc( &$ctxProcess, $settCache, $type, $cont, &$src = null, &$filePath = null )

{

	$fileExt = null;

	if( is_array( $type ) )

	{

		$fileExt = ($type[ 1 ]??null);

		$type = ($type[ 0 ]??null);

	}



	$chunk = CacheCw( array( $ctxProcess[ 'cbs' ], 'ScWr' ), $settCache, $ctxProcess[ 'siteRootDataPath' ], $ctxProcess[ 'dataPath' ], false, $cont, $type, $fileExt );

	if( !$chunk )

		return( false );



	DepsAdd( $ctxProcess[ 'deps' ], $type, $chunk[ 'id' ] );



	$src = $ctxProcess[ 'siteRootUri' ] . '/' . $chunk[ 'relFilePath' ];

	$filePath = $ctxProcess[ 'siteRootDataPath' ] . '/' . $chunk[ 'relFilePath' ];

	return( $chunk[ 'id' ] );

}



function ReadSc( $ctxProcess, $settCache, $id, $type )

{

	return( ReadSce( array( $ctxProcess[ 'cbs' ], 'ScRd' ), $ctxProcess[ 'dataPath' ], $settCache, $id, $type ) );

}



function CheckSc( &$ctxProcess, $settCache, $type, $oiCi, &$src = null, &$filePath = null )

{

	$fileExt = null;

	if( is_array( $type ) )

	{

		$fileExt = ($type[ 1 ]??null);

		$type = ($type[ 0 ]??null);

	}



	$chunk = CacheCc( array( $ctxProcess[ 'cbs' ], 'asuxsadkxsshi' ), $settCache, $ctxProcess[ 'siteRootDataPath' ], $ctxProcess[ 'dataPath' ], $oiCi, $type, $fileExt );

	if( !$chunk )

		return( false );



	DepsAdd( $ctxProcess[ 'deps' ], $type, $chunk[ 'id' ] );



	$src = $ctxProcess[ 'siteRootUri' ] . '/' . $chunk[ 'relFilePath' ];

	$filePath = $ctxProcess[ 'siteRootDataPath' ] . '/' . $chunk[ 'relFilePath' ];

	return( true );

}



function ContentParseStrIntEncodingCorrect()

{

	if( !function_exists( 'mb_strlen' ) || !( ( int )@ini_get( 'mbstring.func_overload' ) & 2 ) )

		return( null );



	$mbIntEnc = mb_internal_encoding();

	mb_internal_encoding( '8bit' );

	return( $mbIntEnc );

}



function ContentParseStrIntEncodingRestore( $mbIntEnc )

{

	if( $mbIntEnc !== null )

		mb_internal_encoding( $mbIntEnc );

}



function GetContentTestData( $size )

{

	$extra = '';



	$n = $size / 32;

	for( $i = 0; $i < $n; $i++ )

		$extra .= md5( '' . $i );



	return( $extra );

}



function GetContentsRawHead( $data )

{

	$nPos = Gen::StrPosArr( $data, array( '</head>', '</HEAD>' ) );

	if( $nPos === false )

		return( false );

	$data = substr( $data, 0, $nPos );



	$nPos = Gen::StrPosArr( $data, array( '<head>', '<HEAD>' ) );

	if( $nPos === false )

		return( false );

	return( substr( $data, $nPos + 6 ) );

}



function GetContentsMetaProps( $data )

{

	$res = array();



	$data = GetContentsRawHead( $data );

	if( !$data )

		return( $res );



	$doc = new \DOMDocument();

	if( !@$doc -> loadHTML( '<!DOCTYPE html><html><head>' . $data . '</head></html>', LIBXML_NOBLANKS | LIBXML_NONET | LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_PARSEHUGE ) )

		return( $res );



	foreach( $doc -> getElementsByTagName( 'meta' ) as $item )

	{

		$k = $item -> getAttribute( 'property' );

		if( !$k )

			$k = $item -> getAttribute( 'name' );



		$v = $item -> getAttribute( 'content' );



		if( $k && $v )

			$res[ $k ] = $v;

	}



	return( $res );

}



function _ContGrpsGet_MatchEx( $v, $aPattern )

{

	global $seraph_accel_g_aContGrpsGet_matchedGroupsData;



	if( @is_a( $v, 'DOMNode' ) )

		$v = $v -> nodeValue;



	if( is_string( $v ) )

	{

		foreach( $aPattern as $pattern )

		{

			$m = array();

			if( !@preg_match( $pattern, $v, $m ) )

				continue;



			array_shift( $m );

			foreach( $m as $mI )

				$seraph_accel_g_aContGrpsGet_matchedGroupsData[ $mI ] = true;

			return( true );

		}

	}



	return( false );

}



function _ContGrpsGet_Match( $v )

{

	$aPattern = func_get_args();

	array_shift( $aPattern );



	if( is_array( $v ) )

	{

		foreach( $v as $vi )

			if( _ContGrpsGet_MatchEx( $vi, $aPattern ) )

				return( true );

	}

	else if( _ContGrpsGet_MatchEx( $v, $aPattern ) )

		return( true );



	return( null );

}



function ContGrpsGet( $ctxProcess, $settGrps, $doc, $viewId, &$grpVariationDataId = null )

{

	$res = array();



	$xpath = null;



	$pathOrig = substr( ParseContCachePathArgs( $ctxProcess[ 'serverArgs' ], $args ), strlen( $ctxProcess[ 'siteRootUri' ] ) );

	$path = CachePathNormalize( $pathOrig, $pathIsDir );

	if( $pathIsDir )

		$path .= '/';



	foreach( Gen::GetArrField( $settGrps, array( 'items' ), array() ) as $contGrpId => $contGrp )

	{

		$mode = ($contGrp[ 'enable' ]??null);

		if( !( $mode & ( ( isset( $res[ 1 ] ) ? 0 : 1 ) | ( isset( $res[ 2 ] ) ? 0 : 2 ) ) ) )

			continue;



		if( $a = Gen::GetArrField( $contGrp, array( 'views' ), array() ) )

			if( !in_array( $viewId, $a ) )

				continue;



		if( $a = Gen::GetArrField( $contGrp, array( 'urisIncl' ), array() ) )

			if( !CheckPathInUriList( $a, $path, $pathOrig ) )

				continue;



		if( $a = Gen::GetArrField( $contGrp, array( 'argsIncl' ), array() ) )

		{

			$found = false;

			foreach( $args as $argKey => $argVal )

			{

				$argKeyCmp = strtolower( $argKey );



				foreach( $a as $aI )

					if( ContProcGetExclStatus_KeyValMatch( $aI, $argKeyCmp, $argVal ) )

					{

						$found = true;

						break;

					}

			}



			if( !$found )

				continue;

		}



		if( $doc && ( $a = Gen::GetArrField( $contGrp, array( 'patterns' ), array() ) ) )

		{

			global $seraph_accel_g_aContGrpsGet_matchedGroupsData;



			$found = false;

			foreach( $a as $pattern )

			{



				if( !$xpath )

				{

					$xpath = new \DOMXPath( $doc );

					$xpath -> registerNamespace( 'php', 'http://php.net/xpath' );

					$xpath -> registerPhpFunctions( array( 'seraph_accel\\_ContGrpsGet_Match' ) );

				}



				$seraph_accel_g_aContGrpsGet_matchedGroupsData = array();



				$pattern = str_replace( 'match(', 'php:function("seraph_accel\\_ContGrpsGet_Match",', $pattern );

				if( HtmlNd::FirstOfChildren( @$xpath -> query( $pattern, $doc ) ) )

				{

					$grpVariationDataId = md5( 'AGGR:' . implode( '', array_keys( $seraph_accel_g_aContGrpsGet_matchedGroupsData ) ) ); unset( $seraph_accel_g_aContGrpsGet_matchedGroupsData );

					$found = true;

					break;

				}

			}



			if( !$found )

				continue;

		}



		if( !isset( $res[ 1 ] ) && ( $mode & 1 ) )

			$res[ 1 ] = array( $contGrp, $contGrpId );

		if( !isset( $res[ 2 ] ) && ( $mode & 2 ) )

			$res[ 2 ] = array( $contGrp, $contGrpId );

	}



	return( $res );

}



function ulyjqbuhdyqcetbhkiy( $url )

{

	return( ($url[ 0 ]??null) == '/' && ($url[ 1 ]??null) != '/' );

}



function Cdn_AdjustUrl( $ctxProcess, $settCdn, &$uri, $fileType )

{

	$uriProbe = $uri;



	if( !ulyjqbuhdyqcetbhkiy( $uriProbe ) )

	{

		if( strpos( $uriProbe, 'seraph_accel_gp' ) === false )

			return( false );

		$uriProbe = $ctxProcess[ 'requestUriPath' ] . '/' . $uriProbe;

	}



	foreach( Gen::GetArrField( $settCdn, array( 'items' ), array() ) as $item )

	{

		$urlCdn = $item[ 'addr' ];

		if( !$item[ 'enable' ] || !$urlCdn )

			continue;



		{

			$types = Gen::GetArrField( $item, array( 'types' ), array() );

			if( $types && !in_array( $fileType, $types ) )

				continue;

		}



		{

			$uris = Gen::GetArrField( $item, array( 'uris' ), array() );

			if( $uris && !IsUrlInPartsList( $uris, $uriProbe ) )

				continue;

		}



		{

			$uris = Gen::GetArrField( $item, array( 'urisExcl' ), array() );

			if( $uris && IsUrlInPartsList( $uris, $uriProbe ) )

				continue;

		}



		$urlCdn = Net::GetUrlWithoutProtoEx( $urlCdn, $proto );

		if( $proto )

		{

			$scheme = ($ctxProcess[ 'serverArgs' ][ 'REQUEST_SCHEME' ]??null);

			if( $proto == 'http' && $scheme == 'https' )

				$proto = $scheme;

			$urlCdn = $proto . '://' . $urlCdn;

		}



		$uri = $urlCdn . $uriProbe;

		return( true );

	}



	return( false );

}



function Fullness_AdjustUrl( $ctxProcess, &$src, $srcUrlFullness = null )

{

	if( !ulyjqbuhdyqcetbhkiy( $src ) )

		return( false );



	$serverArgs = $ctxProcess[ 'serverArgs' ];

	$host = Net::GetUrlWithoutProto( $ctxProcess[ 'siteDomainUrl' ] );



	if( $ctxProcess[ 'srcUrlFullness' ] !== 0 )

		$srcUrlFullness = $ctxProcess[ 'srcUrlFullness' ];

	else if( $srcUrlFullness === null )

		return( false );



	switch( $srcUrlFullness )

	{

	case 4:		$src = ($serverArgs[ 'REQUEST_SCHEME' ]??null) . '://' . $host . $src; return( true );

	case 3:			$src = '//' . $host . $src; return( true );

	}



	if( substr( $src, 0, 3 ) == '//#' )

		$src = substr( $src, 2 );



	return( false );

}



function GetSourceItemTracePath( $ctxProcess, $nodePath, $srcInfo = null, $id = null )

{

	$res = '';

	if( $srcInfo )

	{

		if( isset( $srcInfo[ 'filePath' ] ) )

			$res .= substr( $srcInfo[ 'filePath' ], strlen( $srcInfo[ 'filePathRoot' ] ) );

		else

			$res .= ($srcInfo[ 'url' ]??'');

	}

	else

	{

		$res .= '/' . substr( $ctxProcess[ 'requestUriPath' ], strlen( $ctxProcess[ 'siteRootUri' ] ) );

		$res .= ':' . str_replace( array( '/', '[@' ), array( '>', '[' ), preg_replace( '@\\[(\\d+)\\]@', ':nth-of-type($1)', trim( $nodePath, '/' ) ) );



		if( $id )

			$res .= '#' . $id;

	}



	return( $res );

}



function Conts_CheckExclEx( &$ctxProcess, $doc, $sett, $item, $id1, $settPath )

{

	$exclItems = &$ctxProcess[ $id1 ];

	if( $exclItems === null )

	{

		$exclItems = array();



		$excls = Gen::GetArrField( $sett, $settPath, array() );

		if( $excls )

		{

			$xpath = new \DOMXPath( $doc );



			foreach( $excls as $exclItemPath )

			{

				$mode = 'y';

				if( Gen::StrStartsWith( $exclItemPath, 'ajs:' ) )

				{

					if( ($ctxProcess[ 'isJsDelayed' ]??null) )

						$mode = 'ajs';

					$exclItemPath = substr( $exclItemPath, 4 );

				}



				foreach( HtmlNd::ChildrenAsArr( @$xpath -> query( $exclItemPath, $ctxProcess[ 'ndHtml' ] ) ) as $itemExcl )

					$exclItems[ $mode ][] = $itemExcl;

			}

		}

	}



	foreach( array( 'y', 'ajs' ) as $mode )

		if( isset( $exclItems[ $mode ] ) && in_array( $item, $exclItems[ $mode ], true ) )

			return( $mode );



	return( false );

}



function GetContentProcessCtxEx( $serverArgs, $sett, $siteId, $siteUrl, $siteRootPath, $siteContentPath, $wpRootSubPath, $cacheDir, $scriptDebug )

{

	$ctx = array(

		'siteDomainUrl' => Net::GetSiteAddrFromUrl( $siteUrl, true ),

		'siteRootUri' => Gen::SetLastSlash( Net::Url2Uri( $siteUrl ), false ),

		'siteRootPath' => Gen::SetLastSlash( $siteRootPath, false ),

		'siteContPath' => Gen::SetLastSlash( $siteContentPath, false ),

		'siteRootDataPath' => null,

		'siteCacheRootDir' => $cacheDir,

		'dataPath' => GetCacheDataDir( $cacheDir . '/s/' . $siteId ),

		'wpRootSubPath' => $wpRootSubPath . '/',

		'siteId' => $siteId,

		'deps' => array(),

		'subs' => array(),

		'subCurIdx' => 0,

		'debugM' => ($sett[ 'debug' ]??null),

		'debug' => ($sett[ 'debugInfo' ]??null),

		'jsMinSuffix' => $scriptDebug ? '' : '.min',

		'userAgent' => strtolower( isset( $_SERVER[ 'SERAPH_ACCEL_ORIG_USER_AGENT' ] ) ? $_SERVER[ 'SERAPH_ACCEL_ORIG_USER_AGENT' ] : ($serverArgs[ 'HTTP_USER_AGENT' ]??null) ),

		'mode' => ( 1 | 2 | 4 ),

		'modeReq' => 0,

		'aAttrImg' => array(),



		'aCssCrit' => array(),

		'aCssRpl' => array(),

		'aCssRplExcl' => array(),



		'bJsCssAddType' => apply_filters( 'seraph_accel_jscss_addtype', false ),



	);



	if( strpos( $ctx[ 'dataPath' ], $ctx[ 'siteRootPath' ] . '/' ) === 0 )

		$ctx[ 'siteRootDataPath' ] = $ctx[ 'siteRootPath' ];

	else if( strpos( $ctx[ 'dataPath' ], $ctx[ 'siteContPath' ] . '/' ) === 0 )

		$ctx[ 'siteRootDataPath' ] = Gen::GetFileDir( $ctx[ 'siteContPath' ] );

	else

		$ctx[ 'siteRootDataPath' ] = $cacheDir;



	$ctx[ 'compatView' ] = ContProcIsCompatView( Gen::GetArrField( $sett, array( 'cache' ), array() ), $ctx[ 'userAgent' ] );



	CorrectRequestScheme( $serverArgs );



	$ctx[ 'serverArgs' ] = $serverArgs;

	$ctx[ 'requestUriPath' ] = Gen::GetFileDir( ($serverArgs[ 'REQUEST_URI' ]??null) );

	$ctx[ 'host' ] = Gen::GetArrField( Net::UrlParse( $serverArgs[ 'REQUEST_SCHEME' ] . '://' . GetRequestHost( $serverArgs ) ), array( 'host' ) );

	if( !$ctx[ 'host' ] )

		$ctx[ 'host' ] = ($serverArgs[ 'SERVER_NAME' ]??null);



	$settContPr = Gen::GetArrField( $sett, array( 'contPr' ), array() );

	if( Gen::GetArrField( $settContPr, array( 'normUrl' ), false ) )

		$ctx[ 'srcUrlFullness' ] = Gen::GetArrField( $settContPr, array( 'normUrlMode' ), 0 );

	else

		$ctx[ 'srcUrlFullness' ] = 0;



	$ctx[ 'aVPth' ] = array_map( function( $vPth ) { $vPth[ 'f' ] .= 'S'; return( $vPth ); }, GetVirtUriPathsFromSett( $sett ) );



	return( $ctx );

}



function &GetContentProcessCtx( $serverArgs, $sett )

{

	global $seraph_accel_g_ctxProcess;



	if( !$seraph_accel_g_ctxProcess )

	{

		$siteRootUrl = Wp::GetSiteRootUrl();



		$siteWpRootSubPath = rtrim( Wp::GetSiteWpRootUrl( '', null, true ), '/' );

		if( strpos( $siteWpRootSubPath, rtrim( $siteRootUrl, '/' ) ) === 0 )

			$siteWpRootSubPath = trim( substr( $siteWpRootSubPath, strlen( rtrim( $siteRootUrl, '/' ) ) ), '/' );

		else

			$siteWpRootSubPath = '';



		if( defined( 'SERAPH_ACCEL_SITEROOT_DIR' ) )

			$siteRootPath = SERAPH_ACCEL_SITEROOT_DIR;

		else

		{

			$siteRootPath = ABSPATH;

			if( $siteWpRootSubPath && Gen::StrEndsWith( rtrim( $siteRootPath, '\\/' ), $siteWpRootSubPath ) )

				$siteRootPath = substr( rtrim( $siteRootPath, '\\/' ), 0, - strlen( $siteWpRootSubPath ) );

		}



		$seraph_accel_g_ctxProcess = GetContentProcessCtxEx( $serverArgs, $sett, GetSiteId(), $siteRootUrl, $siteRootPath, WP_CONTENT_DIR, $siteWpRootSubPath, GetCacheDir(), defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG );

		ContentProcess_InitLocalCbs( $seraph_accel_g_ctxProcess );

	}



	return( $seraph_accel_g_ctxProcess );

}



function ContentProcess_InitLocalCbs( &$ctxProcess )

{

	$cbs = new AnyObj();

	$cbs -> ctxProcess = &$ctxProcess;



	$cbs -> ReportStage =

		function( $cbs, $stage = null, $stageDsc = null )

		{

			$dataUpd = array( 'stageDsc' => $stageDsc );

			if( $stage )

				$dataUpd[ 'stage' ] = $stage;



			global $seraph_accel_g_prepPrms;

			return( $seraph_accel_g_prepPrms ? ProcessCtlData_Update( ($seraph_accel_g_prepPrms[ 'pc' ]??null), $dataUpd ) : true );

		};



	$cbs -> IsAborted =

		function( $cbs, $ctxProcess = null, $settCache = null )

		{

			return( ContentProcess_IsAborted( $ctxProcess, $settCache ) );

		};



	$cbs -> GetContentProcessorForce =

		function( $cbs, $sett )

		{

			return( GetContentProcessorForce( $sett ) );

		};



	$cbs -> ContPostProc =

		function( $cbs, $type, $content, $isFile = true )

		{

			if( $type == 'css' )

				$content = apply_filters( 'seraph_accel_css_content', $content, $isFile );

			else if( $type == 'js' )

				$content = apply_filters( 'seraph_accel_js_content', $content, $isFile );

			return( $content );

		};



	$cbs -> PreFetchLocalFiles =

		function( $cbs, $a, $cont = true )

		{

			return( array_keys( ContentProcess_PreFetchLocalFiles_Expand( $cbs -> ctxProcess, $a ) ) );

		};



	$cbs -> LocalFileExists =

		function( $cbs, $filePath, $filePathRoot = null )

		{

			return( @file_exists( $filePath ) );

		};



	$cbs -> ReadLocalFile =

		function( $cbs, $filePath, $filePathRoot = null )

		{

			if( !$filePath )

				return( null );



			$cont = Gen::FileGetContents( $filePath );

			if( $cont === false && $filePathRoot && !Gen::DoesFileDirExist( $filePath, $filePathRoot ) )

				$cont = null;

			return( $cont );

		};



	$cbs -> WriteLocalFile =

		function( $cbs, $filePath, $data, $fileTime = null, $delIfFail = false )

		{

			$lock = new Lock( 'il', $cbs -> ctxProcess[ 'siteCacheRootDir' ] );

			return( Gen::FileWriteTmpAndReplace( $lock, $filePath, $data, $fileTime, $delIfFail ) );

		};



	$cbs -> GetLocalFileSize =

		function( $cbs, $filePath )

		{

			return( Gen::FileSize( $filePath ) );

		};



	$cbs -> GetLocalFileMTime =

		function( $cbs, $filePath )

		{

			return( Gen::FileMTime( $filePath ) );

		};



	$cbs -> DeleteLocalFile =

		function( $cbs, $filePath )

		{

			return( Gen::Unlink( $filePath ) );

		};



	$cbs -> asuxsadkxsshi =

		function( $cbs, $dataPath, $type, $oiCfn )

		{

			return( CacheCcEx( $dataPath, $type, $oiCfn ) );

		};



	$cbs -> ScRd =

		function( $cbs, $dataPath, $settCache, $type, $oiCi, $oiCfn )

		{

			return( CacheCrEx( $dataPath, $settCache, $type, $oiCi, $oiCfn ) );

		};



	$cbs -> ScWr =

		function( $cbs, $settCache, $dataPath, $composite, $content, $type, $oiCfn )

		{

			return( CacheCwEx( $settCache, $dataPath, $composite, $content, $type, $oiCfn ) );

		};



	$cbs -> Tof_GetFileDataEx =

		function( $cbs, $dir, $id )

		{

			return( Tof_GetFileDataEx( $dir, $id ) );

		};



	$cbs -> Tof_SetFileDataEx =

		function( $cbs, $dir, $id, $data, $overwrite = true )

		{

			return( Tof_SetFileDataEx( $dir, $id, $data, $overwrite ) );

		};



	$cbs -> Learn_Abort =

		function( $cbs, $lrnFile )

		{

			Learn_Clear( $lrnFile, false, true );

		};



	$cbs -> Learn_Init =

		function( $cbs, $oInit, $siteId, $bLrn, $settHash, $bAllowLearn, $aSkeletonAggr )

		{

			return( Learn_Init( $oInit, $siteId, $bLrn, $settHash, $bAllowLearn, $aSkeletonAggr ) );

		};



	$cbs -> Learn_Finish =

		function( $cbs, $ctxProcess )

		{

			return( Learn_Finish( $ctxProcess ) );

		};



	$cbs -> ExtContents_CacheGet =

		function( $cbs, $extCacheId )

		{

			return( ExtContents_Local_CacheGet( Gen::GetFileDir( $cbs -> ctxProcess[ 'dataPath' ] ), $extCacheId ) );

		};



	$cbs -> ExtContents_CacheSet =

		function( $cbs, $extCacheId, $fileType, $contCacheTtl, $contId, $contCache )

		{

			ExtContents_Local_CacheSet( Gen::GetFileDir( $cbs -> ctxProcess[ 'dataPath' ] ), $extCacheId, $fileType, $contCacheTtl, $contId, $contCache );

		};



	$cbs -> CustomMethod =

		function( $cbs, $name, $args )

		{

			return( ContentProcess_CallCustomMethod( $name, $args ) );

		};



	$cbs -> ImagesProcessSrcSizeAlternatives_CacheGet =

		function( $cbs, $imgStgId )

		{

			return( Images_ProcessSrcSizeAlternatives_Cache_Get( $cbs -> ctxProcess[ 'dataPath' ], $imgStgId ) );

		};



	$cbs -> ImagesProcessSrcSizeAlternatives_CacheSet =

		function( $cbs, $imgStgId, $v )

		{

			return( Images_ProcessSrcSizeAlternatives_Cache_Set( $cbs -> ctxProcess[ 'dataPath' ], $imgStgId, $v ) );

		};



	$cbs -> PostPrepareObj =

		function( $cbs, $type, $addr, $priority, $data = array(), $priorityInitiator = null, $time = null )

		{

			return( CachePostPrepareObjEx( $type, $addr, $cbs -> ctxProcess[ 'siteId' ], $priority, $data, $priorityInitiator, $time ) );

		};



	$cbs -> GetSalt =

		function( $cbs )

		{

			return( GetSalt() );

		};



	$ctxProcess[ 'cbs' ] = $cbs;

}



function _JsClk_XpathExtFunc_ifExistsThenCssSel( $v, $cssSel )

{

	if( !is_array( $v ) || count( $v ) < 1 )

		return( false );

	return( new JsClk_ifExistsThenCssSel( $cssSel ) );

}



function LazyCont_Process( &$ctxProcess, $sett, $settCache, $settContPr, $doc, $norm, $jsNotCritsDelayTimeout )

{



	$itemsPathes = Gen::GetArrField( $settContPr, array( 'lazy', 'items' ), array() );

	if( !$itemsPathes )

		return( null );



	$bLazyCont = null;

	$itemPathPrmsDef = array( 'bjs' => Gen::GetArrField( $settContPr, array( 'lazy', 'bjs' ), false ), 'sep' => 9999999, 'chunk' => 8192, 'chunkSep' => 524288 );



	$xpath = new \DOMXPath( $doc );

	$xpath -> registerNamespace( 'php', 'http://php.net/xpath' );

	$xpath -> registerPhpFunctions( array( 'seraph_accel\\_LazyCont_XpathExtFunc_FollowingSiblingUpToParent' ) );



	$aItemsExcl = array();

	foreach( Gen::GetArrField( $settContPr, array( 'lazy', 'itemsExcl' ), array() ) as $itemPathExcl )

		foreach( HtmlNd::ChildrenIter( @$xpath -> query( $itemPathExcl ) ) as $item )

			$aItemsExcl[] = $item;



	$idNosPart = 1;

	$aItemSubstBlock = array();

	$aItemNoScript = array();



	foreach( $itemsPathes as $itemPath )

	{

		$itemPathPrms = $itemPathPrmsDef;

		if( preg_match( '@^([\\w,=]+):[^:]@', $itemPath, $m ) )

		{

			$itemPathPrms = array_merge( $itemPathPrms, Gen::ParseProps( $m[ 1 ], ',', '=', array( 'bjs' => '', 'sep' => 1, 'chunk' => 8192, 'chunkSep' => 524288 ) ) );

			$itemPath = substr( $itemPath, strlen( $m[ 1 ] ) + 1 );

		}



		if( $itemPathPrms[ 'bjs' ] && !$jsNotCritsDelayTimeout )

			continue;



		$itemPath = str_replace( 'followingSiblingUpToParent(', 'php:function("seraph_accel\\_LazyCont_XpathExtFunc_FollowingSiblingUpToParent",', $itemPath );



		$items = array();

		foreach( HtmlNd::ChildrenIter( @$xpath -> query( $itemPath ) ) as $item )

		{

			if( ( ( $item -> nodeName == 'script' && !in_array( $item -> getAttribute( 'type' ), array( 'application/json' ) ) ) || $item -> nodeName == 'style' || $item -> nodeName == 'link' ) )

				continue;



			$bExcl = false;

			foreach( $aItemsExcl as $itemExcl )

			{



				if( !HtmlNd::DoesContain( $itemExcl, $item ) && !HtmlNd::DoesContain( $item, $itemExcl ) )

					continue;

				$bExcl = true;

				break;

			}

			if( $bExcl )

				continue;



			for( $i = 0; $i < count( $items ); $i++ )

			{

				if( HtmlNd::DoesContain( $items[ $i ], $item ) )

					break;



				if( HtmlNd::DoesContain( $item, $items[ $i ] ) )

				{

					array_splice( $items, $i, 1 );

					continue;

				}

			}



			if( $i === count( $items ) )

				$items[] = $item;

		}

		if( !$items )

			continue;



		$bLazyCont = true;



		$nItemsGroupSize = 0;

		$itemGroupFirst = $itemGroupLast = null;

		$itemGroupCurParent = null;

		$iSubstSequentalBlock = 1;



		for( $i = 0; $i < count( $items ) + 1; $i++ )

		{

			$item = $i < count( $items ) ? $items[ $i ] : null;



			if( $item )

			{



				$overlapped = false;

				if( !$overlapped )

					foreach( $aItemSubstBlock as $itemSubstBlock )

						if( HtmlNd::DoesContain( $item, $itemSubstBlock ) )

						{

							$overlapped = true;

							break;

						}

				if( !$overlapped )

					foreach( $aItemNoScript as $itemNoScript )

						if( HtmlNd::DoesContain( $itemNoScript, $item ) )

						{

							$overlapped = true;

							break;

						}



				if( $overlapped )

					continue;

			}



			if( $item && $itemGroupLast && HtmlNd::GetNextTypeSibling( $itemGroupLast ) === $item && $nItemsGroupSize < ( ( $iSubstSequentalBlock >= $itemPathPrms[ 'sep' ] ) ? $itemPathPrms[ 'chunkSep' ] : $itemPathPrms[ 'chunk' ] ) )

			{

				$nItemsGroupSize += HtmlNd::GetOuterSize( $item );

				$itemGroupLast = $item;

				continue;

			}



			if( $itemGroupFirst )

			{

				if( ($ctxProcess[ 'compatView' ]??null) )

				{

					ContentMarkSeparate( $itemGroupFirst, false, 1 );

					ContentMarkSeparate( $itemGroupLast, false, 2 );

				}

				else

				{

					$itemSubstBlock = $doc -> createElement( HtmlNd::DoesContain( $ctxProcess[ 'ndBody' ], $itemGroupFirst ) ? 'i' : 'noscript' );

					HtmlNd::AddRemoveAttrClass( $itemSubstBlock, array( $itemPathPrms[ 'bjs' ] !== 'only' ? 'lzl' : null, $itemPathPrms[ 'bjs' ] ? 'bjs' : null ) );

					$itemSubstBlock -> setAttribute( 'data-lzl-nos', ( string )$idNosPart );

					$itemGroupFirst -> parentNode -> insertBefore( $itemSubstBlock, $itemGroupFirst );



					if( isset( $itemPathPrms[ 'height' ] ) )

						$itemSubstBlock -> setAttribute( 'style', Ui::GetStyleAttr( array( 'height' => $itemPathPrms[ 'height' ] ), false ) );



					{

						$itemNoScript = $doc -> createElement( 'noscript' );

						$itemNoScript -> setAttribute( 'data-lzl-nos-c', ( string )$idNosPart );

						HtmlNd::InsertAfter( $itemSubstBlock -> parentNode, $itemNoScript, $itemSubstBlock );



						for( ;; )

						{

							$itemNext = $itemGroupFirst -> nextSibling;

							$itemNoScript -> appendChild( $itemGroupFirst );



							if( $itemGroupFirst === $itemGroupLast )

								break;

							$itemGroupFirst = $itemNext;

						}

						ContNoScriptItemClear( $itemNoScript );

					}



					if( HtmlNd::GetChildrenCount( $itemNoScript ) )

					{

						$aItemSubstBlock[] = $itemSubstBlock;



						$itemNoScript -> insertBefore( _ContentMarkSeparate_CreateSepElem( $doc ), $itemNoScript -> firstChild );

						$itemNoScript -> appendChild( _ContentMarkSeparate_CreateSepElem( $doc ) );



						ContentMarkSeparate( $itemSubstBlock, false, 1 );

						ContentMarkSeparate( $itemNoScript, false, 2 );



						if( $iSubstSequentalBlock >= $itemPathPrms[ 'sep' ] )

						{

							$idCp = ( string )( $ctxProcess[ 'subCurIdx' ]++ );

							$ctxProcess[ 'subs' ][ $idCp . '.html' ] = HtmlDocDeParse( $doc, $norm, $itemNoScript );

							$itemNoScript -> parentNode -> removeChild( $itemNoScript );

							$itemNoScript = null;



							$itemSubstBlock -> setAttribute( 'data-cp', $idCp );

						}

						else if( Gen::GetArrField( $settContPr, array( 'lazy', 'p' ), false ) )

						{



							$itemNoScript -> removeChild( $itemNoScript -> firstChild );

							$itemNoScript -> removeChild( $itemNoScript -> lastChild );



							$itemSubstBlock -> setAttribute( 'data-c', rawurlencode( HtmlDocDeParse( $doc, $norm, $itemNoScript ) ) );

							$itemSubstBlock -> setAttribute( 'data-gt-translate-attributes', @json_encode( array( array( 'attribute' => 'data-c', 'format' => 'html-urlencoded' ) ) ) );



							$itemNoScript -> parentNode -> removeChild( $itemNoScript );

							$itemNoScript = null;

						}

						else

							$aItemNoScript[] = $itemNoScript;



						$iSubstSequentalBlock++;

						$itemGroupCurParent = $itemSubstBlock -> parentNode;



						$idNosPart++;

						$ctxProcess[ 'lazyload' ] = true;

					}

					else

					{

						$itemNoScript -> parentNode -> removeChild( $itemNoScript );

						$itemNoScript = null;



						$itemSubstBlock -> parentNode -> removeChild( $itemSubstBlock );

						$itemSubstBlock = null;

					}

				}

			}



			$itemGroupFirst = $itemGroupLast = $item;

			$nItemsGroupSize = HtmlNd::GetOuterSize( $item );



			if( $item && $item -> parentNode !== $itemGroupCurParent )

				$iSubstSequentalBlock = 1;

		}

	}



	return( $bLazyCont );

}



function _LazyCont_XpathExtFunc_FollowingSiblingUpToParent( $v )

{

	if( !is_array( $v ) || count( $v ) < 1 )

		return( false );



	$aNdParent = func_get_args();

	if( count( $aNdParent ) > 1 && is_array( $aNdParent[ 1 ] ) )

		$aNdParent = $aNdParent[ 1 ];

	else

		$aNdParent = null;

	return( new LazyCont_XpathExtFunc_FollowingSiblingUpToParent_Iterator( $v, $aNdParent ) );

}



function ContentProcess_TryRemote( $itemType, $itemData, &$ctxProcess, $ctxProcessOrig, $sett, $doc = null, $buffer = '', $aStageNoReport = array() )

{

	global $seraph_accel_g_prepPrms;

	global $seraph_accel_g_prepContIsUserCtx;



	if( !( $ctxProcess[ 'mode' ] == ( 1 | 2 | 4 ) && $seraph_accel_g_prepPrms && !$seraph_accel_g_prepContIsUserCtx ) )

		return;



	if( ($seraph_accel_g_prepPrms[ 'selfTest' ]??null) )

		return;



	$skipStatusEngine = null;



	$settCache = Gen::GetArrField( $sett, array( 'cache' ), array() );



	if( ( Gen::GetArrField( $settCache, array( 'procEngn' ), 1 ) & 2 ) )

	{

		if( PluginLic::CheckFeature() == Gen::S_OK )

		{

			$skipStatusEngine = 'engineRemoteUnavailable';



			if( ( $aProcUrl = Gen::GetArrField( PluginRmtCfg::Get(), 'Prms.UrlsProcMgr', array() ) ) && ( $epName = PluginLic::GetEpName() ) )

			{

				$skipStatusEngine = _ContentProcess_Remote( $itemType, $itemData, $ctxProcess, $ctxProcessOrig, $doc, $sett, $settCache, $buffer, $aProcUrl, $epName, $aStageNoReport );

				if( !$skipStatusEngine )

					return( 'remote' );

			}

		}

		else

			$skipStatusEngine = 'engineRemoteNoLicense';

	}



	if( Gen::GetArrField( $settCache, array( 'procEngn' ), 1 ) & 1 )

	{

		if( $skipStatusEngine )

			LastWarnDscs_Add( $skipStatusEngine );

		return( null );

	}

	else if( !$skipStatusEngine )

		$skipStatusEngine = 'noEngine';



	return( $skipStatusEngine );

}



function _ContentProcess_Remote( $itemType, $itemData, &$ctxProcess, $ctxProcessOrig, $doc, $sett, $settCache, $buffer, $aProcUrl, $epName, $aStageNoReport = array() )

{

	global $seraph_accel_g_prepPrms;

	global $seraph_accel_g_dscFile;

	global $seraph_accel_g_dscFilePending;

	global $seraph_accel_g_dataPath;

	global $seraph_accel_g_ctxCache;

	global $seraph_accel_g_prepLearnId;

	global $seraph_accel_g_ahuddqrText;



	unset( $ctxProcessOrig[ 'cbs' ] );



	$skipStatusEngine = 'engineRemoteBusy';



	if( !( $resUpd = $ctxProcess[ 'cbs' ] -> ReportStage( null, LocId::Pack( 'StateSubProgress_RemotePrepare' ) ) ) )

		return( ( $resUpd === null ) ? 'aborted' : 'err:internal' );



	$settImg = Gen::GetArrField( $sett, array( 'contPr', 'img' ), array() );



	$requestData = null;

	{



		$ctxLocalFetch = new AnyObj();

		$ctxLocalFetch -> ctxProcess = &$ctxProcess;

		$ctxLocalFetch -> settCache = $settCache;

		$ctxLocalFetch -> settImg = $settImg;

		$ctxLocalFetch -> bAdaptImg = Gen::GetArrField( $settImg, array( 'szAdaptImg' ) ) || Gen::GetArrField( $settImg, array( 'szAdaptBg' ) );

		$ctxLocalFetch -> aFile = array();

		$ctxLocalFetch -> cbImgSrcAttrData =

			function( $ctxLocalFetch, $imgSrc )

			{

				if( !$ctxLocalFetch -> bAdaptImg )

					return;



				foreach( Images_ProcessSrc_SizeAlternatives_GetAssociatedFiles( $ctxLocalFetch -> ctxProcess, $ctxLocalFetch -> settCache, $ctxLocalFetch -> settImg, $imgSrc ) as $filePathAssoc )

					ContentProcess_PreFetchLocalFiles( $ctxLocalFetch -> aFile, $ctxLocalFetch -> ctxProcess, array( $filePathAssoc => array() ), false );

			};



		if( $itemType == 0 )

		{

			$xpath = new \DOMXPath( $doc );



			{

				foreach( $xpath -> query( './/img[@src]' ) as $item )

				{

					$aImgSrc = array( HtmlNd::GetAttrVal( $item, 'src' ) );

					foreach( Ui::ParseSrcSetAttr( $item -> getAttribute( 'srcset' ) ) as $srcItem )

						$aImgSrc[] = html_entity_decode( $srcItem[ 0 ] );



					foreach( $aImgSrc as $imgSrc )

					{

						$imgSrc = new ImgSrc( $ctxProcess, $imgSrc );

						$imgSrc -> Init();

						if( $imgSrc -> IsSrcData() )

							$ctxLocalFetch -> cbImgSrcAttrData( $imgSrc );

						else if( $filePath = ($imgSrc -> srcInfo[ 'filePath' ]??null) )

							ContentProcess_PreFetchLocalFiles( $ctxLocalFetch -> aFile, $ctxProcess, array( $filePath => array( 'filePathRoot' => ($imgSrc -> srcInfo[ 'filePathRoot' ]??null) ) ), true, 120 * 1024 );

					}

				}

			}



			{



				foreach( $xpath -> query( './/*[@style]' ) as $item )

					ContentProcess_PreFetchLocalFilesEx( $ctxLocalFetch -> aFile, $ctxProcess, ContentProcess_PreFetchLocalFiles_ExpandCssCont( $ctxProcess, $item -> getAttribute( 'style' ), array( 'cbImgSrcAttrData' => array( $ctxLocalFetch, 'cbImgSrcAttrData' ) ) ), true, 120 * 1024 );



				foreach( $xpath -> query( './/style' ) as $item )

					ContentProcess_PreFetchLocalFilesEx( $ctxLocalFetch -> aFile, $ctxProcess, ContentProcess_PreFetchLocalFiles_ExpandCssCont( $ctxProcess, $item -> nodeValue, array( 'cbImgSrcAttrData' => array( $ctxLocalFetch, 'cbImgSrcAttrData' ) ) ), true, 120 * 1024 );



				foreach( $xpath -> query( './/link[@rel="stylesheet"][@href] | .//link[@rel="preload"][@as="style"][@onload][@href]' ) as $item )

				{

					$src = HtmlNd::GetAttrVal( $item, 'href' );

					$srcInfo = GetSrcAttrInfo( $ctxProcess, null, null, $src );

					if( $filePath = ($srcInfo[ 'filePath' ]??null) )

						ContentProcess_PreFetchLocalFiles( $ctxLocalFetch -> aFile, $ctxProcess, array( $filePath => array( 'filePathRoot' => ($srcInfo[ 'filePathRoot' ]??null), 'cssExtract' => true, 'cbImgSrcAttrData' => array( $ctxLocalFetch, 'cbImgSrcAttrData' ) ) ) );

				}

			}



			foreach( $xpath -> query( './/script[@src]' ) as $item )

			{

				$src = HtmlNd::GetAttrVal( $item, 'src' );

				$srcInfo = GetSrcAttrInfo( $ctxProcess, null, null, $src );

				if( $filePath = ($srcInfo[ 'filePath' ]??null) )

					ContentProcess_PreFetchLocalFiles( $ctxLocalFetch -> aFile, $ctxProcess, array( $filePath => array( 'filePathRoot' => ($srcInfo[ 'filePathRoot' ]??null) ) ) );

			}



			unset( $xpath );

		}

		else if( $itemType == 10 )

		{

			$filePath = ($itemData[ 'u' ]??null);



			ContentProcess_PreFetchLocalFiles( $ctxLocalFetch -> aFile, $ctxProcess, array( $filePath => array() ) );

		}

		else if( $itemType == 20 )

		{

			$filePath = ($itemData[ 'u' ]??null);



			ContentProcess_PreFetchLocalFiles( $ctxLocalFetch -> aFile, $ctxProcess, array( $filePath => array() ) );



			foreach( Images_ProcessSrc_SizeAlternatives_GetAssociatedFiles( $ctxProcess, $settCache, $settImg, $filePath, null, ($itemData[ 'crp' ]??null), ($itemData[ 'ai' ]??null) ) as $filePathAssoc )

				ContentProcess_PreFetchLocalFiles( $ctxLocalFetch -> aFile, $ctxProcess, array( $filePathAssoc => array() ), false );

		}



		$aSrcChunk = array(); $aTof = array();

		if( $itemType == 0 )

		{

			$aData = array();

			$aLrnGlob = array();



			if( $dsc = CacheReadDsc( $seraph_accel_g_dscFile ) )

				$aData = Gen::GetArrField( $dsc, array( 's' ), array() );



			$lrnDataPath = ($ctxProcess[ 'lrnDataPath' ]??null);

			if( !$lrnDataPath )

				$lrnDataPath = Gen::GetFileDir( $ctxProcess[ 'dataPath' ] ) . '/l';

			$lrnDsc = ($ctxProcess[ 'lrnDsc' ]??null);

			$lrnFile = ($ctxProcess[ 'lrnFile' ]??null);

			if( !$lrnDsc && $dsc && isset( $dsc[ 'l' ] ) && $seraph_accel_g_ctxCache )

			{

				$lrnFile = $seraph_accel_g_ctxCache -> viewPath . '/l/' . Learn_Id2File( $dsc[ 'l' ] );

				$lrnDsc = Learn_ReadDsc( $lrnFile );

			}



			if( $lrnDsc )

				Learn_GetNeededData( $aData, $aLrnGlob, $lrnDsc, $lrnDataPath );



			foreach( $aData as $type => $aId )

			{



				foreach( $aId as $oiCi )

				{

					if( $oiCi == '*' )

						continue;



					foreach( ( $type == 'img' ? array( 'jpe','jpg','jpeg','png','gif','bmp' ) : array( null ) ) as $fileExt )

					{

						$dataPath = $ctxProcess[ 'dataPath' ];

						$oiCfn = ReadSceGf( $dataPath, $settCache, $oiCi, $type, $fileExt );

						$filePath = $dataPath . '/' . $oiCfn;

						if( CacheCcEx( $dataPath, $type, $oiCfn ) )

						{

							$aSrcChunk[ $type ][ substr( $dataPath, strlen( $ctxProcess[ 'siteCacheRootDir' ] ) ) ][ $oiCfn ] = true;

							ContentProcess_PreFetchLocalFiles( $ctxLocalFetch -> aFile, $ctxProcess, array( $filePath => array( 'filePathRoot' => $ctxProcess[ 'siteCacheRootDir' ] ) ) );



						}

					}

				}

			}



			foreach( $aLrnGlob as $file )

			{

				$file = $lrnDataPath . '/' . $file;

				$dir = Gen::GetFileDir( $file );

				$id = Gen::GetFileName( $file );



				if( is_string( $c = Tof_GetFileDataEx( $dir, $id ) ) )

					$aTof[ substr( $dir, strlen( $ctxProcess[ 'siteCacheRootDir' ] ) ) ][ $id ] = array( 'c' => $c );

			}



			unset( $aData, $aLrnGlob );

		}



		if( $itemType == 0 && $ctxLocalFetch -> bAdaptImg )

		{

			foreach( $ctxLocalFetch -> aFile as $filePath => $info )

				if( in_array( Gen::GetFileExt( $filePath ), array( 'jpe','jpg','jpeg','png','gif','bmp', 'webp','avif' ) ) )

					if( !Gen::StrStartsWith( $filePath, $ctxProcess[ 'dataPath' ] . '/ai/' ) )

						foreach( Images_ProcessSrc_SizeAlternatives_GetAssociatedFiles( $ctxProcess, $settCache, $settImg, $filePath, ($info[ 'c' ]??null) ) as $filePathAssoc )

							ContentProcess_PreFetchLocalFiles( $ctxLocalFetch -> aFile, $ctxProcess, array( $filePathAssoc => array() ), false );

		}



		{

			foreach( $ctxLocalFetch -> aFile as $filePath => $info )

				foreach( Images_ProcessSrc_ConvertAll_GetAssociatedFiles( $filePath ) as $filePathAssoc )



						ContentProcess_PreFetchLocalFiles( $ctxLocalFetch -> aFile, $ctxProcess, array( $filePathAssoc => array() ), false );

		}



		$aImagesProcessSrcSizeAlternativesCache = array();

		$aImagesProcessSrcSizeAlternativesCache = Images_ProcessSrcSizeAlternatives_Cache_GetAll( $ctxProcess[ 'dataPath' ] );



		$requestData = array( 'itemType' => $itemType, 'itemData' => $itemData, 'ctxProcess' => $ctxProcessOrig, 'sett' => $sett, 'content' => $buffer, 'ctxCache' => ( array )$seraph_accel_g_ctxCache, 'aBnrTxt' => $seraph_accel_g_ahuddqrText, 'prepPrms' => $seraph_accel_g_prepPrms, 'salt' => GetSalt(), 'lrnDsc' => $lrnDsc, 'lrnFile' => $lrnFile, 'ContSkeleton_FltName_PrepPatterns_aPlchldr' => ContSkeleton_FltName_PrepPatterns_Plchldrs_Exec(), 'aContPostProcType' => array( 'css' => !!Wp::GetFilters( 'seraph_accel_css_content' ), 'js' => !!Wp::GetFilters( 'seraph_accel_js_content' ) ), 'aFile' => $ctxLocalFetch -> aFile, 'aSrcChunk' => $aSrcChunk, 'aTof' => $aTof, 'aImagesProcessSrcSizeAlternativesCache' => $aImagesProcessSrcSizeAlternativesCache, 'aStageNoReport' => $aStageNoReport );

		if( isset( $requestData[ 'lrnDsc' ][ 's' ] ) )

			$requestData[ 'lrnDsc' ][ 's' ] = gzencode( Gen::Serialize( $requestData[ 'lrnDsc' ][ 's' ] ) );

		$requestData = Gen::Serialize( $requestData );

	}



	if( ( $procUrlLastUsed = Gen::GetArrField( Plugin::DataGet(), array( 'urlProcMgrLastUsed' ) ) ) && ( ( $i = array_search( $procUrlLastUsed, $aProcUrl ) ) !== false ) && ( $i !== 0 ) )

	{

		array_splice( $aProcUrl, $i, 1 );

		array_splice( $aProcUrl, 0, 0, array( $procUrlLastUsed ) );

	}



	foreach( $aProcUrl as $procUrl )

	{

		$fileCtl = Gen::GetFileName( ($seraph_accel_g_prepPrms[ 'pc' ]??null) );

		$procUrlLastUsed = $procUrl;

		$procUrl = Gen::StrReplaceKeyed( array( '{FN}' => 'RunItem', '{EP}' => rawurlencode( $epName ), '{V}' => rawurlencode( '2.29.11' ), '{CB}' => rawurlencode( Net::UrlAddArgs( Wp::GetSiteRootUrl( '/', false ), array( 'seraph_accel_at' => 'ORC', 'pc' => $fileCtl, '_n' => Gen::GetNonce( 'ORC' . $fileCtl, GetSalt() ) ) )  ) ), $procUrl );



		$hr = Gen::S_OK;



		$requestRes = Wp::RemotePost( $procUrl, array( 'connect_timeout' => 5, 'timeout' => 180, 'sslverify' => false, 'headers' => array( 'Content-Type' => 'application/octet-stream' ), 'body' => $requestData ) );

		$hr = Net::GetHrFromWpRemoteGet( $requestRes, false, true );



		if( $hr == Gen::S_OK )

		{

			$aRemoteCtx = ( array )json_decode( wp_remote_retrieve_body( $requestRes ), true );



			if( Gen::GetArrField( $aRemoteCtx, array( 'urlAbort' ) ) )

			{



				$aRemoteCtx[ 'dscFilePending' ] = $seraph_accel_g_dscFilePending;

				$aRemoteCtx[ 'dataPath' ] = $seraph_accel_g_dataPath;

				$aRemoteCtx[ 'urlCur' ] = GetCurRequestUrl();



				if( !( $resUpd = ProcessCtlData_Update( ($seraph_accel_g_prepPrms[ 'pc' ]??null), array( 'remote' => $aRemoteCtx, 'stage' => 'common', 'stageDsc' => LocId::Pack( 'StateSubProgress_RemoteWait' ) ) ) ) )

					return( ( $resUpd === null ) ? 'aborted' : 'err:internal' );



				Plugin::DataSet( array_merge( Plugin::DataGet(), array( 'urlProcMgrLastUsed' => $procUrlLastUsed ) ) );

				return( null );

			}



			$skipStatusEngine = 'engineRemoteInvalidResponse';

		}

		else

		{

			if( $hr == Gen::E_ACCESS_DENIED )

			{

				$skipStatusEngine = 'engineRemoteAccessDenied';

				break;

			}

			else if( Net::GetResponseCodeFromHr( $hr ) == 402 )

			{

				$skipStatusEngine = 'engineRemoteQuotaReached';

				break;

			}

			else if( $hr == Net::E_TIMEOUT || $hr == Gen::E_BUSY )

				$skipStatusEngine = 'engineRemoteBusy';

			else

				$skipStatusEngine = 'engineRemote:' . sprintf( '0x%08X', $hr );

		}

	}



	return( $skipStatusEngine );

}



function ContentProcess_Remote_Cb_Process( $args )

{

	if( ($args[ 'f' ]??'') == 'TestConn' )

	{



		if( !Gen::CheckNonce( Gen::GetArrField( $args, array( '_n' ), '' ), 'ORC', GetSalt(), 300 ) )

			return( Gen::E_ACCESS_DENIED );



		return( Gen::S_OK );

	}



	$fileCtl = Gen::GetArrField( $args, array( 'pc' ), '' );



	if( Gen::GetArrField( $args, array( '_n' ), '' ) != Gen::GetNonce( 'ORC' . $fileCtl, GetSalt() ) )

	{



		return( Gen::E_ACCESS_DENIED );

	}



	$fileCtl = ProcessCtlData_GetFullPath( $fileCtl );



	$ctlRes = ProcessCtlData_Get( $fileCtl );

	if( $ctlRes === null )

		return( Gen::S_ABORTED );



	$data = Gen::Unserialize( ( string )@file_get_contents( 'php://input' ), true );



	if( Gen::GetArrField( $ctlRes, array( 'remote', 'id' ) ) != Gen::GetArrField( $data, array( 'id' ), '' ) )

		return( Gen::E_NOT_FOUND );



	$func = ($data[ 'f' ]??'');



	if( $func == 'ReportStage' )

	{

		$dataUpd = array( 'stageDsc' => ($data[ 'stageDsc' ]??null) );

		if( isset( $data[ 'stage' ] ) )

			$dataUpd[ 'stage' ] = $data[ 'stage' ];



		ProcessCtlData_Update( $fileCtl, $dataUpd );

		return( Gen::S_OK );

	}



	if( $func == 'ContPostProc' )

	{

		if( !isset( $data[ 'type' ] ) || !isset( $data[ 'content' ] ) || !isset( $data[ 'isFile' ] ) )

			return( Gen::E_INVALIDARG );



		if( $data[ 'type' ] == 'css' )

			@header( 'Content-Type: text/css; charset=UTF-8' );

		else if( $data[ 'type' ] == 'js' )

			@header( 'Content-Type: text/javascript; charset=UTF-8' );



		$ctxLoad = new AnyObj( array( 'vars' => get_defined_vars() ) );

		$ctxLoad -> cb =

			function( $ctxLoad )

			{

				extract( $ctxLoad -> vars );



				if( $data[ 'type' ] == 'css' )

					$data[ 'content' ] = apply_filters( 'seraph_accel_css_content', $data[ 'content' ], $data[ 'isFile' ] );

				else if( $data[ 'type' ] == 'js' )

					$data[ 'content' ] = apply_filters( 'seraph_accel_js_content', $data[ 'content' ], $data[ 'isFile' ] );



				CacheWriteOut( $data[ 'content' ] );

				exit;

			};



		add_action( 'wp_loaded', array( $ctxLoad, 'cb' ), -999999 );

		return;

	}



	if( $func == 'PreFetchLocalFiles' )

	{

		if( !isset( $data[ 'a' ] ) || !isset( $data[ 'ctxProcess' ] ) || !isset( $data[ 'cont' ] ) )

			return( Gen::E_INVALIDARG );



		@header( 'Content-Type: application/octet-stream' );



		$aFile = array();

		ContentProcess_PreFetchLocalFiles( $aFile, $data[ 'ctxProcess' ], $data[ 'a' ], $data[ 'cont' ] );

		CacheWriteOut( Gen::Serialize( $aFile ) );

		return( Gen::S_OK );

	}



	if( $func == 'LearnInit' )

	{

		if( !isset( $data[ 'oInit' ] ) )

			return( Gen::E_INVALIDARG );



		@header( 'Content-Type: application/octet-stream' );



		$oInit = new AnyObj( $data[ 'oInit' ] );



		$res = Learn_Init( $oInit, $data[ 'siteId' ], $data[ 'bLrn' ], $data[ 'settHash' ], $data[ 'bAllowLearn' ], $data[ 'aSkeletonAggr' ], $ctlRes );



		CacheWriteOut( Gen::Serialize( array( 'oInit' => ( array )$oInit, 'res' => $res ) ) );

		return( Gen::S_OK );

	}



	if( $func == 'CustomMethod' )

	{

		if( !isset( $data[ 'name' ] ) || !isset( $data[ 'args' ] ) || !isset( $data[ 'mode' ] ) )

			return( Gen::E_INVALIDARG );



		@header( 'Content-Type: application/octet-stream' );



		if( !$data[ 'mode' ] )

		{

			$r = ContentProcess_CallCustomMethod( $data[ 'name' ], $data[ 'args' ] );

			if( $r === false )

				return( Gen::E_NOT_FOUND );



			CacheWriteOut( Gen::Serialize( $r ) );

			return( Gen::S_OK );

		}



		$ctxLoad = new AnyObj( array( 'vars' => get_defined_vars() ) );

		$ctxLoad -> cb =

			function( $ctxLoad )

			{

				extract( $ctxLoad -> vars );



				$r = ContentProcess_CallCustomMethod( $data[ 'name' ], $data[ 'args' ] );

				if( $r === false )

					http_response_code( 404 );

				else

					CacheWriteOut( Gen::Serialize( $r ) );

				exit;

			};



		add_action( 'wp_loaded', array( $ctxLoad, 'cb' ), -999999 );

		return;

	}



	if( $func == 'Finish' )

	{

		if( !isset( $data[ 'ctxProcess' ] ) || !isset( $data[ 'content' ] ) || !isset( $data[ 'skipStatus' ] ) )

			return( Gen::E_INVALIDARG );



		$ctxLoad = new AnyObj( array( 'vars' => get_defined_vars() ) );

		$ctxLoad -> cb =

			function( $ctxLoad )

			{

				extract( $ctxLoad -> vars );



				$itemType = ($data[ 'itemType' ]??null);



				$sett = Plugin::SettGet( Gen::GetArrField( $data, array( 'sett' ) ), true );

				$settCache = Gen::GetArrField( $sett, array( 'cache' ), array() );

				$content = &$data[ 'content' ];

				$skipStatus = &$data[ 'skipStatus' ];



				$ctxProcess = &$data[ 'ctxProcess' ];

				if( isset( $ctxProcess[ 'lrnDsc' ][ 's' ] ) )

					$ctxProcess[ 'lrnDsc' ][ 's' ] = Gen::Unserialize( gzdecode( $ctxProcess[ 'lrnDsc' ][ 's' ] ) );



				ContentProcess_InitLocalCbs( $ctxProcess );



				foreach( Gen::GetArrField( $data, array( 'aPostPrepareObj' ), array() ) as $postPrepareObj )

					CachePostPrepareObjEx( $postPrepareObj[ 'type' ], $postPrepareObj[ 'addr' ], $ctxProcess[ 'siteId' ], $postPrepareObj[ 'priority' ], $postPrepareObj[ 'data' ], $postPrepareObj[ 'priorityInitiator' ], $postPrepareObj[ 'time' ] );



				Images_ProcessSrcSizeAlternatives_Cache_SetMany( $ctxProcess[ 'dataPath' ], Gen::GetArrField( $data, array( 'aImagesProcessSrcSizeAlternativesCache' ), array() ) );



				{

					$lock = new Lock( 'il', $ctxProcess[ 'siteCacheRootDir' ] );

					foreach( Gen::GetArrField( $data, array( 'aFile' ) ) as $filePath => $info )

					{

						$c = ($info[ 'c' ]??null);

						if( is_string( $c ) )

						{

							if( Gen::StrStartsWith( $filePath, $ctxProcess[ 'siteCacheRootDir' ] ) )

								Gen::MakeDir( Gen::GetFileDir( $filePath ), true );



							if( !Gen::FileWriteTmpAndReplace( $lock, $filePath, $info[ 'c' ], $info[ 'tm' ], ($info[ 'delIfFail' ]??null) ) && !$skipStatus )

								$skipStatus = 'err:internal';

						}

						else if( $c === false )

							Gen::Unlink( $filePath );

					}

					unset( $lock, $filePath, $info );

				}



				{

					foreach( Gen::GetArrField( $data, array( 'aSrcChunk' ) ) as $type => $aDataPath )

					{

						$bCompr = in_array( $type, array( 'css', 'js', 'json', 'html' ) );

						foreach( $aDataPath as $dataPath => $aoiCf )

						{

							foreach( $aoiCf as $oiCf => $c )

							{

								if( $bCompr )

								{

									$c = @gzdecode( $c );

									if( $c === false )

									{

										if( !$skipStatus )

											$skipStatus = 'err:' . rawurlencode( LocId::Pack( 'GzEncodingFail', 'Common' ) );

										continue;

									}

								}



								if( !CacheCwEx( $settCache, $ctxProcess[ 'siteCacheRootDir' ] . $dataPath, false, $c, $type, $oiCf ) )

									if( !$skipStatus )

										$skipStatus = 'err:internal';

							}

						}

					}

					unset( $aCont, $oiCf, $aoiCf, $aDataPath, $aDataPath, $type, $bCompr );

				}



				{

					foreach( Gen::GetArrField( $data, array( 'aTof' ) ) as $dir => $aId )

						foreach( $aId as $id => $aCont )

							if( Gen::HrFail( Tof_SetFileDataEx( $ctxProcess[ 'siteCacheRootDir' ] . $dir, $id, $aCont[ 'c' ], !!($aCont[ 'o' ]??null) ) ) )

								if( !$skipStatus )

									$skipStatus = 'err:internal';

					unset( $dir, $aId, $id, $aCont );

				}



				foreach( Gen::GetArrField( $data, array( 'warns' ) ) as $warn )

					LastWarnDscs_Add( $warn );



				$bLrnFinish = Gen::GetArrField( $data, array( 'bLrnFinish' ) );

				if( $bLrnFinish === false )

					Learn_Abort( $ctxProcess[ 'lrnFile' ] );

				else if( $bLrnFinish === true && Learn_Finish( $ctxProcess ) === false )

					if( !$skipStatus )

						$skipStatus = 'err:writeLrnDone';



				global $seraph_accel_g_prepPrms; $seraph_accel_g_prepPrms = Gen::GetArrField( $data, array( 'prepPrms' ) );

				$seraph_accel_g_prepPrms[ 'pc' ] = $fileCtl;



				global $seraph_accel_g_ctxCache; $seraph_accel_g_ctxCache = ( object )Gen::GetArrField( $data, array( 'ctxCache' ) );



				global $seraph_accel_g_ctxProcess; $seraph_accel_g_ctxProcess = $ctxProcess;

				global $seraph_accel_g_contProcGetSkipStatus; $seraph_accel_g_contProcGetSkipStatus = $skipStatus;



				global $seraph_accel_g_cacheObjChildren; $seraph_accel_g_cacheObjChildren = DepsExpand( $ctxProcess[ 'deps' ], false );

				global $seraph_accel_g_cacheObjSubs; $seraph_accel_g_cacheObjSubs = $ctxProcess[ 'subs' ];



				global $seraph_accel_g_dscFile; $seraph_accel_g_dscFile = Gen::GetArrField( $ctlRes, array( 'dscFile' ) );

				global $seraph_accel_g_dscFilePending; $seraph_accel_g_dscFilePending = Gen::GetArrField( $ctlRes, array( 'remote', 'dscFilePending' ) );

				global $seraph_accel_g_dataPath; $seraph_accel_g_dataPath = Gen::GetArrField( $ctlRes, array( 'remote', 'dataPath' ) );



				global $seraph_accel_g_prepLearnId; $seraph_accel_g_prepLearnId = Gen::GetArrField( $data, array( 'prepLearnId' ) );



				global $seraph_accel_g_noFo; $seraph_accel_g_noFo = true;



				if( $itemType == 0 )

					_CbContentFinishEx( apply_filters( 'seraph_accel_content', $content ), Gen::GetArrField( $ctlRes, array( 'remote', 'urlCur' ) ), $ctxProcess[ 'serverArgs' ] );

				else

					ProcessCtlData_Update( ($seraph_accel_g_prepPrms[ 'pc' ]??null), array_merge( array( 'finish' => true, 'skip' => $skipStatus, 'warns' => LastWarnDscs_Get() ), ($sett[ 'debugInfo' ]??null) ? array( 'infos' => array( LocId::Pack( 'ProcStat' ) => PackKvArrInfo( ($ctxProcess[ 'stat' ]??null) ) ) ) : array() ) );



				{

					$ctx = new ProcessQueueItemCtx( Gen::GetArrField( $ctlRes, array( 'queue', 'id' ) ), Gen::GetArrField( $ctlRes, array( 'queue', 'siteId' ) ) );

					$ctx -> hdrsForRequest = Gen::GetArrField( $ctlRes, array( 'queue', 'hdrsForRequest' ) );

					if( $ctx -> id && $ctx -> siteId )

					{

						$ctx -> fileCtl = $fileCtl;

						$ctx -> item = null;



						$lock = new Lock( 'l', $ctx -> dirQueue );

						if( $lock -> Acquire() )

						{

							$aProgress = new ArrayOnFiles( Queue_GetStgPrms( $ctx -> dirQueue, 1 ) );

							$ctx -> item = $aProgress[ $ctx -> id ];



							$aProgress -> dispose(); $lock -> Release();

							unset( $aProgress, $lock );

						}



						if( $ctx -> item && ( $ctx -> data = Gen::GetArrField( Gen::Unserialize( ($ctx -> item[ 'd' ]??null) ), array( '' ), array() ) ) && $ctx -> WaitForEndRequest() )

							$ctx -> Finish();

					}

				}



				exit;

			};



		add_action( 'wp_loaded', array( $ctxLoad, 'cb' ), -999999 );

		return;

	}



	return( Gen::E_INVALIDARG );

}



function OnRunContentProcessRemote( $idProc, $endpoint, $urlCb, $aUrlCbProxy, $data, $stat, $cbIsAborted )

{

	$cbs = new AnyObj();

	$cbs -> netRecvSz = strlen( $data );

	$cbs -> netSendSz = 0;

	$cbs -> netFetchCount = 0;

	$cbs -> netFetchAvgTm = 0;



	$data = Gen::Unserialize( $data );



	$cbs -> urlCb = $urlCb;

	$cbs -> aUrlCbProxy = $aUrlCbProxy;

	$cbs -> idProc = $idProc;

	$cbs -> cbIsAborted = $cbIsAborted;



	$cbs -> ctxProcessOrig = &$data[ 'ctxProcess' ];

	$cbs -> settOrig = &$data[ 'sett' ];



	$itemType = ($data[ 'itemType' ]??null);



	$cbs -> transport = Wp::GetRemoteTransport();



	global $seraph_accel_g_ahuddqrText; $seraph_accel_g_ahuddqrText = ($data[ 'aBnrTxt' ]??null);



	{

		if( ($endpoint[ 'isFree' ]??false) && !$seraph_accel_g_ahuddqrText )

		{

			_ContentProcessRemote_CbCall( 'Finish', $cbs, array( 30, 180 ), array( 'itemType' => $itemType, 'ctxProcess' => $cbs -> ctxProcessOrig, 'content' => '', 'skipStatus' => 'engineRemoteAccessDenied' ) );

			return( Gen::S_ACCESS_DENIED );

		}



		if( strpos( $cbs -> ctxProcessOrig[ 'siteDomainUrl' ] . $cbs -> ctxProcessOrig[ 'siteRootUri' ], $endpoint[ 'name' ] ) === false )

		{

			_ContentProcessRemote_CbCall( 'Finish', $cbs, array( 30, 180 ), array( 'itemType' => $itemType, 'ctxProcess' => $cbs -> ctxProcessOrig, 'content' => '', 'skipStatus' => 'engineRemoteAccessDenied' ) );

			return( Gen::S_ACCESS_DENIED );

		}

	}



	$cbs -> salt = ($data[ 'salt' ]??null);



	$cbs -> lrnDsc = ($data[ 'lrnDsc' ]??null);

	if( isset( $cbs -> lrnDsc[ 's' ] ) )

		$cbs -> lrnDsc[ 's' ] = Gen::Unserialize( gzdecode( $cbs -> lrnDsc[ 's' ] ) );



	$cbs -> lrnFile = ($data[ 'lrnFile' ]??null);



	$cbs -> bLrnFinish = null;

	$cbs -> aFile = ($data[ 'aFile' ]??null);

	$cbs -> aSrcChunk = ($data[ 'aSrcChunk' ]??null);

	$cbs -> aTof = ($data[ 'aTof' ]??null);

	$cbs -> aContPostProcType = ($data[ 'aContPostProcType' ]??null);

	$cbs -> aStageNoReport = ($data[ 'aStageNoReport' ]??null);

	$cbs -> aPostPrepareObj = array();

	$cbs -> aImagesProcessSrcSizeAlternativesCache = array_map( function( $v ) { return( array( 'v' =>  $v ) ); }, ( array )($data[ 'aImagesProcessSrcSizeAlternativesCache' ]??null) );



	global $seraph_accel_g_prepPrms; $seraph_accel_g_prepPrms = ($data[ 'prepPrms' ]??null);

	unset( $seraph_accel_g_prepPrms[ 'pc' ] );

	$seraph_accel_g_prepPrms[ '_dummy' ] = 1;



	global $seraph_accel_g_ctxCache; $seraph_accel_g_ctxCache = ( object )($data[ 'ctxCache' ]??null);

	global $seraph_accel_g_prepLearnId;



	$cbs -> ReportStage =

		function( $cbs, $stage = null, $stageDsc = null )

		{

			if( $stage === null )

			    return( true );



			if( $cbs -> aStageNoReport == array( '*' ) || in_array( $stage, $cbs -> aStageNoReport ) )

				return( true );



			$dataUpd = array( 'stageDsc' => $stageDsc );

			if( $stage )

				$dataUpd[ 'stage' ] = $stage;



			$hr = _ContentProcessRemote_CbCall( 'ReportStage', $cbs, 0.1, $dataUpd );

			if( $hr == Gen::S_OK )

				return( true );



			if( Gen::HrSucc( $hr ) )

				return( null );

			return( true );

		};



	$cbs -> IsAborted =

		function( $cbs, $ctxProcess = null, $settCache = null )

		{

			return( !Gen::SliceExecTime( 0, 0, 5, $cbs -> cbIsAborted ) );

		};



	$cbs -> GetContentProcessorForce =

		function( $cbs, $sett )

		{

			return( null );

		};



	$cbs -> ContPostProc =

		function( $cbs, $type, $content, $isFile = true )

		{

			if( !($cbs -> aContPostProcType[ $type ]??null) )

				return( $content );



			$requestRes = _ContentProcessRemote_CbCallEx( 'ContPostProc', $cbs, array( 30, 180 ), array( 'type' => $type, 'content' => $content, 'isFile' => $isFile ) );

			$hr = Net::GetHrFromWpRemoteGet( $requestRes, false, true );

			if( $hr == Gen::S_OK )

				return( wp_remote_retrieve_body( $requestRes ) );



			Gen::LastErrDsc_Set( LocId::Pack( 'RemoteSiteGetDataErr_%1$s', null, array( sprintf( '0x%08X', $hr ) ) ) );

			return( false );

		};



	$cbs -> PreFetchLocalFiles =

		function( $cbs, $a, $cont = true )

		{

			$aRes = array();

			$aNeeded = array();

			foreach( $a as $filePath => $attr )

			{

				$info = ($cbs -> aFile[ $filePath ]??null);

				if( !$info || ( ($attr[ 'cont' ]??$cont) && !array_key_exists( 'c', $info ) ) )

					$aNeeded[ $filePath ] = $attr;

				else

					$aRes[ $filePath ] = true;

			}



			if( !$aNeeded )

				return( $aRes );



			if( isset( $cbs -> ctxProcess[ '_stat' ] ) )

			{

				$tmStat = microtime( true );

				$cbs -> netFetchCount += 1;

			}



			$requestRes = _ContentProcessRemote_CbCallEx( 'PreFetchLocalFiles', $cbs, array( 30, 180 ), array( 'ctxProcess' => $cbs -> ctxProcessOrig, 'a' => $aNeeded, 'cont' => $cont ) );



			if( isset( $cbs -> ctxProcess[ '_stat' ] ) )

			{

				$tmStat = microtime( true ) - $tmStat;

				$cbs -> netFetchAvgTm = ( $cbs -> netFetchAvgTm + $tmStat ) / 2;

			}



			$hr = Net::GetHrFromWpRemoteGet( $requestRes, false, true );

			if( $hr != Gen::S_OK )

			{

				Gen::LastErrDsc_Set( LocId::Pack( 'RemoteSiteGetDataErr_%1$s', null, array( sprintf( '0x%08X', $hr ) ) ) );

				return( false );

			}



			$aFile = Gen::Unserialize( wp_remote_retrieve_body( $requestRes ) );

			$cbs -> aFile = array_merge( $cbs -> aFile, $aFile );

			$aRes = array_keys( array_merge( $aRes, $aFile ) );

			return( $aRes );

		};



	$cbs -> LocalFileExists =

		function( $cbs, $filePath, $filePathRoot = null )

		{

			if( !$filePath )

				return( null );



			if( $cbs -> PreFetchLocalFiles( array( $filePath => array( 'filePathRoot' => $filePathRoot ) ), false ) === false )

				return( false );



			$info = $cbs -> aFile[ $filePath ];

			return( isset( $info[ 'tm' ] ) );

		};



	$cbs -> ReadLocalFile =

		function( $cbs, $filePath, $filePathRoot = null )

		{

			if( !$filePath )

				return( null );



			if( $cbs -> PreFetchLocalFiles( array( $filePath => array( 'filePathRoot' => $filePathRoot ) ) ) === false )

				return( false );



			$info = $cbs -> aFile[ $filePath ];

			$c = $info[ 'c' ];

			if( ($info[ 'cmpr' ]??null) && is_string( $info[ 'c' ] ) )

			{

				$c = @gzdecode( $c );

				if( $c === false )

				{

					Gen::LastErrDsc_Set( LocId::Pack( 'GzEncodingFail', 'Common' ) );

					return( false );

				}

			}



			return( $c );

		};



	$cbs -> WriteLocalFile =

		function( $cbs, $filePath, $data, $fileTime = null, $delIfFail = false )

		{

			$info = ($cbs -> aFile[ $filePath ]??null);

			if( !$info )

				$info = array();



			if( $fileTime === null )

				$fileTime = time();



			$info[ 'c' ] = $data;

			$info[ 'tm' ] = $fileTime;

			$info[ 'sz' ] = strlen( $data );

			if( $delIfFail )

				$info[ 'delIfFail' ] = true;



			$info[ 'd' ] = true;



			ContentProcess_PreFetchLocalFiles_AdjustContCompr( $info, $filePath );



			$cbs -> aFile[ $filePath ] = $info;

			return( true );

		};



	$cbs -> GetLocalFileSize =

		function( $cbs, $filePath )

		{

			$info = ($cbs -> aFile[ $filePath ]??null);

			if( !$info )

				if( $cbs -> PreFetchLocalFiles( array( $filePath => array( 'filePathRoot' => Gen::GetFileDir( $filePath ) ) ), false ) === false )

					return( false );



			$info = $cbs -> aFile[ $filePath ];

			return( ($info[ 'sz' ]??false) );

		};



	$cbs -> GetLocalFileMTime =

		function( $cbs, $filePath )

		{

			$info = ($cbs -> aFile[ $filePath ]??null);

			if( !$info )

				if( $cbs -> PreFetchLocalFiles( array( $filePath => array( 'filePathRoot' => Gen::GetFileDir( $filePath ) ) ), false ) === false )

					return( false );



			$info = $cbs -> aFile[ $filePath ];

			return( ($info[ 'tm' ]??false) );

		};



	$cbs -> DeleteLocalFile =

		function( $cbs, $filePath )

		{

			$info = ($cbs -> aFile[ $filePath ]??null);

			if( !$info )

				$info = array();



			$info[ 'c' ] = false;

			unset( $info[ 'tm' ], $info[ 'sz' ] );



			$info[ 'd' ] = true;



			$cbs -> aFile[ $filePath ] = $info;

			return( true );

		};



	$cbs -> asuxsadkxsshi =

		function( $cbs, $dataPath, $type, $oiCfn )

		{

			$filePath = $dataPath . '/' . $oiCfn;

			$dirKey = substr( $dataPath, strlen( $cbs -> ctxProcess[ 'siteCacheRootDir' ] ) );



			$content = ($cbs -> aSrcChunk[ $type ][ $dirKey ][ $oiCfn ]??null);

			if( is_string( $content ) )

				return( true );



			if( !$content )

			{

				if( $cbs -> PreFetchLocalFiles( array( $filePath => array( 'filePathRoot' => $cbs -> ctxProcess[ 'siteCacheRootDir' ] ) ), false ) === false )

					return( false );

				$cbs -> aSrcChunk[ $type ][ $dirKey ][ $oiCfn ] = true;

			}



			return( isset( $cbs -> aFile[ $filePath ][ 'tm' ] ) );

		};



	$cbs -> ScRd =

		function( $cbs, $dataPath, $settCache, $type, $oiCi, $oiCfn )

		{

			$filePath = $dataPath . '/' . $oiCfn;

			$dirKey = substr( $dataPath, strlen( $cbs -> ctxProcess[ 'siteCacheRootDir' ] ) );



			$content = ($cbs -> aSrcChunk[ $type ][ $dirKey ][ $oiCfn ]??null);

			if( is_string( $content ) )

			{

				if( in_array( $type, array( 'css', 'js', 'json', 'html' ) ) )

				{

					$content = @gzdecode( $content );

					if( $content === false )

					{

						Gen::LastErrDsc_Set( LocId::Pack( 'GzEncodingFail', 'Common' ) );

						return( false );

					}

				}



				return( $content );

			}



			if( !$content )

			{

				if( $cbs -> PreFetchLocalFiles( array( $filePath => array( 'filePathRoot' => $cbs -> ctxProcess[ 'siteCacheRootDir' ] ) ) ) === false )

					return( false );

				$cbs -> aSrcChunk[ $type ][ $dirKey ][ $oiCfn ] = true;

			}



			return( $cbs -> ReadLocalFile( $filePath ) );

		};



	$cbs -> ScWr =

		function( $cbs, $settCache, $dataPath, $composite, $content, $type, $oiCfn )

		{

			$filePath = $dataPath . '/' . $oiCfn;

			$dirKey = substr( $dataPath, strlen( $cbs -> ctxProcess[ 'siteCacheRootDir' ] ) );



			$sz = strlen( $content );

			$compr = null;



			if( in_array( $type, array( 'css', 'js', 'json', 'html' ) ) )

			{

				$content = @gzencode( $content );

				if( $content === false )

				{

					Gen::LastErrDsc_Set( LocId::Pack( 'GzEncodingFail', 'Common' ) );

					return( false );

				}



				$compr = true;

			}



			$cbs -> aSrcChunk[ $type ][ $dirKey ][ $oiCfn ] = $content;



			$time = time();

			$cbs -> aFile[ $filePath ] = array( 'cmpr' => $compr, 'c' => $content, 'sz' => $sz, 'tm' => $time );



			{



				$a = Images_ProcessSrc_ConvertAll_GetAssociatedFiles( $filePath );



				$aFetch = array();

				foreach( $a as $filePath )

					$aFetch[ $filePath ] = array( 'filePathRoot' => $cbs -> ctxProcess[ 'siteCacheRootDir' ] );



				if( $cbs -> PreFetchLocalFiles( $aFetch, false ) === false )

					return( false );



				foreach( $a as $filePath )

				{

					$info = ($cbs -> aFile[ $filePath ]??null);

					if( $info && isset( $info[ 'tm' ] ) )

					{

						$info[ 'tm' ] = $time;

						$cbs -> aFile[ $filePath ] = $info;

					}

				}

			}



			return( true );

		};



	$cbs -> Tof_GetFileDataEx =

		function( $cbs, $dir, $id )

		{

			return( ($cbs -> aTof[ substr( $dir, strlen( $cbs -> ctxProcess[ 'siteCacheRootDir' ] ) ) ][ $id ][ 'c' ]??false) );

		};



	$cbs -> Tof_SetFileDataEx =

		function( $cbs, $dir, $id, $data, $overwrite = true )

		{

			$cbs -> aTof[ substr( $dir, strlen( $cbs -> ctxProcess[ 'siteCacheRootDir' ] ) ) ][ $id ] = array( 'c' => $data, 'o' => $overwrite, 'd' => true );

			return( Gen::S_OK );

		};



	$cbs -> Learn_Init =

		function( $cbs, $oInit, $siteId, $bLrn, $settHash, $bAllowLearn, $aSkeletonAggr )

		{

			if( $cbs -> lrnDsc && $oInit -> lrnFile == $cbs -> lrnFile )

			{

				if( $bLrn )

				{

					$oInit -> lrnDsc = $cbs -> lrnDsc;

					return( true );

				}



				if( _Learn_InitEx( $oInit, $cbs -> lrnDsc, $settHash, $aSkeletonAggr ) )

					return( null );

			}



			$requestRes = _ContentProcessRemote_CbCallEx( 'LearnInit', $cbs, array( 30, 180 ), array( 'oInit' => ( array )$oInit, 'siteId' => $siteId, 'bLrn' => $bLrn, 'settHash'=> $settHash, 'bAllowLearn' => $bAllowLearn, 'aSkeletonAggr' => $aSkeletonAggr ) );

			$hr = Net::GetHrFromWpRemoteGet( $requestRes, false, true );

			if( $hr != Gen::S_OK )

			{

				Gen::LastErrDsc_Set( LocId::Pack( 'RemoteSiteGetDataErr_%1$s', null, array( sprintf( '0x%08X', $hr ) ) ) );



				$oInit -> skipStatus = 'err:network';

				return( false );

			}



			$data = Gen::Unserialize( wp_remote_retrieve_body( $requestRes ) );



			foreach( $data[ 'oInit' ] as $k => $v )

				$oInit -> { $k } = $v;

			return( ($data[ 'res' ]??null) );

		};



	$cbs -> Learn_Abort =

		function( $cbs, $lrnFile )

		{

			$cbs -> bLrnFinish = false;

		};



	$cbs -> Learn_Finish =

		function( $cbs, $ctxProcess )

		{

			$cbs -> bLrnFinish = true;

			return( true );

		};



	$cbs -> ExtContents_CacheGet =

		function( $cbs, $extCacheId )

		{

			global $seraph_accel_g_aExtContents_Cache;



			$file = null;

			$cont = null;



			$contId = ($seraph_accel_g_aExtContents_Cache[ 'i' ][ $extCacheId ]??null);

			if( is_string( $contId ) )

			{

				if( $infoCont = ($seraph_accel_g_aExtContents_Cache[ 'c' ][ $contId ]??null) )

				{

					if( $infoCont[ 'tm' ] > time() )

					{

						$file = bin2hex( $contId ) . '.' . $infoCont[ 't' ];

						$cont = $infoCont[ 'c' ];

					}

					else

					{

						$seraph_accel_g_aExtContents_Cache[ 'c' ][ $contId ];

						unset( $seraph_accel_g_aExtContents_Cache[ 'i' ][ $extCacheId ] );

					}

				}

				else

					unset( $seraph_accel_g_aExtContents_Cache[ 'i' ][ $extCacheId ] );

			}



			return( array( $file, $cont ) );

		};



	$cbs -> ExtContents_CacheSet =

		function( $cbs, $extCacheId, $fileType, $contCacheTtl, $contId, $contCache )

		{

			global $seraph_accel_g_aExtContents_Cache;

			$seraph_accel_g_aExtContents_Cache[ 'i' ][ $extCacheId ] = $contId;

			$seraph_accel_g_aExtContents_Cache[ 'c' ][ $contId ] = array( 'c' => $contCache, 't' => $fileType, 'tm' => time() + $contCacheTtl );

		};



	$cbs -> CustomMethod =

		function( $cbs, $name, $args )

		{

			$requestRes = _ContentProcessRemote_CbCallEx( 'CustomMethod', $cbs, array( 30, 180 ), array( 'name' => $name, 'args' => $args, 'mode' => 0 ) );

			$hr = Net::GetHrFromWpRemoteGet( $requestRes, false, true );

			if( $hr != Gen::S_OK )

			{

				Gen::LastErrDsc_Set( LocId::Pack( 'RemoteSiteGetDataErr_%1$s', null, array( sprintf( '0x%08X', $hr ) ) ) );

				return( false );

			}



			return( Gen::Unserialize( wp_remote_retrieve_body( $requestRes ) ) );

		};



	$cbs -> ImagesProcessSrcSizeAlternatives_CacheGet =

		function( $cbs, $imgStgId )

		{

			return( ($cbs -> aImagesProcessSrcSizeAlternativesCache[ $imgStgId ][ 'v' ]??null) );

		};



	$cbs -> ImagesProcessSrcSizeAlternatives_CacheSet =

		function( $cbs, $imgStgId, $v )

		{

			$cbs -> aImagesProcessSrcSizeAlternativesCache[ $imgStgId ] = array( 'v' =>  $v, 'd' => true );

			return( true );

		};



	$cbs -> PostPrepareObj =

		function( $cbs, $type, $addr, $priority, $data = array(), $priorityInitiator = null, $time = null )

		{

			$cbs -> aPostPrepareObj[] = array( 'type' => $type, 'addr' => $addr, 'priority' => $priority, 'data' => $data, 'priorityInitiator' => $priorityInitiator, 'time' => $time );

			return( true );

		};



	$cbs -> GetSalt =

		function( $cbs )

		{

			return( $cbs -> salt );

		};



	$ctxProcessRt = $cbs -> ctxProcessOrig;

	$cbs -> ctxProcess = &$ctxProcessRt;

	$cbs -> ctxProcess[ 'remote' ] = true;

	$cbs -> ctxProcess[ 'cbs' ] = $cbs;



	$settRt = $cbs -> settOrig;

	Gen::SetArrField( $settRt, array( 'cache', 'procEngn' ), 1 );

	Gen::SetArrField( $settRt, array( 'cache', 'procPauseInt' ), 0 );

	Gen::SetArrField( $settRt, array( 'cache', 'procWorkInt' ), 0 );



	Gen::SetArrField( $settRt, array( 'test', 'optDelay' ), false );



	$content = &$data[ 'content' ];

	$skipStatus = '';



	if( $itemType == 0 )

	{

		ContSkeleton_FltName_PrepPatterns_Plchldrs_Init( ($data[ 'ContSkeleton_FltName_PrepPatterns_aPlchldr' ]??null) );

		$content = ContentProcess( $cbs -> ctxProcess, $settRt, Gen::GetArrField( $settRt, array( 'cache' ), array() ), Gen::GetArrField( $settRt, array( 'contPr' ), array() ), $content, $skipStatus );

		if( $skipStatus && Gen::LastErrDsc_Is() )

			$skipStatus .= ':' . rawurlencode( Gen::LastErrDsc_Get() );

	}

	else if( $itemType == 10 || $itemType == 20 )

	{

		$skipStatus = ContentProcess_ItemType( $itemType, $data[ 'itemData' ], $cbs -> ctxProcess, $settRt );

	}

	else

		return( Gen::E_INVALIDARG );



	foreach( array( 'stat', 'subs', 'deps', 'lrn', 'lrnFile', 'lrnDsc', 'lrnDataPath' ) as $fldBack )

		$cbs -> ctxProcessOrig[ $fldBack ] = ($cbs -> ctxProcess[ $fldBack ]??null);



	if( isset( $cbs -> ctxProcessOrig[ 'lrnDsc' ][ 's' ] ) )

		$cbs -> ctxProcessOrig[ 'lrnDsc' ][ 's' ] = gzencode( Gen::Serialize( $cbs -> ctxProcessOrig[ 'lrnDsc' ][ 's' ] ) );



	{

		$cbs -> aFile = array_map( function( $info ) { unset( $info[ 'd' ] ); return( $info ); }, array_filter( $cbs -> aFile, function( $info ) { return( ($info[ 'd' ]??null) ); } ) );

	}



	{

		foreach( $cbs -> aSrcChunk as $type => &$aDataPath )

			foreach( $aDataPath as $dataPath => &$aoiCf )

				$aoiCf = array_filter( $aoiCf, function( $c ) { return( is_string( $c ) ); } );

		unset( $aDataPath, $aoiCf );

	}



	{

		foreach( $cbs -> aTof as $dir => &$aId )

			$aId = array_map( function( $aCont ) { unset( $aCont[ 'd' ] ); return( $aCont ); }, array_filter( $aId, function( $aCont ) { return( ($aCont[ 'd' ]??null) ); } ) );

		unset( $dir, $aId );

	}



	{

		$cbs -> aImagesProcessSrcSizeAlternativesCache = array_map( function( $info ) { return( $info[ 'v' ] ); }, array_filter( $cbs -> aImagesProcessSrcSizeAlternativesCache, function( $info ) { return( ($info[ 'd' ]??null) ); } ) );

	}



	_ContentProcessRemote_CbCall( 'Finish', $cbs, array( 30, 180 ), array( 'itemType' => $itemType, 'sett' => $cbs -> settOrig, 'ctxProcess' => $cbs -> ctxProcessOrig, 'content' => $content, 'skipStatus' => $skipStatus, 'ctxCache' => ( array )$seraph_accel_g_ctxCache, 'prepPrms' => $seraph_accel_g_prepPrms, 'warns' => LastWarnDscs_Get(), 'aFile' => $cbs -> aFile, 'aSrcChunk' => $cbs -> aSrcChunk, 'aTof' => $cbs -> aTof, 'bLrnFinish' => $cbs -> bLrnFinish, 'prepLearnId' => $seraph_accel_g_prepLearnId, 'aPostPrepareObj' => $cbs -> aPostPrepareObj, 'aImagesProcessSrcSizeAlternativesCache' => $cbs -> aImagesProcessSrcSizeAlternativesCache ) );



	$stat -> cpuTime = $cbs -> ctxProcess[ '_stat' ][ 'cpu' ][ 'v' ];



	return( Gen::S_OK );

}



function _ContentProcessRemote_CbCallEx( $funcId, $cbs, $timeout, $data )

{

	if( !$cbs -> urlCb )

		return( Gen::S_FALSE );



	$connect_timeout = null;

	if( is_array( $timeout ) )

	{

		$connect_timeout = $timeout[ 0 ];

		$timeout = $timeout[ 1 ];

	}



	$data[ 'f' ] = $funcId;

	$data[ 'id' ] = $cbs -> idProc;



	$dataRaw = Gen::Serialize( $data );



	$cbs -> netSendSz += strlen( $dataRaw );



	if( $funcId == 'Finish' )

	{

		$data[ 'ctxProcess' ][ 'stat' ][ 'Network-Sent' ] = size_format( $cbs -> netRecvSz, 1 );

		$data[ 'ctxProcess' ][ 'stat' ][ 'Network-Received' ] = size_format( $cbs -> netSendSz, 1 );

		$data[ 'ctxProcess' ][ 'stat' ][ 'Network-Fetch' ] = $cbs -> netFetchCount;

		$data[ 'ctxProcess' ][ 'stat' ][ 'Network-Fetch-Average-Duration' ] = LocId::Pack( 'TimeDurSec_%1$s', null, array( sprintf( '%.2F', $cbs -> netFetchAvgTm ) ) );



		$dataRaw = Gen::Serialize( $data );

	}



	$requestRes = Wp::RemotePost( $cbs -> urlCb, array( 'proxy' => $cbs -> aUrlCbProxy, 'transport' => $cbs -> transport, 'connect_timeout' => $connect_timeout, 'timeout' => $timeout, 'sslverify' => false, 'headers' => array( 'User-Agent' => 'Seraph-Accel-Agent-Remote/2.29.11', 'Content-Type' => 'application/octet-stream', 'Connection' => 'keep-alive', 'Keep-Alive' => 'timeout=570, max=1000' ), 'body' => $dataRaw  ) );

	if( Net::GetHrFromWpRemoteGet( $requestRes ) == Gen::S_OK )

		$cbs -> netRecvSz += strlen( wp_remote_retrieve_body( $requestRes ) );

	return( $requestRes );

}



function _ContentProcessRemote_CbCall( $funcId, $cbs, $timeout, $data )

{

	$requestRes = _ContentProcessRemote_CbCallEx( $funcId, $cbs, $timeout, $data );

	return( Net::GetHrFromWpRemoteGet( $requestRes, false, true ) );

}



