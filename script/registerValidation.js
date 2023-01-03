import { displayNavBar, showErrors } from './functions.js';

load();

async function load() {
    const formResponse = await fetch('register.php?type=base');
    const formResponseText = await formResponse.text();
    $('#registerForm').html(formResponseText);

    displayNavBar();
    $("[name='username']").blur(async function(){
        const response = await fetch(`register.php?type=username&username=${$(this).val()}`);
        if(response.ok) {
            const responseCode = await response.json();
            showErrors(responseCode);
        }
    });
    $("[name='password']").blur(async function(){
        const response = await fetch(`register.php?type=password&password=${$(this).val()}`);
        if(response.ok) {
            const responseCode = await response.text();
            if(responseCode == 0) {
                showErrors({status: 500, password: 'Invalid password'});
            } else {
                showErrors({status: 200, password: null});
            }
        }
    });
    $("[name='confirmPassword']").blur(async function(){
        const response = await fetch(`register.php?type=password&password=${$(this).val()}`);
        if(response.ok) {
            const responseCode = await response.text();
            if(responseCode == 0) {
                showErrors({status: 500, confirmPassword: 'Password should match!'});
            } else {
                showErrors({status: 200, confirmPassword: null});
            }
        }
    });

    $("[name='privacy']").click(async function(e) {
        e.preventDefault();
        const response = await fetch('register.php?type=policy');
        if(response.ok) {
            const responseCode = await response.text();
            $("#acceptPrivacy").html(responseCode);
            $('#accept').click(function() {
                $("[name='policyAgreement']").val('accepted');
                $("[name='privacy']").addClass('btn-success');
                $("[name='privacy']").removeClass('btn-primary');
                $("#privacyModal").modal('hide');
            });
            $('#decline').click(function() {
                $("#privacyModal").modal('hide');
            });
        }
    });
}

$('form').ready(function() {

    $('form').submit(async function(e) {
        e.preventDefault();
        const response = await fetch('register.php', {
            method: 'POST',
            headers: {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            },
            body: $('form').serialize() // body data type must match "Content-Type" header
        });
        if(response.ok) {
            const responseCode = await response.json();
            if(responseCode.status == 500) {
                showErrors(responseCode);
                return false;
            } else {
                window.location.replace('product_listings.html');
                return true;
            }
        }else {
            return false;
        }
        
    });
});