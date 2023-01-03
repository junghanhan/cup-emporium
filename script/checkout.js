import { displayNavBar, showErrors } from './functions.js';

var pub_key = '';
var cSecret = '';

loadBody();

// make get request to php for body
async function loadBody() {

    displayNavBar();
    
    // open fetch call to get order information and some stripe data
    const response = await fetch('checkout.php');
    // if the response is ok (200)
    if(response.ok) {
        // parse for json in response and send to appropriate
        // sections by id in html with jquery
        const responseCode = await response.json();
        $('#insertSection').html(responseCode['orderInfo']);

        // if the cart is empty, the customer is redirected to the main page
        if ($('.cart-item').length < 1) {
            window.location.replace("product_listings.html");
        }

        $('#payment_button').html(`Pay $${responseCode['Total']} CAD`);
        $('#OrderTotal').html(`Total: $${responseCode['Total']} CAD`);
        $('form').append(responseCode.hiddenForm);
        // set pub and private key (should be handled on php and sent as a stripe object)
        pub_key = responseCode['pub_key'];

        // if location data exists in response
        if(responseCode.hasOwnProperty('country')) {
            $('[name=address]').val(responseCode.address);
            $('[name=city]').val(responseCode.city);
            $('[name=state]').val(responseCode.state);
            $('[name=postalCode]').val(responseCode.postalCode);
            $('[name=country]').val(responseCode.country);
        }

        loadStripe();
    }
}

// loading data from stripe (*rework)
function loadStripe() {
    
    // pub key can be sent and used to create stripe object
    const stripe = Stripe(
        pub_key, 
        {
            apiVersion: '2020-08-27',
        }
    );
    // creating stripe elements and define specific elements to be used
    const elements = stripe.elements();
    // card number and the assocciated html element and css definition
    const cardNumE = elements.create('cardNumber', {
        showIcon: true,
        style: {
            base: {
                backgroundColor: '#F0F8FE',
                color: '#2699FB',
                '::placeholder': {
                    color: '#96cfff'
                },
                ':focus': {
                    backgroundColor: '#F0F8FE',
                    color: '#2699FB'
                }
            },
            invalid: {
                iconColor: '#db393c',
                color: '#db393c'
            }
        }
    });
    // mount is the binding statement to link to an html
    cardNumE.mount('#cardNum');
    // same as card number above but for a card expiry
    const cardExpireE = elements.create('cardExpiry', {
        style: {
            base: {
                backgroundColor: '#F0F8FE',
                color: '#2699FB',
                '::placeholder': {
                    color: '#96cfff'
                },
                ':focus': {
                    backgroundColor: '#F0F8FE',
                    color: '#2699FB'
                }
            },
            invalid: {
                color: '#db393c'
            }
        }
    });
    // mount card expiry
    cardExpireE.mount('#cardExpire');
    // cvc element for card information
    const cardCvcE = elements.create('cardCvc', {
        style: {
            base: {
                backgroundColor: '#F0F8FE',
                color: '#2699FB',
                '::placeholder': {
                    color: '#96cfff'
                },
                ':focus': {
                    backgroundColor: '#F0F8FE',
                    color: '#2699FB'
                }
            },
            invalid: {
                color: '#db393c'
            }
        }
    });
    // mount it
    cardCvcE.mount('#cardCvc');
    $('#payment_button').click(async (e) => {
        e.preventDefault();
        $('#payment_button').prop('disabled', true);
        // get cSecret from purchase type in query
        const response = await fetch('checkout.php?type=purchase');
        if(response.ok) {
            const responseCode = await response.json();
            cSecret = responseCode.secret_key;
        }

        showErrors({status: 200, pay: null});
        if($('[name=fName]').val().length < 1) {
            showErrors({status: 500, fName: "Missing recipient name"});
        } else {  showErrors({status: 200, fName: null}); }
        if($('[name=address]').val().length < 1) {
            showErrors({status: 500, address: "Missing address"});
        } else {  showErrors({status: 200, address: null}); }
        if($('[name=city]').val().length < 1) {
            showErrors({status: 500, city: "Missing city"});
        } else {  showErrors({status: 200, city: null}); }
        if($('[name=postalCode]').val().length < 1) {
            showErrors({status: 500, postalCode: "Missing postal code"});
        } else {  showErrors({status: 200, postalCode: null}); }
        if($('[name=state]').val().length < 1) {
            showErrors({status: 500, state: "Missing state"});
        } else {  showErrors({status: 200, state: null}); }
        if($('[name=country]').val().length < 1) {
            showErrors({status: 500, country: "Missing country"});
        } else {  showErrors({status: 200, country: null}); }

        // if($('#cardNum').hasClass('.StripeElement--invalid')) {
        //     showErrors({status: 500, cardNum: "Invalid card number"});
        // } else {  showErrors({status: 200, cardNum: null}); }
        // if($('#cardExpire').hasClass('.StripeElement--invalid')) {
        //     showErrors({status: 500, cardExpire: "Invalid card expirey"});
        // } else {  showErrors({status: 200, cardExpire: null}); }
        // if($('#cardCvc').hasClass('.StripeElement--invalid')) {
        //     showErrors({status: 500, cardCvc: "Invalid card cvc"});
        // } else {  showErrors({status: 200, cardCvc: null}); }

        // if($('#cardNum').hasClass('.StripeElement--empty')) {
        //     showErrors({status: 500, cardNum: "Empty card number"});
        // } else {  showErrors({status: 200, cardNum: null}); }
        // if($('#cardExpire').hasClass('.StripeElement--empty')) {
        //     showErrors({status: 500, cardExpire: "Empty card expirey"});
        // } else {  showErrors({status: 200, cardExpire: null}); }
        // if($('#cardCvc').hasClass('.StripeElement--empty')) {
        //     showErrors({status: 500, cardCvc: "Empty card cvc"});
        // } else {  showErrors({status: 200, cardCvc: null}); }

        // value testing
        var numItems = $('.StripeElement--invalid').length
        numItems += $('.StripeElement--empty').length;
        if(numItems >= 1) {
            showErrors({status: 500, pay: "Missing card information"});
            $('#payment_button').prop('disabled', false);
            return false;
        }

        try {
            // async await for card payment to process and pass the card information with
            // the secret key (*look into shouldn't need to have the cSecret here)
            const {error, paymentIntent} = await stripe.confirmCardPayment(
                cSecret, {
                    payment_method: {
                        type: 'card',
                        card: cardNumE,
                        billing_details: {
                            name: $('[name=cName]').val(),
                        },
                    },
                },
            );
            // if an error occurs let the user submit again since something failed
            if(error || paymentIntent == undefined) {
                // Re-enable the form so the customer can resubmit.
                $('#payment_button').prop('disabled', false);
                return false;
            }
        } catch(error) {
            console.error(error);
        }

        console.log('WHAT');
        $('form').submit();
        return true;
    });
}
