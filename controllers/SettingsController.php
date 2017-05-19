<?php

/**
 * @package   yii2-dynagrid
 * @author    Kartik Visweswaran <kartikv2@gmail.com>
 * @copyright Copyright &copy; Kartik Visweswaran, Krajee.com, 2015 - 2017
 * @version   1.4.6
 */

namespace kartik\dynagrid\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use kartik\dynagrid\models\DynaGridSettings;

/**
 * SettingsController will manage the actions for dynagrid settings
 *
 * @package kartik\dynagrid\controllers
 */
class SettingsController extends Controller
{
    /**
     * Fetch dynagrid setting configuration
     *
     * @return string
     */
    public function actionGetConfig()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $model = new DynaGridSettings();
        $out = ['status' => '', 'content' => ''];
        $request = Yii::$app->request;
        if ($model->load($request->post()) && $model->validate()) {
            $validate = $model->validateSignature($request->post('configHashData', ''));
            if ($validate === true) {
                $out = ['status' => 'success', 'link' => $model->getDataConfig(false), 'content' => print_r($model->getDataConfig(), true)];
            } else {
                $out = ['status' => 'error', 'content' => '<div class="alert alert-danger">' . $validate . '</div>'];
            }
        }
        return $out;
    }
    
    public function actionGetSaved()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $model = new DynaGridSettings();
        $out = ['status' => '', 'content' => ''];
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            $out = ['status' => 'success',
                'content' => $model->getSavedConfig(),];
        }
        return $out;
    }
    
    public function actionDeleteSaved(){
        Yii::$app->response->format = Response::FORMAT_JSON;
        $model = new DynaGridSettings();
        $model->dynaGridId = \Yii::$app->request->post('dynaGridId');        
        $model->storage = \Yii::$app->request->post('storage');
        $model->settingsId=\Yii::$app->request->post('savedId');        
        $out = ['status' => '', 'content' => ''];
        try {
            $model->deleteSettings();
            $out = ['status' => 'success'];
        } catch (Exception $ex) {
            $out = ['status' => 'error'];
        }           
        
        return $out;
    }
}