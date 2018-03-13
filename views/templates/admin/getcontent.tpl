{*
* 2017 mpSOFT
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
*  @author    mpSOFT <info@mpsoft.it>
*  @copyright 2017 Ditial Solutions® Massimiliano Palermo
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of mpSOFT
*}

<style>
    .modalDialog {
	position: fixed;
	font-family: Arial, Helvetica, sans-serif;
	top: 0;
	right: 0;
	bottom: 0;
	left: 0;
	background: rgba(200,200,200,0.8);
	z-index: 9999;
	opacity:1;
        display:none;
    }
    
    .modalDialog > div {
        width: 400px;
        position: relative;
        margin: 10% auto;
        padding: 0;
        border-radius: 5px;
        background: #fff;
        background: -moz-linear-gradient(#ffffff, #fcfcfc);
        background: -webkit-linear-gradient(#ffffff, #fcfcfc);
        background: -o-linear-gradient(#ffffff, #fcfcfc);
        opacity: 1;
    }
    
    .modalDialog h2 {
        width: 100%;
        padding: 10px;
        background-color: #3399cc;
        color: #fefefe;
        font-weight: bold;
        text-align: left;
        border: 1px solid #3399cc;
        margin-bottom: 10px;
        margin-top: -5px;
        border-top-left-radius: 5px;
        border-top-right-radius: 5px;
    }
    
    .modalDialog p {
        width: 100%;
        padding: 10px;
        font-size: 1.2em;
    }
    
    .modalDialog_close {
            color: #222 !important;
            font: 14px/100% arial, sans-serif;
            position: absolute;
            right: 5px;
            text-decoration: none;
            text-shadow: 0 1px 0 #fff;
            top: 5px;
    }
    
    .modalDialog_close:hover {
        cursor: pointer;
        color: #fff !important;
        transition-duration: 0.5s;
    }
    
    .close-thick:after {
        content: '✖'; /* UTF-8 symbol */
      }
    
</style>

<div id="openModal" class="modalDialog">
    <div>
        <a id='popup-close' href="#" onclick='javascript:$("#openModal").fadeOut();' title="Close" class="modalDialog_close close-thick"></a>
        <h2>{l s='Message notification' mod='mpexportinvoices'}</h2>
        <p id='popup-msg'>
        
        </p>
    </div>
</div>

<form class='defaultForm form-horizontal' method='post' id="form_export_invoices">
    <div class="panel">
        <div class="panel-heading">
            <span>
                <i class="icon-cogs"></i>
                {l s='Export Configuration' mod='mpexportinvoices'}
            </span>
        </div>
        <!-- INPUT SECTION -->
        <div>
            <label class="control-label col-lg-3 ">{l s='Id customer prefix' mod='mpexportinvoices'}</label>
            <div class="input-group input fixed-width-lg">
                <input type="text" id="input_id_customer_prefix" name="input_id_customer_prefix" class="input fixed-width-xl" value="{$id_customer_prefix}">
                <span class="input-group-addon"><i class='icon-user'></i></span>
            </div>
            <br>
            <label class="control-label col-lg-3 ">{l s='Id document' mod='mpexportinvoices'}</label>
            <div class="input-group input fixed-width-lg">
                <input type="text" id="input_id_document" name="input_id_document" class="input fixed-width-xl" value="{$id_document}">
                <span class="input-group-addon"><i class='icon-copy'></i></span>
            </div>
            <br>
            <button type="button" value="1" id="submit_id_customer_prefix" name="submit_id_customer_prefix" class="btn btn-default">
                <i class="icon-2x icon-save"></i> 
                {l s='Save prefix' mod='mpexportinvoices'}
            </button>
            <br>
            <hr>
            <br>
            <label class="control-label col-lg-3 ">{l s='Payment module list' mod='mpexportinvoices'}</label>
            <div class="input-group input fixed-width-lg">
                <select id="select_payment_module_list" name="select_payment_module_list" class="input fixed-width-xxl">
                    {foreach $modules as $module}
                        <option value="{$module['id']}">{$module['name']}</option>
                    {/foreach}
                </select>
            </div>
            <br>
            <label class="control-label col-lg-3 ">{l s='Payment module name' mod='mpexportinvoices'}</label>
            <div class="input-group input fixed-width-lg">
                <input type="text" id="input_payment_module_name" name="input_payment_module_name" class="input fixed-width-xl">
                <span class="input-group-addon"><i class='icon-edit-sign'></i></span>
            </div>
            <br>
            <label class="control-label col-lg-3 ">{l s='Document Type' mod='mpexportinvoices'}</label>
            <div class="input-group input fixed-width-lg">
                <input type="text" id="input_document_type" name="input_document_type" class="input fixed-width-xl">
                <span class="input-group-addon"><i class='icon-edit-sign'></i></span>
            </div>
            <br>
            <button type="button" value="1" id="submit_module_name" name="submit_module_name" class="btn btn-default">
                <i class="icon-2x icon-save"></i> 
                {l s='Save module name' mod='mpexportinvoices'}
            </button>
            <br>
            <hr>
            <br>
        </div>
    </div>
</form>

<script type="text/javascript">
    $(window).bind("load",function()
    {
        $('#submit_id_customer_prefix').on('click', function(event){
            event.preventDefault();
            $.ajax({
                url: '{$page_link}',
                type: 'POST',
                data:
                {
                    ajax : true,
                    action: 'saveCustomerPrefix',
                    token : '{$token}',
                    configure: 'mpexportinvoices',
                    id_customer_prefix : $('#input_id_customer_prefix').val(),
                    id_document: $('#input_id_document').val(),
                },
                success: function(msg)
                {
                    $('#popup-msg').html(msg);
                    $('#openModal').fadeIn();
                }                    
            });
        });
        
        $('#submit_module_name').on('click', function(event){
            event.preventDefault();
            $.ajax({
                url: '{$page_link}',
                type: 'POST',
                data:
                {
                    ajax : true,
                    action: 'saveModuleName',
                    token : '{$token}',
                    configure: 'mpexportinvoices',
                    id_module : $('#select_payment_module_list').val(),
                    module_name : $('#input_payment_module_name').val()
                },
                success: function(msg)
                {
                    $('#popup-msg').html(msg);
                    $('#openModal').fadeIn();
                }                    
            });
        });
        
        $('#select_payment_module_list').on('change', function(event){
            event.preventDefault();
            $.ajax({
                url: '{$page_link}',
                type: 'POST',
                data:
                {
                    ajax : true,
                    action: 'refreshPaymentModuleList',
                    token : '{$token}',
                    configure: 'mpexportinvoices',
                    id_payment_module : $('#select_payment_module_list').val()
                },
                success: function(msg)
                {
                    $('#input_payment_module_name').val(msg);
                }                    
            });
        });
        
    });
</script>
