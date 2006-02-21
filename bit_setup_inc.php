<?php
global $gBitSystem, $gBitSmarty;

$registerHash = array(
	'package_name' => 'stars',
	'package_path' => dirname( __FILE__ ).'/',
	'service' => LIBERTY_SERVICE_RATING,
);
$gBitSystem->registerPackage( $registerHash );

if( $gBitSystem->isPackageActive( 'stars' ) ) {
	require_once( STARS_PKG_PATH.'LibertyStars.php' );

	$gLibertySystem->registerService( LIBERTY_SERVICE_RATING, STARS_PKG_NAME, array(
//		'content_display_function'  => 'stars_content_display',
		'content_load_sql_function' => 'stars_content_load_sql',
		'content_list_sql_function' => 'stars_content_list_sql',
		'content_expunge_function'  => 'stars_content_expunge',
		'content_body_tpl'          => 'bitpackage:stars/stars_inline_service.tpl',
		'content_list_sort_tpl'     => 'bitpackage:stars/stars_list_sort_service.tpl',
		'content_list_tpl'          => 'bitpackage:stars/stars_list_service.tpl',
	) );
}
?>
