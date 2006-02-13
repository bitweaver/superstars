<?php
global $gBitSystem, $gBitSmarty;
$gBitSystem->registerPackage( 'stars', dirname( __FILE__).'/', TRUE, LIBERTY_SERVICE_RATING );

if( $gBitSystem->isPackageActive( 'stars' ) ) {
	require_once( STARS_PKG_PATH.'LibertyStars.php' );
	$gBitSmarty->assign( 'loadAjax', TRUE );

	$gLibertySystem->registerService( LIBERTY_SERVICE_RATING, STARS_PKG_NAME, array(
		'content_display_function' => 'stars_content_display',
		'content_load_function' => 'stars_content_load',
		'content_list_function' => 'stars_content_list',
		'content_expunge_function' => 'stars_content_expunge',
		'content_body_tpl' => 'bitpackage:stars/stars_service.tpl',
	) );
}
?>
