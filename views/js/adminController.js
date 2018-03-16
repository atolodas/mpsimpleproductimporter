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
var input_switch_import_images = 1;
$(document).ready(function(){
    $('.btn.btn-default.dropdown-toggle').hide();
    $('#progress_circle').percircle(
        {
            percent: 0,
            pogressBarColor: "#8050bb"
        }
    ).closest('div').hide();
    $('input[name=input_switch_import_images]').on('change', function(){
        input_switch_import_images = this.value;
    });
    input_switch_import_images = $('input[name=input_switch_import_images]').val();
    $('input[name=input_switch_import_images]').change();
});

function mpimport_checkAll()
{
    $('input[name="importBox[]"]').each(function(){
        this.checked=true;
    });
}

function mpimport_uncheckAll()
{
    $('input[name="importBox[]"]').each(function(){
        this.checked=false;
    });
}

function mpimport_importSelectedProducts()
{
    var title = 'Confirm';
    var translate = 'Import selected products?';
    var translation = '';
    $.ajax({
        type: 'POST',
        dataType: 'json',
        async: false,
        useDefaultXhrHeader: false,
        data: 
        {
            ajax: true,
            action: 'GetTranslation',
            translate: translate,
            title: title
        }
    })
    .done(function(json){
        if (json.result === true) {
            console.log(json.translation + '\n' + json.title);
            translation = json.translation;
            title = json.title;
        } else {
            translation = translate; 
        }
    })
    .fail(function(){
        translation = translate;
    });
    
    jConfirm(translation, title, function(r){
        if (r===true) {
            $('#progress_circle').percircle(
                {
                    percent: 0
                }
            ).closest('div').show();
            var ids = [];
            $('input[name="importBox[]"]:checked').each(function(){
                ids.push(Number($(this).closest('tr').find('td:nth-child(2)').text()));
            });
            
            $.ajax({
                type: 'POST',
                dataType: 'json',
                useDefaultXhrHeader: false,
                data: 
                {
                    ajax: true,
                    action: 'importSelected',
                    ids: ids,
                    import_images: input_switch_import_images
                }
            })
            .done(function(json){
                if (json.result === true) {
                    $('#progress_circle').percircle(
                    {
                        percent: 100
                    }
                ).closest('div').delay(3000).hide();
                }
                if (json.result === true && json.errors.length>0) {
                    jAlert(
                        '<div style="overflow: auto; height: 300px;">' +
                        json.message + 
                        "<br>" + 
                        json.errors.join('<br>') +
                        "</div>",
                        json.title
                    );
                } else if (json.result === true && json.errors.length === 0) { 
                    jAlert(json.message,json.title);
                } else {
                    jAlert(json.msg_error,json.title);
                }
            })
            .fail(function(){
                jAlert('AJAX ERROR');
            });
            
            $.ajax({
                type: 'POST',
                dataType: 'json',
                useDefaultXhrHeader: false,
                data: 
                {
                    ajax: true,
                    action: 'ResetProgressBar',
                }
            }).done(function(){
                progressBar(0);
            });
        }
    });
    
    function progressBar(value = 0)
    {
        $('#progress_circle').percircle(
        {
            percent: value
        });
        
        $.ajax({
            type: 'POST',
            dataType: 'json',
            useDefaultXhrHeader: false,
            data: 
            {
                ajax: true,
                action: 'ProgressBar',
                value: value
            }
        }).done(function(json) {
            if (json.result===true) {
                value = json.current_progress;
                if (value === 100) {
                    return;
                }
                progressBar(value);
            }
        });
    }
}