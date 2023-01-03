<?php
    include('script/functions.php');
	
	// MySQL Database Connection
    $dbs =  mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
    mysqli_set_charset($dbs, 'utf8');
    $all_products = get_available_product_info();

    //if page is requested by index.js's addProductCards() function, it will send back all products in the database
    if($_SERVER['REQUEST_METHOD'] == 'GET'){
        if(isset($_GET['Id'])){
            echoCard($all_products, $_GET['Id']);
        }
        //otherwise it redirects to index.html
        else{
            header('Location: index.html');
        }
    }
	
    //echos a product card for use in the featured products section
    function echoCard($all_products, $target_id){
        while($row = mysqli_fetch_array($all_products, MYSQLI_ASSOC)){
            if($row['productId'] == $target_id){
                echo" <div class='card'>
                    <h1 class='card-title'>{$row['title']}</h1>
                    <img src={$row['viewCode']}>
                    <p>{$row['description']}</p>
                    <p>Type: {$row['type']}</p>
                    <p>Size: {$row['size']}</p>
                    <p>Price: {$row['price']}</p>
                    <p>In Stock: {$row['quantity']}</p>
                    <a href='product_listings.html'><button class='btn-primary btn'>Add to Cart</button></a>
                </div>";
            }
        }
        
    }

    
?>