<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class MpSimpleProductImporterCsvParserController
{
    protected $controller;
    protected $context;
    protected $table_import = 'mp_import_products';
    protected $token;
    
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
    
    public function processActionInsert()
    {
        $attachment = Tools::fileAttachment('input_file_csv_import', false);
        if ($attachment['mime']!='text/csv') {
            $this->controller->errors[] = $this->l('Please, select an csv file.');
            return false;
        }
        if ($attachment['error'] != 0 || $attachment['size'] == 0) {
            $this->controller->errors[] = sprintf(
                $this->l('File not valid. Error %d, Size %s'),
                $attachment['error'],
                $attachment['size']
            );
            return false;
        }
        $result = $this->processActionParse($attachment);
        return $result;
    }
    
    public function processActionParse($attachment)
    {
        $csv = $this->readCSV($attachment['tmp_name']);
        $totRows = count($csv);
        if ($totRows<2) {
            $this->controller->errors[] = $this->l('Bad excel format. Check rows.');
            return false;
        }
        $csvTitles = array();
        $csvContent = array();
        
        $mandatory = array('id', 'new', 'reference', 'name', 'category default');
        //Get titles
        foreach($csv[0] as $col)
        {
            $csvTitles[] = Tools::strtolower($col);
        }
        
        $intersect = count(array_intersect($mandatory, $csvTitles));
        if ($intersect != count($mandatory)) {
            $this->controller->errors[] = $this->l('Missing mandatory columns.');
            return false;
        }
        //Create associative array with titles for each row
        array_shift($csv);
        $idxRow = 0;
        foreach ($csv as $row) {
            $idxCol = 0;
            if (!empty($row)) {
                foreach ($row as $col) {
                    $csvContent[$idxRow][$csvTitles[$idxCol]] = $col;
                    $idxCol++;
                }
            }
            $idxRow++;
        }
        
        //Insert array in archive;
        $db = Db::getInstance();
        $db->execute('truncate table `'._DB_PREFIX_.$this->table_import.'`;');
        foreach ($csvContent as $product) {
            if (!empty($product['reference'])) {
                $fmt = new NumberFormatter('it-IT', NumberFormatter::DECIMAL);
                $price = isset($product['price'])?$fmt->parse($product['price']):'0.00';
                $product['price'] = $price;
                $content = serialize($product);
                if (empty($content)) {
                    $this->controller->errors[] = sprintf(
                        $this->l('Error importing product %s.'), $product['reference']
                    );
                } else {
                    $db->insert(
                        $this->table_import,
                        array(
                            'token' => $this->token,
                            'reference' => $product['reference'],
                            'content' => preg_replace('/\'/',"\'",$content),
                        ),
                        true,
                        true,
                        Db::REPLACE);
                }
            }
        }
        
        $sql = new DbQueryCore();
        $sql->select('count(*)')
            ->from($this->table_import)
            ->where('token=\''.pSQL($this->token) . '\'');
        $result = $db->getValue($sql);
        if ($result) {
            return $result;
        }
        $this->controller->errors[] = sprintf($this->l('Error getting table tada: %s'), $db->getMsgError());
        return false;
    }
    
    public function readCSV($csvFile){
        $file_handle = fopen($csvFile, 'r');
        while (!feof($file_handle) ) {
            $line_of_text[] = fgetcsv($file_handle, 0, ';', '"');
        }
        fclose($file_handle);
        return $line_of_text;
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