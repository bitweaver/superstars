<?php
$tables = array(
	'stars_version' => "
		content_id I4 NOTNULL PRIMARY,
		version I4 NOTNULL PRIMARY,
		rating_count I4,
		rating I4,
		update_count I4 DEFAULT 0;
		CONSTRAINT '
			, CONSTRAINT `stars_version_ref` FOREIGN KEY (`content_id`) REFERENCES `".BIT_DB_PREFIX."liberty_content`( `content_id` )'
	",
	'stars_history' => "
		content_id I4 NOTNULL PRIMARY,
		version I4 NOTNULL PRIMARY,
		user_id I4 NOTNULL PRIMARY,
		rating I4 NOTNULL,
		weight I4 NOTNULL,
		rating_time I8 NOTNULL DEFAULT 0,
		update_count I4 DEFAULT 0
		CONSTRAINT '
			, CONSTRAINT `stars_history_content_ref` FOREIGN KEY (`content_id`) REFERENCES `".BIT_DB_PREFIX."liberty_content`( `content_id` )
			, CONSTRAINT `stars_history_user_ref` FOREIGN KEY (`user_id`) REFERENCES `".BIT_DB_PREFIX."users_users`( `user_id` )'
	",
);

global $gBitInstaller;

foreach( array_keys( $tables ) AS $tableName ) {
	$gBitInstaller->registerSchemaTable( STARS_PKG_NAME, $tableName, $tables[$tableName] );
}

$indices = array (
	'stars_users_idx' => array( 'table' => 'stars_history', 'cols' => 'user_id', 'opts' => NULL ),
);



$gBitInstaller->registerSchemaIndexes( STARS_PKG_NAME, $indices );


$gBitInstaller->registerPackageInfo( STARS_PKG_NAME, array(
	'description' => "A ratings package that allows users to rate any content using a basic interface.",
	'license' => '<a href="http://www.gnu.org/licenses/licenses.html#LGPL">LGPL</a>',
) );

// ### Default UserPermissions
$gBitInstaller->registerUserPermissions( STARS_PKG_NAME, array(
//	array( 'p_stars_vote', 'Can commit their vote', 'registered',  STARS_PKG_NAME ),
) );

// ### Default Preferences
$gBitInstaller->registerPreferences( STARS_PKG_NAME, array(
	//array( STARS_PKG_NAME, "stars_display_width", "125" ),
	array( STARS_PKG_NAME, "stars_used_in_display", "5" ),
	array( STARS_PKG_NAME, "stars_minimum_ratings", "5" ),
	array( STARS_PKG_NAME, "stars_user_weight", "y" ),
	array( STARS_PKG_NAME, "stars_weight_age", "5" ),
	array( STARS_PKG_NAME, "stars_weight_permission", "5" ),
	array( STARS_PKG_NAME, "stars_weight_activity", "5" ),
	array( STARS_PKG_NAME, "stars_icon_width", "22" ),
	array( STARS_PKG_NAME, "stars_icon_height", "22" ),
) );
?>
