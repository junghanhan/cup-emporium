<?php
    include "script/functions.php";

    require_once 'config.php';

    $_SESSION ['checkout_done'] = false;

    // dbc to db...
    $dbc =  mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
    mysqli_set_charset($dbc, 'utf8');
    
    $accountId = get_account_id();
    $cartId = get_user_cart_id($accountId); // Reminder: get the actual cart of user.

    // singular get condition for the cart body in checkout
    if($_SERVER['REQUEST_METHOD'] == 'GET') {

        if(!isset($_GET['fName'])) {
            // create sql object, no stmt since we are using values we generate and are not prone to injection attacks
            $sql = 'SELECT * FROM cartInfo WHERE cartId = '.$cartId;
            $r = mysqli_query($dbc, $sql);

            $total = 0;
            $body = array();

            $body['pub_key'] = $_ENV['STRIPE_PUBLISHABLE_KEY'];
            $body['orderInfo'] = "";
            $body['hiddenForm'] = "";

            // checking from all rows and calculating totals and subtotals
            $p_q_pairs = array(); // product ID - quantity pairs of the order
            while ($row = mysqli_fetch_array ($r, MYSQLI_ASSOC)) {
                $subTotal = $row['price'] * $row['quantity'];
                $total += $subTotal;
                $body['orderInfo'] .= "<div class='row cart-item'><div class='row bottomDivider'><div class='col'><img src='{$row['viewCode']}'></div><div class='col'><h6>Product Name</h6><p>{$row['title']}</p></div><div class='col'><h6>Product Info</h6><p>{$row['description']}</p></div><div class='col'><h6>Product Price</h6><p>$ {$row['price']}</p></div><div class='col'><h6>Quantity</h6>{$row['quantity']}</div><div class='col'><h6>Subtotal Price</h6><p>$ {$subTotal}</p></div></div></div>";
                $p_q_pairs["{$row['productId']}"] = $row['quantity'];
            }

            // total section at the end of the oreder information
            $body['orderInfo'] .= "<div class='row'><h2 id='OrderTotal'></h2></div>";
            $body['hiddenForm'] .= "<input type='hidden' name='purchased' value='" . serialize($p_q_pairs) . "'/>";
            $body['hiddenForm'] .= "<input type='hidden' name='total' value='" . $total*100 . "'/>";

            // change to amount of decimal to two
            $body['Total'] = number_format($total, 2);
            
            // Add location information for js response
            $locationData = get_location();
            if($locationData['status'] == 200) {
                $body['country'] = $locationData['country'];
                $body['state'] = $locationData['state'];
                $body['city'] = $locationData['city'];
                $body['address'] = $locationData['address'];
                $body['postalCode'] = $locationData['postalCode'];
            }

            if(isset($_GET['type']) AND $_GET['type'] == 'purchase') {
                // payment intent is something that is currently un used but is part of what should
                // replace the secret and public key in response body
                $paymentIntent = $stripe->paymentIntents->create([
                    'payment_method_types' => ['card'], // define a specific type for the payment
                    'amount' => (number_format($total, 2) * 100), // amount is fickle but wants the total in cents so multiply total (example: 10.87) by 100 (to get 1087)
                    'currency' => 'cad', // currency type so it can do conversions on the stripe side of things
                ]);

                $body['secret_key'] =  $paymentIntent->client_secret;
            }

            echo json_encode($body); // this is just how to translate the php array to json for reponse
        }

    }

    // returns json_encode erray for location if present otherwise returns status 500
    function get_location() {
        $id = get_account_id();
        $result = array();
        global $dbc;
        if($id != -1) {
            $sql = "SELECT country, state, city, address, postalCode FROM location WHERE accountId = $id";
            if($stmt = mysqli_prepare($dbc, $sql)) { 
                if(mysqli_stmt_execute($stmt)) {
                    mysqli_stmt_store_result($stmt);
                    if(mysqli_stmt_num_rows($stmt) >= 1) {
                        // store all results to appropriate array values
                        $result['status'] = 200;
                        mysqli_stmt_bind_result($stmt, $country, $state, $city, $address, $postalCode);
                        mysqli_stmt_fetch($stmt);
                        $result['country'] = $country;
                        $result['state'] = $state;
                        $result['city'] = $city;
                        $result['address'] = $address;
                        $result['postalCode'] = $postalCode;

                    } else {
                        // return here array of status 500
                        $result['status'] = 500;
                    }
                } else {
                    // error report mysqli error
                }
            } else {
                // error report mysqli error
            }
        } else {
            return;
        }

        return $result;
    }

    mysqli_close($dbc);
?>
