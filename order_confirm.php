<?php
include('script/functions.php'); // get session started and include session related functions

// prevent the customer place the same order again.
// if the customer tries to refresh the page, then they are redirected to the main page
if (isset($_SESSION ['checkout_done']) && $_SESSION ['checkout_done'] == true) {
    header('Location: product_listings.html');
    $_SESSION ['checkout_done'] = false;
    exit();
}
else {
    $_SESSION ['checkout_done'] = true;
}


/* ----- stripe transaction ------- */
require_once('./config.php');

$total_cents = $_POST['total'];

/* ---- save the placed order info into DB ---- */
$account_id = get_account_id();

// set the customer's location information in the DB
$location = [];
$location['fName'] = $_POST['fName']; // recipient name
$location['country'] = $_POST['country'];
$location['state'] = 'British Columbia'; // TODO: there is no 'state' field in checkout page.
$location['city'] = $_POST['city'];
$location['address'] = $_POST['address'];
$location['postalCode'] = $_POST['postalCode'];
$location['accountId'] = $account_id;
set_location ($location);

$p_q_pairs = unserialize($_POST['purchased']); // load the purchased items info

$order_id = save_placed_order ($account_id, $p_q_pairs, round($total_cents / 100, 2)); // save the placed order into DB

// update stock
$p_a_pairs = []; // product ID - amount to change pairs
foreach ($p_q_pairs as $p => $q) {
    $p_a_pairs ["$p"] = (-$q);
}
update_quantity_stock ($p_a_pairs);

// make the order as a JSON file
$order_file_name = "a" . $account_id . "_o" . $order_id . ".json";
make_order_file ($account_id, $order_id, $order_file_name);

empty_user_cart ($account_id);


/* ---- functions used in order_confirm.php ---- */

// save the placed order to DB
// input: account ID (int), product ID - quantity pairs (array), total price of the placed order (float)
// output: order ID of the placed order
function save_placed_order ($account_id, $p_q_pairs, $total) {
    // Make the connection:
    $dbc = @mysqli_connect (DB_HOST, DB_USER, DB_PASSWORD, DB_NAME) OR
           die ('Could not connect to MySQL: ' . mysqli_connect_error() );
    mysqli_set_charset($dbc, 'utf8'); // Set the encoding

    // Insert the placed order information
    $added_date = date_format (date_create(), 'Y-m-d H:i:s');
    $location_id = get_user_location_id ($account_id);
    // assumes tax, shipping are 0. and what is `subtotal` for the placed order ?
    $q = "INSERT INTO `order` (`accountId`, `status`, `subTotal`, `tax`, `shipping`, `grandTotal`, `locationId`, `createdAt`)
          VALUES ('$account_id','new','0','0','0','$total','$location_id','$added_date');";
    mysqli_query ($dbc, $q); // execute query
    $order_id = mysqli_insert_id ($dbc); // get the ID of the placed order

    // Insert transaction information
    $q = "INSERT INTO `transaction` (`accountId`, `orderId`, `code`, `type`, `status`, `createdAt`)
          VALUES ('$account_id','$order_id','200','credit','success','$added_date');";
    mysqli_query ($dbc, $q); // execute query

    // Insert the products and their quantities of the placed order.
    $cart_id = get_user_cart_id ($account_id);
    $q = "INSERT INTO `orderitem` (`productId`, `orderId`, `quantity`, `createdAt`) VALUES (?,$order_id,?,'$added_date');"; // data type : ii

    if ($stmt = mysqli_prepare($dbc, $q)) {
        // Bind variables to statement
        mysqli_stmt_bind_param($stmt, "ii", $_product_id, $_quantity);

        foreach ($p_q_pairs as $product_id => $quantity) {
            $_product_id = $product_id;
            $_quantity = $quantity;

            // execute sql insert
            if(mysqli_stmt_execute($stmt)) {
                // on success do nothing
            } else {
                // on failure display error message
                $err_message = mysqli_error($dbc);
                echo $err_message;
            }
        }

        mysqli_stmt_close($stmt);
    }
    else {
        echo "Connect failed in save_placed_order()";
        exit();
    }

    mysqli_close($dbc); // close DB server connection

    return $order_id;
}

// make the placed order as HTML
// input: account ID (int), the placed order ID (int)
// output: the HTML representation of the placed order
function make_placed_order ($account_id, $order_id) {
    $order_infos = make_orders_array ($account_id);
    $order_info = $order_infos ["$order_id"];
    $total_price = get_total_price ($order_info);
    $tran_status = get_tran_status ($order_id);

    $made_html = "<div class='order'>
                      <div class='row'>
                        <div class='col-3'>
                            <h6>Order ID</h6>
                            <div>$order_id</div>
                        </div>
                        <div class='col-2'>
                            <h6>Order Date</h6>
                            <div>{$order_info ['createdAt']}</div>
                        </div>
                        <div class='col-2'>
                            <h6>Transaction Status</h6>
                            <div>$tran_status</div>
                        </div>
                        <div class='col-2'>
                            <h6>Total Price</h6>
                            <div>\$$total_price</div>
                        </div>
                      </div>
                      <hr id='order-hr'>
                      ";

    foreach ($order_info ['products'] as $product_id => $product_info) {
        $subtotal = $product_info['quantity'] * $product_info['price'];

        // order items info
        $made_html .= "
        <div class='row'>
          <div class='col-2'>
            <img class='img-thumbnail' src='{$product_info['viewCode']}'>
          </div>
          <div class='col-2'>
            <h6>Product Name</h6>
            <div class='align-middle'>{$product_info['title']}</div>
          </div>
          <div class='col-2'>
            <h6>Product Info</h6>
            <div class='align-middle'>{$product_info['description']}</div>
          </div>
          <div class='col-2'>
            <h6>Item Price</h6>
            <div class='align-middle'>\${$product_info['price']}</div>
          </div>
          <div class='col-2'>
            <h6>Quantity</h6>
            <div class='align-middle'>{$product_info['quantity']}</div>
          </div>
          <div class='col-2'>
            <h6>Subtotal Price</h6>
            <div class='align-middle'>\$$subtotal</div>
          </div>
        </div>
        <hr>
        ";
    }

    // close the div for an order
    $made_html .= "
    </div>\n";

    return $made_html;
}

?>


<!DOCTYPE html>
<html>
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- Bootstrap -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" integrity="sha384-B0vP5xmATw1+K9KRQjQERJvTumQW0nPEzvF6L/Z6nronJ3oUOFUFpCjEUQouq2+l" crossorigin="anonymous">
  <!-- Our CSS -->
  <link rel="stylesheet" href="style/style.css">
  <!-- JQuery -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
  <!-- Stripe -->
  <script src="https://js.stripe.com/v3/"></script>
</head>
<body>
<style>
    html body {
        width: 100%;
        height: 100%;
        margin: 0;
        padding: 0;
        overflow-x: hidden;
    }

    .checkout-container {
        background-color: #F0F8FE;
        color: #2699FB;
    }


    .bottomDivider {
        width: 100%;
        border-bottom: 2px solid #2699FB;
        margin-top: 5px;
        padding-bottom: 5px;
    }

    .checkout-header {
        color: #2699FB;
        background-color: #BCE0FD;
        padding: 15px;
        padding-left: 35px;
        text-align: center;
        width: 100%;
    }

    img {
        width: 100%;
        border: 1px solid;
    }

    input[type='number'] {
        width: 50%;
        height: 25%;
    }

    input[type=number]::-webkit-inner-spin-button,
    input[type=number]::-webkit-outer-spin-button {
        opacity: 1;
    }

    .cardStyle {
        border: 1px solid #BCE0FD;
        border-radius: 5%;;
        padding: 10px 3px 10px 3px;
    }

    #payment_button {
        width: 30%;
        margin-bottom: 10px;
    }

    #thank-msg {
        text-align: center;
    }

</style>
<div id='nav'></div>
<div class='m-3'>
  <div class='checkout-container'>
    <h2 id='thank-msg'>Thank you for purchasing our products!</h2>
  </div>
</div>
<div class='row'>
  <h2 class='checkout-header'>PURCHASED ORDER</h2>
  <div class='col-2'></div>
  <div id='insertSection' class='col checkout-container'>

<?php

/* ---- print receipt ---- */
echo make_placed_order ($account_id, $order_id);

?>

  </div>
  <div class='col-2'></div>
</div>
<script src="script/order_confirm.js" type="module"></script>
</body>
</html>
