# Enhanced Ecommerce Library for PHP

## Requirements

PHP 5.3.0 and later.

## Composer (Github is most always most recent.  My Packagist will lag)

You can install the bindings via [Composer](http://getcomposer.org/). Run the following command:

```bash
composer require erikh60/Eephp
```

To use the bindings, use Composer's [autoload](https://getcomposer.org/doc/01-basic-usage.md#autoloading):

```php
require_once('vendor/autoload.php');
```

## Manual Installation

If you don't use Composer, you can download the [latest release](https://github.com/erikh60/Eephp/releases).

```php
require_once('/path/to/Eephp.php');
```

## Dependencies

Require the following extension in order to work properly:

- [`json`](https://secure.php.net/manual/en/book.json.php)

If you use Composer, it should get handled automatically. If you install manually, make sure on your own.

## Getting Started

```
Enhanced Ecommerce/GTM Details here:
// https://developers.google.com/tag-manager/enhanced-ecommerce

Set GA Funnels to match the steps as described here:
// https://developers.google.com/analytics/devguides/collection/analyticsjs/enhanced-ecommerce#ecommerce-data

Steps:
//step1 -- search results  
//step2 -- product details page with "might also like"  
//step3 (optional) -- donate/addtocart -- before amounts/designation/dedication given
//step4 -- add--add to cart (with/after price)
//step4 -- remove--remove from cart
//step5 -- checkout steps
	//step:5.1 view/review cart 
	//step:5.2 choose--guest/login/new (not in express)
	//step:5.3 account page (not in express)
	//step:5.4 enter or review address (not in express)
	//step:5.5 review order
	//step:5.6 pay via paypal or stripe
//step6 -- purchase --checkout thankyou

///XXX -- not yet -- Refunds

```

## Simple Usage

For example to load the proper JS for a thank-you/confirmation at the end of checkout:

```php
\Eephp\Eephp::set_cart_id('...');
$cart_id =  \Eephp\Eephp::get_cart_id();
$js =  \Eephp\Eephp::ee_load_thankyou($cart_id);

echo $js;
```

Full examples for Steps above in Test.php

## JS in the Wild

View Source at cfp-dc.org