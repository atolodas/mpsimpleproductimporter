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

class MpSimpleProductImporterImporterController
{
    protected $controller;
    protected $context;
    protected $table_import = 'mp_import_products';
    protected $token;
    protected $exists;
    
    public $id_lang;
    public $id_shop;
    
    public function __construct($controller) {
        $this->controller = $controller;
        $this->context = Context::getContext();
        $this->token = Tools::getAdminTokenLite($this->controller->name);
        $this->id_lang = (int)Context::getContext()->language->id;
        $this->id_shop = (int)Context::getContext()->shop->id;
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
    
    public function processActionImport($params)
    {
        $ids = $params['ids'];
        $import_images = (int)$params['import_images'];
        $result = $this->getRows($ids);
        $products = array();
        
        if ($result) {
            $products = $this->parse($result);
        } else {
            print Tools::jsonEncode(
                array(
                    'result' => false,
                    'msg_error' => $this->l('Error parsing products.'),
                )
            );
        }
        if ($products) {
            $this->importProducts($products, $import_images);
            print Tools::jsonEncode(
                array(
                    'result' => true,
                    'title' => $this->l('Operation done'),
                    'message' => $this->l('Products imported.'),
                    'errors' => $this->controller->errors,
                )
            );
        } else {
            print Tools::jsonEncode(
                array(
                    'result' => false,
                    'title' => $this->l('IMPORT FAILED'),
                    'msg_error' => $this->l('Error parsing products.'),
                    'errors' => $this->controller->errors,
                )
            );
        }
        
        exit();
    }
    
    public function getRows($ids)
    {
        
        $db = Db::getInstance();
        $sql = new DbQueryCore();
        $sql->select('*')
            ->from($this->table_import)
            ->where('id in (' . implode(',',$ids) . ')');
        return $db->executeS($sql);
    }
    
    public function parse($result)
    {
        $products = array();
        foreach($result as $row) {
            $products[] = unserialize($row['content']);
        }
        $records = array();
        foreach ($products as $product) {
            $attr = array();
            $feat = array();
            $desc = array();
            $categories = $this->getCategories(
                $product['category default'],
                isset($product['category other'])?$product['category other']:''
            );
            $record = array();
            $record['new'] = (int)$product['new'];
            $record['reference'] = Tools::strtoupper($product['reference']);
            $record['manufacturer_reference'] = Tools::strtoupper($product['id']);
            $record['name'] = $product['name'];
            $record['stock'] = isset($product['#stock'])?$product['#stock']:'';
            $record['no-stock'] = isset($product['#no-stock'])?$product['#stock']:'';
            $record['tax_rate'] = isset($product['#tax'])?$product['#tax']:'';
            $record['image'] = isset($product['image'])?$product['image']:'';
            $record['category_default'] = $product['category default'];
            $record['categories'] = $categories;
            $record['price'] = $product['price'];
            
            foreach ($product as $key=>$value) {
                //if $key starts with # parse this value
                if (Tools::substr($key, 0, 1) == '#') {
                    $result = $this->parseValue($key, $value);
                    if ($result) {
                       if (key($result) == '#desc') {
                           $desc[] = $result['#desc'];
                       } elseif (key($result) == '#attr') {
                           $attr[] = $result['#attr'];
                       } elseif (key($result) == '#feat') {
                           $feat[] = $result['#feat'];
                       }
                    }
                } 
            }
            $record['features'] = $feat;
            $record['attributes'] = $attr;
            if ($desc) {
                $record['description_short'] = '<br><ul>' . implode('<br>',$desc) . '</ul>';
            }
            $records[] = $record;
        }
        return $records;
    }
    
    public function getLinkRewrite($name)
    {
        $link_rewrite_1 = Tools::strtolower(preg_replace("/[^[:alnum:]]/u", '-', $name));
        $link_rewrite_2 = Tools::strtolower(preg_replace("/[[:space:]]/u", '-', $link_rewrite_1));
        while (Tools::strpos($link_rewrite_2, '--')!==false) {
            $link_rewrite_2 = Tools::strtolower(preg_replace("/--/u", '-', $link_rewrite_2));
        }
        return $link_rewrite_2;
    }
    
    public function getCategories($category_default, $categories)
    {
        if ((int)$category_default && $categories) {
            $output = explode(',',(int)$category_default.','.$categories);
        } elseif ((int)$category_default && !$categories) {
            $output = array((int)$category_default);
        } else {
            $this->controller->errors[] = $this->l('Category default not set or wrong categories.');
            return false;
        }
        return $output;
    }
    
    public function parseValue($key, $value)
    {
        //Get first 5 letters
        $col_title = trim(Tools::substr($key, 0, 5));
        $col_value = trim(Tools::substr($key, 5));
        if (Tools::strpos($col_value, ":")) {
            $split = explode(':', $col_value);
            $col_value = (int)trim($split[0]);
        }
        $array_content = array();
        
        switch ($col_title) {
            case '#attr' :
                $array_title = '#attr';
                $result = $this->getProperty($value);
                if ($result) {
                    $array_content = array($col_value => $result);
                }
                break;
            case '#feat' :
                $array_title = '#feat';
                $result = $this->getProperty($value);
                if ($result) {
                    $array_content = array($col_value => $result);
                }
                break;
            case '#desc' :
                $array_title = '#desc';
                $array_content = $this->getDescription($col_value, $value);
                break;
            default :
                $this->controller->errors[] = $this->l('Switch failed: Unable to get property '.$key.'=>'.$value);
                return false;
        }
        
        if ($array_content) {
            return array($array_title => $array_content);
        } else {
            $this->controller->errors[] = $this->l('Unable to get content '.$key.'=>'.$value);
            return false;
        }
    }
    
    public function getProperty($value)
    {
        $output = array();
        //Get values separate by comma
        $values = explode(',', $value);
        //Split id:value
        foreach ($values as &$val) {
            $split = explode(':', $val);
            $id_value = (int)trim($split[0]);
            if ($id_value) {
                $output[] = $id_value;
            }
        }
        if (empty($output)) {
            return false;
        }
        return $output;
    }
    
    public function getDescription($title, $value)
    {
        if (empty(trim($value))) {
            return false;
        }
        return '<li>'. $title . ': <strong>' . $value . '</strong></li>';
    }
    
    public function importProducts($products, $import_images = 1)
    {
        $totalProducts = count($products);
        $idx = 0;
        
        foreach ($products as $product) {
            if (empty($product['reference'])) {
                $this->controller->errors[] = $this->l('No reference found');
                continue;
            }
            if ((int)$product['category_default'] == 0) {
                $this->controller->errors[] = sprintf(
                    $this->l('No category default for product %s'),
                    $product['reference']
                );
                continue;
            }
            if (empty($product['name'])) {
                $this->controller->errors[] = sprintf(
                    $this->l('No name for product %s'),
                    $product['reference']
                );
                continue;
            }
            
            $id_product = $this->getProductByReference($product['reference']);
            if ($id_product) {
                $this->controller->errors[] = sprintf(
                    $this->l('%s already exists with id %d'),$product['reference'],$id_product
                );
                $this->exists = true;
            } else {
                $this->exists = false;
            }
            
            $objproduct = new ProductCore($id_product);
            $objproduct->active = true;
            $objproduct->available_date = date('Y-m-d h:i:s');
            $objproduct->available_now[$this->id_lang] = $product['stock'];
            $objproduct->available_later[$this->id_lang] = $product['no-stock'];
            $objproduct->available_for_order = 1;
            $objproduct->description_short[$this->id_lang] = $product['description_short'];
            $objproduct->out_of_stock = 1;
            try {
                $this->controller->errors[] = sprintf(
                    $this->l('add to categories %s: %d'),
                    print_r($product['categories'], 1),
                    (int)$objproduct->addToCategories($product['categories'])
                );
            } catch (Exception $ex) {
                $this->controller->errors[] = sprintf(
                    $this->l('addToCategories for product %s failed: %s'),
                    $objproduct->reference,
                    $ex->getMessage()
                );
            }
            
            $objproduct->id_category_default = (int)$product['category_default'];
            $objproduct->id_manufacturer = 3;
            $objproduct->id_supplier = 3;
            $objproduct->id_tax_rules_group = 7;
            $objproduct->name[$this->id_lang] = $product['name'] . ' ISACCO ' . $product['manufacturer_reference'];
            $objproduct->new = $product['new'];
            $objproduct->price = $product['price'];
            $objproduct->reference = $product['reference'];
            $objproduct->supplier_reference = $product['manufacturer_reference'];
            $objproduct->visibility = 'both';
            $objproduct->link_rewrite[$this->id_lang] = $this->getLinkRewrite($product['name']);
            $result = $objproduct->save();
            if ($result) {
                $id_product = $objproduct->id;
                //ADD FEATURES
                $this->controller->errors[] = sprintf(
                    $this->l('Features for product %s : %s'),
                    $objproduct->reference,
                    "<pre>" . print_r($product['features'], 1) . "</pre>"
                );
                
                if ($this->exists) {
                    $this->removeProperties($objproduct->id);
                }
                
                foreach ($product['features'] as $feature) {
                    $id_feature = key($feature);
                    $id_feature_value = $feature;
                    foreach ($feature as $values) {
                        foreach($values as $value) {
                            $this->controller->errors[] = "<pre>value:" . $value . "</pre>";
                            $id_feature_value = $value;
                            try {
                                $result = $objproduct->addFeaturesToDB($id_feature, $id_feature_value);
                                $this->controller->errors[] = sprintf(
                                    $this->l('Product %s adding feature %d-%d: %d'),
                                    $objproduct->reference,
                                    $id_feature,
                                    $id_feature_value,
                                    $result
                                );
                            } catch (Exception $ex) {
                                $this->controller->errors[] = sprintf(
                                    $this->l('Product %s has already feature %d-%d. Error: %s'),
                                    $objproduct->reference,
                                    $id_feature,
                                    $id_feature_value,
                                    $ex->getMessage()
                                );
                            }
                        }
                    }
                }
                //ADD COMBINATIONS
                $this->enumeration($objproduct->id, $product['attributes']);
                if ($import_images) {
                    //ADD IMAGES
                    $this->addImage($objproduct->id, $objproduct->supplier_reference);
                }
            }
            $idx++;
            $this->setPercent($idx, $totalProducts);
        }
    }
    
    private function setPercent($current, $total)
    {
        $percent = (int)($current * 100 / $total);
        $db = Db::getInstance();
        $db->insert(
            'mp_progress',
            array(
                'id_progress' => pSQL($this->controller->module->name),
                'progress' => $percent,
            ),
            true,
            false,
            Db::REPLACE
        );
    }
    
    private function removeProperties($id_product)
    {
        $db = Db::getInstance();
        //Remove features
        $db->delete(
            'feature_product',
            'id_product='.(int)$id_product);
        $feat = $db->Affected_Rows();
        $this->controller->errors[] = sprintf(
            $this->l('Removed %d features product.'),
            $feat
        );
        //Remove attributes
        $db->delete(
            'product_attribute',
            'id_product='.(int)$id_product);
        $attr = $db->Affected_Rows();
        $this->controller->errors[] = sprintf(
            $this->l('Removed %d product attributes.'),
            $attr
        );
        
        $db->delete(
            'product_attribute_shop',
            'id_product='.(int)$id_product.' and id_shop='.(int)$this->id_shop);
        $attr_shop = $db->Affected_Rows();
        $this->controller->errors[] = sprintf(
            $this->l('Removed %d product attributes from shop %d.'),
            $attr_shop,
            $this->id_shop
        );
        
        $sql = 'delete from `'._DB_PREFIX_.'product_attribute_combination` ' .
            'where `id_product_attribute` not in (' .
            'select id_product_attribute from `'._DB_PREFIX_.'product_attribute`)';
        $this->controller->errors[] = "<pre>Query: " . $sql . "</pre>";
        $db->execute($sql);
        $comb = $db->Affected_Rows();
        $this->controller->errors[] = sprintf(
            $this->l('Removed %d combinations.'),
            $comb
        );
    }
    
    private function getProductByReference($reference)
    {
        $db = Db::getInstance();
        $sql = new DbQueryCore();
        $sql->select('id_product')
            ->from('product')
            ->where('reference = \''.pSQL($reference).'\'');
        $id_product = (int)$db->getValue($sql);
        return $id_product;
    }
    
    /**
     * Create combinations from a list of attributes
     * @param int  $id_product Product id to add combinations
     * @param array $attributes List of Attribute [ImportProperty class]
     */
    private function enumeration($id_product, $attributes)
    {
        //Create groups attributes
        $groups = array();
        foreach ($attributes as $group_attributes) {
            foreach ($group_attributes as $attributes_id) {
                $groups[] = array();
                foreach ($attributes_id as $id_attribute) {
                    $groups[count($groups)-1][] = $id_attribute;
                }
            }
        }
        
        $combinations = $this->enumerations($groups);
        $this->controller->errors[] = "<pre>Combinations for $id_product : " . print_r($combinations,1) . "</pre>"; 
        if ($combinations) {
            $db = Db::getInstance();
            $product = new ProductCore($id_product);
            $is_default = 1;
            foreach ($combinations as $id_attributes) {
                try {
                    $id_product_attribute = $product->addAttribute(0, 0, 0, 0, $id_product, $product->reference, '', $is_default);
                    $this->controller->errors[] = sprintf(
                        $this->l('Add product attribute per product %s: %d'),
                        $product->reference,
                        $id_product_attribute
                    );
                } catch (Exception $ex) {
                    $this->controller->errors[] = sprintf(
                        $this->l('error: %s'),
                        $ex->getMessage()
                    );
                    continue;
                }
                foreach ($id_attributes as $id_attribute) {
                    $db->insert(
                        'product_attribute_combination',
                        array(
                            'id_attribute'=> (int)$id_attribute,
                            'id_product_attribute' => (int)$id_product_attribute,
                        ),
                        true,
                        false,
                        Db::INSERT_IGNORE
                    );
                }
                $is_default *= 0;
            }
        }
    }
    
    private function enumerations($comb_arrays) {
        $result = array();
        $arrays = array_values($comb_arrays);
        $sizeIn = sizeof($arrays);
        $size = $sizeIn > 0 ? 1 : 0;
        
        foreach ($arrays as $array) {
            $size = $size * sizeof($array);
        }
            
        for ($i = 0; $i < $size; $i ++)
        {
            $result[$i] = array();
            for ($j = 0; $j < $sizeIn; $j ++) {
                array_push($result[$i], current($arrays[$j]));
            }
            for ($j = ($sizeIn -1); $j >= 0; $j --)
            {
                if (next($arrays[$j])) {
                    break;
                } elseif (isset ($arrays[$j])) {
                    reset($arrays[$j]);
                }
            }
        }
        return $result;
    }
     
    private function addImage($id_product, $supplier_reference)
    {      
        //Image folder
        $image_folder = $this->controller->module->getPath() . '../../upload/img-prod/';
        
        if (!file_exists($image_folder)) {
            $this->controller->errors[] = sprintf($this->l('%s not exists.'), $image_folder);
            return false;
        }
        
        //Image name
        $image_name = $supplier_reference . '.jpg';
        
        if (!file_exists($image_folder.'/'.$image_name)) {
            return false;
        }
        
        //import image
        $image = new ImageCore();
        $image->cover=true;
        $image->force_id=false;
        $image->id=0;
        $image->id_image=0;
        $image->id_product = $id_product;
        $image->image_format = 'jpg';
        $image->legend = '';
        $image->position=0;
        $image->source_index='';
        try {
            $image->save();
        } catch (Exception $exc) {
            PrestaShopLoggerCore::addLog('Error during image add: error ' . $exc->getCode() . ' ' . $exc->getMessage());
            $image->cover=false;
            $image->force_id=false;
            $image->id=0;
            $image->save();
        }
        
        if (!(int)$image->id) {
            PrestaShopLoggerCore::addLog('Error: imported image has not a valid id.');
            return false;
        }
        
        $imageTargetFolder = _PS_PROD_IMG_DIR_ . ImageCore::getImgFolderStatic((int)$image->id);
        if (!file_exists($imageTargetFolder)) {
            mkdir($imageTargetFolder, 0777, true);
        }
        $source = $image_folder.'/'.$image_name;
        $target = $imageTargetFolder . $image->id . '.' . $image->image_format;
        
        $copy = copy($source, $target);
        $this->controller->errors[] = "Image copy from $source to $target = $copy";
        
        return (int)$copy;
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