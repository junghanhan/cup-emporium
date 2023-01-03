<?php
    include 'script/functions.php';

    if(!is_user_logged_in() && !is_admin()) {
        echo "<script type='text/javascript'> document.location = 'product_listings.html'; </script>";
    }

    // dbc to db...
    $dbc =  mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
    mysqli_set_charset($dbc, 'utf8');   

    $formBody = "<h2>Add product</h2>
                 <div class='px-2'>
                     <div class='form-group px-1'>
                         <label>Product Name</label>
                         <input type='text' name='title' class='form-control formInput' placeholder='Enter Title....' value=''>
                     </div>
                     <div class='form-group px-1'>
                         <label>Description</label>
                         <textarea name='description' class='form-control formInput' placeholder='Enter Desciption' rows='6' value=''></textarea>
                     </div>
                     <div class='form-group px-1'>
                         <label for='viewCode' id='uploadButton' class='btn btn-primary w-100'>Image upload</label>
                         <input id='viewCode' type='file' style='display:none' accept='image/*' name='viewCode'>
                     </div>
                     <div class='form-group px-1'>
                         <label>Product Type</label>
                         <select name='type' class='form-control formInput' required>
                             <option selected value='Teacup'>Teacup</option>
                             <option value='Glass'>Glass</option>
                             <option value='Mug'>Mug</option>
                             <option value='Custom'>Custom</option>
                             <option value='Special'>Special</option>
                         </select>
                     </div>
                     <div class='form-group px-1'>
                         <label>Cup Size</label>
                         <select name='size' class='form-control formInput'>
                             <option selected value='4'>4oz</option>
                             <option value='8'>8oz</option>
                             <option value='12'>12oz</option>
                             <option value='16'>16oz</option>
                             <option value='20'>20oz</option>
                         </select>
                     </div>
                     <div class='form-group px-1'>
                         <label>Price</label>
                         <input type='number' name='price' class='form-control formInput w-100' step='any' min='0.99' placeholder='$0.0' value='0.99'>
                     </div>
                     <div class='form-group px-1'>
                         <label>Quantity</label>
                         <input type='number' name='quantity' class='form-control formInput w-100' step='any' min='1' placeholder='0' value='1'>
                     </div>
                     <div class='form-group'>
                         <button type='submit' name='submit' class='btn btn-primary py-2 mt-5 rounded-pill mx-4'>
                             <svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 16 16'>
                                 <path id='Path_10' data-name='Path 10' d='M8,0,6.545,1.455l5.506,5.506H0V9.039H12.052L6.545,14.545,8,16l8-8Z' fill='#fff'/>
                             </svg>
                         </button>
                     </div>
                 </div>";
    
    function checkProductName($productName) {
        global $dbc;
        $result = false;
        $sql = 'SELECT title FROM productfullinfo WHERE title = ?';
        if($stmt = mysqli_prepare($dbc, $sql)) {
            // bind em up
            mysqli_stmt_bind_param($stmt, 's', $productName);
            // execute and store result
            if(mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);
                // check for one or more row in result
                if(mysqli_stmt_num_rows($stmt) >= 1) {
                    $result = true;
                } else {
                    $result = false;
                }
            }
            return $result;
        }
    }

    //get requests
    if($_SERVER['REQUEST_METHOD'] == "GET") {
        if(isset($_GET['type'])) {
            if($_GET['type'] == 'base') {
                echo $formBody;
            }
        }
        if($_GET['type'] == 'prodName') {
            echo  0;// checkProductName($_GET['productName']); // echo is blank?
        }
    }

    // when the request method is POST
    if($_SERVER["REQUEST_METHOD"] == "POST") {

        // File handling...
        if(isset($_FILES['viewCode'])) {
            $uploaddir = 'C:\xampp\htdocs\shopping-cart\img\\';
            $uploadfile = $uploaddir . basename($_FILES['viewCode']['name']);
            $uploadName = basename($_FILES['viewCode']['name']);

            if (move_uploaded_file($_FILES['viewCode']['tmp_name'], $uploadfile)) {
                // "File is valid, and was successfully uploaded.\n";
            } else {
                switch($_FILES['viewCode']['error']) {
                    case 1: $errorList['viewCode'] = 'File is too large to upload.'; break;
                    case 2: $$errorList['viewCode'] = 'File is too large to upload.'; break;
                    case 3: $$errorList['viewCode'] = 'File upload was interrupted'; break;
                    case 4: $$errorList['viewCode'] = 'No file uploaded'; break;
                }
                $errorList['status'] = 500;
            }
        }
        // File handling complete...

        if(checkProductName($_POST['title'])) {
            $errorList['status'] = 500;
            $errorList['title'] = 'Product name already exists';
        }

        // SQL statements with bind codes at the end
        $metaInserSQL = "INSERT INTO `productmeta`(`title`, `type`, `size`, `description`, `discount`) VALUES (?, ?, ?, ?, ?)"; // ssssd
        $productInserSQL = "INSERT INTO `product`(`metaId`, `price`, `quantity`, `viewCode`) VALUES (LAST_INSERT_ID(), ?, ?, ?)"; // dds
        
        if($stmt = mysqli_prepare($dbc, $metaInserSQL)) {
            // Bind variables to statement
            mysqli_stmt_bind_param($stmt, "ssdsd", $title, $type, $size, $description, $discount);

            // Initialize bound variables
            $title = $_POST['title'];
            $type = $_POST['type'];
            $size = $_POST['size'];
            $description = $_POST['description'];
            $discount = 0.0;

            // execute sql insert
            if(mysqli_stmt_execute($stmt)) {
                // on success do nothing
            } else {
                //on failure set error to commitError for display later
                $errorList['SQL_ERROR_INSERT'] = mysqli_error($dbc);
                $errorList['status'] = 500;
            }

            // close the stmt variable, undbc variables, and clear sql qurey string
            mysqli_stmt_close($stmt);
        } else {
            
            $errorList['SQL_ERROR_PREPARE'] = mysqli_error($dbc);
        }

        // open new stmt with second qurey and repeat above.
        if($stmt = mysqli_prepare($dbc, $productInserSQL)) {
            // set the variables
            mysqli_stmt_bind_param($stmt, "dds", $price, $quantity, $viewCode);
            // initialize the variables
            $price = $_POST['price'];
            $quantity = $_POST['quantity'];
            $viewCode = 'img/'.$_FILES['viewCode']['name'];

            //execute sql insert
            if(mysqli_stmt_execute($stmt)) {
                $errorList['status'] = 200;
            } else {
                //on failure set error to commitError for display later
                $errorList['SQL_ERROR_INSERT'] = mysqli_error($dbc);
                $errorList['status'] = 500;
            }

            // close stmt variable, undbc variables, and clear sql qurey string
            mysqli_stmt_close($stmt);

        } else {
            $errorList['SQL_ERROR_PREPARE'] = mysqli_error($dbc);
            $errorList['status'] = 500;
        }
        
        echo json_encode($errorList);
    
    }

    mysqli_close($dbc); // close DB server connection
?>