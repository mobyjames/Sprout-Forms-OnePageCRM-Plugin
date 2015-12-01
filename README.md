# Sprout-Forms-OnePageCRM-Plugin

A Craft CMS plugin that integrates your Sprout Forms with OnePageCRM.

OnePageCRM is a great little CRM for small business. Why not send your Craft form data into it for leads, etc?

http://www.onepagecrm.com

###Installation

- Install Sprout Forms http://sprout.barrelstrengthdesign.com/craft-plugins/forms
- Install Sprout Fields (optional) http://sprout.barrelstrengthdesign.com/craft-plugins/fields
- Install OnePageCRM plugin by copying the folder named sproutforms_onepagecrm to your plugin folder
- Create a file in /craft/config/ named sproutforms_onepagecrm.php
- Fill in your settings using the template below as a guide

```
<?php

return array(
    'username' => 'you@email.com',

    'password' => 'yourpassword',

    'defaultOwnerId' => '5bd212a51727fa4f3a002b53',

    'defaultLeadSourceId' => '55ccf3541787fa40cef'
);
```

To find ownerIds:

- navigate to an existing contact
- Click the dropdown for the owner
- Hover over an option and select "copy link"
- The Id is the last part of the URL
- Maybe there's a better way?

To find leadSourceIds:

- Click on a lead source (left side)
- The id is the last part of the URL

You can override the default lead source and owner by including hidden fields in your forms with ownerId and leadSourceId handles and appropriate values.


###Setup Forms
- Create a form in the usual way
- The following field handles are automatically supported and will populate in new contacts with each form submit

```
firstname
first_name
firstName
lastname
last_name
lastName
name
fullname
full_name
fullName
company
companyname
company_name
companyName
email
emailaddress
email_address
emailAddress
phone
phoneNumber
phone_number
phoneNumber
ownerId
leadSourceId
```