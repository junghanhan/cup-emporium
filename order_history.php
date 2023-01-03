<?php
include('script/functions.php'); // get session started and include session related functions

// if user is not logged in then send data with isLoggedIn 'false'. JS will handle this data.
$account_id = get_account_id();
if ($account_id < 0) {
    $data = array("isLoggedIn" => false);
    echo json_encode($data); // JSON format : {"isLoggedIn":false}
    exit();
}


//send response data with isLoggedIn 'true'. JS will handle this data to display the HTML snippet.
$response = array("isLoggedIn" => true, "html" => make_orders_html ($account_id));
echo json_encode($response); // JSON format : {"isLoggedIn":false, "html":"..."}


?>