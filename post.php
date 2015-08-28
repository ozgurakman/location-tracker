<? 
/******************** 

DB Stuff..

********************/

$dbhost = ""; // this will ususally be 'localhost', but can sometimes differ
$dbname = ""; // the name of the database that you are going to use for this project
$dbuser = ""; // the username that you created, or were given, to access your database
$dbpass = ""; // the password that you created, or were given, to access your database

$db = new mysqli($dbhost, $dbuser, $dbpass, $dbname);

if (mysqli_connect_errno()) {
    printf("Connect failed: %s\n", mysqli_connect_error());
    exit();
}

$responseData = json_decode(file_get_contents('php://input'), true);

if ($responseData['MiataruLocation'] != '') { // IF this is a location update
	foreach ($responseData['MiataruLocation'] as $key => $value) { // loop all updates
		if ($value['Device'] == 'XXX-XXXX-XXX') { // This is your device ID!
			$loccheckq = $db->query("SELECT * FROM latlng WHERE ts = '".date("Y-m-d H:i:s", $value['Timestamp'])."'"); // Check if we imported this before
			if ($loccheckq->num_rows == 0) { // IF all is good
				$db->query("INSERT INTO latlng (ts,lat,lng) VALUES ('".date("Y-m-d H:i:s", $value['Timestamp'])."','".$value['Latitude']."','".$value['Longitude']."')"); // Import location
			}
		}
	}
}
die; // Die.
?>