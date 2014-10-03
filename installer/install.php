<?php

/**
* @package manifest file for Like Posts
* @version 1.6.1
* @author Joker (http://www.simplemachines.org/community/index.php?action=profile;u=226111)
* @copyright Copyright (c) 2014, Siddhartha Gupta
* @license http://www.mozilla.org/MPL/MPL-1.1.html
*/

/*
* Version: MPL 1.1
*
* The contents of this file are subject to the Mozilla Public License Version
* 1.1 (the "License"); you may not use this file except in compliance with
* the License. You may obtain a copy of the License at
* http://www.mozilla.org/MPL/
*
* Software distributed under the License is distributed on an "AS IS" basis,
* WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
* for the specific language governing rights and limitations under the
* License.
*
* The Initial Developer of the Original Code is
*  Joker (http://www.simplemachines.org/community/index.php?action=profile;u=226111)
* Portions created by the Initial Developer are Copyright (C) 2012
* the Initial Developer. All Rights Reserved.
*
* Contributor(s): Big thanks to all contributor(s)
* emanuele45 (https://github.com/emanuele45)
*
*/

if (file_exists(dirname(__FILE__) . '/SSI.php') && !defined('SMF'))
	require_once(dirname(__FILE__) . '/SSI.php');

elseif (!defined('SMF'))
	exit('<b>Error:</b> Cannot install - please verify you put this in the same place as SMF\'s index.php.');

global $smcFunc, $db_prefix, $sourcedir;

if (!array_key_exists('db_add_column', $smcFunc))
	db_extend('packages');

// Table structure for like posts
$tables = array(
	'like_post' => array (
		'columns' => array (
			array(
				'name' => 'id_like',
				'type' => 'int',
				'size' => 10,
				'unsigned' => true,
				'auto' => true,
			),
			array(
				'name' => 'id_msg',
				'type' => 'int',
				'size' => 10,
				'unsigned' => true,
				'default' => '0',
			),
			array(
				'name' => 'id_topic',
				'type' => 'mediumint',
				'size' => 8,
				'unsigned' => true,
				'default' => '0',
			),
			array(
				'name' => 'id_board',
				'type' => 'smallint',
				'size' => 5,
				'unsigned' => true,
				'default' => '0',
			),
			array(
				'name' => 'id_member_received',
				'type' => 'mediumint',
				'size' => 8,
				'unsigned' => true,
				'default' => '0',
			),
			array(
				'name' => 'id_member_gave',
				'type' => 'mediumint',
				'size' => 8,
				'unsigned' => true,
				'default' => '0',
			),
			array(
				'name' => 'rating',
				'type' => 'smallint',
				'size' => 1,
				'unsigned' => true,
				'default' => '0',
			),
			array(
				'name' => 'liked_timestamp',
				'type' => 'int',
				'size' => 10,
				'unsigned' => true,
				'default' => '0',
			),
		),
		'indexes' => array(
			array(
				'type' => 'primary',
				'columns' => array('id_like', 'id_msg', 'id_member_gave'),
			),
		),
	),
	'like_count' => array (
		'columns' => array (
			array(
				'name' => 'id_member',
				'type' => 'mediumint',
				'size' => 8,
				'unsigned' => true,
				'default' => '0',
			),
			array(
				'name' => 'like_count',
				'type' => 'mediumint',
				'size' => 8,
				'unsigned' => true,
				'default' => '0',
			),
		),
		'indexes' => array(
			array(
				'type' => 'primary',
				'columns' => array('id_member'),
			),
		),
	)
);

// create the tables if not created
foreach ($tables as $table => $data) {
	$smcFunc['db_create_table']('{db_prefix}' . $table, $data['columns'], $data['indexes']);
}
	
// Upgrade thinggy
// Changes made in v1.2
checkVersion1_2Upgrade();

// Changes made in v1.5
checkVersion1_5Upgrade();

// add update settings

lpAddUpdateSettings();

function lpAddUpdateSettings() {
	global $smcFunc;

	$request = $smcFunc['db_query']('', '
		SELECT * 
		FROM {db_prefix}settings
		WHERE variable =  {string:like_post_mod_version}
		LIMIT 1',
		array(
			'like_post_mod_version' => 'like_post_mod_version',
		)
	);

	if ($smcFunc['db_num_rows']($request) == 0) {
		// For all general settings add 'like_post_' as prefix
		updateSettings(array('like_post_mod_version' => '1.6.1', 'like_post_enable' => 1, 'like_per_profile_page' => 10, 'like_in_notification' => 10, 'lp_show_like_on_boards' => 1, 'lp_active_boards' => ''));
	} else {
		list ($last_version) = $smcFunc['db_fetch_row']($request);

		if (version_compare('1.6.1', $last_version) >= 0) {
			$smcFunc['db_query']('', '
				UPDATE {db_prefix}settings
				SET value = {string:current_version}
				WHERE variable = {string:like_post_mod_version}',
				array(
					'current_version' => '1.6.1',
					'like_post_mod_version' => 'like_post_mod_version'
				)
			);
		}
	}
	$smcFunc['db_free_result']($request);
}

if (SMF == 'SSI')
echo 'Database adaptation successful!';

function checkVersion1_2Upgrade() {
	global $smcFunc;

	$is_upgrade = true;
	$request = $smcFunc['db_query']('', '
		SHOW COLUMNS
		FROM {db_prefix}like_post',
		array(
		)
	);
	if ($request !== false) {
		while ($row = $smcFunc['db_fetch_assoc']($request)) {
			if ($row['Field'] == 'id_like' && ($row['Type'] == 'int(10) unsigned' || $row['Type'] == 'int(10)'))
				$is_upgrade = false;
		}
		$smcFunc['db_free_result']($request);
	}

	// If upgrade, fire the bullet
	if($is_upgrade === true) {
		$smcFunc['db_query']('', '
			ALTER TABLE {db_prefix}like_post
			CHANGE id_member id_member_gave mediumint (8) unsigned,
			Add column id_member_received mediumint (8) unsigned Default 0 AFTER id_member_gave',
			array(
			)
		);

		$smcFunc['db_query']('', '
			ALTER TABLE {db_prefix}like_post
			ADD id_like INT(10) unsigned NOT NULL AUTO_INCREMENT FIRST,
			DROP PRIMARY KEY,
			ADD PRIMARY KEY(id_like, id_msg, id_member_gave)',
			array(
			)
		);

		$request = $smcFunc['db_query']('', '
			SELECT lp.id_msg, m.id_member
			FROM {db_prefix}like_post as lp
			INNER JOIN {db_prefix}messages as m ON (m.id_msg = lp.id_msg)
			GROUP BY id_msg
			ORDER BY id_msg'
		);

		while ($row = $smcFunc['db_fetch_assoc']($request)) {
			$smcFunc['db_query']('', '
				UPDATE {db_prefix}like_post
				SET id_member_received = {int:id_member_received}
				WHERE id_msg = {int:id_msg}',
				array(
					'id_member_received' => (int) $row['id_member'],
					'id_msg' => $row['id_msg']
				)
			);
		}
		$smcFunc['db_free_result']($request);
	}
}

function checkVersion1_5Upgrade() {
	global $smcFunc;

	$is_upgrade = true;
	$request = $smcFunc['db_query']('', '
		SHOW COLUMNS
		FROM {db_prefix}like_post',
		array(
		)
	);
	if ($request !== false) {
		while ($row = $smcFunc['db_fetch_assoc']($request)) {
			if ($row['Field'] == 'liked_timestamp' && ($row['Type'] == 'int(10) unsigned' || $row['Type'] == 'int(10)'))
				$is_upgrade = false;
		}
		$smcFunc['db_free_result']($request);
	}

	// If upgrade, fire the bullet
	if($is_upgrade === true) {
		$smcFunc['db_query']('', '
			ALTER TABLE {db_prefix}like_post
			Add column liked_timestamp int (10) unsigned Default 0',
			array(
			)
		);
	}
}

?>