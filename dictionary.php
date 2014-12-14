<?php
/**
 * Dictionary file por migration script
 * 
 * This file contains a tiny dictionary to map the relationships between
 * flyspray's database and redmine's database
 * 
 * @author Sergio Infante <sergio@neosergio.net>
 * @sponsor BeezNest Latino http://www.beeznest.com
 * @version 0.3
 * 
 * @package flyspray_to_redmine_migration
 */
/**
 * Array for the origin database - Flyspray
 * @global array $migrate_origin
 */
global $migrate_origin;
$migrate_origin = array(
	'users' => 'flyspray_users',
	'projects' => 'flyspray_projects',
	'tasks' => 'flyspray_tasks',
	'assigned' => 'flyspray_assigned',
	'attachments' => 'flyspray_attachments',
	'comments' => 'flyspray_comments',
	'members' => 'flyspray_assigned',
	'users_in_groups' => 'flyspray_users_in_groups',
	'groups' => 'flyspray_groups',
	'versions' => 'flyspray_list_version',
	'categories' => 'flyspray_list_category',
	'related' => 'flyspray_related',

);
/**
 * Array for the destiny database - Redmine
 * @global array $migrate_destiny
 */
global $migrate_destiny;
$migrate_destiny = array(
	'users' => 'users',
	'projects' => 'projects',
	'tasks' => 'issues',
	'assigned' => 'issues',
	'attachments' => 'attachments',
	'comments' => 'journals',
	'members' => 'members',
	'versions' => 'versions',
	'roles' => 'roles',
	'categories' => 'issue_categories',
	'watchers' => 'watchers',
	'issue_relations'=> 'issue_relations',
	'issue_statuses'=>'issue_statuses',
	'user_preferences'=>'user_preferences'
	
);

