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
from then you can create contacts, companies, blogs, different activities, set relation between items, tag them etc:
```php
$contact = $solve360Service->getContact(479038);
$newContact = $solve360Service->addContact(array(
    'firstname' => 'John', 'lastname' => 'Smith', 'personalemail' => 'john@example.com'));
echo $contact->item->name;
$solve360Service->addContactRelation($contact->item->id, $newContact->item->id);
```

To use the new [batch api](http://norada.com/answers/api/external_api_reference_batch), you can turn it on and off with the methods available:
```php
$solve360Service->enableBatchMode(); // enables batch mode
$key1 = $solve360Service->getContact(479038); // schedules a GET contact request
$key2 = $solve360Service->getContact(299430);
$key3 = $solve360Service->addContact(['firstname' => 'Alex', 'lastname' => 'Steshenko']);
$responses = $solve360Service->requestBatch(); // here the batch HTTP request happens

$contact1 = $responses[$key1]; // we can now retrieve the exact response
$contact2 = $responses[$key2]; // by the keys we were given earlier
$newContact = $responses[$key3];

echo $contact2->item->name; // outputs name of contact 299430
echo $newContact->item->id; // echoes new contact id

$solve360Service->disableBatchMode(); // proceed in the normal regime from now 
```

You can also use the utility class to upload a file to an item, a photo to a photo container or an attachment file to a scheduled email:
```php
// 77721604 is the id of a photo container
$solve360Service->upload(77721604, 'photo', 'C:\Users\Alex\Photos\0612.jpg', 'My File.jpg'); // overwrites the file name to My File.jpg

// 321123 is a contact id
$solve360Service->upload(321123, 'file', '/home/alex/docs/manual.pdf'); // the name will be deferred here as "manual.pdf"
```
Notice, file uploads work with the batches, too.
