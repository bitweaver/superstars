<?php
/**
 * $Header: /cvsroot/bitweaver/_bit_superstars/details.php,v 1.5 2010/02/08 21:27:25 wjames5 Exp $
 * date created 2006/02/10
 * @author xing <xing@synapse.plus.com>
 * @version $Revision: 1.5 $ $Date: 2010/02/08 21:27:25 $
 * @package superstars
 * @subpackage functions
 */

/**
 * Initialization
 */
require_once( "../kernel/setup_inc.php" );
require_once( STARS_PKG_PATH."LibertyStars.php" );

$gBitSystem->verifyPackage( 'stars' );

if( !@BitBase::verifyId( $_REQUEST['content_id'] ) ) {
	header( "Location: ".BIT_ROOT_URL );
}

$stars = new LibertyStars( $_REQUEST['content_id'] );
$stars->getRatingDetails( !empty( $_REQUEST['show_raters'] ) );

$gBitSmarty->assign( 'starsDetails', $stars->mInfo );
$gBitSystem->display( 'bitpackage:stars/details.tpl', tra( 'Details of Rated Content' ) , array( 'display_mode' => 'display' ));
?>
