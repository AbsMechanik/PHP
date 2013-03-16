<?php

// version 0.1

/* Configuration constants */

// Paramaters you change begin with "your" e.g. yourSolve360EmailAddress, yourSolve360Token,

require_once 'Solve360Service.php';

// User to authenticate as 
define('SOLVE360_LOGIN', 'yourSolve360EmailAddress');
define('SOLVE360_TOKEN', 'yourSolve360Token');

// Secret value you enter in the webhooks panel
define('WEBHOOKS_SECRET', 'yourSecret');

// Category tag that must be applied to trigger the new lead scenario
define('NEW_LEAD_CATEGORY_ID', yourNewLeadCategoryTagID);

// Category tag that must be applied to trigger the new client scenario
define('NEW_CLIENT_CATEGORY_ID', yourNewClientCategoryTagID);

// The field where we'll record how long it's taken to convert a lead to a client
define('LEAD_TO_CLIENT_DAYS_FIELDNAME', 'yourCustomDaysToCloseFieldName');

// The activity template to insert for each new client
define('NEW_CLIENT_TEMPLATE_ID', yourNewClientTemplateID);

// Responsible salesperson and email addresses for contacts with last name starting with A-G, H-P and Q-Z accordingly
define('ASSIGNEE_ID_A_G', yourUserSaleperson1ID);
define('ASSIGNEE_ID_H_P', yourUserSaleperson2ID);
define('ASSIGNEE_ID_Q_Z', yourUserSaleperson3ID);
define('ASSIGNEE_EMAIL_A_G', 'yourUserSalesperson1EmailAddress');
define('ASSIGNEE_EMAIL_H_P', 'yourUserSalesperson2EmailAddress');
define('ASSIGNEE_EMAIL_Q_Z', 'yourUserSalesperson3EmailAddress');

// Mr. Boss email
define('BOSS_EMAIL', 'yourBossEmailAddress');

// Contract task title
define('CONTRACT_TASK_TITLE', 'Receive contract');

// Notifications from
define('NOTIFICATION_SENDER',  'Solve360 <yourEmailAddress>');

// ----------------------------------------------------------------------------

// Getting raw request data
$postdata = file_get_contents("php://input");
// First of all, we verify the request has come from Solve360:
if (hash_hmac('sha256', $postdata, WEBHOOKS_SECRET) !== $_SERVER['HTTP_X_SOLVE360_HMAC_SHA256']) {
    header('Server Error', true, 500);
    die();
}
// Prepare post data as an xml object:
$notification = new SimpleXMLElement($postdata);
// Calling different handlers for different events
$handler = str_replace('.' , '_', $notification->type); // e.g. items.update => items_update
if (!function_exists($handler)) {
    header('Bad event type', true, 500);
    die();
}
call_user_func($handler, $notification);

/**
 * 1. Add Lead category tag
 *
 * @param $notification
 */
function items_update($notification) {
    // We listen to items.update in order to catch the moment when there is
    // enough information available to register a new lead:

    // 1.1 The "Lead" tag must be assigned
    $lead = false;
    foreach ($notification->content->categories->children() as $category) {
        if ($category->id == NEW_LEAD_CATEGORY_ID) {
            $lead = true;
            break;
        }
    }
    if (!$lead) {
        // This contact isn't a lead
        die();
    }

    // 1.2 It must have a email and last name
    if (empty($notification->content->item->fields->lastname)) {
        // No last name
        die();
    }
    $emailSet = false;
    foreach (array('personalemail', 'businessemail', 'otheremail') as $emailField) {
        if (!empty($notification->content->item->fields->{$emailField})) {
            $emailSet = $notification->content->item->fields->{$emailField};
            break;
        }
    }
    if (!$emailSet) {
        // No email set
        die();
    }

    // 1.3 It mustn't be assigned to anyone
    if (!empty($notification->content->item->fields->assignedto)) {
        // Already assigned
        die();
    }

    /* It is a new lead, performing the desired logic: */

    // 1.4 Choosing a manager and assigning to the lead
    $lastNameStartsWith = strtolower(substr($notification->content->item->fields->lastname, 0, 1));
    if ($lastNameStartsWith <= 'g') {
        $managerId = ASSIGNEE_ID_A_G;
        $managerEmail = ASSIGNEE_EMAIL_A_G;
    } elseif ($lastNameStartsWith <= 'p') {
        $managerId = ASSIGNEE_ID_H_P;
        $managerEmail = ASSIGNEE_EMAIL_H_P;
    } else {
        $managerId = ASSIGNEE_ID_Q_Z;
        $managerEmail = ASSIGNEE_EMAIL_Q_Z;
    }
    $solve360Api = new Solve360Service(SOLVE360_LOGIN, SOLVE360_TOKEN);
    $solve360Api->editContact($notification->objectid, array('assignedto' => $managerId));

    // 1.5 Sending the manager an email
    $name = $notification->content->item->name;
    $permalink = 'https://secure.solve360.com/contact/' . $notification->objectid;
    mail($managerEmail, 'A new lead has been assigned to you', "
        Name: $name
        Email: $emailSet
        $permalink
    ", 'From: ' . NOTIFICATION_SENDER . "\r\n" . 'Reply-To: ' . NOTIFICATION_SENDER . "\r\n");
}


/**
 * 2. Add Client category tag
 *
 * @param $notification
 */
function items_categorize($notification) {
    // A contact has just been categorized
    // We need to check if it has both Client and Lead tag set (meaning - it is a new client but it's not processed yet)
    $client = false;
    $lead = false;
    foreach ($notification->content->categories->children() as $category) {
        if ($category->id == NEW_CLIENT_CATEGORY_ID) {
            $client = true;
        }
        if ($category->id == NEW_LEAD_CATEGORY_ID) {
            $lead = true;
        }
        if ($client && $lead) {
            break;
        }
    }
    if (!$client || !$lead) {
        // If it's not a client - we don't need to do anything with it yet
        // If it's not a lead - it has been processed before
        die();
    }

    /* It is a new client, performing the desired logic */

    // 2.1 Removing Lead tag
    $solve360Api = new Solve360Service(SOLVE360_LOGIN, SOLVE360_TOKEN);
    $solve360Api->uncategorizeContact($notification->objectid, NEW_LEAD_CATEGORY_ID);

    // 2.2 Applying the Client tag
    $solve360Api->categorizeContact($notification->objectid, NEW_CLIENT_CATEGORY_ID);

    // 2.3 Inserting a predefined set of activities
    $solve360Api->addActivity($notification->objectid,
        Solve360Service::ACTIVITY_TEMPLATE, array('templateid' => NEW_CLIENT_TEMPLATE_ID));

    // 2.4 Calculate how long it took to convert this lead to a client
    $diff = (time() - strtotime($notification->content->item->created))
        /  (60*60*24); //  60*60*24 is the length of day in seconds
    $solve360Api->editContact($notification->objectid, array(LEAD_TO_CLIENT_DAYS_FIELDNAME => (int) $diff));
}

/**
 * 3. Complete Receive contract task
 *
 * @param $notification
 */
function activities_update($notification) {
    // 3.1 Checking if the task is completed
    if (empty($notification->content->fields->completed)) {
        // Task is not completed
        die();
    }
    // 3.2 Checking the title of the task
    if (strtolower($notification->content->name) != strtolower(CONTRACT_TASK_TITLE)) {
        // This is not a contract task
        die();
    }

    // 3.3 Connection to local database // - you can replace this logic with mysql/sqlite/nosql etc
    $db = new SimpleXMLElement(file_get_contents('db.xml')); // Make sure db.xml is readable AND writable !
    // Checking if the contact has been synced to local db already
    $primaryKey = 'item' . $notification->content->item;
    if (isset($db->{$primaryKey})) {
        // This client has already been synced to local db before
        die();
    }

    // 3.4 Obtaining details for the client from Solve360's API
    $solve360Api = new Solve360Service(SOLVE360_LOGIN, SOLVE360_TOKEN);
    $solveClient = $solve360Api->getContact($notification->content->item);
    // Syncing all fields as they are to local database
    $localClient = $db->addChild($primaryKey);
    foreach ($solveClient->item->fields->children() as $key => $value) {
        $localClient->{$key} = $value;
    }
    $db->saveXML('db.xml');

    // 3.5 Now sending an email to Mr. Boss
    $permalink = 'https://secure.solve360.com/contact/' . $notification->content->item;
    mail(BOSS_EMAIL, 'A contract has just been signed with ' . $solveClient->item->name, "
        Data has been synced to local database.
        Access the contact in Solve360: $permalink
    ", 'From: ' . NOTIFICATION_SENDER . "\r\n" . 'Reply-To: ' . NOTIFICATION_SENDER . "\r\n");

}