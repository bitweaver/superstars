<?php
// $Header: /cvsroot/bitweaver/_bit_superstars/admin/admin_stars_inc.php,v 1.2 2006/02/14 21:32:41 squareing Exp $
// Copyright (c) 2005 bitweaver Stars
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

require_once( STARS_PKG_PATH.'LibertyStars.php' );
$gBitSmarty->assign_by_ref( 'feedback', $feedback = array() );

$formStarsOptions = array(
//	"stars_used_in_display" => array(
//		'label' => 'Stars used in display',
//		'note' => 'If you want to change the number of stars used in the display, you can set the number here.',
//		'type' => 'numeric',
//	),
	"stars_minimum_ratings" => array(
		'label' => 'Minimum Number',
		'note' => 'The minimum number of ratings required before the value is shown. Use 1 if you want to display the results after the first rating.',
		'type' => 'numeric',
	),
);
$gBitSmarty->assign( 'formStarsOptions', $formStarsOptions );

$formStarsPoints = array(
	"stars_user_points" => array(
		'label' => 'Use weighting',
		'note' => 'Value all users the same, regardless of any of these factors.',
		'type' => 'checkbox',
	),
	"stars_weight_age" => array(
		'label' => 'Age weight',
		'note' => 'How long a user has been a member of your site.',
		'type' => 'numeric',
	),
	"stars_weight_permission" => array(
		'label' => 'Permission weight',
		'note' => 'Apply the importance of how much you think the priorities are worth.',
		'type' => 'numeric',
	),
	"stars_weight_activity" => array(
		'label' => 'Activity weight',
		'note' => 'Activity is calculated by the number of content a user has created or contributed to.',
		'type' => 'numeric',
	),
);
$gBitSmarty->assign( 'formStarsPoints', $formStarsPoints );

for( $i = 0; $i <= 20; $i++ ) {
	$numbers[] = $i;
}
$gBitSmarty->assign( 'numbers', $numbers );

if( !empty( $_REQUEST['stars_preferences'] ) ) {
	$stars = array_merge( $formStarsOptions, $formStarsPoints );
	foreach( $stars as $item => $data ) {
		if( $data['type'] == 'numeric' ) {
			simple_set_int( $item, STARS_PKG_NAME );
		} else {
			simple_set_toggle( $item, STARS_PKG_NAME );
		}
	}
}

if( !empty( $_REQUEST['recalculate'] ) ) {
	$stars = new LibertyStars();
	if( $stars->reCalculateRating() ) {
		$feedback['success'] = tra( 'All ratings have been brought up to speed.' );
	} else {
		$feedback['error'] = tra( 'There was a problem updating all the ratings in your database.' );
	}
}
?>
