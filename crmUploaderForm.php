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
	<div class="header"><h1>HubSpot CRM Sales Activities Uploader</h1> <h2>Import your offline Tasks, Notes, Emails, Calls &amp; Meetings
		from Excel <br>directly into the contact's timeline</h2></div>
</div>
<div class="body-wrapper-container" >
<div class="body-wrapper">
	<div class="left-side">
	<h3>How does it work?</h3>
		<ol>
			<li>You create an .xlsx file with the structure outlined below (<a href="http://cdn2.hubspot.net/hubfs/381527/API_ExcelFile.xlsx">sample file</a>)</li>
			<li>You record your offline sales activity in the spreadsheet</li>
			<li>Once you have access to the internet again, you come to this page and upload the file</li>
			<li>the uploader tool adds all your sales activites to the relevant contacts.
				Sales activities that are in relation to new contacts will automatically trigger those new contacts to be created in the HubSpot CRM.</li>
		</ol>
		<br>
	<h3>Requirements for the spreadsheet:</h3>
  <ul>
    <li>Format: .xlsx</li>
		<li>The xlsx file must contain the following structure (column names can be customised but the order must be the same):</li>
			<ol>
				<li>Email address of the contact</li>
	    	<li>Type of Activity. The following values are accepted:</li>
	    		<ul>
			      <li>email</li>
			      <li>call</li>
			      <li>meeting</li>
			      <li>task</li>
			      <li>note</li>
	    		</ul>
		    <li>Description (= the actual content)</li>
		    <li>Date (Format: dd-mm-yyyy)</li>
		    <li>Time (Format: hh:mm)</li>
		    <li>First Name (only needed for new Contacts)</li>
		    <li>Last Name (only needed for new Contacts)</li>
			</ol>
  </ul>
  Download a sample XLSX file <a href="http://cdn2.hubspot.net/hubfs/381527/API_ExcelFile.xlsx">here</a>.
	</div>
	<div class="right-side">
		<h3>Import your .xlsx file:</h3>
		<div class="form-wrapper">
				<!--<form method="post" action="test.php" enctype="multipart/form-data">-->
				<form method="post" action="crmUploaderMain.php" enctype="multipart/form-data">
				<fieldset>
					<!--<legend>My super duper form</legend>-->
					<p>
					<label for="email" >Your Email *</label>
					<input type="email" name="email" required>
					</p>
					<p>
					<label for="hapikey">Enter HAPI Key *</label>
					<input type="password" name="hapikey" size="40" required>
					</p>
					<p>
          <label for="timezones">Please select your timezone *</label>
          <select name="timezones">
						<option value="no selection">Select a timezone</option>;
          <?php
              // get all timezones from PHP method
              $timezone_identifiers = DateTimeZone::listIdentifiers(DateTimeZone::ALL);
              // add all those options to drop down field
              for($i = 0; $i < sizeof($timezone_identifiers); $i++){
                  echo '<option value="'.$timezone_identifiers[$i].'">'.$timezone_identifiers[$i].'</option>';
              }
          ?>
          </select>
				  </p>
					<p>
					<label for="file">Choose XLSX file *</label>
					<input type="file" name="file" required>
					</p>
					<p>
					<input type="submit" id="submit" value="Import"/>
					</p>
				</fieldset>
			</form>
		</div>	<!-- end of form wrapper -->
	</div>	<!-- end of right-side div-->
</div> <!-- end of body wrapper -->
</div> <!-- end of body wrapper container -->
<div class="footer-wrapper">
	<div class="footer">&copy;&nbsp;Daniel Bertschi 2015&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;@SwissRoll</div>
</div>
</body>
</html>
