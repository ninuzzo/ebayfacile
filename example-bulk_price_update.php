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
  for ($codes = $done = $errors = 0; (list($code, $price, $quantity, $shipment_cost) = fgetcsv($handle, 100, ';')) !== FALSE; $codes++) {
		$page = 1;
		do {
			$resp = Ebayfacile\searchcall('findItemsIneBayStores', $code, $filter_string, $page, ENTRIES_PER_PAGE);

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
						<Quantity>$quantity</Quantity>
						<ShippingDetails>
						 <ApplyShippingDiscount>false</ApplyShippingDiscount>
						 <CalculatedShippingRate>
							<WeightMajor measurementSystem="English" unit="lbs">0</WeightMajor>
							<WeightMinor measurementSystem="English" unit="oz">0</WeightMinor>
						 </CalculatedShippingRate>
						 <InsuranceFee currencyID="EUR">0.0</InsuranceFee>
						 <InsuranceOption>NotOffered</InsuranceOption>
						 <SalesTax>
							<SalesTaxPercent>0.0</SalesTaxPercent>
							<ShippingIncludedInTax>false</ShippingIncludedInTax>
						 </SalesTax>
						 <ShippingServiceOptions>
							<ShippingService>IT_ExpressCourier</ShippingService>
							<ShippingServiceCost currencyID="EUR">$shipment_cost</ShippingServiceCost>
							<ShippingServiceAdditionalCost currencyID="EUR">$shipment_cost</ShippingServiceAdditionalCost>
							<ShippingServicePriority>1</ShippingServicePriority>
							<ExpeditedService>true</ExpeditedService>
							<ShippingTimeMin>1</ShippingTimeMin>
							<ShippingTimeMax>2</ShippingTimeMax>
						 </ShippingServiceOptions>
						 <ShippingType>Flat</ShippingType>
						 <ThirdPartyCheckout>false</ThirdPartyCheckout>
						 <InsuranceDetails>
							<InsuranceOption>NotOffered</InsuranceOption>
						 </InsuranceDetails>
						 <ShippingDiscountProfileID>0</ShippingDiscountProfileID>
						 <InternationalShippingDiscountProfileID>0</InternationalShippingDiscountProfileID>
						 <SellerExcludeShipToLocationsPreference>true</SellerExcludeShipToLocationsPreference>
						</ShippingDetails>
						<ShipToLocations>IT</ShipToLocations>
						<Site>Italy</Site>
					 </Item>
EOX;
					$resp2 = EbayFacile\updatecall('ReviseFixedPriceItem', $xml);
					# See: http://developer.ebay.com/DevZone/bulk-data-exchange/CallRef/abortJob.html
					if ($resp2->Ack != 'Success' && $resp2->Ack != 'Warning') {
						EbayFacile\log_xml($resp2);
						$errors++;
						break 2;
					}
					else {
						# DEBUG
						#EbayFacile\log_xml($resp2);

						# DEBUG
						#echo $item->itemId, "\n";
						
						$done++;
					}
					
					# DEBUG
					#die();
				}
				# DEBUG
				EbayFacile\log_msg("update done for code $code");
			} else {
				# Print an error.
				$errors++;
				EbayFacile\log_msg("Item code $code not found.", E_USER_ERROR);

				# DEBUG
				#EbayFacile\log_xml($resp);
			}
      $page++;
		} while ($page <= EbayFacile\pages($resp));
  }

  EbayFacile\log_msg("Codes updated: $codes"); 
  EbayFacile\log_msg("Update calls done: $done"); 
  EbayFacile\log_msg("Errors encountered: $errors"); 
}

StopWatch\stop();

?>
