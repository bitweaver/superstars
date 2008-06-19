<?php
/**
 * $Header: /cvsroot/bitweaver/_bit_superstars/rate.php,v 1.17 2008/06/19 09:34:24 lsces Exp $
 * date created 2006/02/10
 * @author xing <xing@synapse.plus.com>
 * @version $Revision: 1.17 $ $Date: 2008/06/19 09:34:24 $
 * @package superstars
 * @subpackage functions
 */

/**
 * Initialization
 */
require_once( "../bit_setup_inc.php" );
$gBitSystem->verifyPackage( 'stars' );
$starsfeed = array();
if( @BitBase::verifyId( $_REQUEST['content_id'] ) && @BitBase::verifyId( $_REQUEST['stars_rating'] ) ) {
	if( $tmpObject = LibertyBase::getLibertyObject( $_REQUEST['content_id'] ) ) {
		// check if this feature allows rating
		if( $gBitSystem->isFeatureActive( 'stars_rate_'.$tmpObject->getContentType() ) ) {
			$starsfeed = array();
			$stars = new LibertyStars( $tmpObject->mContentId );
			if( !$gBitUser->isRegistered() ) {
				$starsfeed['error'] = tra( "You need to log in to rate." );
			} else {
				if( $stars->store( $_REQUEST ) ) {
					//$starsfeed['success'] = tra( "Thank you for rating." );
				} else {
					$starsfeed['error'] = $stars->mErrors;
				}
			}
		}
	}
	// get up to date reading
	$stars->load();

	if( $userRating = $stars->getUserRating( $tmpObject->mContentId ) ) {
		$serviceHash = array_merge( $tmpObject->mInfo, $stars->mInfo, $stars->getUserRating( $tmpObject->mContentId ) );
	} else {
		$serviceHash = array_merge( $tmpObject->mInfo, $stars->mInfo );
	}

	$gBitSmarty->assign( 'serviceHash', $serviceHash );
} else {
	$starsfeed['warning'] = tra( "There was a problem trying to apply your rating" );
}
$gBitSmarty->assign( "starsfeed", $starsfeed );

if( $gBitSystem->isAjaxRequest() ) {
	echo $gBitSmarty->fetch( 'bitpackage:stars/stars_inline_service.tpl' );
} elseif( !empty( $tmpObject ) && $location = $tmpObject->getDisplayUrl() ) {
	header( "Location:".$location );
	die;
} else {
	// where else can we go?
	header( "Location:".BIT_ROOT_URL );
	die;
}
?>
