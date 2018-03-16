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
 *  @author    Massimiliano Palermo <info@mpsoft.it>
 *  @copyright 2018 Digital Solutions®
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of mpSOFT
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class MpSimpleProductImporter extends Module
{
    protected $id_lang;
    protected $id_shop;
    protected $adminClassName = 'AdminMpSimpleProductImporter';
    protected $link;
    
    public function __construct()
    {
        $this->name = 'mpsimpleproductimporter';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Digital Solutions®';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('MP Simple product importer');
        $this->description = $this->l('Import Products from Excel sheet');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
        $this->id_lang = (int)Context::getContext()->language->id;
        $this->link = Context::getContext()->link;
        $this->id_shop = (int)Context::getContext()->shop->id;
    }
    
    public function install()
    {
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        if (!parent::install() 
                || !$this->registerHook('displayBackOfficeHeader')
                || !$this->registerHook('displayHeader')
                || !$this->installTab('MpModules', $this->adminClassName, $this->l('MP Simple product importer'))
        ) {
            return false;
        }
        
        include $this->_path().'sql/install.php';
        
        return true;
    }
      
    public function uninstall()
    {
        if (!parent::uninstall() || !$this->uninstallTab($this->adminClassName)) {
            return false;
        }
        return true;
    }
    
    /**
     * Install Main Menu
     * @return int Main menu id
     */
    public function installMainMenu()
    {
        $id_mp_menu = (int) TabCore::getIdFromClassName('MpModules');
        if ($id_mp_menu == 0) {
            $tab = new TabCore();
            $tab->active = 1;
            $tab->class_name = 'MpModules';
            $tab->id_parent = 0;
            $tab->module = null;
            $tab->name = array();
            foreach (Language::getLanguages(true) as $lang) {
                $tab->name[$lang['id_lang']] = $this->l('MP Modules');
            }
            $id_mp_menu = $tab->add();
            if ($id_mp_menu) {
                PrestaShopLoggerCore::addLog('id main menu: '.(int)$id_mp_menu);
                return (int)$tab->id;
            } else {
                PrestaShopLoggerCore::addLog('id main menu error');
                return false;
            }
        }
    }
    
    /**
     * Get id of main menu
     * @return int Main menu id
     */
    public function getMainMenuId()
    {
        $id_menu = (int)Tab::getIdFromClassName('MpModules');
        return $id_menu;
    }
    
    /**
     * 
     * @param string $parent Parent tab name
     * @param type $class_name Class name of the module
     * @param type $name Display name of the module
     * @param type $active If true, Tab menu will be shown
     * @return boolean True if successfull, False otherwise
     */
    public function installTab($parent, $class_name, $name, $active = 1)
    {
        // Create new admin tab
        $tab = new Tab();
        $id_parent = (int)Tab::getIdFromClassName($parent);
        PrestaShopLoggerCore::addLog('Install main menu: id=' . (int)$id_parent);
        if (!$id_parent) {
            $id_parent = $this->installMainMenu();
            if (!$id_parent) {
                $this->_errors[] = $this->l('Unable to install main module menu tab.');
                return false;
            }
            PrestaShopLoggerCore::addLog('Created main menu: id=' . (int)$id_parent);
        }
        $tab->id_parent = (int)$id_parent;
        $tab->name      = array();
        
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = $name;
        }
        
        $tab->class_name = $class_name;
        $tab->module     = $this->name;
        $tab->active     = $active;
        
        if (!$tab->add()) {
            $this->_errors[] = $this->l('Error during Tab install.');
            return false;
        }
        return true;
    }
    
    /**
     * 
     * @param string pe $class_name Class name of the module
     * @return boolean True if successfull, False otherwise
     */
    public function uninstallTab($class_name)
    {
        $id_tab = (int)Tab::getIdFromClassName($class_name);
        if ($id_tab) {
            $tab = new Tab((int)$id_tab);
            $result = $tab->delete();
            if (!$result) {
                $this->_errors[] = $this->l('Unable to remove module menu tab.');
            }
            return $result;
        }
    }
    
    public function hookDisplayBackOfficeHeader()
	{
		$ctrl = $this->context->controller;
		if ($ctrl instanceof AdminController && method_exists($ctrl, 'addCss')) {
            $ctrl->addCss($this->_path . 'views/css/icon-menu.css');
        }
        if ($ctrl instanceof AdminController && method_exists($ctrl, 'addJS')) {
            $ctrl->addJs($this->_path . 'views/js/getContent.js');
        }
	}
    
    public function getUrl()
    {
        return $this->_path;
    }
    
    public function getPath()
    {
        return $this->local_path;
    }
}
