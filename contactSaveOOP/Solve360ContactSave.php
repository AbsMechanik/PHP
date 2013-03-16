<?php

// version 2.2.2

// Paramaters you change begin with "your" e.g. yourEmail@yourDomain.com, yourSolve360Token,
// yourOwnership, yourCategoryId, yourTemplateId should be replaced with your own values

// REQUIRED Edit with the email address you login to Solve360 with
define('USER', 'yourSolve360EmailAddress');
// REQUIRED Edit with token, Solve360 menu > My Account > API Reference > API Token
define('TOKEN', 'yourSolve360Token');

// Get request data
$requestData = array();
parse_str($_SERVER['QUERY_STRING'], $requestData);

// Configure service gateway object
require 'Solve360Service.php';
$solve360Service = new Solve360Service(USER, TOKEN);

//
// Preparing the contact data
//
$contactFields = array(
    // field name in Solve360 => field name as specified in html form
    'firstname' => 'firstname',
    'lastname' => 'lastname',
    'jobtitle' => 'jobtitle',
    'businessemail' => 'businessemail',
);
$contactData = array(
    // OPTION Apply category tag(s) and set the owner for the contact to a group
    // You will find a list of IDs for category tags, groups and users in Solve360 menu > My Account > API Reference
    // To enable this option, uncomment the following:

    /*
    // Specify a different ownership i.e. share the item
    'ownership'     => yourOwnership,

    // Add categories
    'categories'    => array(
        'add' => array('category' => array(yourCategoryId,yourCategoryId))
    ),
    */
);
// adding not empty fields
foreach ($contactFields as $solve360FieldName => $requestFieldName) {
    if ($requestData[$requestFieldName]) {
        $contactData[$solve360FieldName] = $requestData[$requestFieldName];
    }
}

//
// Saving the contact
//
// If there was business email provided:
// check if the contact already exists by searching for a matching email address.
// if a match is found update the existing contact, otherwise create a new one.
//
if ($contactData['businessemail']) {
    $contacts = $solve360Service->searchContacts(array(
        'filtermode' => 'byemail',
        'filtervalue' => $contactData['businessemail'],
    ));
}
if (isset($contacts) && (integer)$contacts->count > 0) {
    $contactId = (integer)current($contacts->children())->id;
    $contactName = (string)current($contacts->children())->name;
    $contact = $solve360Service->editContact($contactId, $contactData);
} else {
    $contact = $solve360Service->addContact($contactData);
    $contactName = (string)$contact->item->name;
    $contactId = (integer)$contact->item->id;
}

if (isset($contact->errors)) {
    // Email the error
    mail(
        USER,
        'Error while adding contact to Solve360',
        'Error: ' . $contact->errors->asXml()
    );
    die ('System error');
} else {
    // Email the result
    mail(
        USER,
        'Contact posted to Solve360',
        'Contact "' . $contactName . '" https://secure.solve360.com/contact/' . $contactId . ' was posted to Solve360'
    );
}

//
// OPTION Adding a activity
//

/*
* You can attach an activity to the contact you just posted
* This example creates a Note, to enable this feature just uncomment the following request
*
*/

/*
// Preparing data for the note
$noteData = array(
    'details' => nl2br($requestData['note'])
);

$note = $solve360Service->addActivity($contactId, 'note', $noteData);

// Email the result
mail(
    USER,
    'Note was added to "' . $contactName . '" contact in Solve360',
    'Note with id ' . $note->id . ' was added to the contact with id ' . $contactId
);
// End of adding note activity
*/

//
// OPTION Inserting a template of activities
//

/*
* You can also insert a template directly into the contact you just posted
* You will find a list of IDs for templates in Solve360 menu > My Account > API Reference
* To enable this feature just uncomment the following request
*
*/

/*
// Start of template request
$templateId = yourTemplateId;
$template = $solve360Service->addActivity($contactId, 'template', array('templateid' => $templateId));

// Email the result
mail(
    USER,
    'Template was added to "' . $contactName . '" contact in Solve360',
    'Template with id ' . $templateId . ' was added to the contact with id ' . $contactId
);
// End of template request
*/

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
</head>
<body>
<h2>Result</h2>

<p>Thank you, <b><?php echo $contactName ?></b></p>

<p>The information was saved.</p>
</body>
</html> 