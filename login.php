<?php
    include('script/functions.php');

    // Any GET request
    if($_SERVER['REQUEST_METHOD'] == 'GET') {
        //with type set in the request
        if(isset($_GET['type'])) {
            // get form specifics
            if($_GET['type'] == 'form') {
                echo "
                <h2>Login</h2>
                <div class='form-group px-1'>
                    <label for='username' >Username</label>
                    <input type='text' name='username' class='form-control formInput' placeholder='Enter Username....' value=''>
                </div>
                <div class='form-group px-1'>
                    <label for='password'>Password</label>
                    <input type='password' name='password' class='form-control formInput' placeholder='Enter Password....' value=''>
                </div>
                <div class='form-group'>
                    <button name='submitButton type='submit' class='btn btn-primary py-2 mt-5 rounded-pill mx-4'>
                        <svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 16 16'>
                            <path id='Path_10' data-name='Path 10' d='M8,0,6.545,1.455l5.506,5.506H0V9.039H12.052L6.545,14.545,8,16l8-8Z' fill='#fff'/>
                        </svg>
                    </button>
                </div>";
            }
            // check for username in dbs
            if($_GET['type'] == 'username') {
                if(!findUser($_GET['username'])) {
                    $errorList['status'] = 500;
                    $errorList['username'] = 'Invalid username!';
                } else {
                    $errorList['status'] = 200;
                    $errorList['username'] = null;
                }
                echo json_encode($errorList);
            }

        }
        
    }
    // all post requests
    if($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Make the connection:
        $dbc = @mysqli_connect (DB_HOST, DB_USER, DB_PASSWORD, DB_NAME) OR
               die ('Could not connect to MySQL: ' . mysqli_connect_error() );
        mysqli_set_charset($dbc, 'utf8');

        // post request with username AND password set
        if(isset($_POST['username']) and isset($_POST['password'])) {
            //array of errors
            $errorList;
            // securly set values for statment
            $sql = 'SELECT username, passwordHash, accountId, admin, privacy FROM user WHERE username = ?';
            if($stmt = mysqli_prepare($dbc, $sql)) {
                // bind em up
                mysqli_stmt_bind_param($stmt, 's', $username);
                $username = $_POST['username'];
                $password = $_POST['password'];
                // execute and store result
                if(mysqli_stmt_execute($stmt)) {
                    mysqli_stmt_store_result($stmt);
                    // check for one row in result (SHOULD ONY EVER BE ONE!)
                    if(mysqli_stmt_num_rows($stmt) == 1) {
                        // reverse of the bind statements above for results (THESE ARE EMPTY!)
                        mysqli_stmt_bind_result($stmt, $resultUsername, $hashedPassword, $accountId, $admin, $privacyVal);
                        // now above variables are populated with the first (AND ONLY) row of data
                        if(mysqli_stmt_fetch($stmt)) {
                            //verify password (ya that easy)
                            if(password_verify($password, $hashedPassword)) {
                                // test the value in privacy column
                                if(testPrivacy($privacyVal)) {
                                    // set accountId and check for admin
                                    if($admin == 'T') {
                                        log_in($accountId, true);
                                    } else {
                                        log_in($accountId, false);
                                    }
                                    $errorList['status'] = 200;
                                } else {
                                    $errorList['status'] = 500;
                                    $errorList['acceptPrivacy'] = $privacy;
                                    setcookie('username', $_POST['username']);
                                }
                            } else {
                                $errorList['status'] = 500;
                                $errorList['password'] = 'Wrong password';
                            }
                        }
                    } else if(mysqli_stmt_num_rows($stmt) == 0) {
                        $errorList['status'] = 500;
                        $errorList['username'] = 'Username not found';
                    }
                }
            } else {
                $errorList['status'] = 500;
                $errorList['SQL_Error'] = mysqli_error($dbc);
            }
        }
        if(isset($_POST['privacy'])) {
            $sql = "UPDATE user SET privacy='T' WHERE username = ?";
            if($stmt = mysqli_prepare($dbc, $sql)) {
                mysqli_stmt_bind_param($stmt, 's', $_COOKIE['username']);
                if(!mysqli_stmt_execute($stmt)) {
                    $errorList['status'] = 500;
                    $errorList['SQL_Error'] = mysqli_error($dbc);
                } else {
                    $errorList['setValue'] = $_COOKIE['username'];
                }
            } else {
                $errorList['status'] = 500;
                $errorList['SQL_Error'] = mysqli_error($dbc);
            }
            $errorList['status'] = 200;
        }
        
        echo json_encode($errorList);

        mysqli_close($dbc); // close DB server connection
    }
?>
