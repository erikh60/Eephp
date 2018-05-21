<?php

//
// Test.php -- specific implementation tests for CFP
//

// ------------------ 3 CUSTOM FUNCTIONS WHICH MUST EXIST IN CODE ------------------- //

function cfpshell_order_rows($cart_id)
{
	$all = array();
	$order_row = ee_db_queryone("SELECT id FROM website_orders WHERE cart_id = $cart_id"); // is there an order?
	if ($order_row['id'])
	{
		list($rows1,$rows2,$rows3,$all) = cart_get_rows("order", $cart_id);
	}
	return $all;
}

function cfpshell_cart_rows($cart_id)
{
	//
	// all => rows of ('amount'=>150, 'item_id'=>1456, 'item_type'=>'giftcard', $item_quantity=>1)
	//
	list($rows1,$rows2,$rows3,$all) = cart_get_rows("cart", $cart_id);
	return $all;
}

function cfpshell_product_rows($product_ids_string)
{
	$product_ids_string = preg_replace("/[^0-9\-\,]+/, "", $product_ids_string); // only numeric ids and commas and -(minus)
	$thesql = SELECT charity_id AS id, charity AS name, category_web AS category FROM website_charities WHERE charity_id IN ({$product_ids_string})";
	$rows = db_querymult($thesql);
	return $rows;
}

// ----------------------------------------------- TEST ------------------------------------------- //

if ($_GET['eetest'])
{
	$cart_id = gtm_get_cartid();
	$type = (int)$_GET['type'];
	if ($type)
		gtm_doit();
	else
		gtm_test_html();
}

function print_js($js)
{
	print($js);
	$js2 = htmlentities($js);
	print_r2d($js2);
	print("<hr>");
}

function gtm_get_cartid()
{
	global $cart_id, $order_id;
	if (!$cart_id)
	{
		// first try user_id
		$user_id = (int)$_SESSION['give_user']['id'];
		if ($user_id) $cart_id = checkout_userid2cartid($user_id);
	}

	if (!$cart_id && $order_id)
	{
		// then try order_id
		$cart_id = checkout_orderid2cartid($order_id);
	}

	return $cart_id;
}

function gtm_doit()
{
	global $gtm_js; // OUT

	$url = $_SERVER['REQUEST_URI'];

	$step = "";

	if (strstr($url, "nonprofits-grid.php")) $step = "1";
	if (strstr($url, "nonprofits-list.php")) $step = "1";

	if ($_SERVER['REDIRECT_URL'] == "/cfpdc/nonprofit-detail.php") $step = "2detail";
	if ($_SERVER['REDIRECT_URL'] == "/cfpdc/nonprofit-campaign.php") $step = "2campaign";

	if (strstr($url, "checkout_onestep.php"))
	{
		$step = "3";
		if ($_GET['charity_id']) $_GET['ids'] = $_GET['charity_id']; // submit and fail (no amount)
	}

	if (preg_match("/addtocart.php\?ids=[0-9]/", $url)) $step = "4add"; // addtocart

	if (preg_match("/updated&ids=[0-9]/", $url)) $step = "4remove";

	if (strstr($url, "addtocart.php?ids=&Step=Step2")) $step = "5.1";

	if (strstr($url, "checkout.php?Step21=1")) $step = "5.2";
	if (strstr($url, "login.php")) $step = "5.2";

	if (preg_match("/account.php|account_auto.php|account_update.php/", $url)) $step = "5.3";

	if (strstr($url, "cfpdc/checkout.php?Step=Step3")) $step = "5.4";
	if (strstr($url, "cfpdc/checkout.php?Step=Step4")) $step = "5.5";
	if (strstr($url, "cfpdc/checkout.php?Step=Step34")) $step = "5.5";

	if (strstr($url, "cfpdc/checkout.php") && ($GLOBALS['Step'] == "Step34")) $step = "5.5";

	if (strstr($url, "cfpdc/stripe.php")) $step = "5.6";

	if (strstr($url, "cfpdc/paypal_thankyou.php")) $step = "6";

	if ($step)
	{
		print_r2d("EE=$step");
		//print_r2d($_SESSION);
	}

	if ($step == "1")
	{
		global $gtm_results; // product ids from search results, limited to first 60 for now... since there is a data limit for EE
		if (is_null($gtm_results)) return;
		$gtm_results = array_slice($gtm_results, 0, 30);
		$gtm_results_rows = \Eephp\Eephp::ee_ids2rows($gtm_results);
		//print_r2d($gtm_results);
		//print_r2d($gtm_results_rows);

		$listname = "searchresults";
		if ($_GET['list']) $listname = "searchresults:{$_GET['list']}";

		$gtm_js = \Eephp\Eephp::ee_load_impressions($gtm_results_rows, 1, $listname);
	}

	if (($step == "2detail")||($step == "2campaign"))
	{
		global $gtm_mightlikes; // from detail page

		$chid = $_GET['id']; // detail
		if (!$chid) list($chid,$campaignid) = split("-", $_GET['i']); // campaign uses nonprofit-campaign.php?i=94120-599

		$chid = (int)$chid;
		$gtm_row = \Eephp\Eephp::ee_id2row($chid);

					if (0)
					{
						//TEST
						$gtm_mightlikes = array();
							$gtm_mightlikes[] = 990;
							$gtm_mightlikes[] = 94301;
					}

		if ($step == "2detail")
		{
			$type = "Detail Page";
			$gtm_mightlikes_rows = \Eephp\Eephp::ee_ids2rows($gtm_mightlikes["mightlike"]);
			$mightalsoenjoy = \Eephp\Eephp::ee_load_impressions($gtm_mightlikes_rows, 0, "mightlike");

			$gtm_mightlikes_rows = \Eephp\Eephp::ee_ids2rows($gtm_mightlikes["collaborateswith"]);
			if (sizeof($gtm_mightlikes_rows))
			{
				$mightalsoenjoy .= \Eephp\Eephp::ee_load_impressions($gtm_mightlikes_rows, 0, "collaborateswith");

				$mightalsoenjoy = str_replace("]  'impressions': [", ",", $mightalsoenjoy); // merge the 2 lists
			}
		}
		else
		{
			$type = "Campaign Page";
			$mightalsoenjoy = "";
		}

		$gtm_js = \Eephp\Eephp::ee_load_action("detail", $gtm_row['id'],$gtm_row['name'],$gtm_row['category'],'', " 'list': '{$type}' ", $mightalsoenjoy);
	}

	if ($step == "3")
	{
		$row = array();
			list($row['item_id'],$others) = split(",",$_GET['ids']);
			$row['item_type'] = "nonprofit";
		$row1 = \Eephp\Eephp::ee_json_products_one($row);
		$gtm_js = \Eephp\Eephp::ee_load_action("detail", $row1['id'],$row1['name'],$row1['category'],'', " 'list': 'Add To Cart Page' ");
	}

	if ($step == "4add")
	{
		// addtocart
		list($row['item_id'], $row['item_type'], $row['amount']) = split("~", $_GET['ids']);
		$row = \Eephp\Eephp::ee_json_products_one($row);
		$gtm_js = \Eephp\Eephp::ee_load_cartadd($row['id'],$row['name'],$row['category'],$row['price']);
	}
	if ($step == "4remove")
	{
		// addtocart
		list($row['item_id'], $row['item_type'], $row['amount']) = split("~", $_GET['ids']);
		$row = \Eephp\Eephp::ee_json_products_one($row);
		$gtm_js = \Eephp\Eephp::ee_load_cartremove($row['id'],$row['name'],$row['category'],$row['price']);
	}

	if ($step == "5.1") \Eephp\Eephp::ee_step5_checkout(1);
	if ($step == "5.2") \Eephp\Eephp::ee_step5_checkout(2);
	if ($step == "5.3") \Eephp\Eephp::ee_step5_checkout(3);
	if ($step == "5.4") \Eephp\Eephp::ee_step5_checkout(4);
	if ($step == "5.5") \Eephp\Eephp::ee_step5_checkout(5);
	if ($step == "5.6") \Eephp\Eephp::ee_step5_checkout(6);

	if ($step == "6")
	{
		$cart_id = gtm_get_cartid();
		$gtm_js = \Eephp\Eephp::ee_load_thankyou($cart_id);
	}


}


function gtm_test_type($test)
{
	global $type; // if matched or not set, then ok!
	if (!isset($type)) return 1;
	if ($type == $test) return 1;
	return 0;
}


function gtm_test_html()
{
	// find an order that has mutiple items...

	global $type;
	print("<pre>Demonstration.... We are currently tagging 7 types of pages with EE
		<a href=Test.php?eetest=1&type=1>1</a>=search results (impressions)
		<a href=Test.php?eetest=1&type=2>2</a>=charity detail and campaign pages (detail and actionField.list set+impressions for \"you might also like\")
		<a href=Test.php?eetest=1&type=3>3</a>=add to cart page/express checkout page (detail and actionField.list set to distinguish from #2)
		<a href=Test.php?eetest=1&type=4>4add</a>=the \"added to cart\" page where an amount has been added and you are viewing/reviewing the cart  (add)
		<a href=Test.php?eetest=1&type=4>4remove</a>=\"removed from cart\" (remove)
		<a href=Test.php?eetest=1&type=5>5</a>=stepout steps with funnels 1-6 described in GA (checkout)
		<a href=Test.php?eetest=1&type=6>6</a>=thankyou page (purchase)

	</pre><hr>");

	if (!$type) exit;

	$thesql = "SELECT created_datetime,parent_id,GROUP_CONCAT(DISTINCT item_type ORDER BY item_type) AS types FROM give_orderitems GROUP BY parent_id HAVING types REGEXP ',' ORDER BY created_datetime DESC";

	$cart_id = 48598;
	list($rows1,$rows2,$rows3,$all) = cart_get_rows("order", $cart_id);

	$row1 = $all[2];
	$row1 = \Eephp\Eephp::ee_json_products_one($row1);

	list($total, $all2) = \Eephp\Eephp::ee_json_products($cart_id, 0); // 0 => do not encode

	if (gtm_test_type(1))
	{
		//step1 -- search results -- pretend the order items are the search results
			print_r2("1--search results");
			$js = \Eephp\Eephp::ee_load_impressions($all2, 1, "searchresults");
			print_js($js);
	}

	if (gtm_test_type(2))
	{
		//step2 -- details with "might also" -- pretend the order items are the might-also-enjoy items
			print_r2("2--detail page with \"might also enjoy\" list");
			$mightalsoenjoy = \Eephp\Eephp::ee_load_impressions($all2, 0, "mightlike");
			$js = \Eephp\Eephp::ee_load_action("detail", $row1['id'],$row1['name'],$row1['category'],'', " 'list': 'Detail Page' ", $mightalsoenjoy);
			print_js($js);
	}

	if (gtm_test_type(3))
	{
		//step3 -- donate addtocart -- before amounts/designation/dedication given
			print_r2("3--the add to cart/express checkout page where you add an amount");
			$js = \Eephp\Eephp::ee_load_action("detail", $row1['id'],$row1['name'],$row1['category'],'', " 'list': 'Add To Cart Page' ");
			print_js($js);
	}

	if (gtm_test_type(4))
	{
		//step4 -- add--add to cart (with/after price)
			print_r2( "4--add to cart (tags load on pageview when you are viewing the cart)");
			$js = \Eephp\Eephp::ee_load_cartadd($row1['id'],$row1['name'],$row1['category'],$row1['price']);
			print_js($js);

		//step4 -- remove--remove from cart
			print_r2("4--remove from cart");
			$js = \Eephp\Eephp::ee_load_cartremove($row1['id'],$row1['name'],$row1['category'],$row1['price']);
			print_js($js);
	}

	if (gtm_test_type(5))
	{
		//step5-- checkout steps
			print_r2("5--checkout steps");
			$js1=\Eephp\Eephp::ee_load_checkout_step5($cart_id, 1);
			print_js($js1);
	}

	if (gtm_test_type(6))
	{
		//step6 -- purchase --checkout thankyou
			print_r2("6--checkout thankyou");
			$js =  \Eephp\Eephp::ee_load_thankyou($cart_id);
			print_js($js);
	}

}
