<?php
require_once( "../bit_setup_inc.php" );
$starsfeed = array();
if( @BitBase::verifyId( $_POST['content_id'] ) && @BitBase::verifyId( $_POST['stars_rating'] ) ) {
	if( $tmpObject = LibertyBase::getLibertyObject( $_POST['content_id'] ) ) {
		$starsfeed = array();
		$stars = new LibertyStars( $tmpObject->mContentId );
		if( !$gBitUser->isRegistered() ) {
			$starsfeed['error'] = tra( "You need to log in to rate." );
		} else {
			if( $tmpObject->isOwner() ) {
				$starsfeed['error'] = tra( "You cannot rate your own content." );
			} elseif( $stars->store( $_POST ) ) {
				$starsfeed['success'] = tra( "Thank you for rating." );
			} else {
				$starsfeed['error'] = $stars->mErrors;
			}
		}
	}
	$gBitSmarty->assign( 'serviceHash', $tmpObject->mInfo );
} else {
	$starsfeed['warning'] = tra( "There was a problem trying to apply your rating" );
}
$gBitSmarty->assign( "starsfeed", $starsfeed );
echo $gBitSmarty->fetch( 'bitpackage:stars/stars_service.tpl' );
?>
