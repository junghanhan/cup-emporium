import { displayNavBar } from './functions.js';
// variable definition before page load
var timer;
var checkedItems=[]; // cleared every page load

loadBody();
displayNavBar();

// make get request to php for body
async function loadBody() {
    const response = await fetch('shoppingcart.php');
    if(response.ok) {
        const json = await response.json();
        var roundedTotal = json.total;
        // Add response from server to div with id insertSection
        $('#insertSection').html(json.body);
        $('#Total').html(`${roundedTotal.toFixed(2)}`);

        // Bind a change function to the number type input tags
        $(':input[type="number"]').bind('change', async function() { 
            // Get the id and value of the input 
            var id = $(this).attr('id');
            var quantity = $(this).val();

            clearTimeout(timer); //cancel the previous timer.

            // If the quantity is set to zero
            if(quantity == 0) {
                // If the 'cancel' button is pressed then set the value to 1 and break out of the function.
                if(!confirm(`Your item will be deleted since the quantity is 0, Cancel?`)) {
                    $(this).val(1);
                    return;
                } else { // If 'Ok' is pressed then the id is pushed to the array used to remove items by id
                    checkedItems.push(id);
                    $('#deleteSelected').click(); // and manually click the deleteSelected button
                }
            } else { // if the button is not zero
                // create a timeout for 500ms that acts as a buffer for multiple button presses to not spam messages to the server
                timer = setTimeout(async function() {
                    const repsonse2 = await fetch(`shoppingcart.php`,
                    {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                        },
                        body: `change=true&id=${id}&quantity=${quantity}`
                    });
                    var subtotal = parseFloat($(`#${id}price`).html()) * 100;
                    var oldSubTotal = parseFloat($(`#${id}subtotal`).html()) * 100;
                    subtotal = subtotal * quantity;
                    $(`#${id}subtotal`).html(`${(subtotal /100).toFixed(2)}`);
                    var nTotal = parseFloat($('#Total').html()) * 100;
                    if(oldSubTotal > subtotal) {
                        nTotal -= (oldSubTotal - subtotal);
                    } else if(oldSubTotal < subtotal) {
                        nTotal -= oldSubTotal;
                        nTotal += subtotal;
                    } else {
                        nTotal = nTotal;
                    }
                    nTotal /= 100;
                    $('#Total').html(nTotal.toFixed(2));
                }, 500);
                
            }
         });
    }
}

// Find any input type with checkbox checked and add to array of id's
function getCheckedItems() {
    $('input:checkbox').each(function() {
        if($(this).is(':checked')) {
            checkedItems.push($(this).attr('id'));
        }
    });
    // conditional for button press incase of no selected checkboxes
    if(checkedItems.length >= 1) {
        return true;
    } else {
        return false;
    }
}

// Get all the ids of the checkboxes and add to the array of id's
function getAllItems() {
    $('input:checkbox').each(function() {
        checkedItems.push($(this).attr('id'));
    });
}

// Event bind to the deleteSelected button
$('#deleteSelected').click(async function() {
    // call the relevant method to populate array
    if(getCheckedItems()) {
        const response = await fetch(`shoppingcart.php`,
        {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            },
            body: `delete=true&idsToRemove=${checkedItems}`
        });
        if(response.ok) {
            loadBody();
        }
    }
});

// Same method as above just uses a confirm box to make sure it was intentional and uses apporpriate function to populate id's array
$('#deleteAll').click(async function() {
    if(confirm('This will empty your cart. do you want to proceed?')) {
        getAllItems();
        const response = await fetch(`shoppingcart.php`,
        {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            },
            body: `delete=true&idsToRemove=${checkedItems}`
        });
        if(response.ok) {
            loadBody();
        }
    }
});

// button handler for checkout button.
// The customer is not allowed to proceed to checkout page when the cart is empty.
$('#checkout-button').click ((e) => {
    if ($('.cart-item').length < 1) {
        e.preventDefault();
        alert ('Your cart is empty.');
    }
});