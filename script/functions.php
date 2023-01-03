<?php
session_start();

// errorlisting variable used for responses.
$errorList = array();

/* Set session variable names as constants */
DEFINE ('ACCOUNT_ID', 'account_id');
DEFINE ('ADMIN', 'admin');

// Set the database access information as constants:
DEFINE ('DB_USER', 'test_user');
DEFINE ('DB_PASSWORD', 'test_password');
DEFINE ('DB_HOST', 'localhost');
DEFINE ('DB_NAME', 'store_db');





// get an account's privacy value
// input: account id (int)
// output: the account's privacy value (boolean)
function get_account_privacy ($account_id) {
    $result = false;

    // Make the connection:
    $dbc = @mysqli_connect (DB_HOST, DB_USER, DB_PASSWORD, DB_NAME) OR
           die ('Could not connect to MySQL: ' . mysqli_connect_error() );
    mysqli_set_charset($dbc, 'utf8');

    $q = "SELECT `privacy` FROM `user` WHERE `accountId` = $account_id;";
    $r = mysqli_query ($dbc, $q);

    if (!$r) {
        echo "set_account_privacy(), failed in mysqli_query.";
    }
    else {
        $row = mysqli_fetch_array ($r, MYSQLI_ASSOC);
        $result = ($row['privacy'] == 'T')? true : false;
    }

    mysqli_close($dbc); // close DB server connection

    return $result;
}

// set an account's privacy value
// input: account id (int), desired privacy value (boolean)
function set_account_privacy ($account_id, $privacy) {
    if (gettype($privacy) !== 'boolean') {
        echo "set_account_privacy(), second argument should be boolean type.";
    }

    // Make the connection:
    $dbc = @mysqli_connect (DB_HOST, DB_USER, DB_PASSWORD, DB_NAME) OR
           die ('Could not connect to MySQL: ' . mysqli_connect_error() );
    mysqli_set_charset($dbc, 'utf8');

    // store the string representation of privacy value (boolean)
    $privacy_str = ($privacy) ? 'T' : 'F';

    $q = "UPDATE `user` SET `privacy` = '$privacy_str' WHERE `accountId` = $account_id;";
    $r = mysqli_query ($dbc, $q);

    if (!$r) {
        echo "set_account_privacy(), failed in mysqli_query.";
    }

    mysqli_close($dbc); // close DB server connection
}

// get the total price of an order
// input : information of an order made by 'make_orders_array' or 'make_order_array'(array)
// output : the total price of an order (int)
function get_total_price ($order_info) {
    $total_price = 0;
    foreach ($order_info ['products'] as $product_id => $product_info) {
        $total_price += $product_info ['price'] * $product_info ['quantity'];
    }

    return $total_price;
}

// read transactions information of an order from DB and make it an array
// input : account ID (int), order ID (int)
// output : an array of the transactions info of an order (array)
//     first level key : transaction ID
//     second level key : code, type, status, createdAt
function make_trans_array ($account_id, $order_id) {
    // Make the connection:
    $dbc = @mysqli_connect (DB_HOST, DB_USER, DB_PASSWORD, DB_NAME) OR
           die ('Could not connect to MySQL: ' . mysqli_connect_error() );
    mysqli_set_charset($dbc, 'utf8'); // Set the encoding

    $q = "SELECT `transactionId`, `code`, `type`, `status`, `createdAt`
            FROM `transaction`
           WHERE `accountId` = $account_id
             AND `orderId` = $order_id
        ORDER BY `createdAt`;";
    $r = mysqli_query ($dbc, $q);

    $trans_info = [];
    while ($row = mysqli_fetch_array ($r, MYSQLI_ASSOC)) {
        $trans_info ["{$row['transactionId']}"]['code'] = $row['code'];
        $trans_info ["{$row['transactionId']}"]['type'] = $row['type'];
        $trans_info ["{$row['transactionId']}"]['status'] = $row['status'];
        $trans_info ["{$row['transactionId']}"]['createdAt'] = $row['createdAt'];
    }

    mysqli_close($dbc); // close DB server connection

    return $trans_info;
}

// store an order as a file. The order must already exist in the DB.
// The file will be placed one level upper directory of root directory.
// order file name should contain order ID and account ID.
// if an error occur when making the data, the error message will be put in the saved file.
// input: account ID (int), order ID (int), order file name (string)
function make_order_file ($account_id, $order_id, $file_name) {
    $info = make_order_array ($account_id, $order_id); // keys of the returned array : createdAt, status, products

    if (count($info) != 0) {
        // append the transactions information to the order information
        $info['transactions'] = make_trans_array ($account_id, $order_id);
    } else {
        $info['error'] = "make_order_file(): No order information retrieved. Check the account ID, order ID and/or the actual data inside DB.";
    }

    $json_data = json_encode ($info);
    if (!$json_data) {
        $json_data = '{"error":"make_order_file(): json_encode failed."}';
    }

    $fo_result = file_put_contents ('../' . $file_name, $json_data, 0);

    // error handling for file output
    if (!$fo_result) {
        echo "error: make_order_file(): writing the file failed in make_order_file()";
    }
}

// read an order info of an account from DB and make it an array of hierarchical structure
// input : account ID (int), order ID (int)
// output : an array of an order info of an account (array, keys : createdAt, status, products)
function make_order_array ($account_id, $order_id) {
    // Make the connection:
    $dbc = @mysqli_connect (DB_HOST, DB_USER, DB_PASSWORD, DB_NAME) OR
           die ('Could not connect to MySQL: ' . mysqli_connect_error() );
    mysqli_set_charset($dbc, 'utf8'); // Set the encoding

    // DB attributes : orderId, accountId, createdAt, status, productId, price, discount, quantity, viewCode, title, type, size, description
    $q = "SELECT * FROM `orderInfo` WHERE `accountId` = $account_id AND `orderId` = $order_id ORDER BY `createdAt` DESC;";
    $r = mysqli_query ($dbc, $q);

    $order_info = [];
    $is_meta_info_set = false;
    while ($row = mysqli_fetch_array ($r, MYSQLI_ASSOC)) {
        if (!$is_meta_info_set) {
            $order_info ['createdAt'] = $row['createdAt'];
            $order_info ['status'] = $row['status'];
            $order_info ['products'] = [];
            $is_meta_info_set = true;
        }

        // append an order item to its order
        // the key for the second level array is the value of 'productId'
        $order_info ['products'] [$row['productId']] =
            [
            'price' => $row['price'],
            'discount' => $row['discount'],
            'quantity' => $row['quantity'],
            'viewCode' => $row['viewCode'],
            'title' => $row['title'],
            'type' => $row['type'],
            'size' => $row['size'],
            'description' => $row['description']
            ];
    }

    mysqli_close($dbc); // close DB server connection

    return $order_info;
}



// read all order infos of an account from DB and make it an array of hierarchical structure
// input : account id (int)
// output : an array of all order infos of an account (array) (first level key: orderId, second level keys: createdAt, status, products)
function make_orders_array ($account_id) {
    // Make the connection:
    $dbc = @mysqli_connect (DB_HOST, DB_USER, DB_PASSWORD, DB_NAME) OR
           die ('Could not connect to MySQL: ' . mysqli_connect_error() );
    mysqli_set_charset($dbc, 'utf8'); // Set the encoding

    // DB attributes : orderId, accountId, createdAt, status, productId, price, discount, quantity, viewCode, title, type, size, description
    $q = "SELECT * FROM `orderInfo` WHERE `accountId` = $account_id ORDER BY `createdAt` DESC;";
    $r = mysqli_query ($dbc, $q);

    $order_infos = [];
    while ($row = mysqli_fetch_array ($r, MYSQLI_ASSOC)) {
        // if the array of an order is not set, then make an array of it
        // the key for the first level array is the value of 'orderId'
        if (!isset($order_infos [$row['orderId']])) {
            $order_infos [$row['orderId']] = make_order_array ($account_id, $row['orderId']);
        }
    }

    mysqli_close($dbc); // close DB server connection

    return $order_infos;
}

// Get the information of all products from 'productFullInfo' view
// DB attributes : productId, metaId, price, quantity, viewCode, title, type, size, description, discount
// output : a mysqli_result (object)
function get_all_product_info () {
    // Make the connection:
    $dbc = @mysqli_connect (DB_HOST, DB_USER, DB_PASSWORD, DB_NAME) OR
    	   die ('Could not connect to MySQL: ' . mysqli_connect_error() );

    // Set the encoding
    mysqli_set_charset($dbc, 'utf8');

    $q = "SELECT * FROM `productFullInfo` ORDER BY `productId`;";
    $r = mysqli_query ($dbc, $q);
    mysqli_close($dbc); // close DB server connection

    return $r;
}

// Get the information of available products from 'productFullInfo' view
// DB attributes : productId, metaId, price, quantity, viewCode, title, type, size, description, discount
// output : a mysqli_result (object)
function get_available_product_info () {
    // Make the connection:
    $dbc = @mysqli_connect (DB_HOST, DB_USER, DB_PASSWORD, DB_NAME) OR
    	   die ('Could not connect to MySQL: ' . mysqli_connect_error() );

    // Set the encoding
    mysqli_set_charset($dbc, 'utf8');

    $q = "SELECT * FROM `productFullInfo` WHERE `quantity` > 0 ORDER BY `productId`;";
    $r = mysqli_query ($dbc, $q);
    mysqli_close($dbc); // close DB server connection

    return $r;
}

// input : user's account_id (int)
// output : user's cart id (int)
function get_user_cart_id ($account_id) {
    // Make the connection:
    $dbc = @mysqli_connect (DB_HOST, DB_USER, DB_PASSWORD, DB_NAME) OR
           die ('Could not connect to MySQL: ' . mysqli_connect_error() );
    mysqli_set_charset($dbc, 'utf8');

    $q = "SELECT `cartId` FROM `cart` WHERE `accountId` = $account_id;";
    $r = mysqli_query ($dbc, $q);

    $cart_id = -1;
    if ($row = mysqli_fetch_array ($r, MYSQLI_ASSOC)) {
        $cart_id = $row['cartId'];
    }

    mysqli_close($dbc); // close DB server connection

    return $cart_id;
}

// input : the user's cart ID (int)
// output : an array of the IDs of the products in the user's cart (array)
function get_products_in_cart ($cart_id) {
    // Make the connection:
    $dbc = @mysqli_connect (DB_HOST, DB_USER, DB_PASSWORD, DB_NAME) OR
           die ('Could not connect to MySQL: ' . mysqli_connect_error() );
    mysqli_set_charset($dbc, 'utf8');

    $q = "SELECT `productId` FROM `cartItem` WHERE `cartId` = $cart_id;";
    $r = mysqli_query ($dbc, $q);

    $product_ids = [];
    while ($row = mysqli_fetch_array ($r, MYSQLI_ASSOC)) {
        $product_ids[] = $row['productId'];
    }

    mysqli_close($dbc); // close DB server connection

    return $product_ids;
}

// input : an array of product-quantity pair that a user is going to buy (array), user's account_id (int)
function add_products_to_cart ($product_quantity_pairs_to_buy, $account_id) {
    // Make the connection:
    $dbc = @mysqli_connect (DB_HOST, DB_USER, DB_PASSWORD, DB_NAME) OR
           die ('Could not connect to MySQL: ' . mysqli_connect_error() );
    mysqli_set_charset($dbc, 'utf8');

    $cart_id = get_user_cart_id ($account_id);
    $product_ids_in_cart = get_products_in_cart ($cart_id); // array

    // Add the quantity of the products already in the cart
    $q = "UPDATE `cartItem` SET `quantity` = `quantity` + ? WHERE `productId` = ? AND `cartId` = $cart_id;"; //ii
    if ($stmt = mysqli_prepare($dbc, $q)) {
        // Bind variables to statement
        mysqli_stmt_bind_param($stmt, "ii", $_qu_to_buy, $_product_id);

        foreach ($product_quantity_pairs_to_buy as $product_id => $qu_to_buy) {
            $_qu_to_buy = $qu_to_buy;
            $_product_id = $product_id;

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

    // Remove the products already in the cart from the products to buy
    foreach ($product_ids_in_cart as $product_id_in_cart) {
        if (isset($product_quantity_pairs_to_buy["$product_id_in_cart"])) {
            unset($product_quantity_pairs_to_buy["$product_id_in_cart"]);
        }
    }

    // Insert the products that are not in the cart and their quantities to the cart.
    $q = "INSERT INTO `cartitem`(`productId`, `cartId`, `quantity`, `addedAt`) VALUES (?,$cart_id,?,?);"; // data type : iis

    if ($stmt = mysqli_prepare($dbc, $q)) {
        // Bind variables to statement
        mysqli_stmt_bind_param($stmt, "iis", $_product_id, $_qu_to_buy, $_added_date);

        $_added_date = date_format (date_create(), 'Y-m-d');
        foreach ($product_quantity_pairs_to_buy as $product_id => $qu_to_buy) {
            $_product_id = $product_id;
            $_qu_to_buy = $qu_to_buy;

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
    mysqli_close($dbc); // close DB server connection
}

// output : an array of all sizes of products on sale (array)
function get_sizes_on_sale () {
    // Make the connection:
    $dbc = @mysqli_connect (DB_HOST, DB_USER, DB_PASSWORD, DB_NAME) OR
           die ('Could not connect to MySQL: ' . mysqli_connect_error() );
    mysqli_set_charset($dbc, 'utf8');

    $q = "SELECT DISTINCT `size` FROM `productFullInfo` ORDER BY `size`;";
    $r = mysqli_query ($dbc, $q);

    $sizes = [];
    while ($row = mysqli_fetch_array ($r, MYSQLI_ASSOC)) {
        $sizes[] = $row['size'];
    }

    mysqli_close($dbc); // close DB server connection

    return $sizes;
}

// output : an array of all types of products on sale (array)
function get_types_on_sale () {
    // Make the connection:
    $dbc = @mysqli_connect (DB_HOST, DB_USER, DB_PASSWORD, DB_NAME) OR
           die ('Could not connect to MySQL: ' . mysqli_connect_error() );
    mysqli_set_charset($dbc, 'utf8');

    // build SQL query based on the current selected size
    $q = "SELECT DISTINCT `type` FROM `productFullInfo` WHERE LOWER(`type`) != 'special' ORDER BY `type`;";
    $r = mysqli_query ($dbc, $q);

    $types = [];
    while ($row = mysqli_fetch_array ($r, MYSQLI_ASSOC)) {
        $types[] = $row['type'];
    }

    mysqli_close($dbc); // close DB server connection

    return $types;
}


// get the user account ID logged in. If account ID is not set, then return -1
// output: account ID saved in a session variable (int)
function get_account_id () {
    $account_id = -1;
    if (isset($_SESSION[ACCOUNT_ID])) {
        $account_id = $_SESSION[ACCOUNT_ID];
    }

    return $account_id;
}

// log in to the server. set user account ID to the session variable
// input: account ID (int), the fact whether the user is admin or not (boolean)
function log_in ($account_id, $is_admin) {
    $_SESSION[ACCOUNT_ID] = $account_id;
    $_SESSION[ADMIN] = $is_admin;
}

// check whether the user is logged in or not
// output: the fact whether the user is logged in or not (boolean)
function is_user_logged_in () {
    $result = false;
    if (isset($_SESSION[ACCOUNT_ID]) && $_SESSION[ACCOUNT_ID] > 0) {
        $result = true;
    }

    return $result;
}

// check whether the user is logged in as admin
// output: the fact whether the user is logged in as admin (boolean)
function is_admin () {
    $result = false;
    if (isset($_SESSION[ACCOUNT_ID]) && isset($_SESSION[ADMIN]) && $_SESSION[ADMIN] == true) {
        $result = true;
    }

    return $result;
}

// Simple test if the user has agreed to the privacy agreement,
// this test should hook in between the authentication of the user data to the db
// and when the session variables are set.
// With above rules when the user is registering, before the data is inserted
// prompt for the privacy agreement.
function testPrivacy($privacyVal) {
    if($privacyVal == 'F') {
        return false;
    } else if($privacyVal == 'T') {
        return true;
    }
}

// regex test statement function for username
function testUsername($username) {
    if(preg_match('/^[A-Z]+\w+/', $username) == 1) {
        return true;
    } else {
        return false;
    }
}

// regex test statement function for password
function testPassword($password) {
    if(preg_match('/^[A-Z]+\w*[0-9]+\w*[0-9]*/', $password) == 1) {
        return true;
    } else {
        return false;
    }
}

// aggrigating some logic to use in more then one place
// simply returns true if it is found and false otherwise    
function findUser($username) {
    global $errorList;

    // Make the connection:
    $dbc = @mysqli_connect (DB_HOST, DB_USER, DB_PASSWORD, DB_NAME) OR
           die ('Could not connect to MySQL: ' . mysqli_connect_error() );
    mysqli_set_charset($dbc, 'utf8');

    $result = false;
    $sql = 'SELECT username FROM user WHERE username = ?';
    if($stmt = mysqli_prepare($dbc, $sql)){
        // Bind variables to the prepared statement as parameters
        mysqli_stmt_bind_param($stmt, 's', $username);
        // Attempt to execute the prepared statement
        if(mysqli_stmt_execute($stmt)){
            /* store result */
            mysqli_stmt_store_result($stmt);
            if(mysqli_stmt_num_rows($stmt) == 1){
                $result = true;
            } else{
                $result = false;
            }
        } else{
            $errorList['SQL_ERROR'] = mysqli_error($dbc);
        }
        // Close statement
        mysqli_stmt_close($stmt);

    }

    mysqli_close($dbc); // close DB server connection
    return $result;
}

// make order infos HTML elements
// input: account ID (int)
// output: the HTML representation of order infos
function make_orders_html ($account_id) {
    $order_infos = make_orders_array ($account_id);

    $made_html = "";

    foreach ($order_infos as $order_id => $order_info) {
        $total_price = get_total_price ($order_info);

        // order info
        $made_html .= "
        <div class='order'>
          <div class='row'>
            <div class='col-3'>
                <h6>Order ID</h6>
                <div>$order_id</div>
            </div>
            <div class='col-2'>
                <h6>Order Date</h6>
                <div>{$order_info ['createdAt']}</div>
            </div>
            <div class='col-3'>
                <h6>Updated Date</h6>
                <div>{$order_info ['createdAt']}</div>
            </div>
            <div class='col-2'>
                <h6>Order Status</h6>
                <div>{$order_info ['status']}</div>
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

    }

    return $made_html;
}

// update/set location table information after purchase
// if their account id is in the location dbs make a new row or
// overwrite the previous? (for now I am overwritting)
// $arrayData should contain the country, state, city, address, postalCode and accountId in an array
function set_location($arrayData) {
    // Make the connection:
    $dbc = @mysqli_connect (DB_HOST, DB_USER, DB_PASSWORD, DB_NAME) OR
           die ('Could not connect to MySQL: ' . mysqli_connect_error() );
    mysqli_set_charset($dbc, 'utf8');


    //test array data has country and accountId set
    if(isset($arrayData['country']) AND isset($arrayData['state']) AND isset($arrayData['city']) AND isset($arrayData['address']) AND isset($arrayData['postalCode']) AND isset($arrayData['accountId'])) {
        // initialize variables for insert later
        $country = $arrayData['country'];
        $state = $arrayData['state'];
        $city = $arrayData['city'];
        $address = $arrayData['address'];
        $postalCode = $arrayData['postalCode'];
        $accountId = $arrayData['accountId'];

        // first check for the accountId in the location table
        $sql = "SELECT accountId FROM location WHERE accountId = $accountId";
        if($stmt = mysqli_prepare($dbc, $sql)) {
            // make a $sql assigned to an update function if the result row number is one or more.
            if(mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);
                if(mysqli_stmt_num_rows($stmt) >= 1) {
                    mysqli_stmt_close($stmt);
                    // assign an update statement to the $sql variable
                    // "UPDATE `cartItem` SET `quantity` = `quantity` + ? WHERE `productId` = ? AND `cartId` = $cart_id;";
                    $sql = "UPDATE `location` SET `country` = ?, `state` = ?, `city` = ?, `address` = ?, `postalCode` = ? WHERE `accountId` = ?";
                    if($stmt = mysqli_prepare($dbc, $sql)) {
                        mysqli_stmt_bind_param($stmt, "sssssi", $country, $state, $city, $address, $postalCode, $accountId);
                        if(mysqli_stmt_execute($stmt)) {
                            // success
                        } else {
                            // mysqli_stmt_error($stmt);
                        }

                    } else {
                        // mysqli_stmt_error($stmt);
                    }
                } else {
                    mysqli_stmt_close($stmt);
                    // assign an insert statement to the $sql variable
                    $sql = "INSERT INTO location (country, state, city, address, postalCode, accountId) VALUES (?, ?, ?, ?, ?, ?)";
                    if($stmt = mysqli_prepare($dbc, $sql)) {
                        mysqli_stmt_bind_param($stmt, "sssssi", $country, $state, $city, $address, $postalCode, $accountId);
                        if(mysqli_stmt_execute($stmt)) {
                            // success
                        } else {
                            // mysqli_stmt_error($stmt);
                        }
                    } else {
                        // mysqli_stmt_error($stmt);
                    }
                }
            } else {
                // mysqli_stmt_error($stmt);
            }
        } else {
            // mysqli_stmt_error($stmt);
        }
    } else {
        // Error for bad arguments/malformed arrayData array (missing attributes)
    }

    mysqli_close($dbc); // close DB server connection
}

// get location ID of the user. The function assumes that only one location ID is mapped to an account ID.
// input: account ID (int)
// output: location ID mapped to the account ID inputted (int)
function get_user_location_id ($account_id) {
    // Make the connection:
    $dbc = @mysqli_connect (DB_HOST, DB_USER, DB_PASSWORD, DB_NAME) OR
           die ('Could not connect to MySQL: ' . mysqli_connect_error() );
    mysqli_set_charset($dbc, 'utf8');

    // build SQL query based on the current selected size
    $q = "SELECT `locationId` FROM `location` WHERE `accountId` = $account_id;";
    $r = mysqli_query ($dbc, $q);

    $row = mysqli_fetch_array ($r, MYSQLI_ASSOC);
    $location_id = $row['locationId'];

    mysqli_close($dbc); // close DB server connection

    return $location_id;
}

// empty the cart of a user
// input: account ID (int)
function empty_user_cart ($account_id) {
    // Make the connection:
    $dbc = @mysqli_connect (DB_HOST, DB_USER, DB_PASSWORD, DB_NAME) OR
           die ('Could not connect to MySQL: ' . mysqli_connect_error() );
    mysqli_set_charset($dbc, 'utf8');

    $cart_id = get_user_cart_id ($account_id);

    $q = "DELETE FROM `cartitem` WHERE `cartId` = $cart_id;";
    mysqli_query ($dbc, $q);

    mysqli_close($dbc); // close DB server connection
}


// get the most recent transaction status of an order
// input: an order ID (int)
// output: the status of the order inputted (string)
function get_tran_status ($order_id) {
    // Make the connection:
    $dbc = @mysqli_connect (DB_HOST, DB_USER, DB_PASSWORD, DB_NAME) OR
           die ('Could not connect to MySQL: ' . mysqli_connect_error() );
    mysqli_set_charset($dbc, 'utf8');

    // get the most recent transaction status of the order
    $q = "SELECT `status` FROM `transaction` WHERE `orderId` = $order_id ORDER BY `createdAt` DESC;";
    $r = mysqli_query ($dbc, $q);
    $row = mysqli_fetch_array ($r, MYSQLI_ASSOC);
    $status = $row['status'];

    mysqli_close($dbc); // close DB server connection

    return $status;
}

// update the available quantities of the products in stock in the DB
// input: product ID - the amount to increase(+)/decrease(-) quantity pair (array)
function update_quantity_stock ($p_a) {
    $dbc = @mysqli_connect (DB_HOST, DB_USER, DB_PASSWORD, DB_NAME) OR
           die ('Could not connect to MySQL: ' . mysqli_connect_error() );
    mysqli_set_charset($dbc, 'utf8');

    // get the most recent transaction status of the order
    $q = "UPDATE `product` SET `quantity` = `quantity` + ? WHERE `productId` = ?;"; // ii

    if ($stmt = mysqli_prepare($dbc, $q)) {
        // Bind variables to statement
        mysqli_stmt_bind_param($stmt, "ii", $_amount_to_change, $_product_id);

        foreach ($p_a as $product_id => $amount_to_change) {
            $_amount_to_change = $amount_to_change;
            $_product_id = $product_id;

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
        echo "Connect failed in update_quantity_stock()";
        exit();
    }

    mysqli_close($dbc); // close DB server connection
}

$privacy = "
<div class='modal fade bd-modal-lg' id='privacyModal' tabindex='-1' role='dialog' aria-labelledby='Privacy Agreement' aria-hidden='true'>
<div class='modal-dialog modal-lg'>
<div class='modal-content'>
<div class='p-2'>
<h1>Privacy policy</h1>
<p> This privacy policy (&quot;Policy&quot;) describes how the personally identifiable information (&quot;Personal Information&quot;) you may provide on the <a target='_blank' rel='nofollow' href='http://www.CupEmporium.com'>CupEmporium.com</a> website (&quot;Website&quot; or &quot;Service&quot;) and any of its related products and services (collectively, &quot;Services&quot;) is collected, protected and used. It also describes the choices available to you regarding our use of your Personal Information and how you can access and update this information. This Policy is a legally binding agreement between you (&quot;User&quot;, &quot;you&quot; or &quot;your&quot;) and this Website operator (&quot;Operator&quot;, &quot;we&quot;, &quot;us&quot; or &quot;our&quot;). By accessing and using the Website and Services, you acknowledge that you have read, understood, and agree to be bound by the terms of this Policy. This Policy does not apply to the practices of companies that we do not own or control, or to individuals that we do not employ or manage.</p>
<h2>Automatic collection of information</h2>
<p>When you open the Website, our servers automatically record information that your browser sends. This data may include information such as your device's IP address, browser type and version, operating system type and version, language preferences or the webpage you were visiting before you came to the Website and Services, pages of the Website and Services that you visit, the time spent on those pages, information you search for on the Website, access times and dates, and other statistics.</p>
<p>Information collected automatically is used only to identify potential cases of abuse and establish statistical information regarding the usage and traffic of the Website and Services. This statistical information is not otherwise aggregated in such a way that would identify any particular user of the system.</p>
<h2>Collection of personal information</h2>
<p>You can access and use the Website and Services without telling us who you are or revealing any information by which someone could identify you as a specific, identifiable individual. If, however, you wish to use some of the features on the Website, you may be asked to provide certain Personal Information (for example, your name and e-mail address). We receive and store any information you knowingly provide to us when you create an account,  or fill any online forms on the Website. When required, this information may include the following:</p>
<ul>
<li>Personal details such as name, country of residence, etc.</li>
<li>Contact information such as email address, address, etc.</li>
<li>Account details such as user name, unique user ID, password, etc.</li>
</ul>
<p> Some of the information we collect is directly from you via the Website and Services. However, we may also collect Personal Information about you from other sources such as public databases and our joint marketing partners. You can choose not to provide us with your Personal Information, but then you may not be able to take advantage of some of the features on the Website. Users who are uncertain about what information is mandatory are welcome to contact us.</p>
<h2>Use and processing of collected information</h2>
<p>In order to make the Website and Services available to you, or to meet a legal obligation, we may need to collect and use certain Personal Information. If you do not provide the information that we request, we may not be able to provide you with the requested products or services. Any of the information we collect from you may be used for the following purposes:</p>
<ul>
<li>Create and manage user accounts</li>
<li>Send administrative information</li>
<li>Respond to inquiries and offer support</li>
<li>Improve user experience</li>
<li>Administer prize draws and competitions</li>
<li>Enforce terms and conditions and policies</li>
<li>Run and operate the Website and Services</li>
</ul>
<p>Processing your Personal Information depends on how you interact with the Website and Services, where you are located in the world and if one of the following applies: (i) you have given your consent for one or more specific purposes; this, however, does not apply, whenever the processing of Personal Information is subject to European data protection law; (ii) provision of information is necessary for the performance of an agreement with you and/or for any pre-contractual obligations thereof; (iii) processing is necessary for compliance with a legal obligation to which you are subject; (iv) processing is related to a task that is carried out in the public interest or in the exercise of official authority vested in us; (v) processing is necessary for the purposes of the legitimate interests pursued by us or by a third party.</p>
<p> Note that under some legislations we may be allowed to process information until you object to such processing (by opting out), without having to rely on consent or any other of the following legal bases below. In any case, we will be happy to clarify the specific legal basis that applies to the processing, and in particular whether the provision of Personal Information is a statutory or contractual requirement, or a requirement necessary to enter into a contract.</p>
<h2>Disclosure of information</h2>
<p> Depending on the requested Services or as necessary to complete any transaction or provide any service you have requested, we may share your information with your consent with our trusted third parties that work with us, any other affiliates and subsidiaries we rely upon to assist in the operation of the Website and Services available to you. We do not share Personal Information with unaffiliated third parties. These service providers are not authorized to use or disclose your information except as necessary to perform services on our behalf or comply with legal requirements. We may share your Personal Information for these purposes only with third parties whose privacy policies are consistent with ours or who agree to abide by our policies with respect to Personal Information. These third parties are given Personal Information they need only in order to perform their designated functions, and we do not authorize them to use or disclose Personal Information for their own marketing or other purposes.</p>
<p>We will disclose any Personal Information we collect, use or receive if required or permitted by law, such as to comply with a subpoena, or similar legal process, and when we believe in good faith that disclosure is necessary to protect our rights, protect your safety or the safety of others, investigate fraud, or respond to a government request.</p>
<h2>Retention of information</h2>
<p>We will retain and use your Personal Information for the period necessary to comply with our legal obligations, resolve disputes, and enforce our agreements unless a longer retention period is required or permitted by law. We may use any aggregated data derived from or incorporating your Personal Information after you update or delete it, but not in a manner that would identify you personally. Once the retention period expires, Personal Information shall be deleted. Therefore, the right to access, the right to erasure, the right to rectification and the right to data portability cannot be enforced after the expiration of the retention period.</p>
<h2>The rights of users</h2>
<p>You may exercise certain rights regarding your information processed by us. In particular, you have the right to do the following: (i) you have the right to withdraw consent where you have previously given your consent to the processing of your information; (ii) you have the right to object to the processing of your information if the processing is carried out on a legal basis other than consent; (iii) you have the right to learn if information is being processed by us, obtain disclosure regarding certain aspects of the processing and obtain a copy of the information undergoing processing; (iv) you have the right to verify the accuracy of your information and ask for it to be updated or corrected; (v) you have the right, under certain circumstances, to restrict the processing of your information, in which case, we will not process your information for any purpose other than storing it; (vi) you have the right, under certain circumstances, to obtain the erasure of your Personal Information from us; (vii) you have the right to receive your information in a structured, commonly used and machine readable format and, if technically feasible, to have it transmitted to another controller without any hindrance. This provision is applicable provided that your information is processed by automated means and that the processing is based on your consent, on a contract which you are part of or on pre-contractual obligations thereof.</p>
<h2>Privacy of children</h2>
<p>We do not knowingly collect any Personal Information from children under the age of 13. If you are under the age of 13, please do not submit any Personal Information through the Website and Services. We encourage parents and legal guardians to monitor their children's Internet usage and to help enforce this Policy by instructing their children never to provide Personal Information through the Website and Services without their permission. If you have reason to believe that a child under the age of 13 has provided Personal Information to us through the Website and Services, please contact us. You must also be old enough to consent to the processing of your Personal Information in your country (in some countries we may allow your parent or guardian to do so on your behalf).</p>
<h2>Cookies</h2>
<p>The Website and Services use &quot;cookies&quot; to help personalize your online experience. A cookie is a text file that is placed on your hard disk by a web page server. Cookies cannot be used to run programs or deliver viruses to your computer. Cookies are uniquely assigned to you, and can only be read by a web server in the domain that issued the cookie to you.</p>
<p>We may use cookies to collect, store, and track information for statistical purposes to operate the Website and Services. You have the ability to accept or decline cookies. Most web browsers automatically accept cookies, but you can usually modify your browser setting to decline cookies if you prefer. <a target='_blank' href='https://www.websitepolicies.com/blog/cookies'>Click here</a> to learn more about cookies and how they work.</p>
<h2>Do Not Track signals</h2>
<p>Some browsers incorporate a Do Not Track feature that signals to websites you visit that you do not want to have your online activity tracked. Tracking is not the same as using or collecting information in connection with a website. For these purposes, tracking refers to collecting personally identifiable information from consumers who use or visit a website or online service as they move across different websites over time. How browsers communicate the Do Not Track signal is not yet uniform. As a result, the Website and Services are not yet set up to interpret or respond to Do Not Track signals communicated by your browser. Even so, as described in more detail throughout this Policy, we limit our use and collection of your personal information.</p>
<h2>Links to other resources</h2>
<p>The Website and Services contain links to other resources that are not owned or controlled by us. Please be aware that we are not responsible for the privacy practices of such other resources or third parties. We encourage you to be aware when you leave the Website and Services and to read the privacy statements of each and every resource that may collect Personal Information.</p>
<h2>Information security</h2>
<p>We secure information you provide on computer servers in a controlled, secure environment, protected from unauthorized access, use, or disclosure. We maintain reasonable administrative, technical, and physical safeguards in an effort to protect against unauthorized access, use, modification, and disclosure of Personal Information in its control and custody. However, no data transmission over the Internet or wireless network can be guaranteed. Therefore, while we strive to protect your Personal Information, you acknowledge that (i) there are security and privacy limitations of the Internet which are beyond our control; (ii) the security, integrity, and privacy of any and all information and data exchanged between you and the Website and Services cannot be guaranteed; and (iii) any such information and data may be viewed or tampered with in transit by a third party, despite best efforts.</p>
<h2>Data breach</h2>
<p>In the event we become aware that the security of the Website and Services has been compromised or users Personal Information has been disclosed to unrelated third parties as a result of external activity, including, but not limited to, security attacks or fraud, we reserve the right to take reasonably appropriate measures, including, but not limited to, investigation and reporting, as well as notification to and cooperation with law enforcement authorities. In the event of a data breach, we will make reasonable efforts to notify affected individuals if we believe that there is a reasonable risk of harm to the user as a result of the breach or if notice is otherwise required by law. When we do, we will post a notice on the Website, send you an email.</p>
<h2>Changes and amendments</h2>
<p>We reserve the right to modify this Policy or its terms relating to the Website and Services from time to time in our discretion and will notify you of any material changes to the way in which we treat Personal Information. When we do, we will send you an email to notify you. We may also provide notice to you in other ways in our discretion, such as through contact information you have provided. Any updated version of this Policy will be effective immediately upon the posting of the revised Policy unless otherwise specified. Your continued use of the Website and Services after the effective date of the revised Policy (or such other act specified at that time) will constitute your consent to those changes. However, we will not, without your consent, use your Personal Information in a manner materially different than what was stated at the time your Personal Information was collected.</p>
<h2>Acceptance of this policy</h2>
<p>You acknowledge that you have read this Policy and agree to all its terms and conditions. By accessing and using the Website and Services you agree to be bound by this Policy. If you do not agree to abide by the terms of this Policy, you are not authorized to access or use the Website and Services. This privacy policy was created with the <a target='_blank' href='https://www.websitepolicies.com/privacy-policy-generator'>privacy policy generator</a>.</p>
<h2>Contacting us</h2>
<p>If you would like to contact us to understand more about this Policy or wish to contact us concerning any matter relating to individual rights and your Personal Information, you may send an email to CupEmporium.webmaster@servermaster.com</p>
<p>This document was last updated on June 8, 2021</p>
<button class='btn btn-primary' id='accept'>Accept</button><button class='btn btn-secondary' id='decline'>Decline</button>
</div>
</div>
</div>
</div>
<script>
$('#privacyModal').modal('show');
</script>";

?>