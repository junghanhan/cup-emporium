<?php

include('script/functions.php'); // get session started and include session related functions

function optOut() {
	set_account_privacy(get_account_id(),false);
	header('Location: logout.php');
}

echo "<nav class='navbar navbar-expand-lg navbar-dark'>
        <a href='index.html' class='navbar-brand'><img src='img/logo_white.png' class='navbar-logo'/></a>

        <button class='navbar-toggler' type='button' data-toggle='collapse' data-target='#navbarNav' aria-controls='navbarNav' aria-expanded='false' aria-label='Toggle navigation'>
            <span class='navbar-toggler-icon'></span>
        </button>

        <div class='collapse navbar-collapse' id='navbarNav'>
            <ul class='navbar-nav mr-auto'>
                <li class='nav-item active'>
                    <a class='nav-link' href='product_listings.html'>Catalogue</a>
                </li>";


// user is not logged in
if (is_user_logged_in() == false) {
    echo "</ul>

            <a href='login.html'><button type='button' class='btn btn-primary'>
                Log In
            </button></a>
            <a href='register.html'><button type='button' class='btn btn-primary'>
                Sign Up
            </button></a>";
}
// user is logged in
else {
    // logged in as admin
    if (is_admin()) {
		echo "<li class='nav-item active'>
						<a class='nav-link' href='addProduct.html'>Add Product</a>
					</li>";
    }
	echo "</ul>
            <a href='shoppingcart.html'><button type='button' class='btn btn-primary'>
                My Cart
			</button></a>
			<div class='dropdown'>
				<button type='button' class='btn btn-primary dropdown-toggle' data-toggle='dropdown' aria-haspopup='true' aria-expanded='false'>
					User Options
				</button>
				<div class='dropdown-menu'>
					<a href='logout.php' class='dropdown-item'>
						Log Out
					</a>
					<a href='navbar.php?policy=decline' class='dropdown-item'>
						Decline Privacy Policy
					</a>
					<a href='order_history.html' class='dropdown-item'>
                        Order History
                    </a>
				</div>
			</div>";

}
echo "</div>
    </nav>";

if($_SERVER['REQUEST_METHOD'] == 'GET') {
    if(isset($_GET['policy'])) {
        optOut();
    }
}
?>