<?php
require_once( "../kernel/setup_inc.php" );
require_once( STARS_PKG_PATH."LibertyStars.php" );

$gBitSystem->verifyPackage( 'stars' );

$stars = new LibertyStars();

$listHash = $_REQUEST;
$ratedContent = $stars->getList( $listHash );

$gBitSmarty->assign( 'ratedContent', $ratedContent );
$gBitSmarty->assign( 'listInfo', $listHash['listInfo'] );
$gBitSystem->display( 'bitpackage:stars/rated.tpl', tra( 'Rated Content' ) , array( 'display_mode' => 'display' ));
?>
