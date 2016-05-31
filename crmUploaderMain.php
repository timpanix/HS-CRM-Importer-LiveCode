<!doctype html>
<html>
<head>
	<!-- stylesheets -->
	<link rel="stylesheet" type="text/css" href="CSS/styles.css">			<!-- custom stylesheet -->

	<!-- custom google font -->
	<link href='http://fonts.googleapis.com/css?family=Ubuntu:300,400,700,300italic' rel='stylesheet' type='text/css'>

	<title>HS CRM Sales Activities Uploader</title>
</head>
<body>
<div class="header-wrapper">
	<div class="header"><h1>Import Report</h1></div>
</div>
<div class="body-wrapper-container" >
<div class="body-wrapper">

<?php

//  ini_set('display_errors', 'On');
//  error_reporting(E_ALL);

  require_once "simplexlsx.class.php";
  require_once 'crmUploaderFunctions.php';


	if(empty($_POST['email']) || empty($_POST['hapikey']) || ($_POST['timezones']=='no selection')
		|| ($_FILES['file']['error'] == UPLOAD_ERR_NO_FILE)){
		die('Please provide all the required information.');
	}
    $hapikey = $_POST['hapikey']; // hapikey from form input
    $ownerEmail = $_POST['email'];
    $xlsx = new SimpleXLSX($_FILES['file']['tmp_name']);
    $dictionary = array();	// this property is used to store key-value pairs: Email address -> (contactID, public contact URL)
    $timezone = $_POST['timezones'];  // get timezone value from dropdown menu
    $hubID;
    $ownerID;
    // some simple counter properties used to display the summary
		$activitiesCounter;
    $newContactsCounter = 0;
    $existingContactsCounter = 0;

    // get owner ID
    $ownerID = lookupOwnerID($ownerEmail, $hapikey);
    if($ownerID == -1){ // -1 used to flag invalid value
        die('ERROR: this is not a valid owner email address!');
    }

    // initialise counter
    $activitiesCounter = initialiseCounter();
    echo '<br>';
		// print column names of results table
    echo '<table border="1" cellpadding="3" style="border-collapse: collapse">';
    echo '<tr>';
    echo '<td>Email</td>';
    echo '<td>Type</td>';
    echo '<td>Status</td>';
    echo '<td>Link to CRM Record</td>';
    echo '<td>Public Link</td>';
    list($cols,) = $xlsx->dimension();

    // loop through all rows in the spreadsheet
		foreach( $xlsx->rows() as $k => $r) {
        if ($k == 0) {
          continue; // skip first row
        }
        // parse column values from the current row in the spreadsheet
        $email = $r[0];
        $type = $r[1];
        $description = $r[2];
        $date = $r[3];
        $time = $r[4];
        $firstname = $r[5];
        $lastname = $r[6];

        // start printing out table
        echo '<tr>';
        echo '<td>'.$email.'</td>';
        echo '<td>'.$type.'</td>';
        echo '<td>';  // opening tag for status

        //check if the same email address was already used in this session
        if(isset($dictionary[$email])){
            $contactIDAndURL = $dictionary[$email]; // if yes: simply extract VID and public URL from the dictionary
        }else{
            $contactIDAndURL = getContactIDFromAPI($email, $hapikey);  // if no: ping the API for the VID
            // 2nd check: is there a contact with this VID?
            if($contactIDAndURL[0] == -1){  // 'no' case
              createNewContact($email, $firstname, $lastname, $hapikey, $ownerID);  // create new contact in the CRM
              echo 'New contact created & owner assigned, ';
              $newContactsCounter++;  // increment counter of new contacts created
              $contactIDAndURL = getContactIDFromAPI($email, $hapikey);  // ping the API for the VID again
            }else{
              $existingContactsCounter++; // increment counter of updated existing contacts
            }
            // add new key-value pair to dictionary
            $dictionary = array_merge($dictionary, array($email => $contactIDAndURL));
        }
        // prepare data for call to Engagements API
        $contactID = $contactIDAndURL[0];
        $data = setDataSet($contactID, $ownerID, $type, $description, $date, $time, $timezone, $email, $ownerEmail);
        // ping API to create engagement in HS CRM
        $statusCode = sendDataToEndpoint($hapikey, json_encode($data));

        // update counter for the relevant activity
        $activitiesCounter[$type]++;

        // configure CRM Contact Link for display on results page
        if(!isset($hubID)){ // the first time we need to extract the HubID from the public contact URL
            $hubID = getHubID($contactIDAndURL[1]);
        }
        $contactLink = getContactLink($contactID, $hubID);  // create CRM Contact Link with contactID and hubID
        // continue displaying results table:
        echo $type.' added</td>'; // end of status table cell
        echo '<td><a href="'.$contactLink.'">CRM Contact Record</a></td>';
        echo '<td><a href="'.$contactIDAndURL[1].'">Public Link</a></td>';
        echo '</tr>';
    }
    echo '</table><br>';
    printSummary($activitiesCounter, $existingContactsCounter, $newContactsCounter);
/*}else{
  die('Please select a file.');
  //die('Please provide all the necessary details.');
}*/
?>
      </div> <!-- end of body wrapper -->
    </div> <!-- end of body wrapper container -->
    <div class="footer-wrapper">
      <div class="footer">&copy;&nbsp;Daniel Bertschi 2015&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;@SwissRoll</div>
    </div>
  </body>
</html>
