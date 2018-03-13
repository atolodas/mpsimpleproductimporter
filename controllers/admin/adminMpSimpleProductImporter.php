<?php
/**
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
 *  @author    mpSOFT by Massimiliano Palermo<info@mpsoft.it>
 *  @copyright 2017 mpSOFT by Massimiliano Palermo
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of mpSOFT
 */

class AdminMpSimpleProductImporterController extends ModuleAdminController
{
    public $id_customer_prefix;
    private $date_start;
    private $date_end;
    private $helperListContent;
    private $helperFormContent;
    public $link;
    public $id_lang;
    public $id_shop;
    
    public function __construct()
    {
        $this->bootstrap = true;
        $this->context = Context::getContext();
        $this->smarty = Context::getContext()->smarty;
        $this->debug = false;
        $this->name = 'AdminMpSimpleProductImporter';
        
        parent::__construct();
        
        $this->link = Context::getContext()->link;
        $this->id_lang = (int) Context::getContext()->language->id;
        $this->id_shop = (int) Context::getContext()->shop->id;
    }
    
    public function initToolbar()
    {
        parent::initToolbar();
    }
    
    public function ajaxProcessGetTranslation()
    {
        $translate = Tools::getValue('translate');
        $title = Tools::getValue('title');
        
        $translations = array(
            'Export selected documents?' => $this->l('Export selected documents?'),
        );
        
        $titles = array(
            'Confirm' => $this->l('Confirm'),
        );
        
        foreach ($translations as $key=>$value) {
            if ($key == $translate) {
                $translate = $value;
                break;
            }
        }
        
        foreach ($titles as $key=>$value) {
            if ($key == $title) {
                $title = $value;
                break;
            }
        }
        
        return Tools::jsonEncode(
            array(
                'result' => true,
                'translation' => $translate,
                'title' => $title,
            )
        );
    }
    
    public function initContent()
    {
        if (Tools::isSubmit('ajax') && !empty(Tools::getValue('action'))) {
            $action = 'ajaxProcess' . Tools::getValue('action');
            print $this->$action();
            exit();
        }
        
        $this->helperListContent = '';
        $this->helperFormContent = '';
        $this->messages = array();
        
        if (Tools::isSubmit('submitForm')) {
            $controller = $this->getCustomController('CsvParser');
            $content = $controller->run(array('action' => 'GetFile'));
            $helperlist = $this->getCustomController('DisplayList');
            $helperlistContent = $helperlist->run(array('action' => 'Display'));
            
            if ($helperlistContent) {
                $this->helperListContent = "<pre>" . print_r($helperlistContent,1) . "</pre>"; //$this->initHelperList($content);
            } else {
                $this->errors[] = $this->l('File has no content to parse.');
            }
        } elseif (Tools::isSubmit('submitBulkexportorders')) {
            $this->messages = $this->processBulkExport();
            exit();
        }
        $this->helperFormContent = $this->initHelperForm();
        $this->content = implode('<br>', $this->messages) 
            . $this->helperFormContent 
            . $this->helperListContent 
            . $this->scriptContent();
        
        parent::initContent();
    }
    
    private function scriptContent()
    {
        Context::getContext()->controller->addJS($this->module->getUrl().'views/js/adminController.js');
        return '';
    }
    
    private function getHelperListContent()
    {
        $attachment = Tools::fileAttachment('input_file_excel_import');
        if ($attachment['mime']!='application/vnd.ms-excel') {
            $this->errors[] = $this->l('Please, select an 97-2003 excel file.');
            return false;
        }
        return "<pre>" . print_r($attachment,1) . "</pre>";
        return $result;
    }
    
    private function addDateToSql(DbQueryCore $sql, $date_field)
    {
        if (Tools::strpos($date_field, '.')) {
            $split = explode('.',$date_field);
            $date_field = $split[0].'.`'.$split[1].'`';
        }
        
        if (!empty($this->date_start) && !empty($this->date_end)) {
            $sql->where("$date_field between '$this->date_start' and '$this->date_end'");
        } elseif (!empty($this->date_start) && empty($this->date_end)) {
            $sql->where("$date_field >= '$this->date_start'");
        } elseif (empty($this->date_start) && !empty($this->date_end)) {
            $sql->where("$date_field <= '$this->date_end'");
        }
        return $sql;
    }
    
    public function getDiscount($price_full, $price_reducted)
    {
        return sprintf('%.4f', (($price_full-$price_reducted)/$price_full) * 100);
    }
    
    public function tableExists($tablename)
    {
        try {
            Db::getInstance()->getValue("select 1 from `$tablename`");
            return true;
        } catch (Exception $exc) {
            PrestaShopLoggerCore::addLog('Table ' . $tablename . ' not exists.' . $exc->getMessage());
            return false;
        }
    }
    
    private function object_to_array($data)
    {
        if (is_array($data) || is_object($data))
        {
            $result = array();
            foreach ($data as $key => $value)
            {
                $result[$key] = $this->object_to_array($value);
            }
            return $result;
        }
        return $data;
    }
    
    private function initFieldsList()
    {
        $list = array(
            'document_id' => array(
                'title' => $this->l('Id'),
                'text-align' => 'text-right',
                'type' => 'text',
                'width' => 'auto',
                'search' => false,
            ),
            'document_date' => array(
                'title' => $this->l('Date'),
                'align' => 'text-center',
                'width' => 'auto',
                'type' => 'date',
                'search' => false,
            ),
            'document_number' => array(
                'title' => $this->l('Number'),
                'align' => 'text-right',
                'type' => 'text',
                'width' => 'auto',
                'search' => false,
            ),
            'customer_name' => array(
                'title' => $this->l('Customer'),
                'align' => 'text-left',
                'width' => 'auto',
                'type' => 'text',
                'search' => false,
            ),
            'document_total' => array(
                'title' => $this->l('Total'),
                'align' => 'text-right',
                'width' => 'auto',
                'type' => 'price',
                'search' => false,
            ),
        );
        
        return $list;
    }
    
    public function initHelperList($rows)
    {
        $fields_list = $this->initFieldsList();
        $helper = new HelperListCore();
        $helper->shopLinkType = '';
        $helper->simple_header = false;
        // Actions to be displayed in the "Actions" column
        $helper->actions = array(); //array('edit', 'delete', 'view');
        $helper->identifier = 'document_id';
        $helper->show_toolbar = true;
        $helper->toolbar_btn = array(
            'export' => array(
                'href' => 'javascript:exportSelectedDocuments();',
                'desc' => $this->l('Export selected'),
            )
        );
        $helper->title = $this->l('Documents List');
        $helper->table = 'expdoc';
        $helper->bulk_actions = array(
            'export' => array(
                'text' => $this->l('Export selected'),
                'confirm' => $this->l('Continue with this operation?'),
                'icon' => 'icon fa-list'
            )
        );
        $helper->no_link=true; // Row is not clickable
        $helper->token = Tools::getAdminTokenLite($this->name);
        $helper->currentIndex = ContextCore::getContext()->link->getAdminLink($this->name, false);
        $helper->listTotal = count($rows);
        return $helper->generateList($rows, $fields_list);
    }
    
    protected function initHelperForm()
    {
        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Export configuration'),
                    'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'required' => true,
                        'type' => 'file',
                        'name' => 'input_file_excel_import',
                        'label' => $this->l('Excel file'),
                        'desc' => $this->l('Select an excel sheet containing products data'),
                        'prefix' => '<i class="icon-chevron-right"></i>',
                        'suffix' => '<i class="icon-excel"></i>',
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Get'),
                    'icon' => 'process-icon-next'
                ),
            ),
        );
        
        $helper = new HelperFormCore();
        $helper->table = '';
        $helper->default_form_language = (int)$this->id_lang;
        $helper->allow_employee_form_lang = (int) Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANGUAGE');
        $helper->submit_action = 'submitForm';
        $helper->currentIndex = $this->link->getAdminLink($this->name); 
        $helper->token = Tools::getAdminTokenLite($this->name);
        if (Tools::isSubmit('submitForm')) {
            $submit_values = Tools::getAllValues();
            $output = array();
            foreach($submit_values as $key=>$value) {
                if(is_array($value)) {
                    $output[$key.'[]'] = $value;
                } else {
                    $output[$key] = $value;
                }
            }
            $helper->tpl_vars = array(
                'fields_value' => $output,
                'languages' => $this->context->controller->getLanguages(),
            );
        } else {
            $helper->tpl_vars = array(
                'fields_value' => array(
                    'input_text_date_start' => '',
                    'input_text_date_end' => '',
                    'input_select_type_document' => 0,
                ),
                'languages' => $this->context->controller->getLanguages(),
            );
        }
        return $helper->generateForm(array($fields_form));
    }
    
    public function getCustomController($name, $folder='admin')
    {
        //Include filename
        require_once $this->module->getPath().'controllers/'.$folder.'/'.$name.'.php';
        //Build controller name
        $controller_name = get_class($this->module).$name.'Controller';
        //Instantiate controller
        $controller = new $controller_name($this);
        //Return controller
        return $controller;
    }
}
