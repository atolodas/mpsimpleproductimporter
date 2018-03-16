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

class MpSimpleProductImporterDisplayListController
{
    protected $controller;
    protected $context;
    protected $table_import = 'mp_import_products';
    protected $token;
    protected $list;
    protected $fields;

    public function __construct($controller) {
        $this->controller = $controller;
        $this->context = Context::getContext();
        $this->token = Tools::getAdminTokenLite($this->controller->name);
    }
    
    /**
     * 
     * @param array $params Array of parameters to parse
     * $params['file'] = file attachment object
     * $params['action'] = action to execute
     */
    public function run($params)
    {
        if (isset($params['action']) && !empty($params['action'])) {
            $action = 'processAction'.$params['action'];
            return $this->$action($params);
        } else {
            $this->controller->errors[] = $this->l('No action defined.');
        }
    }
    
    public function processActionDisplay($params)
    {
        $this->list = $this->prepareList($params);
        $this->fields = $this->initFieldsList();
        
        return $this->initHelperList();
    }
    
    public function prepareList($params)
    {
        $list = $this->readFromDb($params);
        $output = array();
        foreach ($list as $row) {
            $price = isset($row['price'])?$row['price']:0;
            $output[] = array(
                'id' => $row['id_row'],
                'image_url' => $this->getImage($row['id']),
                'reference' => $row['reference'],
                'name' => $row['name'],
                'price' => $price,
            );
        }
        return $output;
    }
    
    public function getImage($thumb)
    {
        $path = $this->controller->module->getUrl().'../../upload/img-prod/'.$thumb.'.jpg';
        return $this->img($path);
    }
    
    public function img($url) 
    {
        return "<img src='" . $url . "' style='max-width: 48px; max-height: 48px; object-fit: contain;'>";
    }
    
    public function readFromDb($params)
    {
        if (!isset($params['token'])) {
            $token = Tools::getAdminTokenLite($this->controller->name);
        } else {
            $token = $params['token'];
        }
        $db = Db::getInstance();
        $sql = new DbQueryCore();
        $sql->select('*')
            ->from($this->table_import)
            ->where('token=\''.$token.'\'');
        $result = $db->executeS($sql);
        $output = array();
        foreach($result as $row) {
            $content = unserialize($row['content']);
            $content['id_row'] = (int)$row['id'];
            $output[] = $content;
        }
        return $output;
    }
    
    private function initFieldsList()
    {
        $list = array(
            'id' => array(
                'title' => $this->l('Id'),
                'align' => 'text-right',
                'width' => 64,
                'type' => 'text',
                'search' => false,
            ),
            'image_url' => array(
                'title' => $this->l('Image'),
                'text-align' => 'text-center',
                'type' => 'bool',
                'width' => 64,
                'float' => true,
                'search' => false,
            ),
            'reference' => array(
                'title' => $this->l('Reference'),
                'align' => 'text-left',
                'width' => 'auto',
                'type' => 'text',
                'search' => false,
            ),
            'name' => array(
                'title' => $this->l('Name'),
                'align' => 'text-left',
                'type' => 'text',
                'width' => 'auto',
                'search' => false,
            ),
            'price' => array(
                'title' => $this->l('Price'),
                'align' => 'text-right',
                'width' => 'auto',
                'type' => 'price',
                'search' => false,
            ),
        );
        
        return $list;
    }
    
    public function initHelperList()
    {
        $helper = new HelperListCore();
        $helper->shopLinkType = '';
        $helper->simple_header = false;
        // Actions to be displayed in the "Actions" column
        $helper->actions = array(); //array('edit', 'delete', 'view');
        $helper->identifier = 'id';
        $helper->show_toolbar = true;
        $helper->toolbar_btn = array(
            'import' => array(
                'href' => 'javascript:mpimport_importSelectedProducts();',
                'desc' => $this->l('Import selected'),
            ),
            'toggle-on' => array(
                'href' => 'javascript:mpimport_checkAll();',
                'desc' => $this->l('Select All'),
            ),
            'toggle-off' => array(
                'href' => 'javascript:mpimport_uncheckAll();',
                'desc' => $this->l('Select None'),
            )
        );
        $helper->title = $this->l('Products List');
        $helper->table = 'import';
        $helper->bulk_actions = array(
            'import' => array(
                'text' => $this->l('Import selected'),
                'confirm' => $this->l('Continue with this operation?'),
                'icon' => 'icon fa-list'
            )
        );
        $helper->no_link=true; // Row is not clickable
        $helper->token = Tools::getAdminTokenLite($this->controller->name);
        $helper->currentIndex = ContextCore::getContext()->link->getAdminLink($this->controller->name, false);
        $helper->listTotal = count($this->list);
        return $helper->generateList($this->list, $this->fields);
    }
    
    /**
     * Non-static method which uses AdminController::translate()
     *
     * @param string  $string Term or expression in english
     * @param string|null $class Name of the class
     * @param bool $addslashes If set to true, the return value will pass through addslashes(). Otherwise, stripslashes().
     * @param bool $htmlentities If set to true(default), the return value will pass through htmlentities($string, ENT_QUOTES, 'utf-8')
     * @return string The translation if available, or the english default text.
     */
    protected function l($string, $class = null, $addslashes = false, $htmlentities = true)
    {
        if ($class === null || $class == 'AdminTab') {
            $class = substr(get_class($this), 0, -10);
        } elseif (strtolower(substr($class, -10)) == 'controller') {
            /* classname has changed, from AdminXXX to AdminXXXController, so we remove 10 characters and we keep same keys */
            $class = substr($class, 0, -10);
        }
        return Translate::getAdminTranslation($string, $class, $addslashes, $htmlentities);
    }
    
    
}