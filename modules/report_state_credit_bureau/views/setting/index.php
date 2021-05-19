<?php

use yii\helpers\Html;
use yii\helpers\Url;
use kartik\form\ActiveForm;
use kartik\widgets\DatePicker;

/**
 * @var $model \backend\modules\report_state_credit_bureau\models\FileDateModel
 * @var $this \yii\web\View
 * @var $reportModel \backend\modules\report_state_credit_bureau\components\Report
 */

$label = \Yii::t('app', 'Generate Report');
$this->title = \Yii::t('app','Формирование отчета');
$this->params['breadcrumbs'][]=\Yii::t('app','Настройки модулей');
$this->params['breadcrumbs'][]=$this->title;
?>
<div class="col-md-12 col-lg-12">
    <h1><?=$this->title?></h1>
    <?php $form = ActiveForm::begin()?>
    <div class="col-md-6 col-lg-6">
        <div class="panel panel-default">
            <div class="panel-body">
                <?= $form->field($model, 'date_to')->widget(DatePicker::classname(), [
                    'options' => ['placeholder' => $label],
                    'pluginOptions' => [
                        'autoclose'=>true
                    ]
                ]);?>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-6">
        <?php if (count($reportModel->getListOfCreatedFiles())):?>
            <div class="panel panel-default">
                <div class="panel-body">
                    <h2>Already created reports earlier</h2>
                    <?=Html::beginTag('table',['class'=>'table table-bordered table-responsive table-striped table-hover'])?>
                    <tr>
                        <th>Report file</th>
                        <th>Reload</th>
                    </tr>
                    <?php foreach ($reportModel->getListOfCreatedFiles() as $createdFile):?>
                        <tr>
                            <td>
                                <?=Html::a($reportModel->getReportDate($createdFile),Url::to(['setting/download','file'=>$createdFile]))?>
                            </td>
                            <td>
                                <?=Html::a('',Url::to(['setting/reload-report','file'=>$createdFile]),['class'=>'glyphicon glyphicon-refresh'])?>
                            </td>
                        </tr>
                    <?php endforeach;?>
                    <?=Html::endTag('table')?>
                    <?php endif;?>
                </div>
            </div>
    </div>
    <div class="col-md-12 col-lg-12">
        <?=Html::submitButton($label,[
            'class'=>'btn btn-success'
        ])?>
    </div>

    <?php ActiveForm::end()?>
</div>
