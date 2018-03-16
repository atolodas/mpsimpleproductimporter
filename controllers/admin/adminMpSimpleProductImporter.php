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
    
    public function ajaxProcessResetProgressBar()
    {
        $db = Db::getInstance();
        $db->insert(
            'mp_progress',
            array(
                'id_progress' => pSQL($this->module->name),
                'progress' => 0,
            ),
            true,
            false,
            Db::REPLACE
        );
        
        exit();
    }
    
    public function ajaxProcessProgressBar()
    {
        $db = Db::getInstance();
        $sql = "select progress from `"._DB_PREFIX_."mp_progress` where id_progress = '".pSQL($this->module->name)."'";
        $current_value = (int)$db->getValue($sql);
        print Tools::jsonEncode(
            array(
                'result' => true,
                'current_progress' => $current_value,
            )
        );
        
        exit();
    }
    
    public function ajaxProcessGetTranslation()
    {
        $translate = Tools::getValue('translate');
        $title = Tools::getValue('title');
        
        $translations = array(
            'Export selected documents?' => $this->l('Export selected documents?'),
            'Import selected products?' => $this->l('Import selected products?'),
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
    
    public function ajaxProcessImportSelected()
    {
        $ids = Tools::getValue('ids');
        $controller = $this->getCustomController('Importer');
        $result = $controller->run(array('action' => 'import', 'ids' => $ids));
        exit();
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
            $result = $controller->run(
                array(
                    'action' => 'Insert'
                )
            );
            if ($result) {
                $helperlist = $this->getCustomController('DisplayList');
                $helperlistContent = $helperlist->run(
                    array(
                        'action' => 'Display',
                    )
                );

                if ($helperlistContent) {
                    $this->helperListContent = $helperlistContent;
                } else {
                    $this->errors[] = $this->l('File has no content to parse.');
                }
            }
        } elseif (Tools::isSubmit('submitBulkexportorders')) {
            $this->messages = $this->processBulkExport();
            exit();
        }
        
        $this->helperFormContent = $this->initHelperForm();
        $this->content = implode('<br>', $this->messages) 
            . $this->addHtmlContent()
            . $this->addCssContent()
            . $this->addJsContent()
            . $this->helperFormContent 
            . $this->helperListContent ;
        
        parent::initContent();
    }
    
    private function addHtmlContent()
    {
        $smarty = Context::getContext()->smarty;
        return $smarty->fetch($this->module->getPath().'views/templates/admin/percircle.tpl');
    }
    
    private function addCssContent()
    {
        Context::getContext()->controller->addCSS($this->module->getPath().'views/css/percircle.css');
        return '';
    }
    
    private function addJsContent()
    {
        Context::getContext()->controller->addJS($this->module->getUrl().'views/js/adminController.js');
        Context::getContext()->controller->addJS($this->module->getPath().'views/js/percircle.js');
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
                        'name' => 'input_file_csv_import',
                        'label' => $this->l('CSV file'),
                        'desc' => $this->l('Select a CSV file containing products data'),
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
                    'input_file_csv_import' => '',
                    'input_file_image_folder' => '',
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
