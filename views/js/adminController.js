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

function exportSelectedDocuments()
{
    var title = 'Confirm';
    var translate = 'Export selected documents?';
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
        var boxes = getSelectedBoxes();
        if (r===true) {
            $.ajax({
                type: 'POST',
                dataType: 'json',
                useDefaultXhrHeader: false,
                data: 
                {
                    ajax: true,
                    action: 'exportSelected',
                    boxes: boxes
                }
            })
            .done(function(json){
                if (json.result === true) {
                    alert('EXPORTED!!');
                } else {
                    alert('FAIL!');
                }
            })
            .fail(function(){
                jAlert('AJAX ERROR');
            });
        }
    });
    
    function getSelectedBoxes()
    {
        return [];
    }
}