<?php
/**
* $Header: /cvsroot/bitweaver/_bit_superstars/LibertyStars.php,v 1.37 2006/09/07 14:21:18 squareing Exp $
* @date created 2006/02/10
* @author xing <xing@synapse.plus.com>
* @version $Revision: 1.37 $ $Date: 2006/09/07 14:21:18 $
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
			global $gBitSystem;
			$stars = $gBitSystem->getConfig( 'stars_used_in_display', 5 );
			$pixels = $stars *  $gBitSystem->getConfig( 'stars_icon_width', 22 );
			$query = "SELECT ( `rating` * $pixels / 100 ) AS `stars_pixels`, `rating` AS `stars_rating`, `rating_count` AS `stars_rating_count`, `content_id` FROM `".BIT_DB_PREFIX."stars` WHERE `content_id`=?";
			$this->mInfo = $this->mDb->getRow( $query, array( $this->mContentId ) );
			if( !empty( $this->mInfo ) && $gBitSystem->isFeatureActive( 'stars_per_version_rating' ) ) {
				$this->mInfo['c_version'] = $this->getCurrentVersion( $this->mContentId );

				$query = "SELECT ( `rating` * $pixels / 100 ) AS `stars_version_pixels`, `rating` AS `stars_version_rating`, `rating_count` AS `stars_version_rating_count` FROM `".BIT_DB_PREFIX."stars_version` WHERE `content_id`=? AND `version`=?";
				$v = $this->mDb->getRow( $query, array( $this->mContentId, $this->mInfo['c_version'] ) );
				$this->mInfo = array_merge($this->mInfo,$v);
			}
		}
		return( count( $this->mInfo ) );
	}

	function getCurrentVersion( $pContentId ) {
		$query = "SELECT version FROM `".BIT_DB_PREFIX."liberty_content` WHERE `content_id`=?";
		return ($this->mDb->getOne( $query, array( $this->mContentId ) ));
	}

	/**
	* get list of all rated content
	* @param $pListHash contains array of items used to limit search results
	* @param $pListHash[sort_mode] column and orientation by which search results are sorted
	* @param $pListHash[find] search for a pigeonhole title - case insensitive
	* @param $pListHash[max_records] maximum number of rows to return
	* @param $pListHash[offset] number of results data is offset by
	* @access public
	* @return array of rated content
	**/
	function getList( &$pListHash ) {
		global $gBitSystem, $gBitUser, $gLibertySystem;

		$ret = $bindVars = array();
		$where = $order = '';

		if( !empty( $pListHash['sort_mode'] ) ) {
			$order .= " ORDER BY ".$this->mDb->convert_sortmode( $pListHash['sort_mode'] )." ";
		} else {
			// set a default sort_mode
			$order .= " ORDER BY sts.`rating` DESC";
		}

		LibertyContent::prepGetList( $pListHash );

		if( !empty( $pListHash['find'] ) ) {
			$where .= empty( $where ) ? ' WHERE ' : ' AND ';
			$where .= " UPPER( lc.`title` ) LIKE ? ";
			$bindVars[] = '%'.strtoupper( $pListHash['find'] ).'%';
		}

		$query = "SELECT sts.*, lch.`hits`, lch.`last_hit`, lc.`event_time`, lc.`title`,
			lc.`last_modified`, lc.`content_type_guid`, lc.`ip`, lc.`created`
			FROM `".BIT_DB_PREFIX."stars` sts
				INNER JOIN `".BIT_DB_PREFIX."liberty_content` lc ON ( lc.`content_id` = sts.`content_id` )
				LEFT JOIN `".BIT_DB_PREFIX."liberty_content_hits` lch ON ( lc.`content_id` = lch.`content_id` )
			$where $order";

		$result = $this->mDb->query( $query, $bindVars, $pListHash['max_records'], $pListHash['offset'] );

		while( $aux = $result->fetchRow() ) {
			$type = &$gLibertySystem->mContentTypes[$aux['content_type_guid']];
			if( empty( $type['content_object'] ) ) {
				include_once( $gBitSystem->mPackages[$type['handler_package']]['path'].$type['handler_file'] );
				$type['content_object'] = new $type['handler_class']();
			}
			if( !empty( $gBitSystem->mPackages[$type['handler_package']] ) ) {
				$aux['display_link'] = $type['content_object']->getDisplayLink( $aux['title'], $aux );
				$aux['title']        = $type['content_object']->getTitle( $aux );
				$aux['display_url']  = $type['content_object']->getDisplayUrl( $aux['content_id'], $aux );
			}
			$ret[] = $aux;
		}

		$query = "SELECT COUNT( sts.`content_id` ) FROM `".BIT_DB_PREFIX."stars` sts $where";
		$pListHash['cant'] = $this->mDb->getOne( $query, $bindVars );

		LibertyContent::postGetList( $pListHash );
		return $ret;
	}

	/**
	 * Get the rating history of a loaded content
	 *
	 * @param boolean $pExtras loading the extras will get all users who have rated in the past and their ratings
	 * @access public
	 * @return TRUE on success, FALSE on failure
	 */
	function getRatingDetails( $pExtras = FALSE ) {
		if( $this->isValid() ) {
			global $gBitSystem;
			$stars = $gBitSystem->getConfig( 'stars_used_in_display', 5 );
			$pixels = $stars *  $gBitSystem->getConfig( 'stars_icon_width', 22 );
			$query = "SELECT ( `rating` * $pixels / 100 ) AS `stars_pixels`, `rating` AS `stars_rating`, `rating_count` AS `stars_rating_count`, `content_id` FROM `".BIT_DB_PREFIX."stars` WHERE `content_id`=?";
			$obj = $this->getLibertyObject( $this->mContentId );
			$this->mInfo = $this->mDb->getRow( $query, array( $this->mContentId ) );
			$this->mInfo = array_merge( $this->mInfo, $obj->mInfo );
			if( $pExtras ) {
				$query = "SELECT sth.`content_id` as `hash_key`, sth.*, uu.`login`, uu.`real_name`
					FROM `".BIT_DB_PREFIX."stars_history` sth
						INNER JOIN `".BIT_DB_PREFIX."users_users` uu ON sth.`user_id`=uu.`user_id`
					WHERE sth.`content_id`=? ORDER BY sth.`rating` ASC";
				$this->mInfo['user_ratings'] = $this->mDb->getAll( $query, array( $this->mContentId ) );
			}
		}
		return( count( $this->mInfo ) );
	}

	/**
	* quick method to get a nice summary of past ratings for a given content
	* @return usable hash with a summary of ratings of a given content id
	*/
	function getRatingSummary( $pContentId = NULL , $pVersion = NULL ) {
		$ret = FALSE;
		if( !@BitBase::verifyId( $pContentId ) && $this->isValid() ) {
			$pContentId = $this->mContentId;
		}

		if( @BitBase::verifyId( $pContentId ) ) {
			$bindVars = array( $pContentId );
			if (BitBase::verifyId($pVersion)) {
				$query = "SELECT sth.`rating`, COUNT( sth.`rating`) AS `rating_count`, SUM( sth.`weight` ) AS `weight`
				FROM `".BIT_DB_PREFIX."stars_version` sts
				LEFT JOIN `".BIT_DB_PREFIX."stars_history` sth ON( sth.`content_id`=sts.`content_id` AND sth.`version`= sts.`version` )
				WHERE sts.`content_id`=? AND sts.`version`=?
				GROUP BY sth.`rating`";
				$bindVars[]=$pVersion;
			} else {
				$query = "SELECT sth.`rating`, COUNT( sth.`rating`) AS `rating_count`, SUM( sth.`weight` ) AS `weight`
				FROM `".BIT_DB_PREFIX."stars` sts
				LEFT JOIN `".BIT_DB_PREFIX."stars_history` sth ON( sth.`content_id`=sts.`content_id` )
				WHERE sts.`content_id`=?
				GROUP BY sth.`rating`";
			}
			$ret = $this->mDb->getAll( $query, $bindVars );
		}
		return $ret;
	}

	/**
	* @param array pParams hash of values that will be used to store the page
	* @return bool TRUE on success, FALSE if store could not occur. If FALSE, $this->mErrors will have reason why
	* @access public
	**/
	function store( &$pParamHash ) {
		global $gBitUser;
		if( $this->verify( $pParamHash ) ) {
			$table = BIT_DB_PREFIX."stars";
			$this->mDb->StartTrans();
			if( !empty( $this->mInfo ) ) {
				if( $this->getUserRating( $pParamHash['content_id'] ) ) {
					$result = $this->mDb->associateUpdate( $table."_history", $pParamHash['stars_history_store'], array( "content_id" => $this->mContentId, "user_id" => $gBitUser->mUserId , "version" => $pParamHash['stars_history_store']['version'] ) );
					// we don't have a new entry in the database and the rating_count stays the same
					unset( $pParamHash['stars_store']['rating_count'] );
				} else {
					$result = $this->mDb->associateInsert( $table."_history", $pParamHash['stars_history_store'] );
				}
				$result = $this->mDb->associateUpdate( $table, $pParamHash['stars_store'], array( "content_id" => $this->mContentId ) );
				if ($this->getRatingSummary($pParamHash['content_id'],$this->mInfo['c_version'])) {
					$result = $this->mDb->associateUpdate( $table."_version", $pParamHash['stars_version_store'], array( "content_id" => $this->mContentId , "version" => $this->mInfo['c_version'] ) );
				} else {
					$result = $this->mDb->associateInsert( $table."_version", $pParamHash['stars_version_store'] );
				}
			} else {
				$result = $this->mDb->associateInsert( $table, $pParamHash['stars_store'] );
				$result = $this->mDb->associateInsert( $table."_version", $pParamHash['stars_version_store'] );
				$result = $this->mDb->associateInsert( $table."_history", $pParamHash['stars_history_store'] );
			}
			//$this->mDb->rollBackTrans();
			$this->mDb->CompleteTrans();
			global $gLibertySystem;
/* apparently unused 
			$pHash = array(
			'stars_rating'=>$pParamHash['stars_store']['rating'],
			'stars_rating_count'=>$pParamHash['stars_store']['rating_count'],
			'v_gstars_rating'=>$pParamHash['stars_version_store']['rating'],
			'stars_version_rating_count'=>$pParamHash['stars_version_store']['rating_count']
			);
*/
			if( $loadFuncs = $gLibertySystem->getServiceValues( 'content_rating_updated_function' ) ) {
				foreach( $loadFuncs as $func ) {
					if( function_exists( $func ) ) {
						$func($pParamHash['content_id'],$pParamHash['stars_store']['rating'],$pParamHash['stars_version_store']['rating']);
					}
				}
			}
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
			if( $this->calculateRating( $pParamHash ) ) {
				if (empty($this->mInfo['c_version'])) {
					$c_version = $this->getCurrentVersion($pParamHash['content_id']);
				} else {
					$c_version = $this->mInfo['c_version'];
				}
				$pParamHash['stars_store']['rating']                = ( int )$pParamHash['calc']['rating'];
				$pParamHash['stars_store']['rating_count']          = ( int )$pParamHash['calc']['count'] + 1;
				$pParamHash['stars_version_store']['rating']        = ( int )$pParamHash['v_calc']['rating'];
				$pParamHash['stars_version_store']['rating_count']  = ( int )$pParamHash['v_calc']['count'] + 1;
				$pParamHash['stars_version_store']['version']       = $c_version;
				$pParamHash['stars_history_store']['content_id']    = $pParamHash['stars_store']['content_id'] = $pParamHash['stars_version_store']['content_id'] = ( int )$this->mContentId;
				$pParamHash['stars_history_store']['rating']        = ( int )$pParamHash['rating'];
				$pParamHash['stars_history_store']['weight']        = ( int )$pParamHash['user']['weight'];
				$pParamHash['stars_history_store']['rating_time']   = ( int )BitDate::getUTCTime();
				$pParamHash['stars_history_store']['user_id']       = ( int )$gBitUser->mUserId;
				$pParamHash['stars_history_store']['version']       = $c_version;
			} else {
				$this->mErrors['calculate_rating'] = "There was a problem calculating the rating.";
			}
		} else {
			$this->mErrors['unregistered'] = "You have to be registered to rate content.";
		}

		return( count( $this->mErrors )== 0 );
	}

	/**
	* check if this user has already voted before
	*/
	function getUserRating( $pContentId = NULL ) {
		global $gBitSystem, $gBitUser;
		$ret = FALSE;
		if( !@BitBase::verifyId( $pContentId ) && $this->isValid() ) {
			$pContentId = $this->mContentId;
		}

		if( @BitBase::verifyId( $pContentId ) ) {
			$c_version = $this->getCurrentVersion( $this->mContentId );
			$stars = $gBitSystem->getConfig( 'stars_used_in_display', 5 );
			$pixels = $stars *  $gBitSystem->getConfig( 'stars_icon_width', 22 );

			$query = "SELECT (`rating` * $pixels / 100) AS `stars_user_pixels`, ( `rating` * $stars / 100 ) AS `stars_user_rating` FROM `".BIT_DB_PREFIX."stars_history` WHERE `content_id`=? AND `user_id`=? AND `version`=?";
			$ret = $this->mDb->getRow( $query, array( $pContentId, $gBitUser->mUserId, $c_version ) );
			if( !empty( $ret['stars_user_rating'] ) ) {
				$ret['stars_version_user_rating'] = $ret['stars_user_rating'];
				$ret['stars_version_user_pixels'] = $ret['stars_user_pixels'];
			}
		}
		return $ret;
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
			$query = "DELETE FROM `".BIT_DB_PREFIX."stars_version` WHERE `content_id` = ?";
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
			$vData[$aux['content_id']][] = $aux['version'];
		}
		$userIds = array_unique( $userIds );
		$contentIds = array_unique( $contentIds );
		foreach( $vData as $content_id =>$versions ) {
			$vData[$content_id] = array_unique($vData[$content_id]);
		}

		// update user weight in accordance with new settings
		foreach( $userIds as $userId ) {
			$userWeight = $this->calculateUserWeight( $userId );
			$result = $this->mDb->query( "UPDATE `".BIT_DB_PREFIX."stars_history` SET `weight`=? WHERE `user_id`=?", array( $userWeight, $userId ) );
		}

		// update the calculations in the stars table
		foreach( $contentIds as $content_id ) {
			$calc['sum'] = $calc['weight'] = $calc['count'] = 0;
			if( $summary = $this->getRatingSummary( $content_id ) ) {
				foreach( $summary as $info ) {
					$calc['sum']    += $info['weight'] * $info['rating'];
					$calc['weight'] += $info['weight'];
					$calc['count']  += $info['rating_count'];
				}
			}

			$minRatings = $gBitSystem->getConfig( 'stars_minimum_ratings', 5 );
			if( $calc['count'] < $minRatings ) {
				$rating = 0;
			} else {
				$rating = round( $calc['sum'] / $calc['weight'] );
			}

			$result = $this->mDb->query( "UPDATE `".BIT_DB_PREFIX."stars` SET `rating`=?, `rating_count`=? WHERE `content_id`=?", array( $rating, $calc['count'], $content_id ) );
		}

		$data = array();
		// update the calculations in the stars_version table
		foreach( $vData as $content_id =>$versions ) {
			foreach ($versions as $version) {
				$calc['sum'] = $calc['weight'] = $calc['count'] = 0;
				if( $summary = $this->getRatingSummary( $content_id , $version ) ) {
					foreach( $summary as $info ) {
						$calc['sum']    += $info['weight'] * $info['rating'];
						$calc['weight'] += $info['weight'];
						$calc['count']  += $info['rating_count'];
					}
				}
				$minRatings = $gBitSystem->getConfig( 'stars_minimum_ratings', 5 );
				if( $calc['count'] < $minRatings ) {
					$rating = 0;
				} else {
					$rating = round( $calc['sum'] / $calc['weight'] );
				}

				$result = $this->mDb->query( "UPDATE `".BIT_DB_PREFIX."stars_version` SET `rating`=?, `rating_count`=? WHERE `content_id`=? AND version=?", array( $rating, $calc['count'], $content_id, $version ) );
			}
		}
		return TRUE;
	}

	/**
	* calculate the correct value to insert into the database
	*/
	function calculateRating( &$pParamHash , $ro = FALSE ) {
		global $gBitSystem, $gBitUser;
		$stars = $gBitSystem->getConfig( 'stars_used_in_display', 5 );
		$ret = FALSE;

		// TODO: factors that haven't been taken into accound yet:
		//       - time since last rating(s) - how should this be dealt with?
		//       - age of document - ???

		// number of ratings needed before value is displayed
		if( $ro || (@BitBase::verifyId( $pParamHash['stars_rating'] ) && $pParamHash['stars_rating'] > 0 && $pParamHash['stars_rating'] <= $stars && $this->isValid() )) {
			if (!$ro) {
				// normalise to 100 weight
				$pParamHash['rating'] = $pParamHash['stars_rating'] / $stars * 100;
			}

			// if the user is submitting his rating again, we need to update the value in the db before we get the summary
			if( !$ro && ( $userRating = $this->getUserRating() )) {
				$tmpUpdate['rating'] = ( int )$pParamHash['rating'];
				$result = $this->mDb->associateUpdate( BIT_DB_PREFIX."stars_history", $tmpUpdate, array( "content_id" => $this->mContentId, "user_id" => $gBitUser->mUserId ) );
			}

			$pParamHash['user']['weight'] = $this->calculateUserWeight();
			$calc['sum'] = $calc['weight'] = $calc['count'] = 0;
			// the user rating has to be updated before we get the summary
			if( $summary = $this->getRatingSummary() ) {
				foreach( $summary as $info ) {
					$calc['sum']    += $info['weight'] * $info['rating'];
					$calc['weight'] += $info['weight'];
					$calc['count']  += $info['rating_count'];
				}
			}

			$minRatings = $gBitSystem->getConfig( 'stars_minimum_ratings', 5 );
			if( ( $calc['count'] + 1 ) < $minRatings ) {
				$pParamHash['calc']['rating'] = 0;
			} elseif($ro) {
				if ($calc['sum']>0) {
					$pParamHash['calc']['rating'] = round( $calc['sum'] / $calc['weight'] );
				} else {
					$pParamHash['calc']['rating'] = 0;
				}
			} else {
				$pParamHash['calc']['rating'] = round( ( $calc['sum'] + ( $pParamHash['rating'] * $pParamHash['user']['weight'] ) ) / ( $calc['weight'] + $pParamHash['user']['weight'] ) );
			}
			$pParamHash['calc']['count'] = $calc['count'];

			$calc=array();
			$calc['sum'] = $calc['weight'] = $calc['count'] = 0;
			// the user rating has to be updated before we get the summary
			if( $summary = $this->getRatingSummary(NULL,$this->getCurrentVersion($this->mContentId ))) {
				foreach( $summary as $info ) {
					$calc['sum']    += $info['weight'] * $info['rating'];
					$calc['weight'] += $info['weight'];
					$calc['count']  += $info['rating_count'];
				}
			}

			$minRatings = $gBitSystem->getConfig( 'stars_minimum_ratings', 5 );
			if( ( $calc['count'] + 1 ) < $minRatings ) {
				$pParamHash['v_calc']['rating'] = 0;
			} elseif($ro) {
				if ($calc['sum']>0) {
					$pParamHash['v_calc']['rating'] = round( $calc['sum'] / $calc['weight'] );
				} else {
					$pParamHash['v_calc']['rating'] = 0;
				}
			} else {
				$pParamHash['v_calc']['rating'] = round( ( $calc['sum'] + ( $pParamHash['rating'] * $pParamHash['user']['weight'] ) ) / ( $calc['weight'] + $pParamHash['user']['weight'] ) );
			}
			$pParamHash['v_calc']['count'] = $calc['count'];
			$ret = TRUE;
		}
		return $ret;
	}

	function calculateUserWeight( $pUserId = NULL ) {
		global $gBitUser, $gBitSystem;
		if( $gBitSystem->isFeatureActive( 'stars_user_weight' ) ) {

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
			$userWeight['age'] = $age['user'] / $age['site'];

			// permissioning relative to full number of permissions
			$query = "SELECT COUNT( `perm_name` ) FROM `".BIT_DB_PREFIX."users_permissions`";
			if( $tmpUser->isAdmin() ) {
				$userWeight['permission'] = 1;
			} else {
				$userWeight['permission'] = count( $tmpUser->mPerms ) / $this->mDb->getOne( $query );
			}

			// activity - we could to the same using the history as well.
			$query = "SELECT COUNT( `content_id` ) FROM `".BIT_DB_PREFIX."liberty_content` WHERE `user_id`=?";
			$activity['user'] = $this->mDb->getOne( $query, array( $tmpUser->getField( 'user_id' ) ) );
			$query = "SELECT COUNT( `content_id` ) FROM `".BIT_DB_PREFIX."liberty_content`";
			$activity['site'] = $this->mDb->getOne( $query );
			$userWeight['activity'] = $activity['user'] / $activity['site'];

			// here we can add some weight to various areas
			$custom['age']        = $gBitSystem->getConfig( 'stars_weight_age' );
			$custom['permission'] = $gBitSystem->getConfig( 'stars_weight_permission' );
			$custom['activity']   = $gBitSystem->getConfig( 'stars_weight_activity' );

			foreach( $userWeight as $type => $value ) {
				$$type = 10 * $value * $custom[$type];
				if( empty( $$type ) ) {
					$$type = 1;
				}
			}

			// TODO: run some tests to see if this is a good way of evaluating power of a user
			// ensure that we always have a positive number here to avoid chaos - this also makes sure new users have at least a bit of a say
			if( ( $ret = round( log( $age * $permission * $activity, 2 ) ) ) < 1 ) {
				$ret = 1;
			}
		} else {
			$ret = 1;
		}

		return $ret;
	}

	function getOverallUserRating( $pUserId ) {
		$ret = array();
		if( @BitUser::verifyId( $pUserId ) && $pUserId>0 ) {
			global $gBitSystem;
			$bindVars = array( $pUserId );
			$query = "SELECT sth.`rating`, COUNT( sth.`rating`) AS `rating_count`, SUM( sth.`weight` ) AS `weight`
				FROM `".BIT_DB_PREFIX."liberty_content` lc
				LEFT JOIN `".BIT_DB_PREFIX."liberty_content_history` lch ON( lch.`content_id`=lc.`content_id` )
				LEFT JOIN `".BIT_DB_PREFIX."stars_history` sth ON( sth.`content_id`=lc.`content_id` )
				WHERE lch.`user_id`=?
				GROUP BY sth.`rating`";
			$summary = $this->mDb->getAll( $query, $bindVars );

			$calc['sum'] = $calc['weight'] = $calc['count'] = 0;
			if( $summary ) {
				foreach( $summary as $info ) {
					$calc['sum']    += $info['weight'] * $info['rating'];
					$calc['weight'] += $info['weight'];
					$calc['count']  += $info['rating_count'];
				}
			}

			$minRatings = $gBitSystem->getConfig( 'stars_minimum_ratings', 5 );
			if( ( $calc['count'] + 1 ) < $minRatings ) {
				$ret['stars_rating'] = 0;
			} elseif ($calc['sum']>0) {
				$ret['stars_rating'] = round( $calc['sum'] / $calc['weight'] );
			} else {
				$ret['stars_rating'] = 0;
			}
			$ret['stars_rating_count'] = $calc['count'];
			$ret['stars_pixels'] = $ret['stars_rating'] *  $gBitSystem->getConfig( 'stars_used_in_display', 5 ) *  $gBitSystem->getConfig( 'stars_icon_width', 22 ) / 100;
			$ret['stars_load']=true;

		}
		return $ret;
	}
}

/********* SERVICE FUNCTIONS *********/

/**
 * Function to prepare and assign data to the stars service template
 * 
 * @param array $pStars Stars information
 * @access public
 * @return void
 */
function stars_template_setup( $pStars ) {
	global $gBitSystem, $gBitUser, $gBitSmarty;
	$default_names = array();
	for($i=0;$i<$pStars;$i++) {
		$default_names[] = tra("Rating") . ":" . ($i+1);
	}
	$default_names_flat = implode(",", $default_names);	
	$ratingNames = explode(",", "," . $gBitSystem->getConfig( 'stars_rating_names', $default_names_flat ) );
	$gBitSmarty->assign( 'ratingNames', $ratingNames);
	$gBitSmarty->assign( 'starsLinks', $hash = array_fill( 1, $pStars, 1 ) );
	$gBitSmarty->assign( 'loadStars', TRUE );
}

/**
 * stars_content_list_sql 
 * 
 * @param array $pObject 
 * @access public
 * @return SQL - using the following keys:
 *         stars_load         = content_id of the stars item belongs to
 *         stars_rating_count = number of ratings this content has recieved so far
 *         stars_rating       = the actual rating normalised to 100
 *         stars_pixels       = the number of pixels that should be displayed using the cool css method
 *         stars_user_rating  = the rating given by the user who rated is viewing the page
 *         stars_user_pixels  = the number of pixels that should be displayed using the cool css method
 *
 *         all the version related stars keys are the same as the above - but relating to the currently active version as opposed to the overall rating
 */
function stars_content_list_sql( &$pObject ) {
	global $gBitSystem, $gBitUser, $gBitSmarty;

	if( !method_exists( $pObject,'getContentType' ) || $pObject->getContentType() == NULL || $gBitSystem->isFeatureActive( 'stars_rate_'.$pObject->getContentType() ) )  {
		$stars = $gBitSystem->getConfig( 'stars_used_in_display', 5 );
		$pixels = $stars *  $gBitSystem->getConfig( 'stars_icon_width', 22 );
		stars_template_setup($stars);

		$ret['select_sql'] = ",
			lc.`content_id` AS `stars_load`,
			sts.`rating_count` AS stars_rating_count,
			sts.`rating` AS stars_rating,
			( sts.`rating` * $pixels / 100 ) AS stars_pixels,
			( sth.`rating` * $stars / 100 ) AS stars_user_rating,
			( sth.`rating` * $pixels / 100 ) AS stars_user_pixels ";
		$ret['join_sql'] = "
			LEFT OUTER JOIN `".BIT_DB_PREFIX."stars` sts ON
				( lc.`content_id`=sts.`content_id` )
			LEFT OUTER JOIN `".BIT_DB_PREFIX."stars_history` sth ON
				( lc.`content_id`=sth.`content_id` AND lc.`version`=sth.`version` AND sth.`user_id`='".$gBitUser->mUserId."' ) ";

		$ret['select_sql'] .= ",
			v_sts.`rating_count` AS stars_version_rating_count,
			v_sts.`rating` AS stars_version_rating,
			( v_sts.`rating` * $pixels / 100 ) AS stars_version_pixels,
			( v_sth.`rating` * $stars / 100 ) AS stars_version_user_rating,
			( v_sth.`rating` * $pixels / 100 ) AS stars_version_user_pixels ";
		$ret['join_sql'] .= "
			LEFT OUTER JOIN `".BIT_DB_PREFIX."stars_version` v_sts ON
				( lc.`content_id`=v_sts.`content_id` AND lc.`version`=v_sts.`version` )
			LEFT OUTER JOIN `".BIT_DB_PREFIX."stars_history` v_sth ON
				( v_sts.`content_id`=v_sth.`content_id` AND v_sts.`version`=v_sth.`version` AND v_sth.`user_id`='".$gBitUser->mUserId."' )";

		if( $gBitSystem->isFeatureActive( 'stars_auto_hide_content' ) ) {
			// need to take rating_count into the equation as well
			$ret['where_sql'] = " AND( sts.`rating`>? OR sts.`rating` IS NULL OR sts.`rating`=? )";
			$ret['bind_vars'][] = $gBitSystem->getConfig( 'stars_auto_hide_content' );
			$ret['bind_vars'][] = 0;
		}
		return $ret;
	}
}

/**
 * stars_list_history_sql_function 
 * 
 * @param array $pObject 
 * @access public
 * @return SQL - using the following keys:
 *         stars_load         = content_id of the stars item belongs to
 *         stars_rating_count = number of ratings this content has recieved so far
 *         stars_rating       = the actual rating normalised to 100
 *         stars_pixels       = the number of pixels that should be displayed using the cool css method
 *         stars_user_rating  = the rating given by the user who rated is viewing the page
 *         stars_user_pixels  = the number of pixels that should be displayed using the cool css method
 *
 *         all the version related stars keys are the same as the above - but relating to the currently active version as opposed to the overall rating
 */
function stars_list_history_sql_function( &$pObject ) {
	global $gBitSystem, $gBitUser, $gBitSmarty;
	if( !method_exists( $pObject,'getContentType' ) || ( $pObject->getContentType() == NULL ) || $gBitSystem->isFeatureActive( 'stars_rate_'.$pObject->getContentType() ) ) {
		$stars = $gBitSystem->getConfig( 'stars_used_in_display', 5 );
		$pixels = $stars *  $gBitSystem->getConfig( 'stars_icon_width', 22 );
		stars_template_setup($stars);

		$ret['select_sql'] = ",
			lc.`content_id` AS `stars_load`,
			sts.`rating_count` AS stars_rating_count,
			sts.`rating` AS stars_rating,
			( sts.`rating` * $pixels / 100 ) AS stars_pixels,
			( sth.`rating` * $stars / 100 ) AS stars_user_rating,
			( sth.`rating` * $pixels / 100 ) AS stars_user_pixels ";
		$ret['join_sql'] = "
			LEFT OUTER JOIN `".BIT_DB_PREFIX."stars` sts ON
				( lc.`content_id`=sts.`content_id` )
			LEFT OUTER JOIN `".BIT_DB_PREFIX."stars_history` sth ON
				( lc.`content_id`=sth.`content_id` AND lc.`version`=sth.`version` AND sth.`user_id`='".$gBitUser->mUserId."' )";

		$ret['select_sql'] .= ",
			v_sts.`rating_count` AS stars_version_rating_count,
			v_sts.`rating` AS stars_version_rating,
			( v_sts.`rating` * $pixels / 100 ) AS stars_version_pixels,
			( v_sth.`rating` * $stars / 100 ) AS stars_version_user_rating,
			( v_sth.`rating` * $pixels / 100 ) AS stars_version_user_pixels ";
		$ret['join_sql'] .= "
			LEFT OUTER JOIN `".BIT_DB_PREFIX."stars_version` v_sts ON
				( lc.`content_id`=v_sts.`content_id` AND th.`version`=v_sts.`version` )
			LEFT OUTER JOIN `".BIT_DB_PREFIX."stars_history` v_sth ON
				( v_sts.`content_id`=v_sth.`content_id` AND v_sts.`version`=v_sth.`version` AND v_sth.`user_id`='".$gBitUser->mUserId."' )";

		if( $gBitSystem->isFeatureActive( 'stars_auto_hide_content' ) ) {
			// need to take rating_count into the equation as well
			$ret['where_sql'] = " AND( sts.`rating`>? OR sts.`rating` IS NULL OR sts.`rating`=? )";
			$ret['bind_vars'][] = $gBitSystem->getConfig( 'stars_auto_hide_content' );
			$ret['bind_vars'][] = 0;
		}
		return $ret;
	}
}

/**
 * stars_content_load_sql 
 * 
 * @param array $pObject 
 * @access public
 * @return SQL - using the following keys:
 *         stars_load         = content_id of the stars item belongs to
 *         stars_rating_count = number of ratings this content has recieved so far
 *         stars_rating       = the actual rating normalised to 100
 *         stars_pixels       = the number of pixels that should be displayed using the cool css method
 *         stars_user_rating  = the rating given by the user who rated is viewing the page
 *         stars_user_pixels  = the number of pixels that should be displayed using the cool css method
 *
 *         all the version related stars keys are the same as the above - but relating to the currently active version as opposed to the overall rating
 */
function stars_content_load_sql( &$pObject ) {
	global $gBitSystem, $gBitUser, $gBitSmarty;
	if( !method_exists($pObject,'getContentType') || $gBitSystem->isFeatureActive( 'stars_rate_'.$pObject->getContentType() ) ) {
		if( $gBitSystem->isFeatureActive( 'stars_use_ajax' ) ) {
			$gBitSmarty->assign( 'loadAjax', TRUE );
		}
		$stars = $gBitSystem->getConfig( 'stars_used_in_display', 5 );
		$pixels = $stars *  $gBitSystem->getConfig( 'stars_icon_width', 22 );
		stars_template_setup($stars);
		$ret['select_sql'] = ",
			lc.`content_id` AS `stars_load`,
			sts.`rating_count` AS stars_rating_count,
			sts.`rating` AS stars_rating,
			( sts.`rating` * $pixels / 100 ) AS stars_pixels,
			( sth.`rating` * $stars / 100 ) AS stars_user_rating,
			( sth.`rating` * $pixels / 100 ) AS stars_user_pixels ";
		$ret['join_sql'] = "
			LEFT JOIN `".BIT_DB_PREFIX."stars` sts ON
				( lc.`content_id`=sts.`content_id` )
			LEFT JOIN `".BIT_DB_PREFIX."stars_history` sth ON
				( lc.`content_id`=sth.`content_id` AND lc.`version`=sth.`version` AND sth.`user_id`='".$gBitUser->mUserId."' )";

		$ret['select_sql'] .= ",
			lc.`content_id` AS `stars_version_load`,
			v_sts.`rating_count` AS stars_version_rating_count,
			v_sts.`rating` AS stars_version_rating,
			( v_sts.`rating` * $pixels / 100 ) AS stars_version_pixels,
			( v_sth.`rating` * $stars / 100 ) AS stars_version_user_rating,
			( v_sth.`rating` * $pixels / 100 ) AS stars_version_user_pixels ";
		$ret['join_sql'] .= "
			LEFT JOIN `".BIT_DB_PREFIX."stars_version` v_sts ON 
				( lc.`content_id`=v_sts.`content_id` AND lc.`version`=v_sts.`version` )
			LEFT JOIN `".BIT_DB_PREFIX."stars_history` v_sth ON
				( lc.`content_id`=v_sth.`content_id` AND lc.`version`=v_sth.`version` AND v_sth.`user_id`='".$gBitUser->mUserId."' )";

		if( $gBitSystem->isFeatureActive( 'stars_auto_hide_content' ) ) {
			// need to take rating_count into the equation as well
			$ret['where_sql'] = " AND( sts.`rating`>? OR sts.`rating` IS NULL OR sts.`rating`=? )";
			$ret['bind_vars'][] = $gBitSystem->getConfig( 'stars_auto_hide_content' );
			$ret['bind_vars'][] = 0;
		}
		return $ret;
	}
}

function stars_content_expunge( &$pObject, &$pParamHash ) {
	$stars = new LibertyStars( $pObject->mContentId );
	$stars->expunge();
}

function stars_content_can_rate($pContentId) {
	$stars = new LibertyStars($pContentId);
	$userRating = $stars->getUserRating($pContentId);
	return (!empty($userRating));
}

function stars_content_get_rating($pContentId) {
	$stars = new LibertyStars($pContentId);
	$lHash = array('content_id'=>intval($pContentId));
	$stars->calculateRating($lHash,true);
	$lHash['stars_rating'] = $lHash['calc']['rating'];
	$lHash['stars_version_rating'] = $lHash['v_calc']['rating'];
	$lHash['stars_count'] = $lHash['calc']['count'];
	$lHash['stars_version_count'] = $lHash['v_calc']['count'];
	unset($lHash['calc']);
	unset($lHash['v_calc']);
	unset($lHash['user']);
	return $lHash;
}

function stars_content_get_rating_field(&$pObject,$pVerField=FALSE,$pCountField=FALSE,$pSQL=FALSE) {
	global $gBitSystem;
	if($gBitSystem->isPackageActive('stars') && ($pObject==NULL || !method_exists($pObject,'getContentType') || $gBitSystem->isFeatureActive( 'stars_rate_'.$pObject->getContentType() ) ) ) {
		if ($pSQL) {
			if ($pCountField) {
				if ($pVerField) {
					return 'sts.`rating_count`';
				}
				return  'sts.`rating_count`';
			} elseif ($pVerField) {
				return 'v_sts.`rating`';
			}
			return 'sts.`rating`';
		} else {
			if ($pCountField) {
				if ($pVerField) {
					return 'stars_version_rating_count';
				}
				return  'stars_rating_count';
			} elseif ($pVerField) {
				return 'stars_version_rating';
			}
			return 'stars_rating';
		}
	}
	return NULL;
}

function stars_content_set_rating($pContentId,$pRating) {
	global $gBitUser;
	$stars = new LibertyStars($pContentId);
	$paramHash =array();
	$paramHash['stars_rating']=$pRating;
	if( $gBitUser->isRegistered() ) {
		$stars->store($paramHash);
	}
}

?>
