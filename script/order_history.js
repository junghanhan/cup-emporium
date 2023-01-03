import { displayNavBar } from './functions.js';

init ();

/* functions used in order history page */
function init() {
  displayOrderHistory();
  displayNavBar();
}

// display the HTML snippet of orders on the order history page
async function displayOrderHistory () {
  // get JSON data of order history from server
  const jsonData = await getOrderHistoryHTML ();

  if (jsonData.isLoggedIn) {
    // append the order history HTML at the end of main section
    document.querySelector('.main-section').innerHTML += jsonData.html;
  }
  // if received data's isLoggedIn is false, then redirect to login page
  else {
    window.location.replace("login.html");
  }
}

// Get order history HTML from server
// output: order history presented as HTML elements (string)
async function getOrderHistoryHTML () {
  try {
    const url = "order_history.php";
    const response = await fetch (url);
    if (response.ok) {
      const jsonData = await response.json();
      return jsonData;
    }

    throw new Error('Error in getOrderHistoryHTML');
  } catch (error) {
    console.log (error);
    return null;
  }
}



