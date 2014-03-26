<?php
/*  © 2013 eBay Inc., All Rights Reserved */ 
/* Licensed under CDDL 1.0 -  http://opensource.org/licenses/cddl1.php */

# TODO: fare diventare le variabili costanti, usare i namespace

# show all errors - useful whilst developing
error_reporting(E_ALL);

const
  # site_id must also be set in the Request's XML
  # site_id = 0  (US) - UK = 3, Canada = 2, Australia = 15, Italy = 101, ....
  # site_id Indicates the eBay site to associate the call with
  SITE_ID = 101,  # Italy.

  # Global ID of the eBay site you want to search (e.g., EBAY-DE)
  GLOBAL_ID = 'EBAY-IT',

  # Number of connection attempts before giving up
  MAX_TRIES = 3,

  # Bump up default timeout values (78 and 13 on Windows)
  CONNECTTIMEOUT = 300,
  TIMEOUT = 150,

  # URL to call
  SEARCH_ENDPOINT = 'http://svcs.ebay.com/services/search/FindingService/v1',

  VERSION = '1.0.0',  # API version supported by your application

  # These keys can be obtained by registering at http://developer.ebay.com

  PRODUCTION = true,   #  toggle to true if going against production
  COMPAT_LEVEL = 535,    #  eBay API version

  STORE_NAME = 'YOUR-STORE-NAME-GOES-HERE',
	
	# Min 1, max 100. See:
	# http://developer.ebay.com/DevZone/finding/CallRef/findItemsIneBayStores.html
	ENTRIES_PER_PAGE = 100;

if (PRODUCTION) {
  $dev_id = 'YOUR-DEV-ID-KEY-GOES-HERE';   #  these prod keys are different from sandbox keys
  $app_id = 'YOUR-APP-ID-KEY-GOES-HERE';
  $cert_id = 'YOUR-CERT-ID-KEY-GOES-HERE';
  # set the Server to use (Sandbox or Production)
  $server_url = 'https://api.ebay.com/ws/api.dll';      // server URL different for prod and sandbox
  # the token representing the eBay user to assign the call with
  $user_token = 'YOUR-USER-TOKEN-GOES-HERE';          
} else {  
  #  sandbox (test) environment
  $dev_id = 'DDD_SAND';         #  insert your devID for sandbox
  $app_id = 'AAA_SAND';   #  different from prod keys
  $cert_id = 'CCC_SAND';  #  need three 'keys' and one token
  # Set the Server to use (Sandbox or Production)
  $server_url = 'https://api.sandbox.ebay.com/ws/api.dll';
  # The token representing the eBay user to assign the call with
  # this token is a long string - don't insert new lines - different from prod token.
  $user_token = 'SANDBOX_TOKEN';                 
}

?>
