<?php

    require_once 'config.php';

    define('DB_SERVER', 'localhost');
    define('DB_USERNAME', 'test_user');
    define('DB_PASSWORD', 'test_password');
    define('DB_NAME', 'store_db');

    define('defaultCart', '1');

    // Link to db...
    $LINK =  mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
    mysqli_set_charset($LINK, 'utf8');

    if($_SERVER['REQUEST_METHOD'] == 'GET') {

        $sql = 'SELECT * FROM cartInfo WHERE cartId = '.defaultCart;
        $r = mysqli_query($LINK, $sql);

        $body = array('orderInfo' => "",
                      'Total' => 0,
                      'pub_key' => $_ENV['STRIPE_PUBLISHABLE_KEY'],
                      'secret_key' => $_ENV['STRIPE_SECRET_KEY']);

        $total = 0;

        while ($row = mysqli_fetch_array ($r, MYSQLI_ASSOC)) {
            $subTotal = $row['price'] * $row['quantity'];
            $total += $subTotal;
            $body['orderInfo'] .= "<div class='row'><div class='row bottomDivider'><div class='col'><img src='{$row['viewCode']}'></div><div class='col'><h6>Product Name</h6><p>{$row['title']}</p></div><div class='col'><h6>Product Info</h6><p>{$row['description']}</p></div><div class='col'><h6>Product Price</h6><p>$ {$row['price']}</p></div><div class='col'><h6>Quantity</h6><input id='{$row['cartItemId']}' type='number' class='formInput' min='0' max='{$row['maxQuantity']}' value='{$row['quantity']}'></div><div class='col'><h6>Subtotal Price</h6><p>$ {$subTotal}</p></div></div></div>";
        }

        $body['orderInfo'] .= "<div class='row'><h2 id='OrderTotal'></h2></div>";

        $body['Total'] = number_format($total, 2);

        $paymentIntent = $stripe->paymentIntents->create([
            'payment_method_types' => ['card'],
            'amount' => ($body['Total'] * 100),
            'currency' => 'cad',
        ]);

        echo json_encode($body);

    }

    if($_SERVER['REQUEST_METHOD'] == 'GET' && false) {

        $sql = 'SELECT * FROM cartInfo WHERE cartId = '.defaultCart;
        $r = mysqli_query($LINK, $sql);

        $body = '';

        while ($row = mysqli_fetch_array ($r, MYSQLI_ASSOC)) {
            $subTotal = $row['price'] * $row['quantity'];
            $body .=
            "<div class='row bottomDivider'>
                <div class='col'><input type='checkbox' id='{$row['cartItemId']}' class='formInput'></div>
                <div class='col'><img src='{$row['viewCode']}'></div>
                <div class='col'><h6>Product Name</h6><p>{$row['title']}</p></div>
                <div class='col'><h6>Product Info</h6><p>{$row['description']}</p></div>
                <div class='col'><h6>Product Price</h6><p>$ {$row['price']}</p></div>
                <div class='col'><h6>Quantity</h6><input id='{$row['cartItemId']}' type='number' class='formInput' min='0' max='{$row['maxQuantity']}' value='{$row['quantity']}'></div>
                <div class='col'><h6>Subtotal Price</h6><p>$ {$subTotal}</p></div>
            </div>";
        }

        echo $body;
    }

    mysqli_close($LINK);
?>