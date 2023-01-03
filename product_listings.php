<?php
include('script/functions.php');

// category change request handling
// send the HTML snippet based on the request from the product listings page
// the client can request the HTML snippet of categories (type, size) or products
if($_SERVER['REQUEST_METHOD'] == 'GET') {
    if (isset($_GET['request'])) {
        $req = $_GET['request'];

        switch ($req) {
            // Display products based on selected category and size
            case 'products':
                // use default type and size when they are not designated
                $type = (isset($_GET['type']))? strtolower($_GET['type']) : 'all';
                $size = (isset($_GET['size']))? strtolower($_GET['size']) : 'all';
                make_products_html ($type, $size);
                break;

            // Display product types in the unordered list
            case 'types':
                // get the list of categories of the products on sale from DB
                $types = get_types_on_sale ();

                // create type list (all and special offers are the default lists)
                make_type_list ('all');
                foreach ($types as $type) {
                    make_type_list ($type);
                }
                make_type_list ('special');
                break;

            // Display product sizes in the dropdown list
            case 'sizes':
                // get the list of sizes of the products on sale
                $sizes = get_sizes_on_sale ();

                // create size entry in the size dropdown list (all is the default list)
                make_size_entry ('all');
                foreach ($sizes as $size) {
                    make_size_entry ($size);
                }
                break;
        }
    }
}

// 'Add product to cart' request handling
// if a client requests to add products, add the products and quantities to the cart in the DB based on received data.
// Note that POST is requested only when 'Add products to cart' button is clicked on product listings page
if (isset($_SERVER['CONTENT_TYPE'])) {
    $content_type = trim($_SERVER['CONTENT_TYPE']);

    if ($content_type === 'application/json') {
        $content = trim(file_get_contents("php://input"));

        $received_data = json_decode ($content, true);

        // decode success
        if (is_array($received_data)) {
            // if user is logged in, then add products to DB
            $account_id = get_account_id();
            if ($account_id < 0) { // not logged in
                $response['isLoggedIn'] = false;
                $response['state'] = true;
            }
            else { // user logged in
                $response['isLoggedIn'] = true;

                if (is_validate_product_to_buy($received_data)) {
                    $products_NA = get_NA_products($received_data, $account_id); // get not available products

                    if (count($products_NA) > 0) {
                        $response['state'] = false;
                        $response['error'] = 'The quantity(ies) of ' . implode(', ', $products_NA)
                            . " in your cart will be over that(those) in stock.\n\n"
                            . 'Please check the available stock and try again.';
                    } else {
                        $response['state'] = true;
                        add_products_to_cart ($received_data, $account_id);
                    }
                }
                else { // received data is not valid
                    $response['state'] = false;
                    $response['error'] = 'Adding products to the cart failed.';
                }
            }
        }
        // failed to decode JSON
        else {
            $response['state'] = false;
            $response['error'] = 'Invalid JSON format';
        }
    }

    echo json_encode ($response);
}

/* functions only used in product listings page */

// get the products that their quantities will be over that in stock if they are added to the cart
// if adding products to cart is successful, output will be an empty array
// input : an array of product-quantity pair that a user is going to buy (array), user's account_id (int)
// output : an array of product names that their quantities will be over the quantity in stock. (array)
function get_NA_products ($product_quantity_pairs_to_buy, $account_id) {
    // Make the connection:
    $dbc = @mysqli_connect (DB_HOST, DB_USER, DB_PASSWORD, DB_NAME) OR
           die ('Could not connect to MySQL: ' . mysqli_connect_error() );
    mysqli_set_charset($dbc, 'utf8');

    $result = []; //product titles that will be out of stock if the products are added to the cart

    $cart_id = get_user_cart_id ($account_id);

    $q = "SELECT `productId`, `maxQuantity`, `quantity`, `title`
            FROM `cartInfo`
           WHERE `cartId` = $cart_id;";

    $r = mysqli_query($dbc, $q);
    while ($row = mysqli_fetch_array ($r, MYSQLI_ASSOC)) {
        $product_id = $row['productId'];
        $max_qu = $row['maxQuantity'];
        $cart_qu = $row['quantity'];
        $product_title = $row['title'];
        $qu_to_buy = (isset($product_quantity_pairs_to_buy["$product_id"])) ? $product_quantity_pairs_to_buy["$product_id"] : 0;

        // check if the quantity of the product in the cart will be over the quantity in stock
        if ($max_qu < $qu_to_buy + $cart_qu) {
            $result[] = $product_title;
        }
    }

    mysqli_close($dbc); // close DB server connection
    return $result;
}


// check whether the received quantity to buy is less than 1
// input : an array of product-quantity pair that a user is going to buy (array)
// output : the fact whether product-quantity pairs from the client is valid or not (boolean)
function is_validate_product_to_buy ($product_quantity_pairs_to_buy) {
    $result = true;

    // check whether the quantity to buy is less than 1
    foreach ($product_quantity_pairs_to_buy as $product_id => $qu_to_buy) {
        if ($qu_to_buy < 1) {
            $result = false;
            break;
        }
    }

    return $result;
}

// make products HTML elements
// input: product type (string), product size (string)
function make_products_html ($type, $size) {
    $r = get_available_product_info (); // get product full info from DB

    while ($row = mysqli_fetch_array ($r, MYSQLI_ASSOC)) {
        if (($type == strtolower($row['type']) || $type == 'all')
            &&
            ($size == strtolower($row['size']) || $size == 'all')) {

            make_product_card ($row);
        }
    }
}

// input : product info (associative array) (keys: productId, metaId, price, quantity, viewCode, title, type, size, description, discount)
function make_product_card ($product_info) {
    // product ID : checkbox value
    // quantity : input element value
    echo "<div class='col-4'>
                    <div class='card'>
                        <h1 class='card-title'>{$product_info['title']}</h1>
                        <img src={$product_info['viewCode']}>
                        <p>{$product_info['description']}</p>
                        <p>Type: {$product_info['type']}</p>
                        <p>Size: {$product_info['size']}</p>
                        <p>Price: {$product_info['price']}</p>
                        <p>In Stock: {$product_info['quantity']}</p>
                        <p class='card-purchace-area'>
                            <label class='card-title'>Quantity: </label>
                            <input type='number' name='quantity' min='1' max='{$product_info['quantity']}' value='1'>
                            </br>
                            <label for='product-{$product_info['productId']}'>Buy</label>
                            <input type='checkbox' name='product-id' value='{$product_info['productId']}' id='product-{$product_info['productId']}'>
                        </p>
                    </div>
         </div>\n";
}

// make the product type HTML list element
// input: product type to display in the list (string)
function make_type_list ($type) {
    echo "<li>" . ucfirst($type) . "</li>";
}

// make the product size HTML dropdown list entry element
// input: product size to display in the dropdown list (string)
function make_size_entry ($size) {
    echo "<option value='$size'>" . ucfirst($size) . "</option>";
}

?>
