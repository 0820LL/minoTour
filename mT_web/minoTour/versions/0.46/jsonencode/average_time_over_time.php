<?php

//header('Content-Type: application/json');
// checking for minimum PHP version
/*if (version_compare(PHP_VERSION, '5.3.7', '<')) {
    exit("Sorry, Simple PHP Login does not run on a PHP version smaller than 5.3.7 !");
} else if (version_compare(PHP_VERSION, '5.5.0', '<')) {
    // if you are using PHP 5.3 or PHP 5.4 you have to include the password_api_compatibility_library.php
    // (this library adds the PHP 5.5 password hashing functions to older versions of PHP)
    require_once("../libraries/password_compatibility_library.php");
}

// include the configs / constants for the database connection
require_once("../config/db.php");

// load the login class
require_once("../classes/Login.php");

// load the functions
require_once("../includes/functions.php");

// create a login object. when this object is created, it will do all login/logout stuff automatically
// so this single line handles the entire login process. in consequence, you can simply ...
$login = new Login();

// ... ask if we are logged in here:
if ($login->isUserLoggedIn() == true) {
     //As user is logged in, we can now look at the memcache to retrieve data from here and so reduce the load on the mySQL server
	// Connection creation
	$memcache = new Memcache;
	#$cacheAvailable = $memcache->connect(MEMCACHED_HOST, MEMCACHED_PORT) or die ("Memcached Failure");
	$cacheAvailable = $memcache->connect(MEMCACHED_HOST, MEMCACHED_PORT);
	
    // the user is logged in. you can do whatever you want here.
    // for demonstration purposes, we simply show the "you are logged in" view.
    //include("views/index_old.php");*/
	if($_GET["prev"] == 1){
		$mindb_connection = new mysqli(DB_HOST,DB_USER,DB_PASS,$_SESSION['focusrun']);
		$currun = $_SESSION['focusrun'];
	}else{
		$mindb_connection = new mysqli(DB_HOST,DB_USER,DB_PASS,$_SESSION['active_run_name']);
		$currun = $_SESSION['active_run_name'];
	}

	if (!$mindb_connection->connect_errno) {
		//Check if entry already exists in jsonstore table:
		$jsonjobname="average_time_over_time";
		$checkvar = $currun . $jsonjobname;
		$jsonstring = $memcache->get("$checkvar");
		if($jsonstring === false){		
			$checkrow = "select name,json from jsonstore where name = '" . $jsonjobname . "' ;";
			$checking=$mindb_connection->query($checkrow);
			if ($checking->num_rows ==1){
				//echo "We have already run this!";
				foreach ($checking as $row){
					$jsonstring = $row['json'];
				}
			} else {
			$sql_template = "select floor(start_time/60)*60 as bin_floor, ROUND(AVG(duration)) as average_time from basecalled_template group by 1 order by 1;";
			$sql_complement = "select floor(start_time/60)*60 as bin_floor, ROUND(AVG(duration)) as average_time from basecalled_complement group by 1 order by 1;";
		
			$resultarray;
	
			$template=$mindb_connection->query($sql_template);
			$complement=$mindb_connection->query($sql_complement);
		
			if ($template->num_rows >= 1){
				foreach ($template as $row) {
					$resultarray['template'][$row['bin_floor']]=$row['average_time'];
				}
			}
		
			if ($complement->num_rows >=1) {
				foreach ($complement as $row) {
					$resultarray['complement'][$row['bin_floor']]=$row['average_time'];
				}
			}
		
			
		
			//var_dump($resultarray);
			//$jsonstring = $jsonstring . json_encode($resultarray);
			$jsonstring;
			$jsonstring = $jsonstring . "var JSON3 = [\n";
			foreach ($resultarray as $key => $value){
				$jsonstring = $jsonstring . "{\n";
				$jsonstring = $jsonstring . "name : '$key', \n";
				$jsonstring = $jsonstring . "data: [";
				foreach ($value as $key2 => $value2) {
					$jsonstring = $jsonstring . "[$key2,$value2],\n";
				}
				$jsonstring = $jsonstring . "]\n";
				$jsonstring = $jsonstring . "},\n";
				
			}
			$jsonstring = $jsonstring .  "]\n";
			if ($_GET["prev"] == 1){
				include 'savejson.php';
			}
		}
	
	// cache for 2 minute as we want yield to update semi-regularly...
	    $memcache->set("$checkvar", $jsonstring, 0, 120);
	}else {
		//echo "Using memcached!";
	}		
			
	$callback = $_GET['callback'];
	echo $callback.'('.$jsonstring.');';
	
		
	}
	//} else {
//	$jsonstring = $jsonstring . "ERROR";
//}
?>