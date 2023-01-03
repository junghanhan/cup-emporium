<?php

require 'vendor/autoload.php';

// Load `.env` file from the server directory so that
// environment variables are available in $_ENV or via
// getenv().
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/');
$dotenv->load();

// set who i am and what version I am targeting
$stripe = new \Stripe\StripeClient([
  'api_key' => $_ENV['STRIPE_SECRET_KEY'],
  'stripe_version' => '2020-08-27',
]);
