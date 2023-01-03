import {displayNavBar, showErrors} from './functions.js';

const MAX_FILE_SIZE = 40000000; // 40 Megabite file size maximum

// Price and quantity have min values predefined

load();

async function load() {
    displayNavBar();

    const response = await fetch('addProduct.php?type=base');
    const responseCode = await response.text();
    $('#form').html(responseCode);

    $('[name="title"]').focusout(function() {
        testProductName();
    });

    $('#viewCode').change(function() {
        FileValidation();
    })
}

async function testProductName() {
    var testName = $('[name="title"]').val();
    const response = await fetch(`addProduct.php?type=prodName&productName=${testName}`);
    const responseCode = await response.text();
    if(responseCode == 1) {
        showErrors({status: 500, title: 'Product name already exists'});
        return false;
    } else if(testName.length <= 2) {
        showErrors({status: 500, title: 'Product name too short (minimum 3 letters)'});
        return false;
    } else if(responseCode == 0) {
        showErrors({status: 200, title: null});
        return true;
    }
}

// onChange function for the file upload to validate files
function FileValidation() {
    var errorList = {};
    var f = document.getElementById('viewCode');
    if(f.files.length > 1) {
        showErrors({status: 500, viewCode:'Only select one file'});
        return false;
    } else if(f.files.length > 0) {
        if(f.files[0].size > MAX_FILE_SIZE) {
            showErrors({status: 500, viewCode:'File size too large use (< 40MB)'});
            return false;
        } else {
            $('#uploadButton').html('Image: ' + f.files[0].name);
            showErrors({status: 200, viewCode: null});
            return true;
        }
    } else if(f.files.length == 0) {
        showErrors({status: 500, viewCode: 'Select a file'});
        return false;
    } else {
        showErrors({status: 200, viewCode: null});
        return true;
    }
}

$(document).ready(function() {

    $('form').submit(async function(e) {
        e.preventDefault();

        const prodTest = await testProductName();
        if(prodTest && FileValidation()) {
            // prepare validation fetch method POST
            var data = new FormData(document.getElementById('form'));
            const response = await fetch('addProduct.php', {
                method: 'POST',
                body: data
            });
            if(response.ok) {
                const responseCode = await response.json();
                if(responseCode.status == 500) {
                    showErrors(responseCode);
                    return false;
                } else if(responseCode.status == 200) {
                    showErrors({status: 200, submit: ''});
                    window.location = 'addProduct.html';
                    return true;
                }
            }else {
                return false;
            }
        }
        showErrors({status: 500, submit: ''});
        return false;
    });
});