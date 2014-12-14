<?php
/**
 * Main file for migration database script Flyspray to Redmine
 * @author Sergio Infante <sergio@neosergio.net>
 * @author Julio Montoya <julio.montoya@beeznest.com> several and real implementation
 * @sponsor BeezNest Latino http://www.beeznest.com
 * @version 2.0
 * @date 21-Jul-2009
 */

/**
 * Set initial values for php.ini
 */
ini_set('memory_limit', '200M');
ini_set('max_execution_time', 10000);

$run_all = true; // if false nothing happens

// custom origin DB status
$new_fp_users= array();	// list of users with new ids

/* The following settings may be used and <> 0 if Redmine install already
 * defines some users
 */
// Define the last user id registered in Redmine
$last_redmine_user_id = 298; 
// last user id registered in Flyspray
$last_flyspray_user_id = 320;
// Redmine and Flyspray user's table are the same up to user $difference_id - 1
$difference_id = 283;
$same_id = $difference_id -1;

// Don't modified this
// The number of "versions" that we have in the original DB/Flyspray
$start_version =  75;
// The number of "categories" in the original DB/Flyspray (General, Social, etc)
$start_category =  257;

/**
 * Flyspray db connection
 * @global resource $db_fs
 */
global $db_fs;
$db_fs = mysql_connect('localhost','root','pass');
/**
 * Redmine db connection
 * @global resource $db_rm
 */
global $db_rm;
$db_rm = mysql_connect('localhost','root','pass');

/**
 * include dictionary file to understand databases
 */
include ('dictionary.php');

/**
 * Unix datetime conversion function
 */
function get_datetime($time=null) {
	if (empty($time)) {
		return '';
	}elseif (!isset($time)) { 
		$time = time();
	}
	return date('Y-m-d H:i:s', $time);
}
/*
 * Unix date conversion function
 */
function get_date($time=null) {
	if (empty($time)) {
		return '';
	}elseif (!isset($time)) { 
		$time = time();
	}
	return date('Y-m-d', $time);
}



// Tasks are in the same redmine project or not
/* @param int task id
   @param int task id
   @return bool 
*/
function same_project($id,$id2) {
	global $db_rm;	
	if (!empty($id) && !empty($id2)) {
		$sql = "select project_id from issues where id = $id";
		mysql_select_db('redmine',$db_rm);
		$results = mysql_query($sql,$db_rm);
		$row = mysql_fetch_array($results);	
		$sql = "select project_id from issues where id = $id2";
		mysql_select_db('redmine',$db_rm);
		$results = mysql_query($sql,$db_rm);
		$row2 = mysql_fetch_array($results);
		if ($row[project_id] == $row2[project_id])
			return true;
		else
			return false;
	}
	return false;
}

// In case we need to update the user preferences
$time_zone_fix = array('-12'=>"",
'-11'=>"International Date Line West",'-10'=>"Hawaii",
'-9'=>"Alaska",'-8'=>"Pacific Time (US & Canada)",'-7'=>"Arizona",
'-6'=>"Central America",'-5'=>"Lima",'-4'=>"Santiago",'-3'=>"Buenos Aires",
'-2'=>"Mid-Atlantic",
'-1'=>"Azores",
'0'=>"London",
'1'=>"Brussels",
'2'=>"Kyev",
'3'=>"Moscow",
'4'=>"Abu Dhabi",
'5'=>"Ekaterinburg",
'6'=>"Dhaka",
'7'=>"Jakarta",
'8'=>"Beijing",
'9'=>"Osaka",
'10'=>"Melbourne",
'11'=>"Magadan",
'12'=>"Fiji",
'13'=>"Nuku'alofa");

for ($j=$difference_id; $j<=$last_flyspray_user_id;$j++) {
	$last_redmine_user_id++; // the first redmine user id
	$new_fp_users[$j]=$last_redmine_user_id;
}

echo '<h1>Redmine to Flyspray migration</h1><br/>';


/**
 * USERS
 */
 
// for check that all works fine
$sql = "UPDATE $migrate_destiny[users] SET admin = '1' where login = 'jmontoya'";
		
mysql_select_db('redmine',$db_rm);	
mysql_query($sql,$db_rm);  	   
	    
if ($run_all) {
	mysql_select_db('flyspray',$db_fs);
	$sql_users_origin = "SELECT * FROM $migrate_origin[users] where user_id >= $difference_id";
	$results = mysql_query($sql_users_origin,$db_fs);
	while ($row = mysql_fetch_array($results)) {
	    // fixing user names
		$pos =strpos($row[real_name],' ');
		if ($pos===false) {
			$first_name = $row[real_name];
			$last_name = '';
		} else {
			$first_name = substr($row[real_name],0,$pos);		
			$last_name = substr($row[real_name],$pos+1,strlen($row[real_name]));
		}
	    //printf("ID: %s  Login: %s First Name: %s Last Name: %s ", $row["user_id"], $row["user_name"] ,$row["real_name"] ,$row["real_name"]);
	    //printf("Email: %s Password: %s <br/>", $row["email_address"] ,$row["user_pass"]);
	    $is_admin = 0;
	    if ($row[user_name]=='jmontoya')
		    $is_admin = 1;
		$account_status = 1;
		if ($row[account_enabled]==0){
			$account_status = 3; //locked
		}
		$new_user_id = $new_fp_users[$row[user_id]];
	    $sql = "INSERT INTO $migrate_destiny[users] (id, login, hashed_password, firstname, lastname, mail, mail_notification, admin, status, last_login_on, language, auth_source_id, created_on, updated_on, type) 
	    		VALUES ('$new_user_id ', '$row[user_name]', '$row[user_pass]', '$first_name', '$last_name', '$row[email_address]', '1', '$is_admin', '$account_status', NULL, 'en', NULL, NULL, NULL, 'User')";
	    mysql_select_db('redmine',$db_rm);	
	    mysql_query($sql,$db_rm);  	   
	    
		// setup user preferences	    
	/*    $new_time_zone = $time_zone_fix[$row[time_zone]];
	    $sql = "INSERT INTO $migrate_destiny[user_preferences] (user_id, time_zone) 
	    		VALUES ('$row[user_id]', '$new_time_zone') ";
	
	    mysql_select_db('redmine',$db_rm);	
	    mysql_query($sql,$db_rm);  	   	    
    */
	}
	print "<br/>All users loaded<br/>";
}


/**
 * Sql statements Creating roles for Redmine

5 Disable = Non Member
38 Desarrolladores = Developer
52 48 2 27 9 12 34 36 Developers  =Developer
45 46 Extra developers = Developer
31 dev2_group = Developer

3 Reporters = Reporter
4 Basic = Reporter
32 e-learning = Reporter
41 VABX = Reporter
39 Leo Reporters = Reporter

Project Managers = Manager
50 11 16 Bankpost = customer
11 16 NEW Customers customers
*/

//5 Disable = Non Member

//flyspray roles id that will be convert to Developer/Reporter/ ... etc from Redmine
$developer_array = array(50,38,52,48,2,27,9,12,34,36,45,46,31);
$reporter_array  = array(32,41,39,3,4);
$customer_array  = array(11,16);
$manager_array   = array(1,7,8 ,10, 14, 15, 17, 18, 19, 20, 21, 22 ,29 ,35 ,37,51,49,47,43,44,42,40);

// CREATING A NEW ROLE - CUSTOMER
if ($run_all) {
	$role = 'Customer';
	$sql_role = "INSERT INTO $migrate_destiny[roles] (name, position, assignable, builtin, permissions) 
				 VALUES ('$role', '6','1', '0', '--- 
- :add_messages
- :view_documents
- :view_files
- :add_issues
- :add_issue_notes
- :save_queries
- :view_gantt
- :view_calendar
- :comment_news
- :browse_repository
- :view_changesets
- :view_time_entries
- :view_wiki_pages
- :view_wiki_edits
')";	

	mysql_select_db('redmine',$db_rm);	
	$res = mysql_query($sql_role,$db_rm);	
	print "<br/>All roles loaded $res <br/>";	 
}

// CREATING A NEW STATUS
if ($run_all) {		
	$new_status = array(7=>'Unconfirmed',8=>'Researching',9=>'Waiting on Customer',10=>'Requires testing');
	foreach ($new_status as $id=>$item) {
		$sql = "INSERT INTO $migrate_destiny[issue_statuses] (name, is_closed, is_default, position) 
			    VALUES ('$item', '0', '0', $id)";				
		mysql_select_db('redmine',$db_rm);	
		mysql_query($sql,$db_rm);
	}
	print "<br/>New status loaded<br/>";	 
}

// CREATING SEVERITY - Custom fields
if ($run_all) {
 	$sql = "INSERT INTO custom_fields (type,name, field_format,
	possible_values,`regexp`,min_length,max_length,is_required,
	is_for_all, is_filter, position,searchable, default_value) 
	VALUES('IssueCustomField','Severity', 'list', '---
	Very low
	Low
	Medium
	High
	Critical', '','0','0','0','1','1','1','1','Low')";
		mysql_select_db('redmine',$db_rm);	
		mysql_query($sql,$db_rm);
	// adding to the custom_fields_trackers table
	$sql = "INSERT INTO custom_fields_trackers(custom_field_id, tracker_id) 
			VALUES ('1','1')";
	mysql_select_db('redmine',$db_rm);	
	mysql_query($sql,$db_rm);
	print "<br/>Severity created<br/>";	 		
}


/**
 PROJECTS -  PROJECTS
*/
$private_projects_list = array();

if ($run_all) {
	mysql_select_db('flyspray',$db_fs);
	$sql_projects_origin = "SELECT * FROM $migrate_origin[projects]";
	$results = mysql_query($sql_projects_origin,$db_fs);
	
	$private_project = 29;
	while ($row = mysql_fetch_array($results, MYSQL_ASSOC)) {
		$project_id_fixed = $row[project_id]+4;
	   	echo $project_id_fixed ,' - ', $row[project_title].'<br />';
	   	$search = array('/',' ','é','.');
	   	$replace = array('-','-','e','-');
	   	  	
	   	$project_id = str_replace($search,$replace, $row[project_title]);
	   	
	    $sql_projects_destiny = "INSERT INTO $migrate_destiny[projects] (id, name, description, homepage, is_public, parent_id, projects_count, created_on, updated_on, identifier, status) 
				      VALUES ('$project_id_fixed', '$row[project_title]', '$row[intro_message]', '', '1', NULL, '0', NULL, NULL, '".$project_id."', '1')"; 
		//enable 8 modules for projects: issue_tracking, time_tracking, news, documents, files, wiki, repository, boards
		$sql_projects_destiny1 = "INSERT INTO enabled_modules (id,project_id,name) VALUES (NULL, '$project_id_fixed', 'issue_tracking')";
		$sql_projects_destiny2 = "INSERT INTO enabled_modules (id,project_id,name) VALUES (NULL, '$project_id_fixed', 'time_tracking')";
		$sql_projects_destiny3 = "INSERT INTO enabled_modules (id,project_id,name) VALUES (NULL, '$project_id_fixed', 'news')";
		$sql_projects_destiny4 = "INSERT INTO enabled_modules (id,project_id,name) VALUES (NULL, '$project_id_fixed', 'documents')";
		$sql_projects_destiny5 = "INSERT INTO enabled_modules (id,project_id,name) VALUES (NULL, '$project_id_fixed', 'files')";
		$sql_projects_destiny6 = "INSERT INTO enabled_modules (id,project_id,name) VALUES (NULL, '$project_id_fixed', 'wiki')";
		$sql_projects_destiny7 = "INSERT INTO enabled_modules (id,project_id,name) VALUES (NULL, '$project_id_fixed', 'repository')";
		$sql_projects_destiny8 = "INSERT INTO enabled_modules (id,project_id,name) VALUES (NULL, '$project_id_fixed', 'boards')";
		//enable 3 trackers for projects
		$sql_projects_destiny9 = "INSERT INTO projects_trackers (project_id,tracker_id) VALUES ('$project_id_fixed', '1')";
		$sql_projects_destiny10 = "INSERT INTO projects_trackers (project_id,tracker_id) VALUES ('$project_id_fixed', '2')";
		$sql_projects_destiny11 = "INSERT INTO projects_trackers (project_id,tracker_id) VALUES ('$project_id_fixed', '3')";
		//sql statements execution
		mysql_select_db('redmine',$db_rm);
		
		mysql_query($sql_projects_destiny,$db_rm);
		mysql_query($sql_projects_destiny1,$db_rm);
		mysql_query($sql_projects_destiny2,$db_rm);
		mysql_query($sql_projects_destiny3,$db_rm);
		mysql_query($sql_projects_destiny4,$db_rm);
		mysql_query($sql_projects_destiny5,$db_rm);
		mysql_query($sql_projects_destiny6,$db_rm);
		mysql_query($sql_projects_destiny7,$db_rm);
		mysql_query($sql_projects_destiny8,$db_rm);
		mysql_query($sql_projects_destiny9,$db_rm);
		mysql_query($sql_projects_destiny10,$db_rm);
		mysql_query($sql_projects_destiny11,$db_rm);
				
		//create private projects
		$private_projects_list[$row[project_id]] = $private_project;
		
		$title = $row[project_title].' (*)';
		$sql_projects_destiny = "INSERT INTO $migrate_destiny[projects] (id, name, description, homepage, is_public, parent_id, projects_count, created_on, updated_on, identifier, status) 
				      VALUES ('$private_project', '$title', '$row[intro_message]', '', '0', NULL, '0', NULL, NULL, 'h".$private_project."', '1')"; 
		//enable 8 modules for projects: issue_tracking, time_tracking, news, documents, files, wiki, repository, boards
		$sql_projects_destiny1 = "INSERT INTO enabled_modules (id,project_id,name) VALUES (NULL, '$private_project', 'issue_tracking')";
		$sql_projects_destiny2 = "INSERT INTO enabled_modules (id,project_id,name) VALUES (NULL, '$private_project', 'time_tracking')";
		$sql_projects_destiny3 = "INSERT INTO enabled_modules (id,project_id,name) VALUES (NULL, '$private_project', 'news')";
		$sql_projects_destiny4 = "INSERT INTO enabled_modules (id,project_id,name) VALUES (NULL, '$private_project', 'documents')";
		$sql_projects_destiny5 = "INSERT INTO enabled_modules (id,project_id,name) VALUES (NULL, '$private_project', 'files')";
		$sql_projects_destiny6 = "INSERT INTO enabled_modules (id,project_id,name) VALUES (NULL, '$private_project', 'wiki')";
		$sql_projects_destiny7 = "INSERT INTO enabled_modules (id,project_id,name) VALUES (NULL, '$private_project', 'repository')";
		$sql_projects_destiny8 = "INSERT INTO enabled_modules (id,project_id,name) VALUES (NULL, '$private_project', 'boards')";
		//enable 3 trackers for projects
		$sql_projects_destiny9  = "INSERT INTO projects_trackers (project_id,tracker_id) VALUES ('$private_project', '1')";
		$sql_projects_destiny10 = "INSERT INTO projects_trackers (project_id,tracker_id) VALUES ('$private_project', '2')";
		$sql_projects_destiny11 = "INSERT INTO projects_trackers (project_id,tracker_id) VALUES ('$private_project', '3')";
		//sql statements execution
		mysql_select_db('redmine',$db_rm);
		
		mysql_query($sql_projects_destiny,$db_rm);
		mysql_query($sql_projects_destiny1,$db_rm);
		mysql_query($sql_projects_destiny2,$db_rm);
		mysql_query($sql_projects_destiny3,$db_rm);
		mysql_query($sql_projects_destiny4,$db_rm);
		mysql_query($sql_projects_destiny5,$db_rm);
		mysql_query($sql_projects_destiny6,$db_rm);
		mysql_query($sql_projects_destiny7,$db_rm);
		mysql_query($sql_projects_destiny8,$db_rm);
		mysql_query($sql_projects_destiny9,$db_rm);
		mysql_query($sql_projects_destiny10,$db_rm);
		mysql_query($sql_projects_destiny11,$db_rm); 	
			
		// Creating new versions for projects
		mysql_select_db('flyspray',$db_fs);
		$sql = "SELECT project_id, version_id, version_name FROM $migrate_origin[versions] where project_id =  $row[project_id]";
		$result_version = mysql_query($sql,$db_fs);
		
				
		while ($row_version = mysql_fetch_array($result_version, MYSQL_ASSOC)) {
		
			$private_version_list[$row_version[version_id]] = $start_version;
			
		    $sql_versions_destiny="INSERT INTO $migrate_destiny[versions] (id, project_id, name, description, effective_date, created_on, updated_on, wiki_page_title) 
		   			   VALUES ('$row_version[version_id]','$project_id_fixed', '$row_version[version_name]', '', NULL , NULL , NULL , NULL)";
		    mysql_select_db('redmine',$db_rm);
		    mysql_query($sql_versions_destiny,$db_rm);

		    // for private projects 
   		    $sql_versions_destiny="INSERT INTO $migrate_destiny[versions] (id, project_id, name, description, effective_date, created_on, updated_on, wiki_page_title) 
		   			   VALUES ('$start_version','$private_project', '$row_version[version_name]', '', NULL , NULL , NULL , NULL)";
		   	//		   echo '<br><br><br>';
		    mysql_select_db('redmine',$db_rm);
		    mysql_query($sql_versions_destiny,$db_rm); 
		    $start_version ++;
		}
		print "<br/>All versions loaded for $project_id_fixed <br/>";
		
		// Creating new categories for projects
		// just what I needed: 
		// http://stackoverflow.com/questions/1015595/build-dynamic-menu-using-nested-sets
		mysql_select_db('flyspray',$db_fs);
		$sql = "SELECT node.category_id, GROUP_CONCAT(parent.category_name ORDER BY parent.lft  SEPARATOR \"/\" ) AS path, (COUNT(parent.lft) - 1) AS depth 
		FROM  $migrate_origin[categories] AS node, $migrate_origin[categories] AS parent 
		WHERE (node.project_id = $row[project_id] and parent.project_id=$row[project_id]) and node.lft BETWEEN parent.lft AND parent.rgt AND parent.lft > 1 
		GROUP BY node.category_id ORDER BY node.lft";
		
		$result_cat = mysql_query($sql,$db_rm);			
		while ($row_cat = mysql_fetch_array($result_cat, MYSQL_ASSOC)) {
		
			$private_category_list[$row_cat[category_id]] = $start_category;
			
			$sql="INSERT INTO $migrate_destiny[categories] (id, project_id, name) 
		   		  VALUES ('$row_cat[category_id]','$project_id_fixed', '$row_cat[path]')";
			mysql_select_db('redmine',$db_rm);
			mysql_query($sql,$db_rm);
				
			// for private projects
			$sql="INSERT INTO $migrate_destiny[categories] (id, project_id, name) 
		   		  VALUES ('$start_category','$private_project', '$row_cat[path]')";
			mysql_select_db('redmine',$db_rm);
			mysql_query($sql,$db_rm);		
			$start_category++;	
		}
		print "<br/>All categories loaded for $project_id_fixed <br />";		
		print "All categories loaded for $private_project <br />";			
		$private_project++;		
	}
}

/**
 USERS & PROJECTS - MEMBERS
 */
if ($run_all) {
	mysql_select_db('redmine',$db_rm);
	$sql = "ALTER TABLE members ADD UNIQUE control ( user_id , project_id , role_id )  ";
	mysql_query($sql,$db_rm);

	$sql = "ALTER TABLE watchers ADD UNIQUE control_watch (watchable_id ,user_id)  ";
	mysql_query($sql,$db_rm);

	$sql_members_origin = "SELECT group_id, group_name FROM $migrate_origin[groups]";
	mysql_select_db('flyspray',$db_fs);
	$results = mysql_query($sql_members_origin,$db_fs);
	print "<br/>Members<br/>";
	while ($row = mysql_fetch_array($results, MYSQL_ASSOC)) {
		$sql ="SELECT user_id, project_id FROM  $migrate_origin[users_in_groups] ug inner join  $migrate_origin[groups] g 
		       on (ug.group_id = g.group_id) WHERE g.group_id = $row[group_id]";
		       
		$role_id = 5; // worst case - Reporter
	
		if ($row[group_id] == 5)
			$role_id = 1; //Non member
		if (in_array($row[group_id], $manager_array))
			$role_id = 3; // Manager
		if (in_array($row[group_id], $developer_array))
			$role_id = 4; // Developer
		if (in_array($row[group_id],$reporter_array))
			$role_id = 5; // Reporter
		if (in_array($row[group_id], $customer_array))
			$role_id = 6; //customer

		mysql_select_db('flyspray',$db_fs);
		$results0 = mysql_query($sql,$db_fs);	
		while ($row1 = mysql_fetch_array($results0, MYSQL_ASSOC)) {
			if ($row1[project_id]!=0){
				$project_id_fixed = $row1[project_id]+4;
				$new_user_id = $row1[user_id];
				if ($row1[user_id]>$same_id) {
					$new_user_id = $new_fp_users[$row1[user_id]];
				}
					
				$sql_members_destiny = "INSERT INTO members (user_id,project_id,role_id,created_on,mail_notification) 
				VALUES ('$new_user_id', '$project_id_fixed', '$role_id ', NULL, '0')";	
				mysql_select_db('redmine',$db_rm);		
				mysql_query($sql_members_destiny,$db_rm);
			}
		}
	}
	print "<br/>All members are loaded<br/>";	
}

/**
	TASK - ISSUES
 */
if ($run_all) {
	$sql_tasks_origin = "SELECT * FROM $migrate_origin[tasks] ";
	mysql_select_db('flyspray',$db_fs);
	$results = mysql_query($sql_tasks_origin,$db_fs);
	while ($row = mysql_fetch_array($results, MYSQL_ASSOC)) {
		echo $row[task_id].'<br>';
//	    printf("Id: %s Tracker: %s Project: %s Subject: %s Description: %s Author: %s <br/>", $row[task_id], $row[task_type], $row[project_id] ,$row[item_summary], $row[detailed_desc] ,$row[opened_by]);
//	    printf("Status: %s Priority: %s <br/>", $row[item_status], $row[task_priority]);
//	    printf("Created On: %s Last Edited Time: %s Due date: %s <br/>", get_datetime($row[date_opened]), get_datetime($row[last_edited_time]), get_date($row[due_date]));

		if ($row[task_type]==12) {
			$row[task_type]=3; 
		}
		
	    if ($row[task_type]==0||$row[task_type]==4||$row[task_type]==5||$row[task_type]==6||$row[task_type]==7||$row[task_type]==8||$row[task_type]==9||$row[task_type]==10){
	    	$row[task_type]=2; 
		}
		if ($row[task_type]==17||$row[task_type]==18||$row[task_type]==19||$row[task_type]==21||$row[task_type]==22||$row[task_type]==23||$row[task_type]==28){
	    	$row[task_type]=2;
		}
		$sql_tasks_origin0 = "SELECT user_id FROM $migrate_origin[assigned] 
				     		  WHERE task_id='$row[task_id]' ";

	    mysql_select_db('flyspray',$db_fs);
	    $results0 = mysql_query($sql_tasks_origin0,$db_fs);

		$row[item_summary] =  mysql_real_escape_string($row[item_summary]);
		$row[detailed_desc] = mysql_real_escape_string($row[detailed_desc]);
		
		//$new_status = array(7=>'Unconfirmed',8=>'Researching',9=>'Waiting on Customer',10=>'Requires testing');
		switch ($row[item_status]) {
			case 1 : //unconfirmed
			$new_status = 7;  
			break;
			case 2 : //new
			$new_status = 1;  
			break;					
			case 3 : //assigned
			$new_status = 2;  			
			break;		
			case 4 : //researching
			$new_status = 8;  		
			break;		
			case 5 : //waiting
			$new_status = 9;  					
			break;		
			case 6 : // REquires
			$new_status = 10;  					
			break;
			default:
			$new_status = 2; //assigned
			break;
		}
				
		switch ($row[task_severity]) {
			case 1 : // low
			$new_sev= 'Very Low';  
			break;
			case 2 : // 
			$new_sev = 'Low';  
			break;
			case 3 : // 
			$new_sev = 'Medium';  
			break;
			case 4 : // 
			$new_sev = 'High';  
			break;			
			case 5 : // 
			$new_sev = 'Critical';  
			break;
			default:
			$new_sev = 'Low';  
			break;
		}				
		$sql = "INSERT INTO custom_values (customized_type, customized_id, custom_field_id, value)
				VALUES ('Issue', '$row[task_id]','1', '$new_sev')";
	    mysql_select_db('redmine',$db_rm); 
	    $res_sev = mysql_query($sql,$db_rm);
		$project_id_fixed = $row[project_id]+4;
		
		$category_id_fixed = 	$row[product_category];
		$version_id_fixed  =  	$row[product_version];

		if ($row[mark_private]==1) {
			$project_id_fixed = $private_projects_list[$row[project_id]];

			$category_id_fixed = $private_category_list[$category_id_fixed];
			$version_id_fixed  = $private_version_list[$version_id_fixed];
			
		}
		$opened_by = $row[opened_by];
		if ($row[opened_by]>$same_id) {
				$opened_by = $new_fp_users[$row[opened_by]];
		}
			
			
		if (empty($row[due_date])) //include 0
	    {
//			echo 'due_date'.$row[due_date]; echo '<br>';
	//		echo 'date_opened'.$row[date_opened]; echo '<br>';
			$due_date = get_date($row[date_opened]);
		}
		else
			$due_date = get_date($row[due_date]); 
//		echo '<br><br/>';
			
	    $i = 0;
	    if (mysql_num_rows($results0)>0) {
			while ($row1 = mysql_fetch_array($results0, MYSQL_ASSOC)) {
				// if is closed we send to the private project				
				
				$new_user_id = $row1[user_id];
				if ($row1[user_id]>$same_id) {
					$new_user_id = $new_fp_users[$row1[user_id]];
				}

				if ($i==0) {
					// save the real user assigned
					$sql_tasks_destiny = "INSERT INTO $migrate_destiny[tasks] (id,tracker_id, project_id, subject, description, due_date, category_id, status_id, assigned_to_id, priority_id, fixed_version_id, author_id, lock_version, created_on, updated_on, start_date, done_ratio, estimated_hours) 
					VALUES ('$row[task_id]', '$row[task_type]', '$project_id_fixed', '$row[item_summary]', '$row[detailed_desc]','".$due_date."', '$category_id_fixed', '$new_status', '$new_user_id', '$row[task_priority]', '$version_id_fixed', '$opened_by', '0','".get_datetime($row[date_opened])."', '".get_datetime($row[last_edited_time])."', '".get_date($row[date_opened])."', '".$row[percent_complete]."', NULL)";

					mysql_select_db('redmine',$db_rm);
					mysql_query($sql_tasks_destiny,$db_rm);		
					//echo '<br />'; 
				} else {
					// create watchers
					$sql_tasks_destiny = "INSERT INTO $migrate_destiny[watchers] (watchable_type, watchable_id, user_id) 
										  VALUES ('Issue', '$row[task_id]', '$new_user_id')";
					mysql_select_db('redmine',$db_rm);
					mysql_query($sql_tasks_destiny,$db_rm);		
				}
				$i++;
			} 
		} else {						
			// no assigned
			$sql_tasks_destiny = "INSERT INTO $migrate_destiny[tasks] (id,tracker_id, project_id, subject, description, due_date, category_id, status_id, assigned_to_id, priority_id, fixed_version_id, author_id, lock_version, created_on, updated_on, start_date, done_ratio, estimated_hours) 
					VALUES ('$row[task_id]', '$row[task_type]', '$project_id_fixed', '$row[item_summary]', '$row[detailed_desc]','".$due_date."', '$category_id_fixed', '$new_status', '', '$row[task_priority]', '$version_id_fixed', '$opened_by', '0','".get_datetime($row[date_opened])."', '".get_datetime($row[last_edited_time])."', '".get_date($row[date_opened])."', '".$row[percent_complete]."', NULL)";
					mysql_select_db('redmine',$db_rm);
					mysql_query($sql_tasks_destiny,$db_rm);		
		}
//			    echo '<br>';	    echo '<br>';
	}
    print "<br/>Issues loaded<br/>";
}

/* 
 Creates related tasks
*/
if ($run_all) {
	$sql = "SELECT * FROM $migrate_origin[related]";
	mysql_select_db('flyspray',$db_fs);
	$results = mysql_query($sql,$db_fs);
	while ($row = mysql_fetch_array($results, MYSQL_ASSOC)){
		if (same_project($row['this_task'],$row['related_task'])) {	
			$insert = "INSERT INTO issue_relations (issue_from_id, issue_to_id, relation_type) 
				   VALUES ('$row[this_task]', '$row[related_task]', 'relates')";
			mysql_select_db('redmine',$db_rm);
			mysql_query($insert,$db_rm);	
		}
	}
    print "<br/>Related task loaded<br/>";
}

/**
 COMMENTS
 */
if ($run_all) {
	$sql_comments_origin = "SELECT * FROM $migrate_origin[comments]";
	mysql_select_db('flyspray',$db_fs);
	$results = mysql_query($sql_comments_origin,$db_fs);
	while ($row = mysql_fetch_array($results, MYSQL_ASSOC)) {
		$date_added = get_datetime($row[date_added]);
			
		$new_user_id = $row[user_id];
		if ($row[user_id]>$same_id) {
			$new_user_id = $new_fp_users[$row[user_id]];
		}
		$row[comment_text] = mysql_real_escape_string($row[comment_text]);
		$sql= "INSERT INTO journals (id, journalized_id, journalized_type, user_id, notes, created_on) 
			VALUES ('$row[comment_id]', '$row[task_id]', 'Issue', '$new_user_id', '$row[comment_text]', '$date_added')";
	if ($row[task_id]==4249) {
		echo $sql;
		echo '<br>'	;
		echo "'$row[comment_id]', '$row[task_id]', 'Issue', '$new_user_id'";
		echo '<br>'	;
	}
		mysql_select_db('redmine',$db_rm);
		$res = mysql_query($sql,$db_rm);
		
		if ($row[task_id]==4249) {
			echo $res;
		}
	
	}
	print "<br/>Comments loaded<br/>";
}


/**
	ATTACHMENTS
 */
if ($run_all) {
	mysql_select_db('flyspray',$db_fs);
	$sql_attachments_origin = "SELECT * FROM $migrate_origin[attachments]";
	$results = mysql_query($sql_attachments_origin,$db_fs);
	while ($row = mysql_fetch_array($results, MYSQL_ASSOC)){
		$sql_attachments_origin0="SELECT project_id FROM $migrate_origin[tasks] WHERE task_id='$row[task_id]' ";
		mysql_select_db('flyspray',$db_fs);
		$results0=mysql_query($sql_attachments_origin0,$db_fs);
		
		$added_by = $row[added_by];
		if ($row[added_by]>$same_id) {
			$added_by = $new_fp_users[$added_by];
		}
		while ($row1 = mysql_fetch_array($results0, MYSQL_ASSOC)){		
			$created_on = get_datetime($row[date_added]);
			$sql="INSERT INTO attachments (id, container_id, container_type, filename, disk_filename, filesize, content_type, digest, downloads, author_id, created_on, description) 
			VALUES ('$row[attachment_id]', '$row[task_id]','Issue', '$row[orig_name]','$row[file_name]', '$row[file_size]','$row[file_type]', '', '0', '$added_by','$created_on', '')";
			mysql_select_db('redmine',$db_rm);
			mysql_query($sql,$db_rm);
			
			// the files from the task not from the comments
			if ($row[comment_id] != 0) {
			$sql="INSERT INTO journal_details (journal_id, property, prop_key,value) 
			VALUES ('$row[comment_id]','attachment','$row1[attachment_id]', '$row[orig_name]')";
				mysql_select_db('redmine',$db_rm);
				mysql_query($sql,$db_rm);
			}			
		}
	}
	print "<br/>Attachments loaded<br/>";
}


// CREATE CLOSE MESSAGES
if ($run_all) {
	$sql_tasks_origin = "SELECT * FROM $migrate_origin[tasks] WHERE is_closed = 1 and closure_comment != ''";
	mysql_select_db('flyspray',$db_fs);
	$results = mysql_query($sql_tasks_origin,$db_fs);
	while ($row = mysql_fetch_array($results, MYSQL_ASSOC)) {
		$date_added = get_datetime($row[date_closed]);
		$row[closure_comment] = mysql_real_escape_string($row[closure_comment]);
		
		$close_by = $row[closed_by];
		if ($close_by>$same_id) {
			$close_by = $new_fp_users[$close_by];
		}
		
		$sql= "INSERT INTO journals (journalized_id, journalized_type, user_id, notes, created_on) 
			VALUES ('$row[task_id]', 'Issue', '$close_by', '$row[closure_comment]', '$date_added')";
		mysql_select_db('redmine',$db_rm);
		mysql_query($sql,$db_rm);	
	}
 	print "<br/>Close messages loaded<br/>";
}

