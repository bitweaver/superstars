<?php
/**
 * $Header$
 * date created 2006/02/10
 * @author xing <xing@synapse.plus.com>
 * @version $Revision$
 * @package superstars
 */

/**
 * Initialization
 */
require_once( KERNEL_PKG_PATH.'BitBase.php' );

/**
 * @package superstars
 */
class LibertyStars extends LibertyBase {
	var $mContentId;

	function __construct( $pContentId=NULL ) {
		parent::__construct();
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
			$query = "SELECT ( `rating` * $pixels / 100 ) AS `stars_pixels`, `rating` AS `stars_rating`, `rating_count` AS `stars_rating_count`, `content_id` FROM `".BIT_DB_PREFIX."stars_version` WHERE `content_id`=?  AND `version`=?";
			$this->mInfo = $this->mDb->getRow( $query, array( $this->mContentId, 0 ) );

			if( !empty( $this->mInfo ) ) {
				$this->mInfo['c_version'] = $this->getCurrentVersion( $this->mContentId );

				$query = "SELECT ( `rating` * $pixels / 100 ) AS `stars_version_pixels`, `rating` AS `stars_version_rating`, `rating_count` AS `stars_version_rating_count` FROM `".BIT_DB_PREFIX."stars_version` WHERE `content_id`=? AND `version`=?";
				$v = $this->mDb->getRow( $query, array( $this->mContentId, $this->mInfo['c_version'] ) );
				$this->mInfo = array_merge($this->mInfo,$v);
			}
		}
		return( count( $this->mInfo ) );
	}

	/**
	* get the current version number of the specified liberty content
	* @param $pContentId content ID
	* @access public
	* @return version number of specified content
	**/
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

		$where .= empty( $where ) ? ' WHERE ' : ' AND ';
		$where .= ' sts.`version` = 0';

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
			lc.`last_modified`, lc.`content_type_guid`, lc.`ip`, lc.`created`,
			lct.`content_name`
			FROM `".BIT_DB_PREFIX."stars_version` sts
				INNER JOIN `".BIT_DB_PREFIX."liberty_content` lc ON ( lc.`content_id` = sts.`content_id` )
				INNER JOIN `".BIT_DB_PREFIX."liberty_content_types` lct ON ( lct.`content_type_guid` = lc.`content_type_guid` )
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
				$aux['title']        = $type['content_object']->getTitleFromHash( $aux );
				$aux['display_url']  = $type['content_object']->getDisplayUrl( $aux['content_id'], $aux );
			}
			$ret[] = $aux;
		}

		$query = "SELECT COUNT( sts.`content_id` ) FROM `".BIT_DB_PREFIX."stars_version` sts $where";
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
			$query = "
				SELECT
					( `rating` * $pixels / 100 ) AS `stars_pixels`,
					`rating` AS `stars_rating`,
					`rating_count` AS `stars_rating_count`,
					`content_id`
				FROM `".BIT_DB_PREFIX."stars_version`
				WHERE `content_id`=? AND version = 0";
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
			if ($pVersion == 0 || BitBase::verifyId($pVersion)) {
				$query = "SELECT sth.`rating`, COUNT( sth.`rating`) AS `rating_count`, SUM( sth.`weight` ) AS `weight`
				FROM `".BIT_DB_PREFIX."stars_history` sth
				WHERE sth.`content_id`=? AND sth.`version`=?
				GROUP BY sth.`rating`";
				$bindVars[]=$pVersion;
			}
			$ret = $this->mDb->getAll( $query, $bindVars );
		}
		return $ret;
	}




	/**
    * stores a rating for a specified content ID
	* both a version specific and overall non-version specific rating are stored 
	* @param array pParams hash of values that will be used to store the page
	* @return bool TRUE on success, FALSE if store could not occur. If FALSE, $this->mErrors will have reason why
	* @access public
	**/
	function store( &$pParamHash ) {
		global $gBitUser;
		if( $this->verify( $pParamHash ) ) {
			$table = BIT_DB_PREFIX."stars";
			$this->mDb->StartTrans();
			if( 1 ) {

				# get current version number of this content item
				if (empty($this->mInfo['c_version'])) {
					$c_version = $this->getCurrentVersion($pParamHash['content_id']);
				} else {
					$c_version = $this->mInfo['c_version'];
				}

				# get any exising user ratings
				$non_version_user_rating = $this->getUserRating( $this->mContentId, 0 );
				$version_user_rating = $this->getUserRating( $this->mContentId, $c_version );

				$normalized_rating = $this->normalizeRating($pParamHash['stars_rating']);
				$user_weight = $this->calculateUserWeight();

				#write history rows for version and non-version ratings
				foreach (array($c_version, 0) as $version) {

					#build history row
					$history = array();
					$history['content_id']  = ( int )$this->mContentId;
					$history['rating']      = ( int )$normalized_rating;
					$history['weight']      = ( int )$user_weight;
					$history['rating_time'] = ( int )BitDate::getUTCTime();
					$history['user_id']     = ( int )$gBitUser->mUserId;
					$history['version']			  = $version;

					$update_count_expression = '`update_count` = `update_count` + ?';

					$result = $this->mDb->associateUpdate( $table."_history", 
					array_merge($history,array($update_count_expression=>1)), 
					array( "content_id" => $this->mContentId, "user_id" => $gBitUser->mUserId , "version" => $history['version'] ) );
					$affected_rows = $this->mDb->Affected_Rows();				
					#Affected_Rows is zeroe unless a value changed in the row, so with update_count++ we guarantee
					#Affected_Rows > 0 if the row existed and was updated.
					if (!$affected_rows) {
						$result = $this->mDb->associateInsert( $table."_history", $history );
				}
			}


				#build the overall content ratings, both version specific and non-version specific
				if( !$this->calculateRating( $pParamHash ) ) {
					$this->mErrors['calculate_rating'] = "There was a problem calculating the rating.";
					//$this->mDb->rollBackTrans();
					return FALSE;
				}


				#upddate DB with version independent rating	
				$non_version_rating = array();
				$non_version_rating['content_id'] =  $this->mContentId;
				$non_version_rating['rating']              = ( int )$pParamHash['calc']['rating'];
				$non_version_rating['rating_count']        = ( int )$pParamHash['calc']['count'];
				$non_version_rating['version']			  = 0;
				$update_count_expression = '`update_count` = `update_count` + ?';

				$result = $this->mDb->associateUpdate( $table."_version", array_merge($non_version_rating,array($update_count_expression => 1)), array( "content_id" => $this->mContentId , "version" => 0 ) );
				$affected_rows = $this->mDb->Affected_Rows();				
				#Affected_Rows is zeroe unless a value changed in the row, so with update_count++ we guarantee
				#Affected_Rows > 0 if the row existed and was updated.
				if (!$affected_rows) {
					$result = $this->mDb->associateInsert( $table."_version", $non_version_rating );
				}

				#update DB with version specific rating
				$version_rating = array();
				$version_rating['content_id'] =  $this->mContentId;
				$version_rating['rating']              = ( int )$pParamHash['v_calc']['rating'];
				$version_rating['rating_count']        = ( int )$pParamHash['v_calc']['count'];
				$version_rating['version']	= $c_version;
				$update_count_expression = '`update_count` = `update_count` + ?';

				$result = $this->mDb->associateUpdate( $table."_version", array_merge($version_rating,array($update_count_expression=>1)), array( "content_id" => $this->mContentId , "version" => $c_version ) );
				$affected_rows = $this->mDb->Affected_Rows();				
				if (!$affected_rows) {
					$result = $this->mDb->associateInsert( $table."_version", $version_rating );
				}
			}

			$this->mDb->CompleteTrans();
			global $gLibertySystem;

			if( $loadFuncs = $gLibertySystem->getServiceValues( 'content_rating_updated_function' ) ) {
				foreach( $loadFuncs as $func ) {
					if( function_exists( $func ) ) {
						$func($pParamHash['content_id'],
							( int )$pParamHash['calc']['rating'],
							( int )$pParamHash['v_calc']['rating']);
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
		$stars = $gBitSystem->getConfig( 'stars_used_in_display', 5 );
		if( !$gBitUser->isRegistered() && $this->isValid() ) {
			$this->mErrors['unregistered'] = "You have to be registered to rate content.";
				}
		if( !$this->isValid() ) {
			$this->mErrors['invalidcontentid'] = "Invalid Content ID.";
			}
		if ( !(@BitBase::verifyId( $pParamHash['stars_rating'] ) && $pParamHash['stars_rating'] > 0 && $pParamHash['stars_rating'] <= $stars && $this->isValid() ) ) {
			$this->mErrors['invalidrating'] = "Invalid rating.";
		}

		return( count( $this->mErrors )== 0 );
	}

	/**
	* retreive user rating for specified content ID/version
	*/
	function getUserRating( $pContentId = NULL, $pVersion = NULL ) {
		global $gBitSystem, $gBitUser;
		$ret = FALSE;
		if( !@BitBase::verifyId( $pContentId ) && $this->isValid() ) {
			$pContentId = $this->mContentId;
		}

		if( @BitBase::verifyId( $pContentId ) ) {
			if (!empty($pVersion)) {
				$c_version = $pVersion;
			} else {
				$c_version = $this->getCurrentVersion( $this->mContentId );
			}
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
			$query = "DELETE FROM `".BIT_DB_PREFIX."stars_version` WHERE `content_id` = ?";
			$result = $this->mDb->query( $query, array( $this->mContentId ) );
			$query = "DELETE FROM `".BIT_DB_PREFIX."stars_history` WHERE `content_id` = ?";
			$result = $this->mDb->query( $query, array( $this->mContentId ) );
		}
		return $ret;
	}

	// ============================ calculations ============================



	/**
	* Computes a total rating from a list of indiv rating entries
	* normally would be called with an array of rows from stars_history
	**/
	function calculateRatingFromSummary( $pSummary ) {
			global $gBitSystem;
			$calc['sum'] = $calc['weight'] = $calc['count'] = 0;
			if ($pSummary) {
				foreach( $pSummary as $info ) {
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
			$rating_count = $calc['count'];
			return array($rating,$rating_count);
	}

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



		// update the calculations for each version rating
		// this includes version 0 for the overall rating
		// and version 1... for version specific ratings
		foreach( $vData as $content_id =>$versions ) {
			foreach ($versions as $version) {
				$summary = $this->getRatingSummary( $content_id , $version );
				list($rating,$rating_count) = $this->calculateRatingFromSummary( $summary );
				$result = $this->mDb->query( "UPDATE `".BIT_DB_PREFIX."stars_version` SET `rating`=?, `rating_count`=? WHERE `content_id`=? AND version=?", array( $rating, $rating_count, $content_id, $version ) );
			}
		}
		return TRUE;
	}


	function normalizeRating ($pRating) {
		global $gBitSystem;
		$stars = $gBitSystem->getConfig( 'stars_used_in_display', 5 );
		$normalized_rating = $pRating / $stars * 100;
		return $normalized_rating;
	}

	/**
	* calculate the correct values to insert into the database for the overall content ratings
	* both version specific and non-version specific
	* all updates to the stars_history table must have been made before calling this function
	*/
	function calculateRating( &$pParamHash ) {
		global $gBitSystem, $gBitUser;
		$stars = $gBitSystem->getConfig( 'stars_used_in_display', 5 );
		$calc = array();

		// TODO: factors that haven't been taken into accound yet:
		//       - time since last rating(s) - how should this be dealt with?
		//       - age of document - ???

		$normalized_rating = $this->normalizeRating($pParamHash['stars_rating']);
		$user_weight = $this->calculateUserWeight();
		$content_id = NULL;
		$version = 0;
		$summary = $this->getRatingSummary( $content_id , $version );
		list($rating,$rating_count) = $this->calculateRatingFromSummary( $summary );
		$pParamHash['calc']['rating'] = $rating;
		$pParamHash['calc']['count'] = $rating_count;

		$version = $this->getCurrentVersion($this->mContentId );
		$summary = $this->getRatingSummary( $content_id , $version );
		list($rating,$rating_count) = $this->calculateRatingFromSummary( $summary );
		$pParamHash['v_calc']['rating'] = $rating;
		$pParamHash['v_calc']['count'] = $rating_count;

		$ret = TRUE;
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
				WHERE sth.`version` != 0 AND lch.`user_id`=?
				GROUP BY sth.`rating`";
			$summary = $this->mDb->getAll( $query, $bindVars );
			list($rating,$rating_count) = $this->calculateRatingFromSummary( $summary );
			$ret['stars_rating'] = $rating;
			$ret['stars_rating_count'] = $rating_count;

			$normalized_rating = $this->normalizeRating($rating);
			$ret['stars_pixels'] = $normalized_rating *  $gBitSystem->getConfig( 'stars_icon_width', 22 );
			$ret['stars_load'] = TRUE;

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
	for( $i = 0; $i < $pStars; $i++) {
		$default_names[] = tra( "Rating" ) . ": " . ( $i+1 );
	}
	$default_names_flat = implode( ",", $default_names );
	$ratingNames = explode( ",", "," . $gBitSystem->getConfig( 'stars_rating_names', $default_names_flat ) );
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
			LEFT OUTER JOIN `".BIT_DB_PREFIX."stars_version` sts ON
				( lc.`content_id`=sts.`content_id` AND sts.`version` = 0 )
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
			LEFT OUTER JOIN `".BIT_DB_PREFIX."stars_version` sts ON
				( lc.`content_id`=sts.`content_id` AND sts.`version` = 0)
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
			LEFT JOIN `".BIT_DB_PREFIX."stars_version` sts ON
				( lc.`content_id`=sts.`content_id` AND sts.`version` = 0 )
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
#	$stars = new LibertyStars($pContentId);
#	$userRating = $stars->getUserRating($pContentId);
#	return (!empty($userRating));
	# Users can always rate.
	# If they rate more than once, only the last rate is used.
	return TRUE;
}

function stars_content_get_rating($pContentId) {
	$stars = new LibertyStars($pContentId);
	$lHash = array('content_id'=>intval($pContentId));
	$stars->calculateRating($lHash);
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
