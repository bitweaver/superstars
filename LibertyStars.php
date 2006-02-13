<?php
/**
* $Header: /cvsroot/bitweaver/_bit_superstars/LibertyStars.php,v 1.1 2006/02/13 19:05:11 squareing Exp $
* @date created 2006/02/10
* @author xing <xing@synapse.plus.com>
* @version $Revision: 1.1 $ $Date: 2006/02/13 19:05:11 $
* @class BitStars
*/

require_once( KERNEL_PKG_PATH.'BitBase.php' );

class LibertyStars extends LibertyBase {
	var $mContentId;

	function LibertyStars( $pContentId=NULL ) {
		LibertyBase::LibertyBase();
		$this->mContentId = $pContentId;
	}

	/**
	* Load the data from the database
	* @param pParamHash be sure to pass by reference in case we need to make modifcations to the hash
	**/
	function load() {
		if( $this->isValid() ) {
			$query = "SELECT * FROM `".BIT_DB_PREFIX."stars` WHERE `content_id`=?";
			$this->mInfo = $this->mDb->getRow( $query, array( $this->mContentId ) );
		}
		return( count( $this->mInfo ) );
	}

	/**
	* quick method to get a nice summary of past ratings for a given content
	* @return usable hash with a summary of ratings of a given content id
	*/
	function getRatingSummary( $pContentId = NULL ) {
		$ret = FALSE;
		if( !@BitBase::verifyId( $pContentId ) && $this->isValid() ) {
			$pContentId = $this->mContentId;
		}

		if( @BitBase::verifyId( $pContentId ) ) {
			$query = "SELECT sth.`rating`, COUNT( sth.`rating`) AS rating_count, SUM( sth.`points` ) AS points
				FROM `".BIT_DB_PREFIX."stars` sts
				LEFT JOIN `".BIT_DB_PREFIX."stars_history` sth ON( sth.`content_id`=sts.`content_id` )
				WHERE sts.`content_id`=?
				GROUP BY( sth.`rating` )";
			$ret = $this->mDb->getAll( $query, array( $pContentId ) );
		}
		return $ret;
	}

	/**
	* @param array pParams hash of values that will be used to store the page
	* @return bool TRUE on success, FALSE if store could not occur. If FALSE, $this->mErrors will have reason why
	* @access public
	**/
	function store( &$pParamHash ) {
		if( $this->verify( $pParamHash ) ) {
			$table = BIT_DB_PREFIX."stars";
			$this->mDb->StartTrans();
			if( !empty( $this->mInfo ) ) {
				$result = $this->mDb->associateUpdate( $table, $pParamHash['stars_store'], array( "content_id" => $pParamHash['content_id'] ) );
				$result = $this->mDb->associateInsert( $table."_history", $pParamHash['stars_history_store'] );
			} else {
				$result = $this->mDb->associateInsert( $table, $pParamHash['stars_store'] );
				$result = $this->mDb->associateInsert( $table."_history", $pParamHash['stars_history_store'] );
			}
			$this->mDb->CompleteTrans();
		}
		return( count( $this->mErrors )== 0 );
	}

	/**
	* Make sure the data is safe to store
	* @param array pParams reference to hash of values that will be used to store the page, they will be modified where necessary
	* @return bool TRUE on success, FALSE if verify failed. If FALSE, $this->mErrors will have reason why
	* @access private
	**/
	function verify( &$pParamHash ) {
		global $gBitUser, $gBitSystem;

		if( $gBitUser->isRegistered() && $this->isValid() ) {
			$this->load();
			$pParamHash['content_id'] = $this->mContentId;

			// only store stuff if user hasn't rated this content before
			if( !$this->hasUserRated() && $this->calculateRating( $pParamHash ) ) {
				$pParamHash['stars_store']['rating']              = ( int )$pParamHash['calc']['rating'];
				$pParamHash['stars_history_store']['content_id']  = $pParamHash['stars_store']['content_id'] = ( int )$pParamHash['content_id'];
				$pParamHash['stars_history_store']['rating']      = ( int )$pParamHash['rating'];
				$pParamHash['stars_history_store']['points']      = ( int )$pParamHash['user']['points'];
				$pParamHash['stars_history_store']['rating_time'] = ( int )BitDate::getUTCTime();
				$pParamHash['stars_history_store']['user_id']     = ( int )$gBitUser->mUserId;
			} else {
				$this->mErrors['already_rated'] = "You can only rate once per content item.";
			}
		} else {
			$this->mErrors['unregistered'] = "You have to be registered to rate content.";
		}

		return( count( $this->mErrors )== 0 );
	}

	/**
	* check if this user has already voted before
	*/
	function hasUserRated() {
		global $gBitUser;
		if( $this->isValid() ) {
			$query = "SELECT `user_id` FROM `".BIT_DB_PREFIX."stars_history` WHERE `content_id`=? AND `user_id`=?";
			$ret = $this->mDb->getOne( $query, array( $this->mContentId, $gBitUser->mUserId ) );
			return( !empty( $ret ) );
		}
	}

	/**
	* check if the mContentId is set and valid
	*/
	function isValid() {
		return( @BitBase::verifyId( $this->mContentId ) );
	}

	/**
	* This function removes a stars entry
	**/
	function expunge() {
		$ret = FALSE;
		if( $this->isValid() ) {
			$query = "DELETE FROM `".BIT_DB_PREFIX."stars` WHERE `content_id` = ?";
			$result = $this->mDb->query( $query, array( $this->mContentId ) );
			$query = "DELETE FROM `".BIT_DB_PREFIX."stars_history` WHERE `content_id` = ?";
			$result = $this->mDb->query( $query, array( $this->mContentId ) );
		}
		return $ret;
	}

	// ============================ calculations ============================

	/**
	* recalculate the rating of all objects - important when user changes weighting opions
	* TODO: add some check to see if this was successfull, currenlty only returns true
	*/
	function reCalculateRating() {
		global $gBitSystem;

		// get entire rating history
		$result = $this->mDb->query( "SELECT * FROM `".BIT_DB_PREFIX."stars_history`" );
		while( $aux = $result->fetchRow() ) {
			$userIds[] = $aux['user_id'];
			$contentIds[] = $aux['content_id'];
		}
		$userIds = array_unique( $userIds );
		$contentIds = array_unique( $contentIds );

		// update user points in accordance with new settings
		foreach( $userIds as $user_id ) {
			$userPoints = $this->calculateUserPoints( $user_id );
			$result = $this->mDb->query( "UPDATE `".BIT_DB_PREFIX."stars_history` SET `points`=? WHERE `user_id`=?", array( $userPoints, $user_id ) );
		}

		// update the calculations in the stars table
		foreach( $contentIds as $content_id ) {
			$calc['sum'] = $calc['points'] = $calc['count'] = 0;
			if( $summary = $this->getRatingSummary( $content_id ) ) {
				foreach( $summary as $info ) {
					$calc['sum']    += $info['points'] * $info['rating'];
					$calc['points'] += $info['points'];
					$calc['count']  += $info['rating_count'];
				}
			}

			// we are adding the new rating here, so need to reduce this by one
			$minRatings = $gBitSystem->getPreference( 'stars_minimum_ratings', 5 ) - 1;
			if( $calc['count'] < $minRatings ) {
				$rating = 0;
			} else {
				$rating = round( $calc['sum'] / $calc['points'] );
			}

			$result = $this->mDb->query( "UPDATE `".BIT_DB_PREFIX."stars` SET `rating`=? WHERE `content_id`=?", array( $rating, $content_id ) );
		}
		return TRUE;
	}

	/**
	* calculate the correct value to insert into the database
	*/
	function calculateRating( &$pParamHash ) {
		global $gBitSystem;
		$numberOfStars = $gBitSystem->getPreference( 'stars_used_in_display', 5 );
		$ret = FALSE;

		// TODO: factors that haven't been taken into accound yet:
		//       - time since last rating(s) - how should this be dealt with?
		//       - age of document - ???

		// number of ratings needed before value is displayed
		if( @BitBase::verifyId( $pParamHash['stars_rating'] ) && $pParamHash['stars_rating'] > 0 && $pParamHash['stars_rating'] <= $numberOfStars && $this->isValid() ) {
			// normalise to 1000 points
			$pParamHash['rating'] = $pParamHash['stars_rating'] / $numberOfStars * 1000;

			$pParamHash['user']['points'] = $this->calculateUserPoints();
			$calc['sum'] = $calc['points'] = $calc['count'] = 0;
			if( $summary = $this->getRatingSummary() ) {
				foreach( $summary as $info ) {
					$calc['sum']    += $info['points'] * $info['rating'];
					$calc['points'] += $info['points'];
					$calc['count']  += $info['rating_count'];
				}
			}

			$minRatings = $gBitSystem->getPreference( 'stars_minimum_ratings', 5 );
			if( $calc['count'] < $minRatings ) {
				$pParamHash['calc']['rating'] = 0;
			} else {
				$pParamHash['calc']['rating'] = round( ( $calc['sum'] + ( $pParamHash['rating'] * $pParamHash['user']['points'] ) ) / ( $calc['points'] + $pParamHash['user']['points'] ) );
			}
			$ret = TRUE;
		}
		return $ret;
	}

	function calculateUserPoints( $pUserId = NULL ) {
		global $gBitUser, $gBitSystem;
		if( $gBitSystem->isFeatureActive( 'stars_user_points' ) ) {
			// allow overriding of currently loaded user
			if( @BitBase::verifyId( $pUserId ) ) {
				$tmpUser = new BitPermUser( $pUserId );
				$tmpUser->load( TRUE );
			} else {
				$tmpUser = &$gBitUser;
			}

			// age relative to site age
			$query = "SELECT MIN( `registration_date` ) FROM `".BIT_DB_PREFIX."users_users`";
			$age['site'] = BitDate::getUTCTime() - $this->mDb->getOne( $query );
			$age['user'] = BitDate::getUTCTime() - $tmpUser->getField( 'registration_date' );
			$userPoints['age'] = $age['user'] / $age['site'];

			// permissioning relative to full number of permissions
			$query = "SELECT COUNT( `perm_name` ) FROM `".BIT_DB_PREFIX."users_permissions`";
			if( $tmpUser->isAdmin() ) {
				$userPoints['permission'] = 1;
			} else {
				$userPoints['permission'] = count( $tmpUser->mPerms ) / $this->mDb->getOne( $query );
			}

			// activity - we could to the same using the history as well.
			$query = "SELECT COUNT( `content_id` ) FROM `".BIT_DB_PREFIX."liberty_content` WHERE `user_id`=?";
			$activity['user'] = $this->mDb->getOne( $query, array( $tmpUser->getField( 'user_id' ) ) );
			$query = "SELECT COUNT( `content_id` ) FROM `".BIT_DB_PREFIX."liberty_content`";
			$activity['site'] = $this->mDb->getOne( $query );
			$userPoints['activity'] = $activity['user'] / $activity['site'];

			// here we can add some weight to various areas
			$custom['age']        = $gBitSystem->getPreference( 'stars_weight_age' );
			$custom['permission'] = $gBitSystem->getPreference( 'stars_weight_permission' );
			$custom['activity']   = $gBitSystem->getPreference( 'stars_weight_activity' );

			foreach( $userPoints as $type => $value ) {
				$$type = 10 * $value * $custom[$type];
				if( empty( $$type ) ) {
					$$type = 1;
				}
			}

			// TODO: run some tests to see if this is a good way of evaluating power of a user
			if( !$ret = round( log( $age * $permission * $activity, 2 ) ) ) {
				$ret = 1;
			}
		} else {
			$ret = 1;
		}

		return $ret;
	}
}

function stars_content_list() {
	return stars_content_load();
}

function stars_content_load() {
	global $gBitUser, $gBitSystem;
	$numberOfStars = $gBitSystem->getPreference( 'stars_used_in_display', 5 );
	$pixels = 160;
	return array(
		'select_sql' => ", sts.`rating` AS stars_rating, (sts.`rating` * $pixels / 1000) AS stars_pixels ",
		'join_sql' => " LEFT JOIN `".BIT_DB_PREFIX."stars` sts ON ( lc.`content_id`=sts.`content_id` ) ",
	);
}

function stars_content_display( &$pObject, &$pParamHash, $pStoreHash = NULL ) {
}

function stars_content_expunge( &$pObject, &$pParamHash ) {
	$stars = new LibertyStars( $pObject->mContentId );
	$stars->expunge();
}
?>
