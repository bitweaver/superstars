<?php
// $Header: /cvsroot/bitweaver/_bit_superstars/admin/admin_stars_inc.php,v 1.18 2009/10/01 14:17:05 wjames5 Exp $
// Copyright (c) 2005 bitweaver Stars
// All Rights Reserved. See below for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See http://www.gnu.org/copyleft/lesser.html for details.

require_once( STARS_PKG_PATH.'LibertyStars.php' );
$gBitSmarty->assign_by_ref( 'feedback', $feedback = array() );

$formStarsOptions = array(
	"stars_used_in_display" => array(
		'label' => 'Stars used in display',
		'note' => 'If you want to change the number of stars used in the display, you can set the number here.',
		'type' => 'numeric',
	),
	"stars_minimum_ratings" => array(
		'label' => 'Minimum Number',
		'note' => 'The minimum number of ratings required before the value is shown. Use 1 if you want to display the results after the first rating.',
		'type' => 'numeric',
	),
	"stars_use_ajax" => array(
		'label' => 'Use Ajax',
		'note' => 'Choosing this option will decrease load times when rating, however requires modern browsers with javascript enabled to allow for ratings.',
		'type' => 'toggle',
	),
//	"stars_auto_hide_content" => array(
//		'label' => 'Auto Hide Content',
//		'note' => 'If content is rated badly by the users, you can specify what the rating has to be before it is hidden. Enter a percentage rating: 0 disables the feature, 25 means that the average rating has to be below 25% before it is hidden.',
//		'type' => 'input',
//	),
	"stars_rerating" => array(
		'label' => 'Re- Ratings',
		'note' => 'Allow users to change their ratings at any time. When content changes, users can update their rating accordingly.',
		'type' => 'toggle',
	),
	"stars_user_ratings" => array(
		'label' => 'User Ratings',
		'note' => 'Show the average rating of a users contributions on thier homepage',
		'type' => 'toggle',
	),
);
$gBitSmarty->assign( 'formStarsOptions', $formStarsOptions );

$formStarsVersion = array(
	"stars_per_version_rating" => array(
		'label' => 'Display version rating',
		'note' => 'Display the rating based on the actual version of the content in addition to the overall rating.',
		'type' => 'toggle',
	),
);
$gBitSmarty->assign( 'formStarsVersion', $formStarsVersion );

$formStarsWeight = array(
	"stars_user_weight" => array(
		'label' => 'Use weighting',
		'note' => 'Give users individual weighting based on the factors below.',
		'type' => 'toggle',
	),
	"stars_weight_age" => array(
		'label' => 'Age weight',
		'note' => 'How long a user has been a member of your site. This value is relative to the age of your site.',
		'type' => 'numeric',
	),
	"stars_weight_permission" => array(
		'label' => 'Permission weight',
		'note' => 'This calculation takes the number of permissions a user has into account..',
		'type' => 'numeric',
	),
	"stars_weight_activity" => array(
		'label' => 'Activity weight',
		'note' => 'Activity is calculated by the number of content a user has created or contributed to.',
		'type' => 'numeric',
	),
);
$gBitSmarty->assign( 'formStarsWeight', $formStarsWeight );

for( $i = 0; $i <= 20; $i++ ) {
	$numbers[] = $i;
}
$gBitSmarty->assign( 'numbers', $numbers );

// allow selection of what packages can have ratings
$exclude = array( 'bituser', 'tikisticky', 'pigeonholes' );
foreach( $gLibertySystem->mContentTypes as $cType ) {
	if( !in_array( $cType['content_type_guid'], $exclude ) ) {
		$formRatable['guids']['stars_rate_'.$cType['content_type_guid']]  = $cType['content_description'];
	}
}

if( !empty( $_REQUEST['stars_preferences'] ) ) {
	$stars = array_merge( $formStarsOptions, $formStarsWeight );
	foreach( $stars as $item => $data ) {
		if( $data['type'] == 'numeric' ) {
			simple_set_int( $item, STARS_PKG_NAME );
		} elseif( $data['type'] == 'toggle' ) {
			simple_set_toggle( $item, STARS_PKG_NAME );
		} elseif( $data['type'] == 'input' ) {
			simple_set_value( $item, STARS_PKG_NAME );
		}
		simple_set_int( 'stars_icon_width', STARS_PKG_NAME );
		simple_set_int( 'stars_icon_height', STARS_PKG_NAME );
		simple_set_value( 'stars_rating_names', STARS_PKG_NAME );

	}
	foreach( array_keys( $formRatable['guids'] ) as $ratable ) {
		$gBitSystem->storeConfig( $ratable, ( ( !empty( $_REQUEST['ratable_content'] ) && in_array( $ratable, $_REQUEST['ratable_content'] ) ) ? 'y' : NULL ), STARS_PKG_NAME );
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

// check the correct packages in the package selection
foreach( $gLibertySystem->mContentTypes as $cType ) {
	if( $gBitSystem->getConfig( 'stars_rate_'.$cType['content_type_guid'] ) ) {
		$formRatable['checked'][] = 'stars_rate_'.$cType['content_type_guid'];
	}
}
$gBitSmarty->assign( 'formRatable', $formRatable );

?>
