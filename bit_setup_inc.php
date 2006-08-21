<?php
global $gBitSystem, $gBitSmarty, $gPreviewStyle;

$registerHash = array(
	'package_name' => 'stars',
	'package_path' => dirname( __FILE__ ).'/',
	'service' => LIBERTY_SERVICE_RATING,
);
$gBitSystem->registerPackage( $registerHash );

if( $gBitSystem->isPackageActive( 'stars' ) ) {
	require_once( STARS_PKG_PATH.'LibertyStars.php' );

	// if we are using a text browser theme, make sure not to use ajax
	if( $gPreviewStyle == 'lynx' ) {
		$gBitSystem->setConfig( 'stars_use_ajax', FALSE );
	}

	$gLibertySystem->registerService( LIBERTY_SERVICE_RATING, STARS_PKG_NAME, array(
//		'content_display_function'  => 'stars_content_display',
		'content_load_sql_function'		=> 'stars_content_load_sql',
		'content_list_sql_function'		=> 'stars_content_list_sql',
		'content_list_history_sql_function'		=> 'stars_list_history_sql_function',
		'content_expunge_function'		=> 'stars_content_expunge',
		'content_body_tpl'				=> 'bitpackage:stars/stars_inline_service.tpl',
		'content_list_sort_tpl'			=> 'bitpackage:stars/stars_list_sort_service.tpl',
		'content_list_tpl'          	=> 'bitpackage:stars/stars_list_service.tpl',
		'content_can_rate_function'		=> 'stars_content_can_rate',
		'content_get_rating_function'	=> 'stars_content_get_rating',
		'content_set_rating_function'	=> 'stars_content_set_rating',
		'content_get_rating_feild_function'	=> 'stars_content_get_rating_feild',
	) );
}
?>
