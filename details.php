<?php
/**
 * $Header: /cvsroot/bitweaver/_bit_superstars/details.php,v 1.4 2008/06/25 22:04:40 spiderr Exp $
 * date created 2006/02/10
 * @author xing <xing@synapse.plus.com>
 * @version $Revision: 1.4 $ $Date: 2008/06/25 22:04:40 $
 * @package superstars
 * @subpackage functions
 */

/**
 * Initialization
 */
require_once( "../bit_setup_inc.php" );
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
