<?php //begin the php script

/***************************** Connect to the BetaBox Database *****************************/
if(true){ //required for IDE password hiding
	$hostname_database = ""; //set host ip variable
	$database_database = ""; //set database name variable
	$username_database = ""; //set database username variable
	$password_database = ""; //set database password variable
	$database = new mysqli($hostname_database, $username_database, $password_database, $username_database); //create a new database with the stored information
	if($database->connect_errno > 0){ //if there is an error connecting to the database,
		die('Unable to connect to database [' . $db->connect_error . ']'); //stop executing all php and return an error - this should never happen
	}
}

/***************************** Define Variables for Future Use *****************************/
$stmt; //object variable to store database query returns
$help = ""; //string variable to store help messages
$error = ""; //string variable to store error messages
$success = ""; //string variable to store success messages
$ThisLoggedIn = false; //boolean variable to store user is-logged-in value
$ThisUsername = $_COOKIE['username']; //string variable to store the current username - set to site username cookie
$ThisPassword = $_COOKIE['password']; //string variable to store the current password - set to site encrypted password cookie
$ThisUsers = array(); //array variable to store the current user's database values
$ThisSchools = array(); //array variable to store the current school's database values
$ThisGroups = array(); //array variable to store the current groups's database values
$ThisOrders = array(); //array variable to store the current order's database values
$ThisProjects = array(); //array variable to store the current project's school's database values
$ThisUser = ""; //string variable to store the current user's identifier
$ThisSchool = ""; //string variable to store the current school's identifier
$ThisGroup = ""; //string variable to store the current group's identifier
$ThisOrder = ""; //string variable to store the current order's identifier
$ThisProject = ""; //string variable to store the current project's identifier
$ThisComponents = ""; //string variable to store the current components (compact cell in a database)
$alertRemovedA = array(); //array variable to store the removed (dismissed) alert message identifiers
$alertA = array(); //array variable to store alert message identifiers

/***************************** Generate New Item Identification Code *****************************/
function newCode(){
	return date(zBs) . rand(0,9); //return a number based upon the current time and a random value (unique value)
}

/***************************** Add Alert to Array of Alerts *****************************/
function addAlerts($alertFS){
	global $alertRemovedA; //gain access to array of removed alert identifiers
	global $alertA; //gain access to array of alerts
	if($alertFS != ""){ //if the current alert is not blank, ex aaa,,,bbb,,,ccc:::ddd,,,eee,,,fff
		$alertFA = explode(":::",$alertFS); //split this alert string by the ::: seperator ex aaa,,,bbb,,,ccc and ddd,,,eee,,,fff
		for ($i = 0; $i < count($alertFA); $i++) { //go to each of these split fragments and, ex aaa,,,bbb,,,ccc
		    $tempA = explode(",,,", $alertFA[$i]); //split this fragment string by the ,,, seperator ex aaa and bbb and ccc
		    $addIt = true; //set a variable so that this split fragment string will be added to the alerts array
			for ($j = 0; $j < count($alertRemovedA); $j++) { //look through all of the remvoed alert identifiers and,
				if($alertRemovedA[$j] == $tempA[0]){ //if any removed alert identifier matches the current message identifier
					$addIt = false; //set a variable so that this split fragment string will NOT be added to the alerts array
				}
			}
			if($addIt){ //if this split fragment string is to be added to the alerts array,
				$alertA[] = $tempA; //then add it
			}
		} //(repeat for each alert in the provided alerts string)
	}
}

/***************************** Add Error (or help or success) to String of All Errors *****************************/
function addError($addTo,$errors) {
	if($addTo == ""){ //if the error to be added is blank,
		return $errors; //return the original value of the errors string
	} else { //otherwise,
		return $addTo . "<br>" . $errors; //return the errors string plus the new error
	}
}

/***************************** Safe Database Interaction Functions *****************************/
function databasePrepare($query) {
	global $database; //gain access to the connected database
	global $stmt; //gain access to the database query returns variable
	global $error; //gain access to the string of all errors
	if($error == ""){ //if there have not been any errors, continue with the query
		if ($stmt = $database->prepare($query)) { //attempt to prepare the database query and if the attempt was successful,
			return true; //return true
		} else { //otherwise,
			$error = addError($error,"Database prepared statement error. "); //add an error and
		}
	}
	return false; //return false
}
function databaseExecute() {
	global $stmt; //gain access to the database query returns variable
	global $error; //gain access to the string of all errors
	if ($error == "") { //if there have not been any errors, continue with the query
		$stmt->execute(); //execute the database query
		$stmt->store_result(); //store the results of the query
		return true; //return true (if no prior errors exist)
	}
	return false; //return false (if prior errors exist)
}
function databaseFetch() {
	global $stmt; //gain access to the database query returns variable
	global $error; //gain access to the string of all errors
	if ($error == "") { //if there have not been any errors, continue with the query
		$stmt->fetch(); //fetch (get results of) the database query
		if($stmt->num_rows == 1) { //if one entry was returned from the database,
			return true; //return true
		} else { //otherwise,
			$error = addError($error,"Database entry not found. "); //add an error and
		}
	}
	return false; //return false
}

/***************************** Load All Allerts for User *****************************/
function loadAlerts(){
	global $ThisUsername; //gain access to the curent username
	global $ThisUsers; //gain access to the current user data
	global $ThisSchools; //gain access to the current school data
	global $ThisGroups; //gain access to the current group data
	global $ThisOrders; //gain access to the current order data
	global $ThisProjects; //gain access to the current project data
	global $alertHtml; //gain access to the alert html variable (end display html)
	global $alertRemovedA; //gain access to the array of removed alerts
	global $alertA; //gain access to the array of all alerts
	getUser($ThisUsername); //load the current user data based upon the user's username
	getSchool($ThisUsers['School Code']); //load the current school data for this user
	getGroup($ThisUsers['Group Code']); //load the current group data for this user
	getOrder($ThisGroups['Order Code']); //load the current order data for this group
	getProject($ThisOrders['Doc Code']); //load the current project data for this order
	$userDBAlertsA = explode("&&&",$ThisUsers['Alerts']); //split the user alert data string by &&& and store this array
	if(count($userDBAlertsA) == 2) { //if this array has two parts, (one &&& seperator)
		$alertRemovedA = explode(",,,", $userDBAlertsA[0]); //split the first part by ,,, and make these items the removed alerts (add the user removed alerts)
		addAlerts($userDBAlertsA[1]); //and process the other part using the addAlerts function (add the user alerts with respect to the removed alerts)
	}
	addAlerts($ThisSchools['Alerts']); //add the school alerts with respect to the removed alerts
	addAlerts($ThisGroups['Alerts']); //add the group alerts with respect to the removed alerts
	addAlerts($ThisOrders['Alerts']); //add the order alerts with respect to the removed alerts
	addAlerts($ThisProjects['Alerts']); //add the project alerts with respect to the removed alerts
						addAlerts("13,,,12,,,0,,,This is an alert! these can be sent to peeples' dashboards! They can be sent per group, school, individual, etc. ! This one should be blueish! Dale and Erik, you can make it look basicly however you want as long as it is stackable in this right pane!"); //example alert
						addAlerts("23,,,22,,,1,,,Alerts basicly go away forever when you dismiss them.. these are just for testing.. This should be yellow-ish!"); //example alert
						addAlerts("34,,,33,,,2,,,This one should be red-sih! WARNING! THE WORLD HAS ENDED!"); //example alert
	$alertHtml = ""; //set the alert html to be blank
	for($i = 0; $i < count($alertA); $i++){ //for each alert that is to be printed,
		$thisAlertA = $alertA[$i]; //create a temporary variable to store the data for this alert
		$alertHtml = $alertHtml . '<div class="alert-item-outer color-' . $thisAlertA[2] . '" id="alert-' . $thisAlertA[0] . '"><p align="left">' . $thisAlertA[3] . '</p><p align="right"><a style="color:#606060;" href="#" onclick="dismissAlert(' . "'" . $thisAlertA[0] . "'" . ');">Dismiss</a></p></div>'; //add this alert
	}
	if($alertHtml == ''){ //if no alerts were added
		$alertHtml = '<p style="margin-top:3em;color:#ffffff;font-size: 300%;">No new alerts at this time</p>'; //display the no new alerts text
	}
}

/***************************** Explore User Login Attempt *****************************/
function checkLogin(){
	global $ThisUsername; //gain access to the current username
	global $ThisPassword; //gain access to the current encrypted password
	global $stmt; //gain access to the database query returns variable
	global $error; //gain access to the string of all errors
	global $ThisLoggedIn; //gain access to the user sign-in status variable
	$ThisLoggedIn = false; //set the current status of the user to not logged-in
	if($ThisUsername != ''){ //check that the username variable is set
		if(strlen($ThisUsername)>20){ //check the length of this username and,
			$error = addError($error,"Your username is too long. "); //provide an error if it is too long
		}
		if(strpos($ThisUsername,' ') !== false){ //check the username for spaces and,
			$error = addError($error,"Your username cannot have any spaces. "); //provide an error if it contains spaces
		}
		if($ThisPassword==""){ //check that the password variable is set and,
			$error = addError($error,"Please enter your password. "); //provide an error if it is not present
		}
		if($error == ""){ //if no errors have occured,
			databasePrepare("SELECT Password FROM Users WHERE Username = ? LIMIT 1"); //prepare a search for passwords based upon usernames
			$stmt->bind_param('s', $ThisUsername); //use the current user's username
			databaseExecute(); //execute this search
			$stmt->bind_result($db_password); //tell the database to save the results of this search in a variable
			databaseFetch(); //get this information (into the variable)
			if($error == ""){ //if there are still no errors,
				if($db_password !== $ThisPassword){ //check that the encrypted passwords from the user and database match and,
					$error = addError($error,"That password was not correct. "); //add an error if the passwords do not match
				} else { //or,
					$ThisLoggedIn = true; //set the logged-in variable to true if they do match
				}
			}
		}
	} else { //if there was no username,
		$error = addError($error,"Please log in before accessing this page. If this error persists, please make sure you have cookies enabled."); //add an error
	}
	return $ThisLoggedIn; //return the value of the logged-in variable (whether or not the user successfuly logged in)
}

/***************************** Placeholder Function for Database Clearance Check *****************************/
function hasClearance($ftAction, $ftTable, $ftIndex){
	return checkLogin(); //return true if the user is logged-in and false otherwise
}

/***************************** Get Database Data Functions *****************************/
function getUser($ftIndex){
	global $stmt; //gain access to the database query returns variable
	global $ThisUsers; //gain access to the database results variable for this user
	global $ThisUser; //gain access to the current (before this function) user identifier
	if($ThisUser != $ftIndex){ //ensure that the data that is being requested has not already been loaded and if it has not,
		if(hasClearance("get", "Users", $ftIndex)){ //check the clearance level of the requesting user
			databasePrepare("SELECT * FROM Users WHERE Username = ? LIMIT 1"); //prepare a search for everything about a user based upon a user index
			$stmt->bind_param('s', $ftIndex); //use the supplied user identifier
			databaseExecute(); //execute this search
			$stmt->bind_result($db_0,$db_1,$db_2,$db_3,$db_4,$db_5,$db_6,$db_7,$db_8,$db_9,$db_10,$db_11); //tell the database to save the results of this search in a variable set
			databaseFetch(); //get this information (into the variable set)
			if($error == ""){ //if there are no errors,
				$ThisUsers = array("First Name" => $db_0, "Last Name" => $db_1, "Email" => $db_2, "Username" => $db_3, "Password" => $db_4, "School Code" => $db_5, "Has Box" => $db_6, "Linked" => $db_7, "Position" => $db_8, "Clearance" => $db_9, "Group Code" => $db_10, "Alerts" => $db_11); //place returned variables into an array
				$ThisUser = $ftIndex; //save the new user identifier as the current identifier
				return true; //return true (if data was updated)
			}
		}
		return false; //return false (if data needed to be updated but was not)
	}
	return true; //return true (if data did not need to be updated)
}
function getSchool($ftIndex){
	global $stmt; //gain access to the database query returns variable
	global $ThisSchools; //gain access to the database results variable for this school
	global $ThisSchool; //gain access to the current (before this function) school identifier
	if($ThisSchool != $ftIndex){ //ensure that the data that is being requested has not already been loaded and if it has not,
		if(hasClearance("get", "Schools", $ftIndex)){ //check the clearance level of the requesting user
			databasePrepare("SELECT * FROM Schools WHERE `School Code` = ? LIMIT 1"); //prepare a search for everything about a school based upon a school index
			$stmt->bind_param('s', $ftIndex); //use the supplied school identifier
			databaseExecute(); //execute this search
			$stmt->bind_result($db_0,$db_1,$db_2,$db_3,$db_4,$db_5,$db_6,$db_7,$db_8); //tell the database to save the results of this search in a variable set
			databaseFetch(); //get this information (into the variable set)
			if($error == ""){ //if there are no errors,
				$ThisSchools = array("Name" => $db_0, "Address" => $db_1, "Estimated Students" => $db_2, "Has Box" => $db_3, "Grouping Date" => $db_4, "Start Date" => $db_5, "End Date" => $db_6, "School Code" => $db_7, "Alerts" => $db_8); //place returned variables into an array
				$ThisSchool = $ftIndex; //save the new school identifier as the current identifier
				return true; //return true (if data was updated)
			}
		}
		return false; //return false (if data needed to be updated but was not)
	}
	return true; //return true (if data did not need to be updated)
}
function getGroup($ftIndex){
	global $stmt; //gain access to the database query returns variable
	global $ThisGroups; //gain access to the database results variable for this group
	global $ThisGroup; //gain access to the current (before this function) group identifier
	if($ThisGroup != $ftIndex){ //ensure that the data that is being requested has not already been loaded and if it has not,
		if(hasClearance("get", "Groups", $ftIndex)){ //check the clearance level of the requesting user
			databasePrepare("SELECT * FROM Groups WHERE `Group Code` = ? LIMIT 1"); //prepare a search for everything about a group based upon a group index
			$stmt->bind_param('s', $ftIndex); //use the supplied group identifier
			databaseExecute(); //execute this search
			$stmt->bind_result($db_0,$db_1,$db_2,$db_3,$db_4); //tell the database to save the results of this search in a variable set
			databaseFetch(); //get this information (into the variable set)
			if($error == ""){ //if there are no errors,
				$ThisGroups = array("Group Code" => $db_0, "School Code" => $db_1, "Order Code" => $db_2, "Project Type" => $db_3, "Alerts" => $db_4); //place returned variables into an array
				$ThisGroup = $ftIndex; //save the new group identifier as the current identifier
				return true; //return true (if data was updated)
			}
		}
		return false; //return false (if data needed to be updated but was not)
	}
	return true; //return true (if data did not need to be updated)
}
function getOrder($ftIndex){
	global $stmt; //gain access to the database query returns variable
	global $ThisOrders; //gain access to the database results variable for this order
	global $ThisOrder; //gain access to the current (before this function) order identifier
	if($ThisOrder != $ftIndex){ //ensure that the data that is being requested has not already been loaded and if it has not,
		if(hasClearance("get", "Orders", $ftIndex)){ //check the clearance level of the requesting user
			databasePrepare("SELECT * FROM Orders WHERE `Order Code` = ? LIMIT 1"); //prepare a search for everything about a order based upon a order index
			$stmt->bind_param('s', $ftIndex); //use the supplied order identifier
			databaseExecute(); //execute this search
			$stmt->bind_result($db_0,$db_1,$db_2,$db_3,$db_4,$db_5); //tell the database to save the results of this search in a variable set
			databaseFetch(); //get this information (into the variable set)
			if($error == ""){ //if there are no errors,
				$ThisOrders = array("Order Code" => $db_0, "Doc Code" => $db_1, "Linked" => $db_2, "Requester" => $db_3, "Date Ordered" => $db_4, "Done" => $db_5); //place returned variables into an array
				$ThisOrder = $ftIndex; //save the new order identifier as the current identifier
				return true; //return true (if data was updated)
			}
		}
		return false; //return false (if data needed to be updated but was not)
	}
	return true; //return true (if data did not need to be updated)
}
function getProject($ftIndex){
	global $stmt; //gain access to the database query returns variable
	global $ThisProjects; //gain access to the database results variable for this project
	global $ThisProject; //gain access to the current (before this function) project identifier
	if($ThisProject != $ftIndex){ //ensure that the data that is being requested has not already been loaded and if it has not,
		if(hasClearance("get", "Projects", $ftIndex)){ //check the clearance level of the requesting user
			databasePrepare("SELECT * FROM Projects WHERE `Doc Code` = ? LIMIT 1"); //prepare a search for everything about a project based upon a project index
			$stmt->bind_param('s', $ftIndex); //use the supplied project identifier
			databaseExecute(); //execute this search
			$stmt->bind_result($db_0,$db_1,$db_2,$db_3,$db_4,$db_5,$db_6,$db_7,$db_8,$db_9); //tell the database to save the results of this search in a variable set
			databaseFetch(); //get this information (into the variable set)
			if($error == ""){ //if there are no errors,
				$ThisProjects = array("Name" => $db_0, "Is Module" => $db_1, "Project Type" => $db_2, "Doc Code" => $db_3, "Last User" => $db_4, "Date Added" => $db_5, "Date Removed" => $db_6, "Done" => $db_7, "Cost" => $db_8, "Components" => $db_9); //place returned variables into an array
				$ThisProject = $ftIndex; //save the new project identifier as the current identifier
				return true; //return true (if data was updated)
			}
		}
		return false; //return false (if data needed to be updated but was not)
	}
	return true; //return true (if data did not need to be updated)
}

/***************************** Get Database Data Functions *****************************/
function getComponents($ftComponents){
	global $ThisComponents; //gain access to the variable for the current component set
	$ThisComponents = array(); //set this variable to a new array
	$ftComponentsSplit = explode(",", $ftComponents); //split the provided component string by ,
	$ftCounter = 0; //set a temporary pointer variable to zero
	for($i = 0; $i < count($ftComponentsSplit); $i++){ //visit each components string fragment and,
		if(strpos($ftComponentsSplit[$i], "---") === false){ //if it does not have --- in it,
			$ThisComponents[$ftCounter] = $ftComponentsSplit[$i]; //add it to the components array (requires temporary pointer variable in this implimentation)
			$ftCounter++; //update temporary pointer variable
		}
	}
}

/***************************** Convert Values from Int to String *****************************/
function projectTypeStr($index){
	switch ($index) { //look at the project type index and,
		case 0: //if it is 0,
			return "Solo"; //return "Solo"
			break; //or (end this test)
		case 1: //if it is 1,
			return "Global"; //return "Global"
			break; //or (end this test)
		case 2: //if it is 2,
			return "Local"; //return "Local"
			break; //or (end this test)
		case 3: //if it is 3,
			return "Charitable Advanced"; //return "Charitable Advanced"
			break; //or (end this test)
		case 4: //if it is 4,
			return "Charitable"; //return "Charitable"
			break; //or (end this test)
		default: //if it is none of the above,
			return "Unknown"; //return "Unknown"
			break; //end
	}
}
function positionStr($index){
	switch ($index) { //look at the project type index and,
		case 0: //if it is 0,
			return "Student"; //return "Student"
			break; //or (end this test)
		case 1:
			return "Instructor";
			break; //or (end this test)
		case 2:
			return "Customer";
			break; //or (end this test)
		default: //if it is none of the above,
			return "Unknown"; //return "Unknown"
			break; //end
	}
}






function keyValidation($ftKey,$ftValue){
	global $error;
	$tKey = str_replace("_"," ",$ftKey);
	$tShow = true;
	$tName = $tKey;
	$tType = "Input";
	$tValue = $ftValue;
	$tValid = true;
	$tLink = "";
	if($tValue == "abc"){
		$tValid = false;
		$error = addError($error, "Please enter a value for each field. ");
	}
	if(strpos($tValue,',')!==false || strpos($tValue,';;')!==false || strpos($tValue,'&&')!==false){
		$tValid = false;
		$error = addError($error, "Please avoid \",\" \";;\" and \"&&\"");
	}
	switch ($tKey) {
		case 'Name':
		case 'Address':
		case 'Estimated Students':
		case 'First Name':
		case 'Last Name':
		case 'Email':
		case 'Username':
		case 'Clearance':
			break;
		case 'Cost':
			$tName = "Material Cost";
			break;
		case 'Group Code':
		case 'Linked':
		case 'Alerts':
			$tShow = false;
			break;
		case 'School Code':
			$tName = "School";
			$tType = "List";
			//get school information
			$tValue = '<option value="-1">None</option><option value="-2">Not Listed</option>';
			break;
		case 'Has Box':
			$tName = "Can Access Betabox";
			$tType = "List";
			if($ftValue == 0){
				$tValue = '<option value="0">No</option><option value="1">Yes</option>';
			} else {
				$tValue = '<option value="1">Yes</option><option value="0">No</option>';
			}
			break;
		case 'Done':
			$tName = "Ready";
			$tType = "List";
			if($ftValue == 0){
				$tValue = '<option value="0">No</option><option value="1">Yes</option>';
			} else {
				$tValue = '<option value="1">Yes</option><option value="0">No</option>';
			}
			break;
		case 'Is Module':
			$tName = "Is a Module";
			$tType = "Text";
			if($ftValue == 0){
				$tValue = 'Yes';
			} else {
				$tValue = 'No';
			}
			break;
		case 'Project Type':
			$tType = "Text";
			$tValue = projectTypeStr($ftValue);
			break;
		case 'Position':
			$tType = "Text";
			$tValue = positionStr($ftValue);
			break;
		case 'Doc Code':
		case 'Requester':
		case 'Last User':
			$tType = "Text";
			break;
		case 'Date Ordered':
		case 'Date Added':
		case 'Date Removed':
			$tType = "Text";
			$tValue = date("F j, Y", strtotime($ftValue));
			break;
		case 'Order Code':
			$tName = "Order";
			$tType = "Link";
			$tValue = "Report";
			$tLink = "*Report Order";
			break;
		case 'Components':
			$tName = "Steps";
			$tType = "Link";
			$tValue = "Edit";
			$tLink = "*Edit Steps";
			break;
		case 'Password':
			$tType = "Link";
			$tValue = "Change";
			$tLink = "*Change Password";
			break;
		case 'Grouping Date':
			$tValue = date("F j, Y", strtotime($ftValue));
			break;
		case 'Start Date':
			$tName = "BetaBox Arival Date";
			$tValue = date("F j, Y", strtotime($ftValue));
			break;
		case 'End Date':
			$tName = "BetaBox Departure Date";
			$tValue = date("F j, Y", strtotime($ftValue));
			break;
		default:
			$error = addError($error, "Key validation error. ");
			return false;
	}
	$myReturn = array('Show'=>$tShow,'Name'=>$tName,'Type'=>$tType,'Value'=>$tValue,'Valid'=> 'false' ,'Link'=>$tLink);
	return $myReturn;
}





function displayErrors(){
	global $error;
	global $help;
	if($error != ""){
		print "<p class=\"error\">" . $error . "</p>";
	}
	if($help != ""){
		print "<p class=\"error\">" . $help . "</p>";
	}
}





function displaySettings($ftArray,$ftAction,$ftTitle){
	global $errors;
	global $success;
	$ftKeys = array_keys($ftArray);
	
	if($error != ""){
		print "<div style='height:5em;'></div>";
		print "<p class=\"error\" style='text-align:center;padding:4px;width:30%;position:absolute;left:35%;top:8em'>" . $error . "</p>";
	} else {
		if($success != ""){
			print "<div style='height:5em;'></div>";
			print "<p class=\"error\" style='text-align:center;padding:4px;width:30%;position:absolute;left:35%;top:8em;background-color:#BBFFBB;'>" . $success . "</p>";
		}
	}
	
	print '<form action='. $ftAction .' method="POST"><div class="entry-content content"><div class="loginStyle"><div class="login print-form" style="text-align:right">';
	
	for($i = 0; $i < count($ftKeys); $i++){
		$ftValidation = keyValidation($ftKeys[$i],$ftArray[$ftKeys[$i]]);
		if($ftValidation['Show'] == true){
			print '<p>';
			print $ftValidation['Name'] .'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
			switch ($ftValidation['Type']) {
				case 'Input':
					print '<input type="Input" style="float:right;padding-left: 12px; width: 200px;" name="' . $ftKeys[$i] . '" value="' . $ftValidation['Value'] . '">';
					break;
				case 'List':
					print '<select style="float:right;width:200px;" name="' . $ftKeys[$i] . '">' . $ftValidation['Value'] . '</select>';
					break;
				case 'Text':
					print '<input type="Input" style="float:right;background-color: #ddd;border: 1px solid #A9A9A9;padding-left: 12px; width: 200px;" readonly value="' . $ftValidation['Value'] . '">';
					break;
				case 'Link':
					print '<a class="fake-button" href="'.$ftValidation['Link'].'" style="text-align:center">'.$ftValidation['Value'].'</a>';
					break;
			}
			print '</p>';
		}
		
	}				
	print '</div></div></div><div style="text-align:center;">';
	
	print '<input type="Submit" style="display: inline-block;margin-bottom:4em;" value="'.$ftTitle.'">';
	
	print '</div></form>';
}

function updateSettings($type,$key,$ftArray){
	global $error;
	global $success;
	
	$success = addError($success,"Settings successfuly updated! ");
	//print_r($ftArray);

}



?>