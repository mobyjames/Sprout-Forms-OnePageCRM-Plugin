<?php
namespace Craft;

class SproutForms_OnePageCrm_ContactsService extends BaseApplicationComponent
{
  public function saveContactWithEntry($entry)
  {
    $allKnownFields = array(
      'elementId', 'locale', 'title',
      'firstname', 'first_name', 'firstName',
      'lastname', 'last_name', 'lastName',
      'name', 'fullname', 'full_name', 'fullName',
      'company', 'companyname', 'company_name', 'companyName',
      'email', 'emailaddress', 'email_address', 'emailAddress',
      'phone', 'phoneNumber', 'phone_number', 'phoneNumber',
      'ownerId', 'leadSourceId'
    );

    $firstName = $this->findValueLike($entry, array('firstname', 'first_name', 'firstName'), 'Unknown');
    $lastName = $this->findValueLike($entry, array('lastname', 'last_name', 'lastName'), 'Unknown');
    $fullName = $this->findValueLike($entry, array('name', 'fullname', 'full_name', 'fullName'), 'Unknown');
    $company = $this->findValueLike($entry, array('company', 'companyname', 'company_name', 'companyName'), '');
    $email = $this->findValueLike($entry, array('email', 'emailaddress', 'email_address', 'emailAddress'), '');
    $phone = $this->findValueLike($entry, array('phone', 'phoneNumber', 'phone_number', 'phoneNumber'), '');
    $ownerId = $this->findValueLike($entry, array('ownerId'), null);
    $leadSourceId = $this->findValueLike($entry, array('leadSourceId'), null);
    $description = '';

    // Using a full name field instead?
    if (!empty($fullName)) {
      $parts = explode(' ', $fullName);
      $firstName = $parts[0];
      $lastName = count($parts) > 1 ? $parts[1] : 'Unknown';
    }

    // all unused fields
    $attributeNames = $entry->content->attributeNames();

    foreach ($attributeNames as $name) {
      if (!in_array($name, $allKnownFields)) {
        $description .= $name . ': ' . $entry[$name] . ', ';
      }
    }

   $this->postLead($firstName, $lastName, $email, $company, $description, $phone, $ownerId, $leadSourceId);
  }

  private function findValueLike($entry, $names, $default)
  {
    $attributeNames = $entry->content->attributeNames();

    foreach ($names as $name) {
      if (in_array($name, $attributeNames)) {
        return $entry[$name];
      }
    }

    return $default;
  }

  private function postLead($firstname, $lastname, $email, $companyName, $description, $phone = null, $ownerId = null, $leadSourceId = null)
  {
    $username = craft()->config->get('username', 'sproutforms_onepagecrm');
    $password = craft()->config->get('password', 'sproutforms_onepagecrm');
    $defaultOwnerId = craft()->config->get('defaultOwnerId', 'sproutforms_onepagecrm');
    $defaultLeadSourceId = craft()->config->get('defaultLeadSourceId', 'sproutforms_onepagecrm');
    $ownerId = $ownerId ? $ownerId : $defaultOwnerId;
    $leadSourceId = $leadSourceId ? $leadSourceId : $defaultLeadSourceId;

    // Login
    $data = $this->makeApiCall('login.json', 'POST', array('login' => $username, 'password' => $password));

    if ($data == null) {
        return false;
    }

    // Get UID and API key from result
    $uid = $data->data->user_id;
    $key = base64_decode($data->data->auth_key);

    // Create sample contact and delete it just after
    $contactData = array(
      'first_name' => $firstname,
      'last_name' => $lastname,
      'company_name' => $companyName,
      'lead_source_id' => $leadSourceId,
      'owner_id' => $ownerId,
      'background' => $description,
      'emails' => array(
        array('type' => 'work', 'value' => $email)
      ),
      'tags' => 'web',
      'partial' => 1
    );

    if (!empty($phone)) {
      $contactData['phones'] = array(
        array('type' => 'work', 'value' => $phone)
      );
    }

    $newContact = $this->makeApiCall('contacts.json', 'POST', $contactData, $uid, $key);

    if ($newContact == null) {
        return false;
    }

    return true;
  }

  private function makeApiCall($url, $httpMethod, $postDate = array(), $uid = null, $key = null)
  {
    $baseUrl = craft()->config->get('apiUrl', 'sproutforms_onepagecrm');
    $fullUrl = $baseUrl . $url;
    $ch = curl_init($fullUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $httpMethod);
    $timestamp = time();
    $authData = array($uid, $timestamp, $httpMethod, sha1($fullUrl));
    $requestHeaders = array();

    // For POST and PUT requests we will send data as JSON
    // as with regular "form data" request we won't be able
    // to send more complex structures
    if ($httpMethod == 'POST' || $httpMethod == 'PUT') {
      $requestHeaders[] = 'Content-Type: application/json';
      $json_data = json_encode($postDate);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
      $authData[] = sha1($json_data);
    }

    // Set auth headers if we are logged in
    if($key != null){
      $hash = hash_hmac('sha256', implode('.', $authData), $key);
      $requestHeaders[] = "X-OnePageCRM-UID: $uid";
      $requestHeaders[] = "X-OnePageCRM-TS: $timestamp";
      $requestHeaders[] = "X-OnePageCRM-Auth: $hash";
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, $requestHeaders);
    $raw = curl_exec($ch);
    $result = json_decode($raw);
    curl_close($ch);

    if (!empty($result) && $result->status > 99) {
        return null;
    }

    return $result;
  }
}