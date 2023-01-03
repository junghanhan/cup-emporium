// display navigation bar in the div with ID 'nav'
async function displayNavBar () {
  // get HTML of navbar from the server
  let navBarHTML = await getNavBarHTML ();

  // append the order history HTML at the end of main section
  document.querySelector('#nav').innerHTML += navBarHTML;
}

// errorData should be a response JSON from the server
// with any errors having their key be the input id.
// showErrors() dynamicaly display errors using element id's
function showErrors(errorData) {
  //test for JSON
  try {
    if(typeof errorData == 'object') {
      // test status of errorData 500 is error 200 is safe
      if(errorData.status == 500) {
        // iterate through errorData for keys and use them to assaign classes to inputs
        Object.keys(errorData).forEach(function(key) {
          // don't display the status
          if(key != 'status' && key != 'acceptPrivacy') {
            // if the element with the id exists
            if($(`[name='${key}']`).length) {
              // toggle a class
              $(`[name='${key}']`).addClass('errorInput');
              $(`[name='${key}']`).removeClass('formInput');
              // check if subtext exists
              if($(`#${key}Error`).length) {
                // replace existing text
                $(`#${key}Error`).html(`${errorData[key]}`)
                $(`#${key}Error`).show();
              }else {
                // add subtext after
                $(`[name='${key}']`).after(`<sub id='${key}Error' class='error'>${errorData[key]}</sub>`);
              }
            } else {
              // if the id doesn't exist it goes to the console
              console.error(`${key}: ${errorData[key]}`);
            }
          } else if(key == 'acceptPrivacy' && key != 'status') {
            $(`[name='${key}']`).html(errorData[key]);
            $('#accept').click(function() {
              updatePrivacy(true);
            });
            $('#decline').click(function() {
              updatePrivacy(false);
            });
          }
        });
        return true;
      } else {
        // status was 200
        Object.keys(errorData).forEach(function(key) {
          // don't display the status
          if(key != 'status') {
            // if the element with the id exists
            if($(`[name='${key}']`).length) {
              // toggle a class
              $(`[name='${key}']`).removeClass('errorInput');
              $(`[name='${key}']`).addClass('formInput');
              // check if subtext exists
              if($(`#${key}Error`).length && errorData[key] == null) {
                // replace existing text
                $(`#${key}Error`).hide();
              } else if($(`#${key}Error`).length) {
                // add subtext after
                $(`#${key}Error`).html(`<sub id='${key}Error' class='pass'>${errorData[key]}</sub>`);
              }
            } else {
              // if the id doesn't exist it goes to the console
              console.error(`${key}: ${errorData[key]}`);
            }
          }
        });

        return false;
      }
    } else {
      throw new Error('showErrors only takes JSON');
    }
  } catch(error) {
    console.log(error);
    return false;
  }
}

// function used to send fetch type post requests to update the db privacy.
async function updatePrivacy(state) {
  if(state) {
    if($('#registerForm').length) {
      const response = await fetch('register.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
        },
        body: 'privacy=true'
      });
      if(response.ok) {
        const responseCode = await response.json();
        showErrors(responseCode);
      }

      $('#privacyModal').modal('hide');
      $('#registerForm').submit();
    } else if($('#loginForm').length) {
      const response = await fetch('login.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
        },
        body: 'privacy=true'
      });
      if(response.ok) {
        const responseCode = await response.json();
        showErrors(responseCode);
        console.log('privacy post worked!');
      }
      
      $('#privacyModal').modal('hide');
      $('#loginForm').submit();
    }
  } else {
    $('#privacyModal').modal('hide');
  }
}

// get nav bar HTML from server
// output: the HTML snippet of the navigation bar (string)
async function getNavBarHTML () {
  try {
    const url = "navbar.php";
    const response = await fetch (url);
    if (response.ok) {
      return response.text();
    }

    throw new Error('Error in getNavBarHTML');
  } catch (error) {
    console.log (error);
    return null;
  }
}

export { displayNavBar, showErrors };