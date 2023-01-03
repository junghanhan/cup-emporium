<?php
    include 'script/functions.php';

    // Make the connection:
    $dbc = @mysqli_connect (DB_HOST, DB_USER, DB_PASSWORD, DB_NAME) OR
           die ('Could not connect to MySQL: ' . mysqli_connect_error() );
    mysqli_set_charset($dbc, 'utf8');

    // initialize cart in table with/by account id
    // also log in the user so the accountId is ready in session variable
    function initCart($username) {
        // function but need globals
        global $dbc;
        global $errorList;
        $accountId = 0;
        // sql for accountId
        $sql = 'SELECT accountId FROM user WHERE username = ?';
        // get account Id
        if($stmt = mysqli_prepare($dbc, $sql)) {
            // bind em tight!
            mysqli_stmt_bind_param($stmt, 's', $username);
            // execute, on fail throw to errorList
            if(mysqli_stmt_execute($stmt)) {
                // save results
                mysqli_stmt_store_result($stmt);
                    // check for one result, SHOULD ONLY BE ONE!
                    if(mysqli_stmt_num_rows($stmt) == 1) {
                        // bind the result
                        mysqli_stmt_bind_result($stmt, $accountId);
                        // Spent 20-30 min frustrated with why account id was null
                        // then I remembered I need to fetch first TT_TT
                        mysqli_stmt_fetch($stmt);
                        //close statement
                        mysqli_stmt_close($stmt);
                    }
            } else {
                // error and return false since the cart failed to create
                $errorList['SQL_ERROR_SELECT'] = mysqli_stmt_error($stmt);
                return false;
            }
        }
        // sql for inserting new cart, default is NOW() in sql but use it here for reference to what it needs to be
        $cartInsertSql = 'INSERT INTO cart(accountId, createdAt, updatedAt) VALUES (?, NOW(), NOW())';
        // usual prep
        if($stmt = mysqli_prepare($dbc, $cartInsertSql)) {
            // Bind em tight!
            mysqli_stmt_bind_param($stmt, 'i', $accountId);
            // shoot the request out and again errorList any errors
            if(mysqli_stmt_execute($stmt)) {
                // also log_in for session variables default false since admin is unique
                log_in($accountId, false);
                return true;
            }
            else {
                // errorList, name specific for INSERT
                $errorList['SQL_ERROR_INSERT'] = mysqli_stmt_error($stmt);
                return false;
            }
        }
    }


    $formBody = "
    <h2>Register</h2>
    <div class='px-2'>
        <div class='form-group px-1'>
            <label>First Name</label>
            <input type='text' name='fName' class='form-control formInput' placeholder='Enter first name' value='' reqwui>
        </div>
        <div class='form-group px-1'>
            <label>Last Name</label>
            <input type='text' name='lName' class='form-control formInput' placeholder='Enter last name' value=''>
        </div>
        <div class='form-group px-1'>
            <label>E-Mail</label>
            <input type='text' name='email' class='form-control formInput' placeholder='Enter email' value=''>
        </div>
        <div class='form-group px-1'>
            <label>Username<sub>(Username needs to start with a captial letter)</sub></label>
            <input type='text' name='username' class='form-control formInput' placeholder='Enter username' value=''>
        </div>
        <div class='form-group px-1'>
            <label>Password<sub>(Password needs atleast 8 characters, start with a capital and have a number)</sub></label>
            <input type='password' name='password' class='form-control formInput' placeholder='Enter password' value=''>
        </div>
        <div class='form-group px-1'>
            <label>Confirm Password</label>
            <input type='password' name='confirmPassword' class='form-control formInput' placeholder='Enter password again' value=''>
        </div>
        <div class='form-group bottomDivider'>
            <label name='privacyLabel' for='privacy'>Accept our privacy policy to register</label>
            <button name='privacy' class='btn btn-primary py-2 rounded-pill mb-2 mx-4'>
                <b>Privacy Policy</b>
            </button>
            <input name='policyAgreement' type='hidden' value='declined'>
        </div>
        <div class='form-group'>
            <button type='submit' class='btn btn-primary py-2 mt-1 rounded-pill mx-4'>
                <b>Register</b>
            </button>
        </div>
    </div>";

    // just a get for the form body
    if($_SERVER['REQUEST_METHOD'] == 'GET') {
        if(isset($_GET['type']) and $_GET['type'] == 'base') {
            echo $formBody;
        }
        if(isset($_GET['type']) and $_GET['type'] == 'username') {
            
            if(findUser($_GET['username'])) {
                $errorList['status'] = 500;
                $errorList['username'] = 'Username is taken!';
            } else {
                $errorList['status'] = 200;
                $errorList['username'] = 'Username available!';
            }
            if(!testUsername($_GET['username'])) {
                $errorList['status'] = 500;
                $errorList['username'] = 'Invalid username';
            } else {
                $errorList['status'] = 200;
                $errorList['username'] = null;
            }
            echo json_encode($errorList);
        }
        if(isset($_GET['type']) and $_GET['type'] == 'password') {
            echo testPassword($_GET['password']);
        }
        if(isset($_GET['type']) and $_GET['type'] == 'policy') {
            echo $privacy;
        }
    }

    // Define variables and initialize with empty values
    $username = $password = $confirm_password = '';
    
    // Processing form data when form is submitted
    if($_SERVER['REQUEST_METHOD'] == 'POST'){
    
        if(isset($_POST['username'])) {
            // Validate username
            if(empty(trim($_POST['username']))){
                $errorList['username'] = 'Please enter a username.';
            } else if(!testUsername($_POST['username'])) {
                $errorList['username'] = 'Please enter a valid username.';
            } else {
                if(findUser($_POST['username'])) {
                    $errorList['username'] = 'This username is already taken.';
                } else{
                    $username = trim($_POST['username']);
                }
            }

            // Validate password
            if(empty(trim($_POST["password"]))){
                $errorList['password'] = "Please enter a password.";     
            } else if(strlen(trim($_POST["password"])) < 8){
                $errorList['password'] = "Password must have at least 8 characters.";
            } else if(!testPassword($_POST["password"])) {
                $errorList['password'] = "Please enter a valid password";
            }
            else {
                $password = trim($_POST["password"]);
            }

            // Validate confirm password
            if(empty(trim($_POST["confirmPassword"]))){
                $errorList['confirmPassword'] = "Please confirm password.";     
            } else{
                $confirm_password = trim($_POST["confirmPassword"]);
                if(empty($password_err) && ($password != $confirm_password)){
                    $errorList['confirmPassword'] = "Password did not match.";
                }
            }

            // Quick check for empty fields in form
            if(empty(trim($_POST['fName']))) {
                $errorList['fName'] = 'Required field!';
            }
            if(empty(trim($_POST['lName']))) {
                $errorList['lName'] = 'Required field!';
            }
            if(empty(trim($_POST['email']))) {
                $errorList['email'] = 'Required field!';
            } else if(!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
                $errorList['email'] = 'Invalid email!';
            }
            if($_POST['policyAgreement'] == 'declined') {
                $errorList['privacy'] = '';
            }

            // Check input errors before inserting in database
            if(empty($errorList)) {
                
                // Prepare an insert statement
                $sql = "INSERT INTO user (username, passwordHash, firstName, lastName, email, privacy) VALUES (?, ?, ?, ?, ?, 'T')";

                if($stmt = mysqli_prepare($dbc, $sql)) {

                    // Set parameters
                    $param_username = $username;
                    $param_password = password_hash($password, PASSWORD_DEFAULT); // Creates a password hash
                    $param_firstName = $_POST['fName'];
                    $param_lastName = $_POST['lName'];
                    $param_email = $_POST['email'];

                    // Bind variables to the prepared statement as parameters
                    mysqli_stmt_bind_param($stmt, "sssss", $param_username, $param_password, $param_firstName, $param_lastName, $param_email);

                    // Attempt to execute the prepared statement
                    if(mysqli_stmt_execute($stmt)) {
                        initCart($param_username);
                    } else {
                        $errorList['SQL_ERROR'] = mysqli_stmt_error($stmt);
                    }
                    // Close statement
                    mysqli_stmt_close($stmt);
                } else {
                    $errorList['SQL_ERROR'] = mysqli_error($dbc);
                }
                if(empty($errorList)) {
                    $errorList['status'] = 200;
                } else {
                    $errorList['status'] = 500;
                }
            } else {
                $errorList['status'] = 500;
                
            }
            echo json_encode($errorList);
        }
    }

    // Close connection
    mysqli_close($dbc);
?>
