<?php

/**
 * @package   yii2-dynagrid
 * @author    Kartik Visweswaran <kartikv2@gmail.com>
 * @copyright Copyright &copy; Kartik Visweswaran, Krajee.com, 2015 - 2017
 * @version   1.4.6
 */

namespace kartik\dynagrid\models;

use Yii;
use yii\base\Model;
use yii\helpers\Inflector;
use kartik\base\Config;
use kartik\dynagrid\Module;
use kartik\dynagrid\DynaGrid;
use kartik\dynagrid\DynaGridStore;

/**
 * Model for the dynagrid filter or sort configuration
 *
 * @author Kartik Visweswaran <kartikv2@gmail.com>
 * @since 1.0
 */
class DynaGridSettings extends Model
{
    /**
     * @var string the identifier the dynagrid detail
     */
    public $settingsId;

    /**
     * @var string the dynagrid category (FILTER or SORT)
     */
    public $category;

    /**
     * @var string the dynagrid detail storage type
     */
    public $storage;

    /**
     * @var boolean whether the storage is user specific
     */
    public $userSpecific;

    /**
     * @var boolean whether to update only the name, when editing and saving a filter or sort. This is applicable
     * only for [[$storage]] set to [[Dynagrid::TYPE_DB]]. If set to `false`, it will also overwrite the current 
     * `filter` or `sort` settings.
     */
    public $dbUpdateNameOnly = false;

    /**
     * @var string the dynagrid detail setting name
     */
    public $name;

    /**
     * @var string the dynagrid widget id identifier
     */
    public $dynaGridId;

    /**
     * @var string the key for the dynagrid category (FILTER or SORT)
     */
    public $key;

    /**
     * @var array the available list of values data for the specified dynagrid detail category (FILTER or SORT)
     */
    public $data;

    /**
     * @var Module the dynagrid module instance
     */
    protected $_module;
    
    public $savedId;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->_module = Config::initModule(Module::classname());
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['category', 'storage', 'userSpecific', 'dbUpdateNameOnly', 'name', 'dynaGridId', 'settingsId', 'savedId', 'key', 'data'], 'safe'],
            [['name'], 'required'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        if ($this->category === DynaGridStore::STORE_FILTER) {
            return [
                'name' => Yii::t('kvdynagrid', 'Filter Name'),
                'settingsId' => Yii::t('kvdynagrid', 'Saved Filters'),
                'dataConfig' => Yii::t('kvdynagrid', 'Filter Configuration'),
            ];
        } elseif ($this->category === DynaGridStore::STORE_SORT) {
            return [
                'name' => Yii::t('kvdynagrid', 'Sort Name'),
                'settingsId' => Yii::t('kvdynagrid', 'Saved Sorts'),
                'dataConfig' => Yii::t('kvdynagrid', 'Sort Configuration'),
            ];
        }
        return [];
    }

    /**
     * Gets the DynaGridStore configuration instance
     *
     * @return DynaGridStore
     */
    public function getStore()
    {        
        $settings = [
            'id' => $this->dynaGridId,
            'name' => $this->name,
            'category' => $this->category,
            'storage' => $this->storage,
            'userSpecific' => $this->userSpecific,
            'dbUpdateNameOnly' => $this->dbUpdateNameOnly
        ];
        
        if (!empty($this->settingsId)) {
            $settings['dtlKey'] = $this->settingsId;
        }
        $settings['dtlKey']= $this->savedId;
        return new DynaGridStore($settings);
    }

    /**
     * Fetches grid configuration settings from store
     *
     * @return mixed
     */
    public function fetchSettings()
    {
        return $this->getStore()->fetch();
    }

    /**
     * Saves grid configuration settings to store
     */
    public function saveSettings()
    {
        $this->getStore()->save($this->data);
    }

    public function saveGrid($data)
    {
        $settings = [
            'id' => $this->dynaGridId,
            'name' => $this->name,
            'category' => 'saved',
            'storage' => $this->storage,
            'userSpecific' => $this->userSpecific
        ];
        if (isset($this->id) && !empty($this->id)) {
            $settings['dtlKey'] = $this->id;
        }
        $model = new DynaGridStore($settings);
        $model->save($data);
    }
    
    
    /**
     * Gets saved grids
     * @return string
     */
    public function getSavedConfig()
    {       
        return $this->store->fetch();
    }
    
    /**
     * Deletes grid configuration settings from store
     */
    public function deleteSettings()
    {        
        $master = new DynaGridStore([
            'id' => $this->dynaGridId,
            'category' => DynaGridStore::STORE_GRID,
            'storage' => $this->storage,
            'userSpecific' => $this->userSpecific,
            'dbUpdateNameOnly' => $this->dbUpdateNameOnly
        ]);
        $config = $this->storage == DynaGrid::TYPE_DB ? null : $master->fetch();
        $master->deleteConfig($this->category, $config);
        $this->getStore()->delete();
    }

    /**
     * Gets list of values (for filter or sort category)
     *
     * @return mixed
     */
    public function getDtlList()
    {
        return $this->getStore()->getDtlList($this->category);
    }

    /**
     * Gets data configuration as a HTML list markup
     *
     * @return string
     */
    public function getDataConfig($onlyLink = true)
    {
        $data = $this->getStore()->fetch();
        if (!is_array($data) || empty($data) &&
            ($this->category !== DynaGridStore::STORE_SORT && $this->category !== DynaGridStore::STORE_SORT)
        ) {
            return '';
        }
        $attrLabel = $this->getAttributeLabel('dataConfig');
        $out = "<label>{$attrLabel}</label>\n<ul>";
        if ($this->category === DynaGridStore::STORE_FILTER) {
            if (!$onlyLink) {
                if (\yii::$app->urlManager->enablePrettyUrl) {
                    return NULL; //todo: need a implementation
                } else {
                    preg_match("/r=(.*)/", preg_split("/\&/", Yii::$app->request->getReferrer())[0], $route); //todo: need a better solution
                }
                
                return Yii::$app->urlManager->createUrl([urldecode($route[1]), ucwords(preg_split('/\//', urldecode($route[1]))[0]) => $data]);
            }            
            foreach ($data as $attribute => $value) {
                $label = isset($attribute['label']) ? $attribute['label'] : Inflector::camel2words($attribute);
                $value = is_array($value) ? print_r($value, true) : $value;
                $out .= "<li>{$label} = {$value}</li>";
            }
        } else {
            $sort = [];
            foreach ($data as $attribute => $direction) {
                $label = isset($attribute['label']) ? $attribute['label'] : Inflector::camel2words($attribute);
                $icon = $direction === SORT_DESC ? "glyphicon glyphicon-sort-by-alphabet-alt" : "glyphicon glyphicon-sort-by-alphabet";
                $dir = $direction === SORT_DESC ? Yii::t('kvdynagrid', 'descending') : Yii::t('kvdynagrid', 'ascending');
                $out .= "<li>{$label} <span class='{$icon}'></span> <span class='label label-default'>{$dir}</span></li>";
                $sort[] = ($direction === SORT_DESC) ? '-' . $attribute : $attribute;
            }
            if (!$onlyLink) {
                preg_match("/r=(.*)/", preg_split("/\&/", Yii::$app->request->getReferrer())[0], $route);
                return Yii::$app->urlManager->createUrl([urldecode($route[1]), 'sort' => implode(',', $sort),
                        true]);                
            }
        }
        $out .= "</ul>";
        return $out;
    }

    /**
     * Gets a hashed signature for specific attribute data passed between server and client
     *
     * @param array $attribs the list of attributes whose data is to be hashed
     * @return string the hashed signature output
     * @throws \yii\base\InvalidConfigException
     */
    public function getHashSignature($attribs = [])
    {
        $salt = $this->_module->configEncryptSalt;
        $out = '';
        if (empty($attribs)) {
            $attribs = ['dynaGridId', 'category', 'storage', 'userSpecific', 'dbUpdateOnly'];
        }
        foreach ($attribs as $key => $attr) {
            if (isset($this->$attr)) {
                $out .= $attr === 'userSpecific' || $attr === 'dbUpdateOnly' ? !!$this->$attr : $this->$attr;
            }
        }
        return Yii::$app->security->hashData($out, $salt);
    }

    /**
     * Validate signature of the hashed data submitted via hidden fields from the filter/sort update form
     *
     * @param string $hashData the hashed data to match
     * @param array $attribs the list of attributes against which data hashed is to be validated
     *
     * @return boolean|string returns true if valid else the validation error message
     */
    public function validateSignature($hashData = '', $attribs = [])
    {
        $salt = $this->_module->configEncryptSalt;
        $origHash = $this->getHashSignature($attribs);
        $params = YII_DEBUG ? '<pre>OLD HASH:<br>' . $origHash . '<br>NEW HASH:<br>' . $hashData . '</pre>' : '';
        return (Yii::$app->security->validateData($hashData, $salt) && $hashData === $origHash) ? true : Yii::t(
            'kvdynagrid',
            'Operation disallowed! Invalid request signature detected for dynagrid settings. {params}',
            ['params' => $params]
        );
    }
}
