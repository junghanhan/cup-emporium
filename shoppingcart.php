<?php
    include('script/functions.php');

    // dbc to db...
    $dbc =  mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
    mysqli_set_charset($dbc, 'utf8');

    function getCartId() {
        global $dbc;
        $result = -1;
        $sql = 'SELECT cartId FROM cart WHERE accountId = ?';
        if($stmt = mysqli_prepare($dbc, $sql)){
            $accountId = get_account_id();
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, 's', $accountId);
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                /* store result */
                mysqli_stmt_store_result($stmt);
                if(mysqli_stmt_num_rows($stmt) == 1){
                    mysqli_stmt_bind_result($stmt, $result);
                    mysqli_stmt_fetch($stmt);
                } else{
                    $result = -1;
                }
            } else{
                return $result;
            }
            // Close statement
            mysqli_stmt_close($stmt);
            return $result;
        }
    }
    
    // POST method body, checks JSON body definition for either a delete value
    // or a change value to handle removing items from the cart and updating their quantity
    if($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Check for delete in the data, make sql call here to remove data from 
        // cartItem and cart tables
        if(isset($_POST['delete'])) {
            $ids = array_map('intval', explode(',', $_POST['idsToRemove']));
            //incomplete sql since i'm concatinating the ids need to the IN statement
            $sql = 'DELETE FROM `cartitem` WHERE cartItemId IN (';
            $i = 0;
            //for loop of ids
            foreach($ids as &$id) {
                // if the $i value equals the length of the array
                if(++$i === count($ids)) {
                    // put the bracket at the end so the statement is valid
                    $sql .= $id.')';
                } else {
                    // dropping a comma after each value
                    $sql .= $id.',';
                }
            }
            // execute query
            mysqli_query($dbc, $sql);
        }
        // end of delete pass section
        // Check for ids and change values to update quantity
        if(isset($_POST['change'])) {
            // simple query with required data inserted
            $sql = 'UPDATE `cartitem` SET `quantity`='. $_POST['quantity'].' WHERE cartItemId = '. $_POST['id'];
            // run query
            mysqli_query($dbc, $sql);

        }
    }

    if($_SERVER['REQUEST_METHOD'] == 'GET') {

        $cartId = getCartId();

        $sql = 'SELECT * FROM cartInfo WHERE cartId = '.$cartId;
        $r = mysqli_query($dbc, $sql);

        $body = '';

        $data = array();
        $total = 0;

        while ($row = mysqli_fetch_array ($r, MYSQLI_ASSOC)) {
            $subTotal = $row['price'] * $row['quantity'];
            $total += $subTotal;
            $body .=
            "<div class='row bottomDivider cart-item'>
                <div class='col'><input type='checkbox' id='{$row['cartItemId']}' class='formInput'></div>
                <div class='col'><img src='{$row['viewCode']}'></div>
                <div class='col'><h6>Product Name</h6><p>{$row['title']}</p></div>
                <div class='col'><h6>Product Info</h6><p>{$row['description']}</p></div>
                <div class='col'><h6>Product Price</h6><p id='{$row['cartItemId']}price'>{$row['price']}</p></div>
                <div class='col'><h6>Quantity</h6><input id='{$row['cartItemId']}' type='number' class='formInput w-50' min='0' max='{$row['maxQuantity']}' value='{$row['quantity']}'></div>
                <div class='col'><h6>Subtotal Price</h6><p id='{$row['cartItemId']}subtotal'>{$subTotal}</p></div>
            </div>";
        }

        $data['body'] = $body;
        $data['total'] = $total;

        echo json_encode($data);
    }

    mysqli_close($dbc);
?>