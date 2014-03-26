<?php

namespace EbayFacile;

set_time_limit(0);

require 'conf.php';

# See: http:# stackoverflow.com/questions/3616540/format-xml-string
function format_xml_string($xml){
  $xml = preg_replace('/(>)(<)(\/*)/', "$1\n$2$3", $xml);
  $token = strtok($xml, "\n");
  $result = '';
  $pad = 0;
  $matches = array();
  while ($token !== false) {
    if (preg_match('/.+<\/\w[^>]*>$/', $token, $matches))
      $indent=0;
    elseif (preg_match('/^<\/\w/', $token, $matches)) {
      $pad--;
      $indent = 0;
    } elseif (preg_match('/^<\w[^>]*[^\/]>.*$/', $token, $matches))
      $indent=1;
    else
      $indent = 0; 
    $line = str_pad($token, strlen($token)+$pad, ' ', STR_PAD_LEFT);
    $result .= $line . "\n";
    $token = strtok("\n");
    $pad += $indent;
  }
  return $result;
}

# Generates an indexed URL snippet from the array of item filters
function build_URL_Array($filterarray) {
  static $urlfilter = '';
  static $i = 0; # Initialize the item filter index to 0.
  # Iterate through each filter in the array
  foreach($filterarray as $itemfilter) {
    # Iterate through each key in the filter
    foreach ($itemfilter as $key =>$value) {
      if(is_array($value)) {
        foreach($value as $j => $content) { #  Index the key for each value
          $urlfilter .= "&itemFilter($i).$key($j)=$content";
        }
      }
      else {
        if($value != "") {
          $urlfilter .= "&itemFilter($i).$key=$value";
        }
      }
    }
    $i++;
  }
  return $urlfilter;
}

/*
op_name: type of call, e.g. findItemsByKeywords, findItemsIneBayStores.
safe_query: call parameter, e.g. keywords.
url_filter: filter string as returned by build_URL_Array.
*/
function searchcall($op_name, $safe_query, $url_filter = NULL,
    $page_number = 1, $entries_per_page = 100) {
  global $app_id;

  $safe_query = urlencode($safe_query);  # Make the query URL-friendly

  # Construct the findItemsByKeywords HTTP GET call
  $api_call = SEARCH_ENDPOINT . "?OPERATION-NAME=$op_name&SERVICE-VERSION=" . VERSION
    . "&SECURITY-APPNAME=$app_id&GLOBAL-ID=" . GLOBAL_ID . "&RESPONSE-DATA-FORMAT=XML"
    . "&REST-PAYLOAD&storeName=" . STORE_NAME . "&keywords=$safe_query"
		. "&paginationInput.pageNumber=$page_number"
		. "&paginationInput.entriesPerPage=$entries_per_page$url_filter";

  # Load the call and capture the document returned by eBay API.
  return simplexml_load_file($api_call);
}

# Determine number of pages in a response.
# See: http://developer.ebay.com/DevZone/finding/CallRef/types/PaginationInput.html
function pages($resp, $entries_per_page = ENTRIES_PER_PAGE) {
  return ceil($resp->paginationOutput->totalEntries / ENTRIES_PER_PAGE);
}

/*
  Generates an array of string to be used as the headers for the HTTP request to eBay
  Output:	String Array of Headers applicable for this call
*/
function build_ebay_headers($verb) {
  global $dev_id, $app_id, $cert_id;
  $headers = array (
    # Regulates versioning of the XML interface for the API
    'X-EBAY-API-COMPATIBILITY-LEVEL: ' . COMPAT_LEVEL,
    
    # set the keys
    'X-EBAY-API-DEV-NAME: ' . $dev_id,
    'X-EBAY-API-APP-NAME: ' . $app_id,
    'X-EBAY-API-CERT-NAME: ' . $cert_id,
    
    # the name of the call we are requesting
    'X-EBAY-API-CALL-NAME: ' . $verb,
    
    # SiteID must also be set in the Request's XML
    # SiteID = 0  (US) - UK = 3, Canada = 2, Australia = 15, ....
    # SiteID Indicates the eBay site to associate the call with
    'X-EBAY-API-SITEID: ' . SITE_ID,
  );
  
  return $headers;
}

/*
  Sends a HTTP request to the server for this session
  Input:	$requestBody
  Output:	The HTTP Response as a String
*/
function send_http_request($verb, $request_body) {
  global $server_url;

  # build eBay headers using variables passed via constructor
  $headers = build_ebay_headers($verb);

  # DEBUG
  #log_msg(var_dump($headers));
  #log_msg($request_body);
  
  # initialise a CURL session
  $connection = curl_init();
  # set the server we are using (could be Sandbox or Production server)
  curl_setopt($connection, CURLOPT_URL, $server_url);
  
  # stop CURL from verifying the peer's certificate
  curl_setopt($connection, CURLOPT_SSL_VERIFYPEER, 0);
  curl_setopt($connection, CURLOPT_SSL_VERIFYHOST, 0);
  
  curl_setopt($connection, CURLOPT_CONNECTTIMEOUT, CONNECTTIMEOUT);
  curl_setopt($connection, CURLOPT_TIMEOUT, TIMEOUT);

  # set the headers using the array of headers
  curl_setopt($connection, CURLOPT_HTTPHEADER, $headers);
  
  # set method as POST
  curl_setopt($connection, CURLOPT_POST, 1);
  
  # set the XML body of the request
  curl_setopt($connection, CURLOPT_POSTFIELDS, $request_body);
  
  # set it to return the transfer as a string from curl_exec
  curl_setopt($connection, CURLOPT_RETURNTRANSFER, 1);
  
  # Send the Request. Retry up to a certain number of times.
  for ($tries = 1; $tries <= MAX_TRIES; $tries++) {
    if (($response = curl_exec($connection)) === FALSE) {
      if ($tries == MAX_TRIES) {
        log_msg(curl_error($connection));
        die();
      } else {
        log_msg(curl_error($connection), E_USER_WARNING);
      }
    } else {
      break;
    }
  }
  
  # close the connection
  curl_close($connection);
  
  # DEBUG
  #log_msg(format_xml_string($response));

  # return the response
  return $response;
}
	
/*
verb: edit operation name.
xml: xml code that defined the updates.
*/
function updatecall($verb, $xml) {
  global $user_token;

  # Build the request Xml string.
  $request_xml_body = <<<RXB
  <?xml version="1.0" encoding="utf-8" ?>
  <{$verb}Request xmlns="urn:ebay:apis:eBLBaseComponents">
   <RequesterCredentials><eBayAuthToken>$user_token</eBayAuthToken></RequesterCredentials>
   $xml
  </{$verb}Request>
RXB;

	# DEBUG
	#log_msg($request_xml_body);

	# DEBUG
	#$result = send_http_request($verb, $request_xml_body);
	#echo format_xml_string($result);
	#return simplexml_load_string($result);

  return simplexml_load_string(send_http_request($verb, $request_xml_body));
}


function log_msg($error_msg, $error_type = E_USER_NOTICE) {
  echo "$error_msg<br>"; flush();
  trigger_error($error_msg);
}

function log_xml($xml) {
  log_msg(format_xml_string($xml->asXML()));
}

?>
