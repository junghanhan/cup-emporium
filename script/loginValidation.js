import { displayNavBar, showErrors } from './functions.js';

displayNavBar();
displayForm();

async function displayForm() {
    const response = await fetch('login.php?type=form');
    try {
        if(response.ok) {
            const responseText = await response.text();
            $('#loginForm').html(responseText);
        }
        else {
            throw new Error('Error in displayForm()');
        }
    } catch (error) {
        console.log (error);
    }
    $("[name='username']").blur(async function(){
        usernameExists($(this).val());
    });
}

// quick fetch to ask for the username in the dbs
async function usernameExists(username) {
    const response = await fetch(`login.php?type=username&username=${username}`);
    const responseCode = await response.json();

    if(responseCode.status == 200) {
        showErrors({status: 200, username: null});
    } else {
        showErrors({status: 500, username: 'Unknown username!'});
    }

}

//  Comments before and during code writing....
//  Make async requests to php for validation
//  check username first with get for a response
//  with the results then post the login data to the
//  php.
async function validate() {
    var [username] = $('form').serializeArray();
    console.log($('form').serialize());
    // check for username in dbs
    if(usernameExists(username.value)) {
        // prepare post statement
        const response = await fetch('login.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            },
            body: $('form').serialize() // body data type must match "Content-Type" header
        });
        if(response.ok) {
            const responseCode = await response.json();
            if(responseCode.status == 200) {
                window.location.assign('product_listings.html');
            }
            else {
                showErrors(responseCode);
                return false;
            }
            
            return true;
        }
    }
    return false;
}

$(document).ready(function() {
    $('form').submit(function(e) {
        e.preventDefault();
        return validate();
    });
})