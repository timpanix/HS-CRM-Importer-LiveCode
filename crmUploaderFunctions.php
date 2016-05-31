<?php
  //ini_set('display_errors', 'On');
  //error_reporting(E_ALL);

function setEngagementsEndpointUrl($hapikey){
  return 'https://api.hubapi.com/engagements/v1/engagements?hapikey='.$hapikey;
}

function setLookupByEmailEndpointUrl($contactEmail, $apiKey){
  return 'https://api.hubapi.com/contacts/v1/contact/email/'.$contactEmail.'/profile?hapikey='.$apiKey;
}

function setCreateContactEndpointURL($hapikey){
  return'https://api.hubapi.com/contacts/v1/contact?hapikey='.$hapikey;
}

function setOwnerAPIEndpointURL($hapikey){
  return 'https://api.hubapi.com/owners/v2/owners?hapikey='.$hapikey;
}

/*
 * getContactRecordID
 *
 * This function executes the lookup of a contact in the HS Database by email address
 *
 * @param (string): API endpoint (URL)
 * @return (array): contact ID (vid) and public contact URL (if contact exists) or -1 flag as first element (if contact doesn't exist)
 */
function getContactRecordID($endpoint){
  $ch = curl_init();
  @curl_setopt($ch, CURLOPT_URL, $endpoint);
  @curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  @curl_setopt($ch, CURLOPT_HEADER, 0);	// IMPORTANT set this to 0 to not receive the header!
  print curl_error($ch);
  $response = @curl_exec($ch); //Log the response from HubSpot as needed.
  $statusCode = @curl_getinfo($ch, CURLINFO_HTTP_CODE); //Log the response status code
  if($statusCode == 200){ // if contact exists
      $contactRecord = curl_exec($ch);  // get the record
      @curl_close($ch);
      $decodedRecord = json_decode($contactRecord, true);
      //return decoded array with vid and public URL
      return array(
              $decodedRecord['vid'],
              $decodedRecord['profile-url']
            );
  }else{
      @curl_close($ch);
      return array(-1); // contact does not exist: return -1 as flag in first element
  }
}

/*
 * getContactIDFromAPI
 *
 * This is the parent function of the previous one. It sets the endpoint first, then calls the lookup function
 *
 * @param (string): email address of contact
 * @param (string): HubSpot API Key
 * @return (array): contact ID (vid) and public contact URL (if contact exists) or -1 as flag if contact doesn't exist
 */
function getContactIDFromAPI($contactEmail, $apiKey){
  $endpoint = setLookupByEmailEndpointUrl($contactEmail, $apiKey);
  return getContactRecordID($endpoint);
}

/*
 * sendDataToEndpoint
 *
 * This function creates an engagement by calling the 'Create an Engagement' endpoint from the HS Engagements API
 *
 * @param (string): HubSpot API Key
 * @param (array): data to be submitted (see next function for more details)
 * @return (number): status code returned from the endpoint (200 for success, 401 for error)
 */
function sendDataToEndpoint($hapikey, $data){
  $endpoint = setEngagementsEndpointUrl($hapikey);
  $ch = @curl_init();
  @curl_setopt($ch, CURLOPT_POST, true);
  @curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
  @curl_setopt($ch, CURLOPT_URL, $endpoint);
  @curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json'
  ));
  @curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  $response    = @curl_exec($ch); //Log the response from HubSpot as needed.
  $statusCode = @curl_getinfo($ch, CURLINFO_HTTP_CODE); //Log the response status code
  @curl_close($ch);
  return $statusCode;
}

/*
 * setDataSet
 *
 * This function creates the dataset required to create an engagement
 *
 * @param (number): Contact ID (vid)
 * @param (number): HubSpot Owner ID
 * @param (string): Type of engagment (note, task, call, email, meeting)
 * @param (string): Description (content of the engagement)
 * @param (string): Date of engagment (format: dd-mm-yyyy)
 * @param (string): Time of engagment (format: hh:mm)
 * @param (string): Timezone fromt he dropdown menu in the form (this is required to submit accurated timing)
 * @return (array): data set to be submitted
 */
function setDataSet($contactID, $ownerID, $type, $description, $date, $time, $timezone, $contactEmail, $ownerEmail){
  date_default_timezone_set($timezone); // set built-in PHP timezone property
  $date = str_replace('/', '-', $date);
  $timestamp = strtotime($date.' '.$time); // convert string to timestamp
  $milliseconds = 1000 * $timestamp;  // convert to milliseconds

  // special case for sales email due to the different configuration of the metadata
  if(strtoupper($type) == 'EMAIL'){
    $metadata = array(
                  'from' => array (
                            'email'=> $ownerEmail,
                            'firstName' => '',
                            'lastName' => ''
                            ),
                  'to' => array ('email' => $contactEmail),
                  'cc' => array (),
                  'bcc' => array (),
                  'subject' => '',
                  'html' => '',
                  'text' => $description
    );
  // normal case for Tasks, Meetings, Calls, Notes
  }else{
    $metadata = array (
                    'body' => $description     // otherwise: metadata contains just the passed in description
    );
  }
  return array(
        'engagement' => array(
                          'active' => true,
                          'ownerId' => $ownerID,   // dbertschi@hubspot.com
                          'type' => strtoupper($type),
                          'timestamp' => $milliseconds
                          ),
         'associations' => array (
                          'contactIds'=> array($contactID),
                          'ownerIds' => array($ownerID)
                          ),
         'metadata' => $metadata
    );
}

/*
 * createNewContact
 *
 * This function configures the required dataset, then pings the Contacts API to create a contact
 *
 * @param (string): Email address of the contact to be created
 * @param (string): First name of new contact
 * @param (string): Last name of new contact
 * @param (string): HubSpot API Key
 * @param (number): HubSpot Owner ID
 */
function createNewContact($email, $firstname, $lastname, $hapikey, $ownerID){
  $data = array(
         'properties' => array(
             array(
                 'property' => 'email',
                 'value' => $email
             ),
             array(
                 'property' => 'firstname',
                 'value' => $firstname
             ),
             array(
                 'property' => 'lastname',
                 'value' => $lastname
             ),
             array(
                 'property' => 'hubspot_owner_id',
                 'value' => $ownerID
             )
         )
     );
     $post_json = json_encode($data);
     $endpoint = setCreateContactEndpointURL($hapikey);
     $ch = @curl_init();
     @curl_setopt($ch, CURLOPT_POST, true);
     @curl_setopt($ch, CURLOPT_POSTFIELDS, $post_json);
     @curl_setopt($ch, CURLOPT_URL, $endpoint);
     @curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
     @curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
     $response = @curl_exec($ch);
     @curl_close($ch);
}

/*
 * getHubID
 *
 * This function extracts the HubID from the public contact URL (required to build the CRM Contact Record Link)
 *
 * @param (string): public Contact URL of a HubSpot contact
 * @return (string): HubID
 */
function getHubID($publicContactURL){
  $substrings = explode ('/', $publicContactURL); // break public URL into substrings
  return $substrings[4]; // extract portal ID from public URL
}

// configure full CRM Contact URL
function getContactLink($vid, $hubID){
    return 'https://app.hubspot.com/sales/'.$hubID.'/contact/'.$vid.'/';
}

/*
 * lookupOwnerID
 *
 * This function pings the HubSpot Owner API to get all the owners for the portal.
 * It then loops through all the returned owners to find the one with the matching email address.
 * If none is found, the thread shuts down and an error message is displayed

 * @param (string): email address of HubSpot owner (from form input)
 * @param (string): HubSpot API Key
 * @return (number): Owner ID (if owner was found) or -1 otherwise
 */
function lookupOwnerID($ownerEmail, $hapikey){
    $endpoint = setOwnerAPIEndpointURL($hapikey);
    $ch = curl_init();
    @curl_setopt($ch, CURLOPT_URL, $endpoint);
    @curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    @curl_setopt($ch, CURLOPT_HEADER, 0);	// IMPORTANT set this to 0 to not receive the header!
    $contactRecord = curl_exec($ch);
    $statusCode = @curl_getinfo($ch, CURLINFO_HTTP_CODE); //Log the response status code
    @curl_close($ch);
    if($statusCode == 401){
      die('ERROR: This is not a valid API key!');
    }
    $output = json_decode($contactRecord, true);
    for($i = 0; $i < sizeof($output); $i++){
        if($output[$i]['email'] == $ownerEmail){
            return $output[$i]['ownerId'];
        }
    }
    return -1;  // return -1 to flag that no owner exists with that email address
}


function initialiseCounter(){
  return array(
              'note' => 0,
              'meeting' => 0,
              'email' => 0,
              'task' => 0,
              'call' => 0
            );
}

/*
 * printContactsSummary
 *
 * This function displays a summary of the number of updated and newly created contacts.
 * the no. of new contacts created/no. of existing contacts who were updated
 * It takes care of the correct grammar (singular vs. plural) and punctuation
 * (commas before every element except the first one)
 *
 * @param (number): no. of contacts (either new ones or updated ones)
 * @param (string): either the word 'new' or 'existing' is passed in to complete the sentence.
 * @param (string): either the word 'updated' or 'created' is passed in to complete the sentence.
 */
function printContactsSummary($ContactsCounter, $newOrExisting, $updatedOrCreated){
    if($ContactsCounter == 1){
      echo '<li>1 '.$newOrExisting.' contact was '.$updatedOrCreated.'.</li>';
    }else if($ContactsCounter > 1){
      echo '<li>'.$ContactsCounter.' '.$newOrExisting.' contacts were '.$updatedOrCreated.'.</li>';
    }
}

/*
 * printSummary
 *
 * This function displays a summary of the number and type of new activites created and
 * the no. of new contacts created/no. of existing contacts who were updated by calling the previous function twice.
 * It takes care of the correct grammar (singular vs. plural) and punctuation
 * (commas before every element except the first one)
 *
 * @param (array): counters of all activities
 * @param (number): no. of existing contacts that were updated
 * @param (number): no. of new contacts created
 */
function printSummary($activitiesCounter, $existingContactsCounter, $newContactsCounter){
    echo '<br>Summary: <br><ul>';
    echo '<li> A total of ';
    //$tempCounter = 0;
    $first = true;  // flag to make sure we don't display a comma before the first element
    foreach($activitiesCounter as $key => $value){
        if($value != 0){  // if there is a value present
            if(!$first){
                echo ', '; // first value doesn't need a comma before itself
            }
            echo $value.' '.$key; // print it
            if($value !== 1){ // if the number is higher than 1
                echo 's'; // and a 's' for plural
            }
            //$tempCounter++; // increase the counter
            $first = false;
        }
    }
    echo ' were successfully imported.</li>';

    printContactsSummary($existingContactsCounter, 'existing', 'updated'); // print no. of updated contacts
    printContactsSummary($newContactsCounter, 'new', 'created');  // print no. of newly created contacts
    echo '</ul>';
}

 ?>
