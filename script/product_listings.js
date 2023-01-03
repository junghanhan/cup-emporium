import { displayNavBar } from './functions.js';

const form = document.querySelector ('#products-section');
const addToCartButton = form.querySelector ('#add-to-cart-button');
const productsDiv = document.querySelector ('#products-div');
const typeList = form.querySelector('#product-type-ul');
const sizeDropdownList = form.querySelector('#product-size-dl');

init();

function init() {
  // display elements
  displayNavBar();
  displayProductTypes();
  displayProductSizes();
  displayProducts ('all','all');

  addToCartButton.addEventListener('click',() => {
    // check validity when add to product button is clicked
    if (checkValidity()) {
      addProductToCart();

      // uncheck all checkboxes
      form.querySelectorAll('input[type="checkbox"]')
          .forEach((element) => {
            element.checked = false;
      });
    }
  });
}

// build an object of product-quantity pairs to buy
// output: an object of product-quantity pairs to buy (object)
// e.g.) {1: "2", 4: "5"} : two of product ID 1, five of product ID 4
function getProductQuantityPairs () {
  let result = {};

  const selectedProductCheckboxes = form.querySelectorAll('input[name="product-id"]:checked');

  selectedProductCheckboxes.forEach((element) => {
    const productID = element.value;
    const quantity = element.parentNode.querySelector('input[name="quantity"]').value;
    result[`${productID}`] = quantity;
  });

  return result;
}

// add selected products to cart when the button is clicked
async function addProductToCart () {
  try {
    const url = "product_listings.php";
    const data = getProductQuantityPairs(); // build an object of product-quantity pairs to buy
    const response = await fetch (url,{
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(data)
    });

    if (response.ok) {
      const jsonData = await response.json();

      if (jsonData.state) {
        if (jsonData.isLoggedIn) // successfully added the products to cart
          alert ('The selected products are added to your shopping cart.');
        else // user is not logged in, so redirect to the login page
          window.location.replace("login.html");
      }
      else
        alert (jsonData.error);

    }
    else
      throw new Error('Error in addProductToCart()');
  } catch (error) {
    console.log (error);
    return null;
  }
}

// display product types on the category section
async function displayProductTypes () {
  // get HTML of product types
  let productTypesHTML = await getProductTypesHTML ();

  // put product types HTML in the product type unordered list
  typeList.innerHTML += productTypesHTML;

  const typeListItems = typeList.querySelectorAll('li');
  typeListItems[0].classList.add('selected');

  // click event listener for product type list items
  // make the selected bold, update the displayed products based on the selected categories
  typeListItems.forEach((element) => element.addEventListener('click',
      function () {
        // apply 'selected' class only for the clicked type list item
        typeListItems.forEach ((element) => element.classList.remove('selected'));
        this.classList.add('selected');

        productsDiv.innerHTML = ''; // remove previously displayed products
        displayProducts (this.innerText, sizeDropdownList.value);
      }));
}

// fill product sizes in the size dropdown list on the category section
async function displayProductSizes () {
  // get HTML of product sizes
  let productSizesHTML = await getProductSizesHTML ();

  // append the products HTML at the end of product section
  sizeDropdownList.innerHTML += productSizesHTML;

  // dropdown list state change event listener
  // update the displayed products based on the selected categories
  sizeDropdownList.addEventListener('change',
      function () {
        productsDiv.innerHTML = ''; // remove previously displayed products
        displayProducts (typeList.querySelector('.selected').innerText, this.value);
      });
}

// display product cards on the product section
// input: product type (string), product size (string)
async function displayProducts (type, size) {
  // get HTML of the products in the selected category from the server
  let productsHTML = await getProductsHTML (type, size);

  // append the products HTML at the end of product section
  document.querySelector('#products-div').innerHTML += productsHTML;

  // limit the quantity input less than max (in stock) and greater than 0
  productsDiv.querySelectorAll ('input[name="quantity"]')
      .forEach ((element) => {
        element.oninput = function () {
          let max = parseInt(this.max);
          let min = parseInt(this.min);
          let input = parseInt(this.value);
          if (input > max)
            this.value = max;
          else if (input < min)
            this.value = min;
        }
      });
}

// get product types HTML from server
// output: the HTML snippet of product types (string)
async function getProductTypesHTML () {
  try {
    const url = "product_listings.php";
    const requestTarget = "types";
    const endpoint = `${url}?request=${requestTarget}`;
    const response = await fetch (endpoint);
    if (response.ok) {
      return response.text();
    }

    throw new Error('Error in getProductTypesHTML');
  } catch (error) {
    console.log (error);
    return null;
  }
}

// get product sizes HTML from server
// output: the HTML snippet of product sizes (string)
async function getProductSizesHTML () {
  try {
    const url = "product_listings.php";
    const requestTarget = "sizes";
    const endpoint = `${url}?request=${requestTarget}`;
    const response = await fetch (endpoint);
    if (response.ok) {
      return response.text();
    }

    throw new Error('Error in getProductSizesHTML');
  } catch (error) {
    console.log (error);
    return null;
  }
}


// get products HTML from server
// output: the HTML snippet of the products (string)
async function getProductsHTML (type, size) {
  try {
    const url = "product_listings.php";
    const requestTarget = "products";
    const endpoint = `${url}?request=${requestTarget}&type=${type}&size=${size}`;
    const response = await fetch (endpoint);
    if (response.ok) {
      return response.text();
    }

    throw new Error('Error in getProductsHTML');
  } catch (error) {
    console.log (error);
    return null;
  }
}

// check whether at least one product is selected
// output: (boolean)
function isChecked() {
  const selectedProductCheckboxes = form.querySelectorAll('input[name="product-id"]:checked');
  if (selectedProductCheckboxes.length > 0)
    return true;

  return false;
}

// check validity before proceed to add the selected products to the cart
// output: (boolean)
function checkValidity() {
  let result = true;

  if (!isChecked()) {
    alert('At least one product must be selected.');
    result = false;
  }

  return result;
}


