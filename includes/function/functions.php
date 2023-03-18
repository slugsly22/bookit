<?php

/** @wordpress-plugin
 * Author:            cWebco WP Plugin Team
 * Author URI:        https://leadtrail.io/
 */
/* Function for session message */

if (!function_exists('set_error_message')) {
  function set_error_message($msg, $type)
  {
    @session_start();
    if (isset($_SESSION['error_msg'])) :
      unset($_SESSION['error_msg']);
    endif;

    $_SESSION['error_msg']['msg'] = $msg;
    $_SESSION['error_msg']['error'] = $type;
    return true;
  }
}

if (!function_exists('show_error_message')) {
  function show_error_message()
  {
    $msg = '';
    @session_start();

    if (isset($_SESSION['error_msg']) && isset($_SESSION['error_msg']['msg'])) :
      if ($_SESSION['error_msg']['error'] == '1') :
        $tp = 'message_error';
      else :
        $tp = 'message_success';
      endif;
      $msg .= '<div class="portlet light pro_mess"><div class="message center pmpro_message ' . $tp . '">';
      $msg .= esc_html($_SESSION['error_msg']['msg']);
      $msg .= '</div></div>';
      unset($_SESSION['error_msg']['msg']);
      unset($_SESSION['error_msg']['error']);
      unset($_SESSION['error_msg']);
    endif;

    return $msg;
  }
}

if (!function_exists('pr')) {
  function pr($post)
  {
    echo '<pre>';
    print_r($post);
    echo '</pre>';
  }
}

if (!function_exists('do_account_redirect')) {
  //Finishing setting templates 
  function do_account_redirect($url)
  {
    global $post, $wp_query;

    if (have_posts()) {
      include($url);
      die();
    } else {
      $wp_query->is_404 = true;
    }
  }
}

function ghaxlead_enqueue_script()
{
  wp_enqueue_script('jquery');
  wp_enqueue_script('jquery-ui');

  $buy_lead_page = get_option('buy_lead_page');
  wp_enqueue_script('lead-custom-js', GHAX_LEADTRAIL_RELPATH . 'public/assets/js/custom_jquery.js', array('jquery'), '1.1.2', 'all');
  wp_localize_script(
    'lead-custom-js',
    'ajax_script',
    array('ajaxurl' => admin_url('admin-ajax.php'), 'redirecturl' => get_permalink($buy_lead_page))
  );
}




add_action('wp_enqueue_scripts', 'ghaxlead_enqueue_script');

add_action('init', 'GHAXlt_create_buyer_role');
function GHAXlt_create_buyer_role()
{
  if (get_option('GHAXlt_custom_roles_version') < 1) {
    add_role('ghaxlt_buyer', 'Leadtrail Buyer', array('read' => true, 'level_0' => true));
    update_option('GHAXlt_custom_roles_version', 1);
  }
}

function ghax_obfuscate_email($email)
{
  $em   = explode("@", $email);
  $name = implode('@', array_slice($em, 0, count($em) - 1));
  $len  = floor(strlen($name) / 2);
  $endlen  = floor(strlen(end($em)));

  return substr($name, 0, $len) . str_repeat('*', $len) . "@" . str_repeat('*', $endlen);
}


function ghax_checkEmail($email)
{
  $find1 = strpos($email, '@');
  $find2 = strpos($email, '.');
  return ($find1 !== false && $find2 !== false && $find2 > $find1);
}

/* Function for Redirect */
if (!function_exists('foreceRedirect')) {
  function ghax_foreceRedirect($filename)
  {
    if (!headers_sent())
      header('Location: ' . esc_url($filename));
    else {
      echo '<script type="text/javascript">';
      echo 'window.location.href="' . esc_html($filename) . '";';
      echo '</script>';
      echo '<noscript>';
      echo '<meta http-equiv="refresh" content="0;url=' . esc_html($filename) . '" />';
      echo '</noscript>';
    }
  }
}
/* Function for Redirect --ENDS */
if (!do_action('elementor/loaded')) {
  /**** this function is being used for capturing elementor form data to database ******/
  add_action('elementor_pro/forms/new_record', function ($record, $ajax_handler) {
    global $wpdb;
    //make sure its our form
    $settings = $record->get('form_settings');
    $form_id = $settings['id'];
    $fpost_id = $settings['form_post_id'];
    $nform_id = $fpost_id . '-' . $form_id;
    $qry = "SELECT * FROM " . $wpdb->prefix . "ghaxlt_lead_groups WHERE forms LIKE '%" . $nform_id . "%'";
    $res = $wpdb->get_row($qry);
    $form_name = $record->get_form_settings('form_name');
    // Replace MY_FORM_NAME with the name you gave your form
    /*if ( 'Capture Leads Form' !== $form_name ) {
        return;
    }*/
    $lead_quantity = '';
    $raw_fields = $record->get('fields');
    $fields = [];
    foreach ($raw_fields as $id => $field) {
      if ($field['type'] == 'lead-quantity') {
        $fields['lead-quantity'] = $field['value'];
        $lead_quantity = $field['value'];
      } else if ($field['type'] == 'lead-zipcode' || $field['type'] == 'lead-city' || $field['type'] == 'lead-state' || $field['type'] == 'lead-country') {
        $type = $field['type'];
        $fields[$type] = $field['value'];
      } else {
        $fields[$id] = $field['value'];
      }
    }

    global $wpdb;
    $farr = $fields;
    if (get_option('lead_publish')) {
      if (get_option('lead_publish') == 'yes') {
        $publish = 1;
      } else {
        $publish = 0;
      }
    } else {
      $publish = 1;
    }
    $data = array(
      'form_name' => $form_name,
      'data' => json_encode($farr),
      'lead_quantity' => $lead_quantity,
      'created_date' => date('Y-m-d H:i:s'),
      'group' => $res->id,
      'submitted_by' => 'elementor',
      'status' => 'open',
      'publish' => intval($publish)

    );
    $tbllds = $wpdb->prefix . 'ghaxlt_leads';
    $output['success'] = $wpdb->insert($tbllds, $data);
    $lead_id =  $wpdb->insert_id;
    if ($lead_id) {
      send_email_notification_on_lead_creation($lead_id);
    }

    $ajax_handler->add_response_data(true, $output);
  }, 10, 2);
}

/***** save contact form 7 submissions to database *********/

/*if(function_exists('contactform7_before_send_mail'))
{*/
function GHAXlt_contactform7_before_send_mail($form_to_DB)
{
  //set your db details
  global $wpdb;
  $form_to_DB = WPCF7_Submission::get_instance();
  $contact_form = $form_to_DB->get_contact_form();

  $tags = $contact_form->scan_form_tags();
  $wpcf7 = WPCF7_ContactForm::get_current();
  $lead_quantity = '';
  $form_id = $wpcf7->id;
  $qry = "SELECT * FROM " . $wpdb->prefix . "ghaxlt_lead_groups WHERE forms LIKE '%" . $form_id . "%'";
  $res = $wpdb->get_row($qry);

  if ($form_to_DB)
    $formData = $form_to_DB->get_posted_data();

  $form_name = $contact_form->title;
  foreach ($tags as $tag) {
    if ($tag->type == 'leadquantity') {
      $lead_quantity = $formData[$tag->name];
      $formData['lead-quantity'] = $lead_quantity;
      unset($formData[$tag->name]);
    }
    if ($tag->type == 'leadcountry') {
      $formData['lead-country'] = $formData[$tag->name];
      unset($formData[$tag->name]);
    }
    if ($tag->type == 'leadcity') {
      $formData['lead-city'] = $formData[$tag->name];
      unset($formData[$tag->name]);
    }
    if ($tag->type == 'leadstate') {
      $formData['lead-state'] = $formData[$tag->name];
      unset($formData[$tag->name]);
    }
    if ($tag->type == 'leadzipcode') {
      $formData['lead-zipcode'] = $formData[$tag->name];
      unset($formData[$tag->name]);
    }
  }

  $fdata = json_encode($formData);
  if (get_option('lead_publish')) {
    if (get_option('lead_publish') == 'yes') {
      $publish = 1;
    } else {
      $publish = 0;
    }
  } else {
    $publish = 1;
  }
  $tbllds = $wpdb->prefix . 'ghaxlt_leads';
  $wpdb->insert($tbllds, array(
    'form_name' => $form_name, 'data' => $fdata, 'lead_quantity' => $lead_quantity,
    'created_date' => date('Y-m-d H:i:s'), 'group' => $res->id, 'submitted_by' => 'contact-form7', 'status' => 'open', 'publish' => intval($publish)
  ));
  $lead_id =  $wpdb->insert_id;
  if ($lead_id) {
    send_email_notification_on_lead_creation($lead_id);
  }
}
remove_all_filters('wpcf7_before_send_mail');
add_action('wpcf7_before_send_mail', 'GHAXlt_contactform7_before_send_mail');
/*}*/

/****** save wp forsm submission  to database **********/
add_action("wpforms_process_complete", 'GHAXlt_wpforms_save_custom_form_data');

function GHAXlt_wpforms_save_custom_form_data($params)
{
  global $wpdb;
  $keys = array();
  $values = array();
  $frms = wpforms();
  $lead_quantity = '';
  $dta = $frms->process->form_data;
  $form_name = $dta['settings']['form_title'];
  $qry1 = "SELECT * FROM " . $wpdb->prefix . "posts WHERE post_title='$form_name' AND post_type='wpforms'";
  $res1 = $wpdb->get_row($qry1);
  $form_id = $res1->ID;
  $qry = "SELECT * FROM " . $wpdb->prefix . "ghaxlt_lead_groups WHERE forms LIKE '%" . $form_id . "%'";
  $res = $wpdb->get_row($qry);
  foreach ($params as $idx => $item) {

    if ($item['type'] == "address") {
      if (isset($item['address1']) && $item['address1']) {
        $keys[] = 'address1';
        $values[] = $item['address1'];
      }
      if (isset($item['address2']) && $item['address2']) {
        $keys[] = 'address2';
        $values[] = $item['address1'];
      }
      if (isset($item['city']) && $item['city']) {
        $keys[] = 'city';
      }
      if (isset($item['state']) && $item['state']) {
        $keys[] = 'state';
      }
      if (isset($item['postal']) && $item['postal']) {
        $keys[] = 'zipcode';
      }
      $keys[] = $item['name'];
      $values[] = $item['value'];
    } else if ($item['type'] == 'lead-quantity') {
      $keys[] = 'lead-quantity';
      $lead_quantity =  $item['value'];
      $values[] = $item['value'];
    } else if ($item['type'] == 'lead-zipcode' || $item['type'] == 'lead-city' || $item['type'] == 'lead-state' || $item['type'] == 'lead-country') {
      $keys[] = $item['type'];
      $values[] = $item['value'];
    } else {
      $keys[] = $item['name'];
      $values[] = $item['value'];
    }


    // Do whatever you need
  }


  $farr = array_combine($keys, $values);

  $fdata = json_encode($farr);

  if (get_option('lead_publish')) {
    if (get_option('lead_publish') == 'yes') {
      $publish = 1;
    } else {
      $publish = 0;
    }
  } else {
    $publish = 1;
  }
  $tbllds = $wpdb->prefix . 'ghaxlt_leads';
  $wpdb->insert($tbllds, array(
    'form_name' => $form_name, 'data' => $fdata,
    'created_date' => date('Y-m-d H:i:s'), 'group' => $res->id, 'lead_quantity' => $lead_quantity, 'submitted_by' => 'wpforms', 'status' => 'open', 'publish' => intval($publish)
  ));
  $lead_id =  $wpdb->insert_id;
  if ($lead_id) {
    send_email_notification_on_lead_creation($lead_id);
  }
  return true;
}

/*********** save gravity form submissions to database **************/
add_action('gform_after_submission', 'GHAXlt_gform_access_entry_via_field', 10, 2);
function GHAXlt_gform_access_entry_via_field($entry, $form)
{
  global $wpdb;
  $keys = array();
  $values = array();
  $group_id = "";
  $form_name = $form['title'];
  $form_id = $form['id'];
  $qry = "SELECT * FROM " . $wpdb->prefix . "ghaxlt_lead_groups WHERE forms LIKE '%" . $form_id . "%'";
  $res = $wpdb->get_row($qry);
  if ($res) {
    $group_id = $res->id;
  }
  $lead_quantity  = '';
  foreach ($form['fields'] as $field) {
    //echo $field->type;
    $inputs = $field->get_entry_inputs();
    if ($field->type == 'lead-quantity') {
      $keys[] = 'lead-quantity';
      $lead_quantity =  rgar($entry, (string) $field->id);
    } else if ($field->type == 'lead-zipcode' || $field->type == 'lead-city' || $field->type == 'lead-state' || $field->type == 'lead-country') {
      $keys[] = $field->type;
    } else {
      $keys[] = $field['label'];
    }

    if (is_array($inputs)) {
      $value1 = "";
      foreach ($inputs as $input) {

        $value1 .= rgar($entry, (string) $input['id']) . ' ';
        // do something with the value
      }
      $value = $value1;
    } else {
      $value = rgar($entry, (string) $field->id);
      // do something with the value
    }
    $values[] = $value;
  }
  $farr = array_combine($keys, $values);

  $fdata = json_encode($farr);

  if (get_option('lead_publish')) {
    if (get_option('lead_publish') == 'yes') {
      $publish = 1;
    } else {
      $publish = 0;
    }
  } else {
    $publish = 1;
  }
  $tbllds = $wpdb->prefix . 'ghaxlt_leads';
  $wpdb->insert($tbllds, array(
    'form_name' => $form_name, 'data' => $fdata, 'lead_quantity' => $lead_quantity,
    'created_date' => date('Y-m-d H:i:s'), 'group' => $group_id, 'submitted_by' => 'gravity-forms', 'status' => 'open', 'publish' => intval($publish)
  ));
  $lead_id =  $wpdb->insert_id;
  if ($lead_id) {
    send_email_notification_on_lead_creation($lead_id);
  }
}

/********* save forminator submissions to database ******/
//add_action( 'forminator_custom_form_after_save_entry', 'GHAXlt_forminator_save_data' );

function GHAXlt_forminator_save_data($form_id)
{
}


// function my_sendinblue_handle($fields)
// {
//   // this is your function that sends data to SendInBlue over their API
//   // where $fields is an array containing data to be sent
// }

function my_forminator_sendinblue_hook($entry, $form_id, $form_data)
{
  global $wpdb;
  // do something here with $entry, $form_id and $form_data to get them 
  // formatted the way you want/need to work with your code


  $keys = array();
  $values = array();
  $form_name = get_the_title($form_id);
  //$form_name = '';
  foreach ($form_data as $fdata) {
    $keys[] = $fdata['name'];
    $values[] = $fdata['value'];
  }
  $farr = array_combine($keys, $values);
  $fdata = json_encode($farr);
  if (get_option('lead_publish')) {
    if (get_option('lead_publish') == 'yes') {
      $publish = 1;
    } else {
      $publish = 0;
    }
  } else {
    $publish = 1;
  }

  $qry = "SELECT * FROM " . $wpdb->prefix . "ghaxlt_lead_groups WHERE forms LIKE '%" . $form_id . "%'";
  $res = $wpdb->get_row($qry);
  $tbllds = $wpdb->prefix . 'ghaxlt_leads';
  $wpdb->insert($tbllds, array(
    'form_name' => $form_name, 'data' => $fdata,
    'created_date' => date('Y-m-d H:i:s'), 'group' => $res->id, 'submitted_by' => 'forminator', 'status' => 'open', 'publish' => intval($publish)
  ));
  $lead_id =  $wpdb->insert_id;
  if ($lead_id) {
    send_email_notification_on_lead_creation($lead_id);
  }
}
add_action('forminator_custom_form_submit_before_set_fields', 'my_forminator_sendinblue_hook', 10, 3);
/*************** save ninja forms submissions to database *********/
add_action('ninja_forms_after_submission', 'GHAXlt_ninja_forms_after_submission');

function GHAXlt_ninja_forms_after_submission($form_data)
{
  global $wpdb;

  // Do stuff.
  $lead_quantity = '';
  $keys = array();
  $values = array();
  $form_name = $form_data['settings']['title'];
  $form_id = $form_data['form_id'];
  if (!empty($form_data['fields'])) {
    $fields = $form_data['fields'];
  } else {
    $fields = $form_data['fields_by_key'];
  }

  foreach ($fields as $fdata) {
    if ($fdata['type'] == "lead-quantity") {
      $lead_quantity = $fdata['value'];
      $keys[] = $fdata['type'];
    } else if ($fdata['type'] == 'lead-zipcode' || $fdata['type'] == 'lead-city' || $fdata['type'] == 'lead-state' || $fdata['type'] == 'lead-country') {
      $keys[] = $fdata['type'];
    } else {
      $keys[] = $fdata['label'];
    }

    $values[] = $fdata['value'];
  }
  $farr = array_combine($keys, $values);
  $fdata = json_encode($farr);
  if (get_option('lead_publish')) {
    if (get_option('lead_publish') == 'yes') {
      $publish = 1;
    } else {
      $publish = 0;
    }
  } else {
    $publish = 1;
  }

  $qry = "SELECT * FROM " . $wpdb->prefix . "ghaxlt_lead_groups WHERE forms LIKE '%" . $form_id . "%'";
  $res = $wpdb->get_row($qry);
  $tbllds = $wpdb->prefix . 'ghaxlt_leads';
  $wpdb->insert($tbllds, array(
    'form_name' => $form_name, 'data' => $fdata, 'lead_quantity' => $lead_quantity,
    'created_date' => date('Y-m-d H:i:s'), 'group' => $res->id, 'submitted_by' => 'ninja-forms', 'status' => 'open', 'publish' => intval($publish)
  ));

  $lead_id =  $wpdb->insert_id;
  if ($lead_id) {
    send_email_notification_on_lead_creation($lead_id);
  }
}


function ghax_csv_to_array($filename = '', $delimiter = ',')
{
  if (!file_exists($filename) || !is_readable($filename))
    return FALSE;

  $header = [];
  $data = array();
  if (($handle = fopen($filename, 'r')) !== FALSE) {
    while (($row = fgetcsv($handle, 1000, $delimiter)) !== FALSE) {
      if (!$header)
        $header = $row;
      else
        $data[] = array_combine($header, $row);
    }
    fclose($handle);
  }
  return $data;
}

//Not used anywhere
// function verifyTransaction($data)
// {
//   global $paypalUrl;

//   $req = 'cmd=_notify-validate';
//   foreach ($data as $key => $value) {
//     $value = urlencode(stripslashes($value));
//     $value = preg_replace('/(.*[^%^0^D])(%0A)(.*)/i', '${1}%0D%0A${3}', $value); // IPN fix
//     $req .= "&$key=$value";
//   }

//   $ch = curl_init($paypalUrl);
//   curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
//   curl_setopt($ch, CURLOPT_POST, 1);
//   curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//   curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
//   curl_setopt($ch, CURLOPT_SSLVERSION, 6);
//   curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
//   curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
//   curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
//   curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
//   curl_setopt($ch, CURLOPT_HTTPHEADER, array('Connection: Close'));
//   $res = curl_exec($ch);

//   if (!$res) {
//     $errno = curl_errno($ch);
//     $errstr = curl_error($ch);
//     curl_close($ch);
//     throw new Exception("cURL error: [$errno] $errstr");
//   }

//   $info = curl_getinfo($ch);

//   // Check the http response
//   $httpCode = $info['http_code'];
//   if ($httpCode != 200) {
//     throw new Exception("PayPal responded with http code $httpCode");
//   }

//   curl_close($ch);

//   return $res === 'VERIFIED';
// }

function ghax_checkTxnid($txnid)
{
  global $db;

  $txnid = $db->real_escape_string($txnid);
  $results = $db->query('SELECT * FROM `payments` WHERE txnid = \'' . $txnid . '\'');

  return !$results->num_rows;
}

function leadtrail_activate_license($license)
{
  global $wpdb;
  $leadtrail_license_key = $license;

  $args = array(
    'headers' => array(
      'Content-Type' => 'application/json',
      'Authorization' => 'Basic ' . base64_encode(GHAX_LICENSE_PURCHASE_API_USERNAME . ":" . GHAX_LICENSE_PURCHASE_API_PASSWORD)
    )
  );

  $return = wp_remote_get(GHAX_LICENSE_PURCHASE_URL . '/wp-json/lmfwc/v2/licenses/activate/' . $leadtrail_license_key, $args);

  $result = json_decode(wp_remote_retrieve_body($return), true);

  if ($result['data']['status'] == '404') {
    echo esc_html($result['message']);
  } else {
    if ($result['success'] == true) {
      update_option('leadtrail_license_key', $leadtrail_license_key);
      update_option('leadtrail_license_status', 'active');
      update_option('leadtrail_license_expiry_date', $result['data']['expiresAt']);
      wp_redirect(site_url() . '/wp-admin/admin.php?page=leadtrail');
    }
  }
}
/*add_action('init',send_email_notification_on_lead_creation);*/
function send_email_notification_on_lead_creation($lead)
{
  global $wpdb;
  $leaddetail_page = get_option('_leaddetail_page');
  $args = array(
    'role' => 'ghaxlt_buyer',
  );
  $buyers = get_users($args);

  foreach ($buyers as $buyer) {
    $uid = $buyer->data->ID;
    $receive_lead_notifications = get_user_meta($uid, 'receive_lead_notifications', true);
    if ($receive_lead_notifications == 'Yes') {
      $to = $buyer->data->user_email;

      $subject = 'New Lead Posted';

      $headers = "From: " . strip_tags(get_option('admin_email')) . "\r\n";
      $headers .= "Reply-To: " . strip_tags(get_option('admin_email')) . "\r\n";
      $headers .= "MIME-Version: 1.0\r\n";
      $headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
      $message = '<html><body>';
      $message .= '<div class="emailcontainer" style="border:2px solid #74499E;">';
      /* $message .= '<div class="emailheader" style="background:#74499E;color:#fff;padding:1%;">';
			$message .= '<h3 style="text-align:center;">LeadTrail - New Lead Created</h3>';
			$message .= '</div>'; */
      $message .= '<div class="emailcontent" style="background:#fff;padding:2%;">';
      /* $message .= '<p><strong>Dear '.$buyer->data->display_name.',</strong></p>'; */
      $message .= '<p>A new lead has been posted on ' . get_bloginfo("url") . '. <a target="_blank" style="text-decoration:none;color: #734A9D;font-weight:bold" href="' . get_permalink($leaddetail_page) . '/?lead=' . $lead . '">Click here</a> to view it.</p>';
      /* $message .= '<p>Thanks</p>';
			$message .= '<p><strong>Leadtrail Team</strong></p>'; */
      $message .= '</div>';
      $message .= '</div>';
      $message .= '</body></html>';

      wp_mail($to, $subject, $message, $headers);
    }
  }
}
add_action('wp_ajax_nopriv_lead_add_to_cart', 'lead_add_to_cart');
add_action('wp_ajax_lead_add_to_cart', 'lead_add_to_cart');
function lead_add_to_cart()
{
  $user_id = get_current_user_id();
  $leadcart = get_user_meta($user_id, 'leadcart', true);
  $max_lead_purchase = get_option('max_lead_purchase');

  $id = array((int) $_POST['id']);
  if ($leadcart) {
    if (count($leadcart) >= $max_lead_purchase) {
      echo "You can purchased maximum " . (int) $max_lead_purchase . " leads at a time";
      die();
    }
    if (in_array((int) $_POST['id'], $leadcart)) {
      $leadcart1 = $leadcart;
    } else {
      if ($leadcart) {
        $leadcart1 = array_merge($leadcart, $id);
      } else {
        $leadcart1 = $id;
      }
    }
  } else {
    $leadcart1 = $id;
  }

  update_user_meta($user_id, 'leadcart', $leadcart1);
  die();
}
add_action('wp_ajax_nopriv_directleadtobuy', 'directleadtobuy');
add_action('wp_ajax_directleadtobuy', 'directleadtobuy');
function directleadtobuy()
{
  $user_id = get_current_user_id();
  $leadcart = get_user_meta($user_id, 'leadcart', true);
  $id = array((int) $_POST['id']);

  update_user_meta($user_id, 'leadcart', $id);
  die();
}


add_action('wp_ajax_nopriv_lead_remove_cart', 'lead_remove_cart');
add_action('wp_ajax_lead_remove_cart', 'lead_remove_cart');
function lead_remove_cart()
{
  $user_id = get_current_user_id();
  $leadcart = get_user_meta($user_id, 'leadcart', true);
  $del_val = (int) $_POST['id'];
  $id = array((int) $_POST['id']);
  if ($leadcart) {

    $leadcart1 = array_filter($leadcart, function ($e) use ($del_val) {

      return ($e !== $del_val);
    });
    update_user_meta($user_id, 'leadcart', $leadcart1);
  }
  die();
}
if (!function_exists('is_plugin_active')) {
  include_once(ABSPATH . 'wp-admin/includes/plugin.php');
}

if (is_plugin_active('elementor-pro/elementor-pro.php')) {
  function add_new_form_field($form_fields_registrar)
  {

    require_once plugin_dir_path(__dir__) . 'classes/elementor/quantity.php';
    require_once plugin_dir_path(__dir__) . 'classes/elementor/country.php';
    require_once plugin_dir_path(__dir__) . 'classes/elementor/state.php';
    require_once plugin_dir_path(__dir__) . 'classes/elementor/city.php';
    require_once plugin_dir_path(__dir__) . 'classes/elementor/zipcode.php';

    $form_fields_registrar->register(new \Elementor_Quantity_Field());
    $form_fields_registrar->register(new \Elementor_Country_Field());
    $form_fields_registrar->register(new \Elementor_State_Field());
    $form_fields_registrar->register(new \Elementor_City_Field());
    $form_fields_registrar->register(new \Elementor_Zipcode_Field());
  }
  add_action('elementor_pro/forms/fields/register', 'add_new_form_field');
}

if (is_plugin_active('gravityforms/gravityforms.php')) {
  add_filter('gform_field_groups_form_editor', 'add_new_group', 10, 1);
  function add_new_group($field_groups)
  {
    $field_groups[] = array(
      'name'   => 'lead_trail',
      'label'  => __('Lead Trail Field', 'Lead-Trail'),
      'fields' => array()
    );
    return $field_groups;
  }
  if (class_exists('GF_Field')) {
  } else {
    require_once(GHAX_LEADTRAIL_PLUGIN_DIR . '/gravityforms/includes/fields/class-gf-field.php');
  }

  require_once plugin_dir_path(__dir__) . 'classes/gravity/quantity.php';
  require_once plugin_dir_path(__dir__) . 'classes/gravity/country.php';
  require_once plugin_dir_path(__dir__) . 'classes/gravity/state.php';
  require_once plugin_dir_path(__dir__) . 'classes/gravity/city.php';
  require_once plugin_dir_path(__dir__) . 'classes/gravity/zipcode.php';
}

if (is_plugin_active('ninja-forms/ninja-forms.php')) {
  add_filter('ninja_forms_field_type_sections',  'add_leadtrail_plugin_settings_group');
  function add_leadtrail_plugin_settings_group($sections)
  {
    $sections['lead_trail'] = array(
      'id' => 'lead_trail',
      'nicename' => __('Lead Trail Field', 'ninja-forms-lead-trail'),
      'fieldTypes' => array(),
    );

    return $sections;
  }
  require_once(GHAX_LEADTRAIL_PLUGIN_DIR . '/ninja-forms/includes/Abstracts/Element.php');
  require_once(GHAX_LEADTRAIL_PLUGIN_DIR . '/ninja-forms/includes/Abstracts/Field.php');
  require_once(GHAX_LEADTRAIL_PLUGIN_DIR . '/ninja-forms/includes/Abstracts/Input.php');
  require_once(GHAX_LEADTRAIL_PLUGIN_DIR . '/ninja-forms/includes/Fields/Textbox.php');
  require_once(GHAX_LEADTRAIL_PLUGIN_DIR . '/ninja-forms/includes/Abstracts/List.php');
  require_once plugin_dir_path(__dir__) . 'classes/ninjaform/country.php';
  require_once plugin_dir_path(__dir__) . 'classes/ninjaform/state.php';
  require_once plugin_dir_path(__dir__) . 'classes/ninjaform/city.php';
  require_once plugin_dir_path(__dir__) . 'classes/ninjaform/zipcode.php';
  require_once plugin_dir_path(__dir__) . 'classes/ninjaform/quantity.php';
}
if (is_plugin_active('contact-form-7/wp-contact-form-7.php')) {
  if (class_exists('WPCF7_TagGenerator')) {
  } else {
    require_once(GHAX_LEADTRAIL_PLUGIN_DIR . '/contact-form-7/admin/includes/tag-generator.php');
  }

  require_once plugin_dir_path(__dir__) . 'classes/contactform7/quantity.php';
  require_once plugin_dir_path(__dir__) . 'classes/contactform7/country.php';

  require_once plugin_dir_path(__dir__) . 'classes/contactform7/state.php';
  require_once plugin_dir_path(__dir__) . 'classes/contactform7/city.php';
  require_once plugin_dir_path(__dir__) . 'classes/contactform7/zipcode.php';
}
if (is_plugin_active('wpforms-lite/wpforms.php') || is_plugin_active('wpforms/wpforms.php')) {
  add_filter('wpforms_settings_tabs',  'register_settings_tabs_lite', 5, 1);
  function register_settings_tabs_lite($tabs)
  {

    // Add Payments tab.
    $payments = array(
      'lead-trail' => array(
        'name'   => esc_html__('Lead Trail', 'wpforms'),
        'form'   => true,
        'submit' => esc_html__('Save Settings', 'wpforms'),
      ),
    );

    $tabs = wpforms_array_insert($tabs, $payments, 'validation');

    return $tabs;
  }
  if (is_plugin_active('wpforms-lite/wpforms.php')) {
    $wfpugin_path = GHAX_LEADTRAIL_PLUGIN_DIR . '/wpforms-lite/';
  } else {
    $wfpugin_path = GHAX_LEADTRAIL_PLUGIN_DIR . '/wpforms/';
  }
  if (defined('WPFORMS_PLUGIN_DIR')) {
  } else {
    define('WPFORMS_PLUGIN_DIR', $wfpugin_path);
  }

  if (class_exists('WPForms_Fields')) {
  } else {
    require_once(WPFORMS_PLUGIN_DIR . 'includes/class-fields.php');
  }

  require_once plugin_dir_path(__dir__) . 'classes/wpforms/quantity.php';
  require_once plugin_dir_path(__dir__) . 'classes/wpforms/country.php';
  require_once plugin_dir_path(__dir__) . 'classes/wpforms/city.php';
  require_once plugin_dir_path(__dir__) . 'classes/wpforms/state.php';
  require_once plugin_dir_path(__dir__) . 'classes/wpforms/zipcode.php';
}
add_filter('ninja_forms_field_template_file_paths', 'ghax_customfile_path');
function ghax_customfile_path($paths)
{

  $paths[] = GHAX_LEADTRAIL_ABSPATH . '/LeadTrail/includes/classes/ninjaform/templates/';

  return $paths;
}

function ghax_head_hook_function()
{
?>
  <script type="text/javascript">
    var states;
    jQuery.getJSON("<?php echo plugin_dir_url(__dir__); ?>classes/json/states.json").done(function(data) {
      states = data;
    });

    function autocomplete(inp, arr) {
      var currentFocus;
      var positionInfo = inp.getBoundingClientRect();
      inp.addEventListener("input", function(e) {
        var a, b, i, val = this.value;
        closeAllLists();
        if (!val) {
          return false;
        }
        currentFocus = -1;
        a = document.createElement("DIV");
        a.setAttribute("id", this.id + "autocomplete-list");
        a.setAttribute("class", "autocomplete-items");
        a.style.width = positionInfo.width + 'px';
        this.parentNode.appendChild(a);
        for (i = 0; i < arr.length; i++) {
          if (arr[i].substr(0, val.length).toUpperCase() == val.toUpperCase()) {
            b = document.createElement("DIV");
            b.innerHTML = "<strong>" + arr[i].substr(0, val.length) + "</strong>";
            b.innerHTML += arr[i].substr(val.length);
            b.innerHTML += "<input type='hidden' value='" + arr[i] + "'>";
            b.addEventListener("click", function(e) {
              //console.log(this.getElementsByTagName("input")[0].value);
              inp.value = this.getElementsByTagName("input")[0].value;
              closeAllLists();
              var event = document.createEvent("UIEvents"); // See update below
              event.initUIEvent("change", true, true); // See update below
              inp.dispatchEvent(event);
            });
            a.appendChild(b);
          }
        }


      });
      inp.addEventListener("keydown", function(e) {
        var x = document.getElementById(this.id + "autocomplete-list");
        if (x) x = x.getElementsByTagName("div");
        if (e.keyCode == 40) {
          currentFocus++;
          addActive(x);
        } else if (e.keyCode == 38) {
          currentFocus--;
          addActive(x);
        } else if (e.keyCode == 13) {
          e.preventDefault();
          if (currentFocus > -1) {
            if (x) x[currentFocus].click();
          }
        }
      });

      function addActive(x) {
        if (!x) return false;
        removeActive(x);
        if (currentFocus >= x.length) currentFocus = 0;
        if (currentFocus < 0) currentFocus = (x.length - 1);
        x[currentFocus].classList.add("autocomplete-active");
      }

      function removeActive(x) {
        for (var i = 0; i < x.length; i++) {
          x[i].classList.remove("autocomplete-active");
        }
      }

      function closeAllLists(elmnt) {
        var x = document.getElementsByClassName("autocomplete-items");
        for (var i = 0; i < x.length; i++) {
          if (elmnt != x[i] && elmnt != inp) {
            x[i].parentNode.removeChild(x[i]);
          }
        }

      }
      document.addEventListener("click", function(e) {
        closeAllLists(e.target);
      });
    }
  </script>
<?php
}
add_action('wp_head', 'ghax_head_hook_function');
add_action('admin_head', 'ghax_head_hook_function');
add_action('admin_footer', 'ghax_footer_css_function');
add_action('wp_footer', 'ghax_footer_css_function');

/*
add_action( 'wp_ajax_lead_display', 'ghax_lead_display_function' );
add_action( 'wp_ajax_nopriv_lead_display', 'ghax_lead_display_function' );
function ghax_lead_display_function(){
	global $wpdb;
	$tbllds = $wpdb->prefix.'ghaxlt_leads';		
	$tblcats = $wpdb->prefix.'ghaxlt_lead_cats';		
	$tblgrps = $wpdb->prefix.'ghaxlt_lead_groups';
	$tblqlty = $wpdb->prefix.'ghaxlt_lead_qualities';	
	$tblpymts = $wpdb->prefix.'ghaxlt_leads_payments';	
	$requestData = $_REQUEST;

	$where_condition = "";
    if($_REQUEST['country']){
        $where_condition .= "country LIKE '%".$requestData['country']."%' "; 
    }
    if($_REQUEST['state']){
        $where_condition .= "state LIKE '%".$requestData['state']."%' "; 
    }
    if($_REQUEST['city']){
        $where_condition .= "city LIKE '%".$requestData['city']."%' "; 
    }
    if($_REQUEST['zipcode']){
        $where_condition .= "zipcode LIKE '%".$requestData['zipcode']."%' "; 
    }
    if($_REQUEST['custsearch']){
        $where_condition .= "(group_name LIKE '%".$requestData['custsearch']."%' or quality_name LIKE '%".$requestData['custsearch']."%')"; 
    }
    $where = "";
    if($where_condition){
    	$where = " where ".$where_condition;
    }
	$result_count = $wpdb->get_results("select lead.*,cat.name as cat_name,grps.name as group_name,qlty.name as quality_name,grps.price+qlty.price as totalprice,(SELECT count(*) as count FROM {$tblpymts} WHERE lead_id=lead.id  order by id desc) as buylead,substring_index(substring_index(data, '\"lead-country\":\"', -1), '\",', 1) AS country,substring_index(substring_index(data, '\"lead-state\":\"', -1), '\",', 1) AS state,substring_index(substring_index(data, '\"lead-city\":\"', -1), '\",', 1) AS city,substring_index(substring_index(data, '\"lead-zipcode\":\"', -1), '\",', 1) AS zipcode from {$wpdb->prefix}ghaxlt_leads as lead left join  {$tblcats} as cat on lead.category=cat.id left join  {$tblgrps} as grps on lead.group=grps.id left join  {$tblqlty} as qlty on lead.quality=qlty.id ".$where,ARRAY_A);
	$result = $wpdb->get_results("select lead.*,cat.name as cat_name,grps.name as group_name,qlty.name as quality_name,grps.price+qlty.price as totalprice,(SELECT count(*) as count FROM {$tblpymts} WHERE lead_id=lead.id  order by id desc) as buylead,substring_index(substring_index(data, '\"lead-country\":\"', -1), '\",', 1) AS country,substring_index(substring_index(data, '\"lead-state\":\"', -1), '\",', 1) AS state,substring_index(substring_index(data, '\"lead-city\":\"', -1), '\",', 1) AS city,substring_index(substring_index(data, '\"lead-zipcode\":\"', -1), '\",', 1) AS zipcode from {$wpdb->prefix}ghaxlt_leads as lead left join  {$tblcats} as cat on lead.category=cat.id left join  {$tblgrps} as grps on lead.group=grps.id left join  {$tblqlty} as qlty on lead.quality=qlty.id ".$where,ARRAY_A);  

	
	print_r(count($count_result));die();
   	   ## Total number of records with filtering
   


} */

function ghax_footer_css_function()
{ ?>
  <style type="text/css">
    .select-state-text {
      position: relative !important;
    }

    .autocomplete-items {
      position: absolute !important;
      border: 1px solid #d4d4d4 !important;
      border-bottom: none !important;
      border-top: none !important;
      z-index: 99 !important;
      /*position the autocomplete items to be the same width as the container:*/
      top: 100% !important;
      left: 0;
      right: 0;
    }

    .autocomplete-items div {
      padding: 10px !important;
      cursor: pointer !important;
      background-color: #fff !important;
      border-bottom: 1px solid #d4d4d4 !important;
    }

    /*when hovering an item:*/
    .autocomplete-items div:hover {
      background-color: #e9e9e9 !important;
    }

    /*when navigating through the items using the arrow keys:*/
    .autocomplete-active {
      background-color: DodgerBlue !important;
      color: #ffffff !important;
    }
  </style>
<?php
}

add_action('gform_editor_js', 'ghax_custom_option_editor_script');
function ghax_custom_option_editor_script()
{
?>
  <script type='text/javascript'>
    jQuery('.choices_setting')
      .on('input', '.field-choice-text--lead-quantity,.field-choice-value--lead-quantity', function() {
        var $this = jQuery(this);
        $this.val($this.val().replace(/[^0-9]/g, '').replace(/(\..*?)\..*/g, '$1'));
      });
  </script>
<?php
}

function ghax_search($array, $key, $value)
{
  $results = array();
  if (is_array($array)) {
    if (isset($array[$key]) && $array[$key] == $value) {
      $results[] = $array;
    }
    foreach ($array as $subarray) {
      $results = array_merge(
        $results,
        ghax_search($subarray, $key, $value)
      );
    }
  }
  return $results;
}


/* adds stylesheet file to the end of the queue */
function ghax_wpdocs_override_stylesheets()
{
  $dir = plugin_dir_url(__FILE__);
  wp_enqueue_style('theme-override', 'https://fonts.googleapis.com/css2?family=Fredericka+the+Great&family=Jost:ital,wght@0,700;0,800;0,900;1,100&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap', array(), '0.1.0', 'all');
}
add_action('wp_enqueue_scripts', 'ghax_wpdocs_override_stylesheets', PHP_INT_MAX);

/**
 * 
 * Enqueue admin side scripts and styles
 * 
 * @author VJ @GWS
 * @return void
 */
function leadtrail_admin_enqueues()
{
  $screen = get_current_screen();
  $leadtrail_screens = array("leadtrail_page_leads","leadtrail_page_lead_groups","leadtrail_page_lead_qualities","leadtrail_page_lead_categories","leadtrail_page_lead_import","leadtrail_page_lead_settings","leadtrail_page_lead_payments","leadtrail_page_leads_about","leadtrail_page_lead-support","leadtrail_page_leadtrail-license","admin_page_edit_lead_data","admin_page_display_form_submission","admin_page_edit_group","admin_page_edit_quality","admin_page_edit_category","admin_page_create_category","admin_page_create_group","admin_page_create_quality");
	
  if ($screen->base == 'toplevel_page_leadtrail') {
    //dashboard
    wp_enqueue_style('leadtrail-waves', GHAX_LEADTRAIL_RELPATH . 'admin/assets/pages/waves/css/waves.min.css');
    wp_enqueue_style('leadtrail-bootstrap', GHAX_LEADTRAIL_RELPATH . 'admin/assets/css/bootstrap/css/bootstrap.min.css');
    wp_enqueue_style('leadtrail-fawesome', GHAX_LEADTRAIL_RELPATH . 'admin/fontawesome/css/all.min.css');
    wp_enqueue_style('leadtrail-adminfonts', GHAX_LEADTRAIL_RELPATH . 'admin/css/admin-fonts.css');

    wp_enqueue_style('leadtrail-themify', GHAX_LEADTRAIL_RELPATH . 'admin/assets/icon/themify-icons/themify-icons.css');

    wp_enqueue_style('leadtrail-fawes', GHAX_LEADTRAIL_RELPATH . 'admin/fontawesome/css/fontawesome.min.css');
    wp_enqueue_style('leadtrail-morris', GHAX_LEADTRAIL_RELPATH . 'admin/assets/css/morris.js/css/morris.css');

    wp_enqueue_style('leadtrail-scroll', GHAX_LEADTRAIL_RELPATH . 'admin/assets/css/jquery.mCustomScrollbar.css');

    wp_enqueue_style('leadtrail-stylee', GHAX_LEADTRAIL_RELPATH . 'admin/assets/css/style.css');
    wp_enqueue_style('leadtrail-admin', GHAX_LEADTRAIL_RELPATH . 'admin/css/admin.css');
    wp_enqueue_style('leadtrail-adminfont', GHAX_LEADTRAIL_RELPATH . 'admin/css/admin-fonts.css');
	wp_enqueue_style('leadtrail-admin-button', GHAX_LEADTRAIL_RELPATH . 'admin/css/button.css');
    //JS
    wp_enqueue_script('leadtrail-pop', GHAX_LEADTRAIL_RELPATH . 'admin/assets/js/popper.js/popper.min.js', array('jquery'), GHAX_VERSION);
    wp_enqueue_script('leadtrail-btjs', GHAX_LEADTRAIL_RELPATH . 'admin/assets/js/bootstrap/js/bootstrap.min.js ', array('jquery'), GHAX_VERSION);

    wp_enqueue_script('leadtrail-waves', GHAX_LEADTRAIL_RELPATH . 'admin/assets/pages/waves/js/waves.min.js', array('jquery'), GHAX_VERSION);

    wp_enqueue_script('leadtrail-slim', GHAX_LEADTRAIL_RELPATH . 'admin/assets/js/jquery-slimscroll/jquery.slimscroll.js', array('jquery'), GHAX_VERSION);

    wp_enqueue_script('leadtrail-raphael', GHAX_LEADTRAIL_RELPATH . 'admin/assets/js/raphael/raphael.min.js', array('jquery'), GHAX_VERSION);
    wp_enqueue_script('leadtrail-morris', GHAX_LEADTRAIL_RELPATH . 'admin/assets/js/morris.js/morris.js', array('jquery'), GHAX_VERSION);

    wp_enqueue_script('leadtrail-chart', GHAX_LEADTRAIL_RELPATH . 'admin/assets/pages/chart/morris/morris-custom-chart.js', array('jquery'), GHAX_VERSION);
    wp_enqueue_script('leadtrail-pcoded', GHAX_LEADTRAIL_RELPATH . 'admin/assets/js/pcoded.min.js', array('jquery'), GHAX_VERSION);
    wp_enqueue_script('leadtrail-vertical', GHAX_LEADTRAIL_RELPATH . 'admin/assets/js/vertical/vertical-layout.min.js', array('jquery'), GHAX_VERSION);
    wp_enqueue_script('leadtrail-cscroll', GHAX_LEADTRAIL_RELPATH . 'admin/assets/js/jquery.mCustomScrollbar.concat.min.js', array('jquery'), GHAX_VERSION);
    wp_enqueue_script('leadtrail-chartjs', GHAX_LEADTRAIL_RELPATH . 'admin/assets/js/cdn/chart.js', array('jquery'), GHAX_VERSION);
    wp_enqueue_script('leadtrail-scrp', GHAX_LEADTRAIL_RELPATH . 'admin/assets/js/script.js', array('jquery'), GHAX_VERSION);
	  
  } else if (in_array($screen->base, $leadtrail_screens) ) {
    /**ghaxit-admin**/
    wp_enqueue_style('leadtrail-comps', GHAX_LEADTRAIL_RELPATH . 'admin/assets/js/cdn/material-components-web.min.css');
    wp_enqueue_style('leadtrail-datatable', GHAX_LEADTRAIL_RELPATH . 'admin/assets/js/cdn/dataTables.material.min.css');

    // wp_enqueue_style($this->plugin_name, plugin_dir_url(dirname(dirname(__FILE__))) . 'admin/css/admin.css', array(), 18.2, 'all');
    wp_enqueue_style('leadtrail-admin', GHAX_LEADTRAIL_RELPATH . 'admin/css/admin.css');
    wp_enqueue_style('leadtrail-adminfont', GHAX_LEADTRAIL_RELPATH . 'admin/css/admin-fonts.css');
	 wp_enqueue_style('leadtrail-admin-button', GHAX_LEADTRAIL_RELPATH . 'admin/css/button.css');

    ////wp_enqueue_style($this->plugin_name, "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css", array(), $this->version, 'all');

    wp_enqueue_style('leadtrail-gfont', "https://fonts.googleapis.com/css2?family=Fredericka+the+Great&family=Jost:ital,wght@0,700;0,800;0,900;1,100&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap");
    // wp_enqueue_style($this->plugin_name, plugin_dir_url(dirname(dirname(__FILE__))) . 'admin/css/admin.css', array(),  'all');
    // wp_enqueue_style( $this->plugin_name.'timepicker', plugin_dir_url(dirname(dirname(__FILE__))) . 'admin/css/jquery.timepicker.css', array(), $this->version, 'all' );
    // wp_enqueue_style( $this->plugin_name.'datepicker', plugin_dir_url(dirname(dirname(__FILE__))) . 'admin/css/jquery-ui-1.10.4.custom.css', array(), $this->version, 'all' );

    wp_enqueue_script('leadtrail-swtjs', GHAX_LEADTRAIL_RELPATH . 'admin/assets/js/cdn/sweetalert2@11.js', array('jquery'), GHAX_VERSION);
    wp_enqueue_script('leadtrail-dtjs', GHAX_LEADTRAIL_RELPATH . 'admin/assets/js/cdn/jquery.dataTables.min.js', array('jquery'), GHAX_VERSION);
    wp_enqueue_script('leadtrail-dtmaterial', GHAX_LEADTRAIL_RELPATH . 'admin/assets/js/cdn/dataTables.material.min.js', array('jquery'), GHAX_VERSION);
  }else{
	  wp_enqueue_style('leadtrail-admin-button', GHAX_LEADTRAIL_RELPATH . 'admin/css/button.css');
  }
}
add_action("admin_enqueue_scripts", 'leadtrail_admin_enqueues');

/**
 * 
 * Frontend enqueues
 * 
 * @author VJ @GWS
 * @return void
 */
function leadtrail_frontend_enqueues()
{

  //short-codes.php
  wp_enqueue_style('leadtrail-bt', GHAX_LEADTRAIL_RELPATH . 'admin/assets/js/cdn/bootstrap.min.css');

  //wp_enqueue_style('leadtrail-comps', GHAX_LEADTRAIL_RELPATH . 'admin/assets/js/cdn/material-components-web.min.css');
  wp_enqueue_style('leadtrail-dt', GHAX_LEADTRAIL_RELPATH . 'admin/assets/js/cdn/jquery.dataTables.min.css');
  wp_enqueue_style('leadtrail-datatable', GHAX_LEADTRAIL_RELPATH . 'admin/assets/js/cdn/dataTables.material.min.css');

  wp_enqueue_script('leadtrail-btjs', GHAX_LEADTRAIL_RELPATH . 'admin/assets/js/cdn/bootstrap.min.js', array('jquery'), GHAX_VERSION);
  wp_enqueue_script('leadtrail-swt', GHAX_LEADTRAIL_RELPATH . 'admin/assets/js/cdn/sweetalert2@11.js', array('jquery'), GHAX_VERSION);
  wp_enqueue_script('leadtrail-dtjs', GHAX_LEADTRAIL_RELPATH . 'admin/assets/js/cdn/jquery.dataTables.min.js', array('jquery'), GHAX_VERSION);

  if (!is_admin() && $GLOBALS['pagenow'] != 'wp-login.php') {
    $component_css_path = GHAX_LEADTRAIL_RELPATH . 'public/assets/css/components.css';
    // wp_enqueue_style('components', $component_css_path,array(),'1.14', 'all');
    wp_enqueue_style('components', $component_css_path, array(), '22.18', 'all');
  }
}
add_action("wp_enqueue_scripts", 'leadtrail_frontend_enqueues');



