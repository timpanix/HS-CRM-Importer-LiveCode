# HS-CRM-Importer

At the time of writing the HubSpot CRM has limited offline functionality. It is possible to create contacts, companies and deals offline. They can then be imported into the system via CSV import. However, it is currently not possible to log Sales Activities offline and then bring them into the system in a straight forward fashion. The only options are manual copy & paste or setting up your own API connector.
<br>This app provides a simple upload functionality to automate this process:
It parses an XLSX file line by line and translates every line into a separate activity on the relevant contact record. 
If there is no such contact in the CRM yet, it will automatically create a new contact and assign the lead owner
to save on API calls, the app will store previously looked up ContactIDs in an array. The Contacts API is only pinged for an ID if the same email address was not used previously in the same session

This app uses the following APIs:<br><ul>
<li>HubSpot Owner API: http://developers.hubspot.com/docs/methods/owners/get_owners</li>
<li>HubSpot Contacts API: http://developers.hubspot.com/docs/methods/contacts/contacts-overview</li>
<li>HubSpot Engagements API: http://developers.hubspot.com/docs/methods/engagements/create_engagement</li>
<li>SimpleXLSX API written by Sergey Shuchkin
http://www.phpclasses.org/package/6279-PHP-Parse-and-retrieve-data-from-Excel-XLS-files.html</li></ul>

A big thank you goes to Sergey Shuchkin for his SimpleXLSX API. Particularly his examples made the first part of this project a breeze.

PLEASE NOTE: The files are currently configured to be run on localhost. If used on production server, all references to cacert.pem need to be removed. 
