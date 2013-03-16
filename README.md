PHP
===

Libraries and code samples written in PHP language and utilizing Solve360 External API and WebHooks

Contents
========

- **Solve360Service.php** - a one-file library to access Solve360 External API
- **contactSaveOOP** - an HTML form and a script to submit data to Solve360 CRM directly (http://norada.com/?uri=norada/entry/external_api_introduction)
- **webhooksphp** - example for the webhooks tutorial (http://norada.com/?uri=norada/trainingwebhooks)

Solve360Service usage examples
==============================
Generally, feel free to dig into the public API of the class to see what's available, all the methods and their arguments are self-explanatory. To create an instance use:

```php
require 'Solve360Service.php';
$solve360Service = new Solve360Service('yourEmail', 'yourApiToken');
```

from then use can create contacts, companies, blogs, different activities, set relation between items, tag them etc:

```php
$contact = $solve360Service->getContact(479038);
$newContact = $solve360Service->addContact(array(
    'firstname' => 'John', 'lastname' => 'Smith', 'personalemail' => 'john@example.com'));
echo $contact->item->name;
$solve360Service->addContactRelation($contact->item->id, $newContact->item->id);
```
