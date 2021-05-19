<?php

namespace backend\modules\dragonPay\controllers;

use backend\modules\crmfo_kernel_api\src\CRMFOHelpers;
use backend\modules\dragonPay\components\DragonPayComponent;
use backend\modules\dragonPay\models\DragonpaySettings;
use backend\modules\user_interface\controllers\InitController;
use Yii;
use yii\helpers\Url;

/**
 * Раздел настроек модуля dragonpay
 *
 * @backend\modules\crmfo_kernel_api\src\Arm\ControllerAnnotation(cid="administration")
 */
class SettingsController extends InitController
{
    /**
     * @return array|string[]
     */
    public static function GetAIDs() {

        return [
            CRMFOHelpers::ArmGroup()
                ->setGid("modules")
                ->setText(\Yii::t('app',"Настройки модулей"))
                ->setIcon("fa-desktop")
                ->setWeight(100)
                ->setParent("administration")
                ->Format(),
            CRMFOHelpers::ArmLink()
                ->setGid('modules')
                ->setAction("index")
                ->setText('DragonPay')
                ->setIcon("fa-calendar")
                ->Format(),
        ];
    }

    public function actionIndex() {

        $model = DragonpaySettings::getModel();

        $component = new DragonPayComponent();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success',Yii::t('app','Данные успешно сохранены!'));
            return $this->redirect(Url::to(['settings/index']),301);
        }
        return $this->render('index',[
            'model' => $model,
            'component' => $component
        ]);
    }
}
