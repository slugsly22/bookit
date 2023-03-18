<?php

/** DoDirectPayment NVP example; last modified 08MAY23.
 *
 *  Process a credit card payment. 
 */

$environment = 'sandbox';  // or 'beta-sandbox' or 'live'

/**
 * Send HTTP POST Request
 *
 * @param	string	The API method name
 * @param	string	The POST Message fields in &name=value pair format
 * @return	array	Parsed HTTP Response body
 */
function PPHttpPost($methodName_, $nvpStr_)
{
  global $environment;

  // Set up your API credentials, PayPal end point, and API version.
  $API_UserName = urlencode('sdk-three_api1.sdk.com');
  $API_Password = urlencode('QFZCWN5HZM8VBG7Q');
  $API_Signature = urlencode('A-IzJhZZjhg29XQ2qnhapuwxIDzyAZQ92FRP5dqBzVesOkzbdUONzmOU');
  $API_Endpoint = "https://api-3t.paypal.com/nvp";
  if ("sandbox" === $environment || "beta-sandbox" === $environment) {
    // $API_Endpoint = "https://api-3t.$environment.paypal.com/nvp";
    $API_Endpoint = "https://$environment.paypal.com/nvp";
  }
  $version = urlencode('51.0');

  // Set the API operation, version, and API signature in the request.
  ///$nvpreq = "METHOD=$methodName_&VERSION=$version&PWD=$API_Password&USER=$API_UserName&SIGNATURE=$API_Signature$nvpStr_";
  $nvpreq = array(
    'METHOD' => $methodName_,
    'VERSION' => $version,
    'PWD' => $API_Password,
    'USER' => $API_UserName,
    'SIGNATURE' => $API_Signature . $nvpStr_
  );

  $req = wp_remote_post($API_Endpoint, array(
    'body' => $nvpreq,
    'timeout' => 60,
    'sslverify' => false
  ));

  if (is_wp_error($req)) {
    $err = $req->get_error_message();

    exit("$methodName_ failed: " . $err);
  }

  $httpResponse = wp_remote_retrieve_body($req);

  // Extract the response details.
  $httpResponseAr = explode("&", $httpResponse);

  $httpParsedResponseAr = array();
  foreach ($httpResponseAr as $i => $value) {
    $tmpAr = explode("=", $value);
    if (sizeof($tmpAr) > 1) {
      $httpParsedResponseAr[$tmpAr[0]] = $tmpAr[1];
    }
  }

  if ((0 == sizeof($httpParsedResponseAr)) || !array_key_exists('ACK', $httpParsedResponseAr)) {
    exit("Invalid HTTP Response for POST request($nvpreq) to $API_Endpoint.");
  }

  return $httpParsedResponseAr;
}

// Set request-specific fields.
//$paymentType = urlencode($_POST['Authorization']);				// or 'Sale'
$paymentType =  'Sale';
$firstName = urlencode(sanitize_text_field($_POST['firstname']));
$lastName = urlencode(sanitize_text_field($_POST['lastname']));
$creditCardType = urlencode(sanitize_text_field($_POST['cardtype']));
$creditCardNumber = urlencode(sanitize_text_field($_POST['cardnumber']));
$expDateMonth = sanitize_text_field($_POST['cardmonth']);
// Month must be padded with leading zero
$padDateMonth = urlencode(str_pad($expDateMonth, 2, '0', STR_PAD_LEFT));

$expDateYear = urlencode((int) $_POST['cardyear']);
$cvv2Number = urlencode((int) $_POST['cardcvv']);
$address1 = urlencode(sanitize_textarea_field($_POST['address']));
$address2 = '';
$city = urlencode(sanitize_text_field($_POST['city']));
$state = urlencode(sanitize_text_field($_POST['state']));
$zip = urlencode((int) $_POST['zip']);
$country = 'US';  // US or other valid country code
$amount = '1.00';  //actual amount should be substituted here
$currencyID = 'USD'; // or other currency ('GBP', 'EUR', 'JPY', 'CAD', 'AUD')

// Add request-specific fields to the request string.
$nvpStr =  "&PAYMENTACTION=$paymentType&AMT=$amount&CREDITCARDTYPE=$creditCardType&ACCT=$creditCardNumber" .
  "&EXPDATE=$padDateMonth$expDateYear&CVV2=$cvv2Number&FIRSTNAME=$firstName&LASTNAME=$lastName" .
  "&STREET=$address1&CITY=$city&STATE=$state&ZIP=$zip&COUNTRYCODE=$country&CURRENCYCODE=$currencyID";

// Execute the API operation; see the PPHttpPost function above.
$httpParsedResponseAr = PPHttpPost('DoDirectPayment', $nvpStr);

echo '<pre>';
print_r($_POST);
if ("SUCCESS" == strtoupper($httpParsedResponseAr["ACK"]) || "SUCCESSWITHWARNING" == strtoupper($httpParsedResponseAr["ACK"])) {
  exit('Direct Payment Completed Successfully: ' . print_r($httpParsedResponseAr, true));
} else {
  exit('DoDirectPayment failed: ' . print_r($httpParsedResponseAr, true));
}
