<?php

/*
Bulk price update of items in your store. Items are initially located by 
searching a keyword in a title. An external CSV file defines keywords and new 
prices, one per line. All items matching a certain keyword are assigned the 
same new price.
*/

require 'ebayfacile.php';
require 'stopwatch.php';

StopWatch\start();

# Create a PHP array of the item filters you want to use in your request
$filter_array =
  array(
  /*
    array(
    'name' => 'ListingType',
    'value' => array('AuctionWithBIN','FixedPrice','StoreInventory'),
    'paramName' => '',
    'paramValue' => ''),
  */
  );

# Build the indexed item filter URL snippet.
$filter_string = EbayFacile\build_URL_Array($filter_array);

if (($handle = fopen('new_prices.csv', 'r')) != FALSE) {
  for ($codes = $done = $errors = 0; (list($code, $price) = fgetcsv($handle, 100, ';')) !== FALSE; $codes++) {
    $resp = Ebayfacile\searchcall('findItemsIneBayStores', $code, $filter_string);

    # Check to see if the request was successful, else print an error
    if ($resp->ack == 'Success') {
      # DEBUG
      EbayFacile\log_msg("search done for code $code");

      # DEBUG
      #EbayFacile\log_xml($resp);
      #die();

      # If the response was loaded, parse it and build links
      foreach ($resp->searchResult->item as $item) {
        $xml = <<<EOX
         <Item>
          <ItemID>$item->itemId</ItemID>
          <StartPrice currencyID="EUR">$price</StartPrice>
         </Item>
EOX;
        $resp = EbayFacile\updatecall('ReviseFixedPriceItem', $xml);
        # See: http://developer.ebay.com/DevZone/bulk-data-exchange/CallRef/abortJob.html
        if ($resp->Ack != 'Success' && $resp->Ack != 'Warning') {
          EbayFacile\log_xml($resp);
          $errors++;
          break 2;
        }
        else {
          $done++;
        }
      }
      # DEBUG
      EbayFacile\log_msg("update done for code $code");
    } else {
      # Print an error.
      $errors++;
      EbayFacile\log_msg("Item code $code not found.", E_USER_ERROR);
    }
  }
  EbayFacile\log_msg("Codes updated: $codes"); 
  EbayFacile\log_msg("Update calls done: $done"); 
  EbayFacile\log_msg("Errors encountered: $errors"); 
}

StopWatch\stop();

?>
