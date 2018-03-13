/**
* 2007-2015 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    mpSOFT <imfo@mpsoft.it>
*  @copyright 2017 mpSOFT Massimiliano Palermo
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*
* Don't forget to prefix your containers with your own identifier
* to avoid any conflicts with others containers.
*/

$(document).ready(function(){
    $('#input_select_payment_modules').on('change', function(event){
        event.preventDefault();
        let selected_payment = $(this).find('option:selected').text();
        $.ajax({
            type: 'POST',
            dataType: 'json',
            useDefaultXhrHeader: false,
            data: 
            {
                ajax: true,
                action: 'GetPaymentDisplayName',
                payment_module: selected_payment
            }
        })
        .done(function(json){
            if (json.result === true) {
                $('#input_text_payment_display').val(json.return_value);
            } else {
                jAlert(json.error_msg, json.title);
            }
        })
        .fail(function(){
            jAlert('AJAX ERROR');
        });
    });
    
    $('#input_btn_save_payment_name').on('click', function(event){
        event.preventDefault();
        let payment_module = $('#input_select_payment_modules');
        console.log(payment_module);
        let selected_payment = $(payment_module).find('option:selected').text();
        console.log('selected payment: ' + selected_payment);
        let display_payment_name = $('#input_text_payment_display').val();
        console.log('display name: ' + display_payment_name);
        $.ajax({
            type: 'POST',
            dataType: 'json',
            useDefaultXhrHeader: false,
            data: 
            {
                ajax: true,
                action: 'UpdatePaymentDisplayName',
                payment_module: selected_payment,
                display_name: display_payment_name
            }
        })
        .done(function(json){
            if (json.result === true) {
                jAlert(json.display_msg, json.title);
            } else {
                jAlert(json.error_msg, json.title);
            }
        })
        .fail(function(){
            jAlert('AJAX ERROR');
        });
    });
});