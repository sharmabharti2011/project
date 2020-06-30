<?php
// bootstrap.php
if (!class_exists("Doctrine\Common\Version", false)) {
    require_once "bootstrap_doctrine.php";
}
if (!class_exists("Silex\Provider\TwigServiceProvider", false)) {
    require_once "bootstrap_twig.php";
}
if (!class_exists("Silex\Provider\SwiftmailerServiceProvider", false)) {
    require_once "bootstrap_mailer.php";
}

require_once __DIR__.'/../vendor/stripe-php-1.7.10/lib/Stripe.php';

// Test key
// Stripe::setApiKey("sk_test_seMCXtQRvwb6uj5Q4jfSnnR1");

// Production key
Stripe::setApiKey("sk_live_9yYQCMHU2bQHZRCuSkhx8flu");

if (!class_exists("Entities\User", false)) {
    require_once "bootstrap_models.php";
}

function mround($number, $precision=0) {
	$precision = ($precision == 0 ? 1 : $precision);   
	$pow = pow(10, $precision);

	$ceil = ceil($number * $pow)/$pow;
	$floor = floor($number * $pow)/$pow;

	$pow = pow(10, $precision+1);

	$diffCeil     = $pow*($ceil-$number);
	$diffFloor     = $pow*($number-$floor)+($number < 0 ? -1 : 1);

	if($diffCeil >= $diffFloor) return $floor;
	else return $ceil;
}

require __DIR__.'/lib/UserUtils.php';
require __DIR__.'/lib/ReviewUtils.php';
require __DIR__.'/lib/TwitterUtils.php';
require __DIR__.'/lib/StorageUtils.php';
require __DIR__.'/lib/SupplyUtils.php';
?>