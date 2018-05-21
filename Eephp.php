<?php

// Eephp.php

namespace Eephp;

/**
 * Class Eephp
 *
 * @package Eephp
 */
class Eephp
{

	// -------------- INPUTS ----------------- //

	// @var int
	public static $eephp_cart_id;

	// @var string
	public static $eephp_cartitems_func_string;

	// @var string
	public static $eephp_orderitems_func_string;

	// @var string
	public static $eephp_products_func_string;

	// examples from CFP -- see Test.php for stub examples
	// $eephp_cartitems_func_string = "cfpshell_cart_rows";
	// $eephp_orderitems_func_string = "cfpshell_order_rows";
	// $eephp_products_func_string = "cfpshell_product_rows";


	// ------------------ FUNCTIONS ------------------- //

    /**
     * Sets the cart_id to be used
     *
     * @param string $cart_id
     */
    public static function set_cart_id($cart_id)
    {
        self::$eephp_cart_id = $cart_id;
    }

    /**
     * Gets the cart_id to be used
     *
     * @return string
     */
    public static function get_cart_id($cart_id)
    {
        return self::$eephp_cart_id;
    }

	public static function ee_db_rows2assoc($rows,$idfield,$valuefield="")
	{
		$newrows = array();
		foreach($rows as $row)
		{
			if ($valuefield)
				$value = $row[$valuefield];
			else
				$value = $row; // else use the row itself

			$newrows[$row[$idfield]] = $value;
		}
		return $newrows;
	}

    /**
     * grabs actual product rows for the ids
     *
     * @param string|array $ids
     */
	public static function ee_ids2rows($ids)
	{
		// we want to query once but important: we need the results back in the same order as the original keys are given to us... query db once and re-assemble

		//
		// 1/3--prep -- need ids AND ids_array -- string and array for post sorting
		//
		if (!$ids)
		{
			$ids = -123;
		}
		else if (is_array($ids))
		{
			$ids[] = -123;
			$ids_array = $ids;
			$ids = join(",", $ids);
		}
		else
		{
			$ids_array = split(",", $ids);
		}

		if ($ids == "-123") return array();

		//
		// 2/3--doit
		//
		$rows = call_user_func($eephp_products_func_string, $ids);

		//
		// 3/3--sort -- the order matters .. according to sort in cart/order
		//
		$keyedrows = self::ee_db_rows2assoc($rows,"id");

		$orderedrows = array();
		$i = 0;
		foreach($ids_array as $key)
		{
			if ($key <= 0) continue;
			$i++;
			$row = $keyedrows[$key];

			$row['name'] =  self::ee_escape($row['name']);
			$row['category'] =  self::ee_escape($row['category']);
			$row['position'] = $i;
			$orderedrows[] = $row;
		}
		return $orderedrows;
	}

    /**
     * grabs first one
     *
     * @param int $id
     */
	public static function ee_id2row($id)
	{
		$id = (int)$id;
		$rows = self::ee_ids2rows($id);
		return $rows[0];
	}

    /**
     * generates js for step 5 ... example: "step 5.1" => checkout_step=1
     *
     * @param int $checkout_step
     */
	public static function ee_step5_checkout($checkout_step, $substepswithajax=array(5))
	{
	    $gtm_js = "";

		$cart_id = self::$eephp_cart_id;

		$gtm_js = self::ee_load_checkout_step5($cart_id, $checkout_step);

		if (in_array($checkout_step, $substepswithajax))
		{
			$gtm_js .= self::ee_jsfunction_onCheckoutOption(); // ajax processing of on-page clicks
		}
		return $gtm_js;
	}

	public static function ee_load_cartadd($id,$name,$category,$price, $qty=1)
	{
		return self::ee_load_cart('addTo',$id,$name,$category,$price, $qty);
	}

	public static function ee_load_cartremove($id,$name,$category,$price)
	{
		return self::ee_load_cart('removeFrom',$id,$name,$category,$price);
	}

	public static function ee_load_checkout_step5($cart_id, $step_id)
	{
		// step5 -- do this on page load so it is easier to implement rather than on the previous page click

		$cart_id = (int)$cart_id;
		$step_id = (int)$step_id;
		list($amount, $json_products) = self::ee_json_products($cart_id);
		if (!$json_products) $json_products = "[]"; // should not happen...
		if ($json_products == "null") $json_products = "[]";

		$p = "
		  'checkout': {
			'actionField': {'step': {$step_id} },
			'products': {$json_products}
				}";

		$p = self::ee_withDLframework($p);
		return $p;
	}

	public static function ee_load_cart($addORremove,$id,$name,$category,$price,$qty=1)
	{
		//
		// does only 1 cart item at a time for now... either addORremove
		//

		//type3
		$name2 = self::ee_escape($name);
		$category2 = self::ee_escape($category);

		$addORremove2 = preg_replace("/(from|to)/i", "", $addORremove); //addTo=>add

		//$event = "{$addORremove}Cart";

		$p = " '{$addORremove2}': {
				  'products': [{
					'name': '$name2',
					'id': '$id',
					'price': '$price',
					'category': '$category2',
					'quantity': '$qty'
				   }]
				}";

		$p = self::ee_withDLframework($p, $event);
		return $p;
	}

    /**
     * grabs js for handling cross-sells / might-like impressions
     *
     * @param array $rows
     * @param bool $frameit
     * @param string $listname
     * @return string the JS for the impressions
     */
	public static function ee_load_impressions($rows, $frameit, $listname='')
	{
		//
		// example: $mightalsoenjoy = \Eephp\Eephp::ee_load_impressions($gtm_mightlikes_rows, 0, "mightlike");
		//

		$items_txt = array();
		$i = 0;
		foreach($rows as $row)
		{
			$i++;
			if ($list) $row['list'] = $listname;
			$row['position'] = $i;
			$row_txt = local_usesinglequotes(json_encode($row));

			$items_txt[] = $row_txt;
		}

		$items_txt = join(",\n", $items_txt);

		$p = " 'impressions': [ {$items_txt} ] ";
		if ($frameit) $p = self::ee_withDLframework($p);
		return $p;
	}

	public static function ee_load_action($action, $id, $name, $category, $price, $actionfield='' , $impressions='')
	{
		//8 EE defined actions ==> click, detail, add, remove, checkout, purchase, checkout_option, refund
		//ALSO: impressions, promoView, promoClick

		//type3
		$name2 = self::ee_escape($name);
		$category2 = self::ee_escape($category);

		if ($actionfield) $actionfield_nice = " 'actionField': { {$actionfield} },  ";

		$p = " '{$action}': {
				{$actionfield_nice}			  // 'detail' actions have an optional list property.
				  'products': [{
					'name': '$name2',         // Name or ID is required.
					'id': '$id',
					'price': '$price',
					'category': '$category2'
				   }]
				 }";

		if ($impressions) $p .= ", " . $impressions; // FROM  $impressions = self::ee_load_impressions($rows,0);
		$p = self::ee_withDLframework($p);

			return $p;
	}

	public static function ee_load_thankyou($cart_id)
	{
		//type4
		list($amount, $json_products) = self::ee_json_products($cart_id);

		if (!$json_products) $json_products = "[]"; // should not happen...

		$p = " 'purchase': {
			  'actionField': {
					'id': '{$cart_id}',                         // Transaction ID. Required for purchases and refunds.
					'affiliation': 'Online Store',
					'revenue': '{$amount}'                    // Total transaction value (incl. tax and shipping)
				  },
			  'products': {$json_products}
			}";

		$p = self::ee_withDLframework($p);
		return $p;

	}

	public static function ee_json_products_one($row)
	{
			$out = array();

			if ($row['item_type'] == "giftcard")
			{
				// treat giftcards differently since we do not want to know the exact giftcard id
				$out['id'] = "gc";
				$out['name'] = "giftcard";
				$out['category'] = "giftcard";
			}
			else
			{
				//
				// all others "product", "nonprofit", "registry", or blank! ...
				//
				$id = (int)$row['item_id'];
				$rows =  call_user_func($eephp_products_func_string, $id);
				$prodrow = $rows[0];

					$out['id'] = $id;
					$out['name'] = $prodrow['name'];
					$out['category'] = self::ee_escape($prodrow['category']);
			}

			// ALL cases

			if (!$row['item_quantity']) $row['item_quantity'] = 1; // optional, default 1
			$out['quantity'] = $row['item_quantity'];
			$out['price'] = $row['amount'];

			return $out;
	}

    /**
     * grabs products from cart or order (if exists)
     *
     * @param int $cart_id
     * @param bool $encode (optional,default=1)
     * @return array (total dollar amount,rows)
     */
	public static function ee_json_products($cart_id, $encode=1)
	{
		// takes an order-id and returns a javascript array with the data to add to another public static function like self::ee_load_thankyou()
		//
		// eg. [{"id":"10","name":"giftcard","category":"giftcard","quantity":1,"price":"100"},{"id":"10","name":"giftcard","category":"giftcard","quantity":1,"price":"100"},{"id":"990","name":"Catalogue for Philanthropy: Greater Washington","category":"HUMAN SERVICES: Community & Civic Engagement","quantity":1,"price":"150"}]

		$cart_id = (int)$cart_id;

		$all = call_user_func($eephp_cartitems_func_string, $cart_id);

		if (!sizeof($all))
		{
			$all = call_user_func($eephp_orderitems_func_string, $cart_id);
		}

		$totalamount = 0;

		if ($all)
		foreach($all as $row)
		{
			$totalamount += $row['amount'];
			$out = self::ee_json_products_one($row);
			$results[] = $out;
		}

		if ($encode)
		{
			$results = json_encode($results);
			$results = local_usesinglequotes($results);
		}

		return array($totalamount,$results);
	}

    /**
     * ajax processing of on-page clicks
     *
     */
	public static function ee_jsfunction_onCheckoutOption()
	{
		// example call from HTML client
		//	$('document').ready(function() {
		//		$('input[ type=\"radio\"]').change(function(e) {
		//			var name = $(this).attr('name');
		//			var value = $(this).val();
		//			ee_onCheckoutOption(5, name + '~'+ value);
		//		});
		// });

		$p = "<script>
				/**
				* A JAVASCRIPT public static function to handle a click leading to a checkout option selection. sent AFTER the gtm is loaded
				*/
				function ee_onCheckoutOption(step, checkoutOption)
				{
					window.dataLayer = window.dataLayer || [ ];
					dataLayer.push({
						'event': 'checkoutOption',
						'ecommerce': {
							'checkout_option': {
								'actionField': {'step': step, 'option': checkoutOption}
							}
						}
					}); // end push
				}
		</script>";
		return $p;
	}

// ------------------- PRIVATE -------------------- //

	private static function ee_withDLframework($ec_items, $event='')
	{
		$items = array();

		if (is_array($ec_items)) $ec_items = join(",\n\t\t\t", $ec_items);
		if ($ec_items) $items[] = " 'ecommerce': { {$ec_items} }";

		if ($event) $items[] = " 'event': '$event' ";

		$items = join(",\n\n\t\t\t", $items); // events

		$p = "<script>
			window.dataLayer = window.dataLayer || [ ];
			dataLayer.push({
				{$items}
			});
			</script>";

		return $p;
	}

	private static function ee_escape($that)
	{
		// single quotes escape
		$that = str_replace("'", '\'', $that);

		// category: subcategory => category/subcategory used in GA
		$that = str_replace(": ", "/", $that);

		return $that;
	}

	private static function local_usesinglequotes($row_txt)
	{
		$row_txt = str_replace("},", "},\n", $row_txt);
		$row_txt = str_replace("'", "-", $row_txt); // single=>-
		$row_txt = str_replace('"', "'", $row_txt); //double=>single quotes
		return $row_txt;
	}

	private static function UNUSED_ee_one_impression($id,$name,$category,$price, $position)
	{
		$name2 = self::ee_escape($name);
		$category2 = self::ee_escape($category);

		$p = " {
				  'id': '{$id}',
				  'name': '{$name2}',
				  'price': '{$price}',
				  'category': '{$category2}',
				  'position': {$position}
				 }";

			return $p;
	}

	// ------------------- end PRIVATE -------------------- //


} // end class Eephp