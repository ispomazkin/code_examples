<?php

declare(strict_types=1);

namespace backend\modules\report_state_credit_bureau\controllers;

use backend\modules\crmfo_kernel_api\src\CRMFOHelpers;
use backend\modules\crmfo_kernel_modules\Entity\AccountHistory;
use backend\modules\report_state_credit_bureau\components\Report;
use backend\modules\user_interface\controllers\InitController;
use backend\modules\crmfo_kernel_api\src\Arm\ControllerAnnotation;
use backend\modules\report_state_credit_bureau\models\FileDateModel;

/**
 * Раздел для ручного формирования отчетов
 *
 * @package backend\modules\report_state_credit_bureau\controllers
 * @ControllerAnnotation(cid="reports_state_credit")
 */
class SettingController extends InitController
{

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
                ->setAction("index")
                ->setText(\Yii::t('app',"Формирование отчета"))
                ->setGid('modules')
                ->setIcon("fa-calendar")
                ->setWeight(4)
                ->Format(),
        ];
    }

    /**
     * @return string|\yii\console\Response|\yii\web\Response
     * @throws \yii\web\RangeNotSatisfiableHttpException
     */
    public function actionIndex()
    {
        $model = new FileDateModel();
        $reportModel = new Report();
        if ($model->load(\Yii::$app->request->post()) && $model->validate()) {
            $reportModel->date_to=$model->date_to;
            $fileContent = $reportModel
                ->setFileReferenceDate($model->date_to)
                ->generateReport()
                ->saveFile()
                ->getContent();
            return \Yii::$app->response->sendContentAsFile($fileContent, $reportModel->reportFileName);
        }

        return $this->render('index', [
            'model' => $model,
            'reportModel' => $reportModel
        ]);
    }

    /**
     * @param $file
     *
     * @return \yii\console\Response|\yii\web\Response
     */
    public function actionDownload($file)
    {
        return \Yii::$app->response->sendFile((new Report())->uploadPath .'/' .$file);
    }

    /**
     * @param $file
     * @return \yii\console\Response|\yii\web\Response
     * @throws \yii\web\BadRequestHttpException
     * @throws \yii\web\RangeNotSatisfiableHttpException
     */
    public function actionReloadReport($file)
    {
        $fileContent = (new Report())
            ->prepareIds($file)
            ->generateReport()
            ->getContent();
        $fileName = 'reload_'.$file;
        return \Yii::$app->response->sendContentAsFile($fileContent, $fileName);
    }
}
