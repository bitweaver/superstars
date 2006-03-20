<?php
$tables = array(
	'stars' => "
		content_id I4 NOTNULL,
		rating_count I4,
		rating I4
		CONSTRAINT ', CONSTRAINT `stars_ref` FOREIGN KEY (`content_id`) REFERENCES `".BIT_DB_PREFIX."liberty_content`( `content_id` )'
	",
	'stars_history' => "
		content_id I4 NOTNULL,
		user_id I4 NOTNULL,
		rating I4 NOTNULL,
		points I4 NOTNULL,
		rating_time I8 NOTNULL DEFAULT 0
		CONSTRAINT '
			, CONSTRAINT `stars_history_content_ref` FOREIGN KEY (`content_id`) REFERENCES `".BIT_DB_PREFIX."liberty_content`( `content_id` )
			, CONSTRAINT `stars_history_user_ref` FOREIGN KEY (`user_id`) REFERENCES `".BIT_DB_PREFIX."users_users`( `user_id` )'
	",
);

global $gBitInstaller;

foreach( array_keys( $tables ) AS $tableName ) {
	$gBitInstaller->registerSchemaTable( STARS_PKG_NAME, $tableName, $tables[$tableName] );
}

$gBitInstaller->registerPackageInfo( STARS_PKG_NAME, array(
	'description' => "A ratings package that allows users to rate any content using a basic interface.",
	'license' => '<a href="http://www.gnu.org/licenses/licenses.html#LGPL">LGPL</a>',
) );

// ### Default UserPermissions
$gBitInstaller->registerUserPermissions( STARS_PKG_NAME, array(
//	array( 'bit_p_admin_stars', 'Can admin stars', 'admin', STARS_PKG_NAME ),
//	array( 'bit_p_remove_stars', 'Can delete stars', 'admin',  STARS_PKG_NAME ),
) );

// ### Default Preferences
$gBitInstaller->registerPreferences( STARS_PKG_NAME, array(
	//array( STARS_PKG_NAME, "stars_display_width", "125" ),
	array( STARS_PKG_NAME, "stars_used_in_display", "5" ),
	array( STARS_PKG_NAME, "stars_minimum_ratings", "5" ),
	array( STARS_PKG_NAME, "stars_user_points", "y" ),
	array( STARS_PKG_NAME, "stars_weight_age", "1" ),
	array( STARS_PKG_NAME, "stars_weight_permission", "1" ),
	array( STARS_PKG_NAME, "stars_weight_activity", "1" ),
) );
?>
