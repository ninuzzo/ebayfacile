<?php

/*
Bulk template update.
Ref. https://ebay.custhelp.com/app/answers/detail/a_id/477
*/

require 'ebayfacile.php';
require 'stopwatch.php';

StopWatch\start();

/*
If there are more than 200 items, then the HasMoreEntries in the response is 
true.  In that case, increase the PageNumber by 1 and continue making the 
call till HasMoreEntries is false. 
 */
$templates_updated = $page_errors = $item_errors = 0; $page = 1;
# DEBUG:
# use low EntriesPerPage (e.g. 2)
do {
	/*
	Specify the current time as the EndTimeFrom, and a date greater than 3 months 
	in the future, as the EndTimeTo, to assure you receive Active items only.

	DEBUG:
	<WarningLevel>High</WarningLevel>
	<Sort>2</Sort>
	*/
	$endTimeFrom = date('Y-m-d\TH:i:s.000\Z', time());
	$endTimeTo = date('Y-m-d\TH:i:s.000\Z', time() + 31*24*60*60); # 1 month ahead
	$xml = <<<EOX
	<ErrorLanguage>en_US</ErrorLanguage>
	<DetailLevel>ItemReturnDescription</DetailLevel>
	<OutputSelector>HasMoreItems,ItemArray.Item.ItemID,ItemArray.Item.Description</OutputSelector>
	<EndTimeFrom>$endTimeFrom</EndTimeFrom>
	<EndTimeTo>$endTimeTo</EndTimeTo>
	<Pagination>
	 <EntriesPerPage>1</EntriesPerPage>
	 <PageNumber>1</PageNumber>
	</Pagination>
EOX;

	$resp = Ebayfacile\updatecall('GetSellerList', $xml);

	# DEBUG:
	#EbayFacile\log_xml($resp);

	if ($resp->Ack != 'Success' && $resp->Ack != 'Warning') {
    $page_errors++;
		EbayFacile\log_xml($resp);
	} else {
		# Update HTML description code for all items in this page.
		foreach ($resp->ItemArray->Item as $item) {
			# DEBUG
			#EbayFacile\log_xml($item);

			# TODO: put here some PHP code to create the new description HTML code.
			# This is a sample, which just searches and replaces for a string:
		  $description = str_replace('SEARCH_STRING_HERE', 'REPLACEMENT_STRING_HERE', $item->Description);
			# ---------------------------------------------------------------------

			$xml = <<<EOX
         <Item>
          <ItemID>$item->ItemID</ItemID>
          <Description><![CDATA[$description]]></Description>
         </Item>
EOX;
      $resp = EbayFacile\updatecall('ReviseFixedPriceItem', $xml);

			# DEBUG
      #EbayFacile\log_xml($item->ItemID);

			if ($resp->Ack != 'Success' && $resp->Ack != 'Warning') {
			  EbayFacile\log_xml($resp);
				$item_errors++;
			} else {
				$templates_updated++;
			}
		}
	}

  $page++;

	# DEBUG
	#sleep(5);
} while ($resp->HasMoreItems == 'true');

EbayFacile\log_msg("Templates updated: $templates_updated");
EbayFacile\log_msg("Page errors encountered: $page_errors");
EbayFacile\log_msg("Item errors encountered: $item_errors");

StopWatch\stop();
?>
