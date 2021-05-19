<?php

use yii\helpers\Html;
use yii\helpers\Url;
use kartik\form\ActiveForm;
use kartik\widgets\SwitchInput;
use frontend\widgets\Alert;

/* @var $this yii\web\View */
/** @var \backend\modules\dragonPay\models\DragonpaySettings $model*/
/** @var \backend\modules\dragonPay\components\DragonPayComponent $component*/

$this->params['breadcrumbs'] = [
    ['label' => \Yii::t('app','Администрирование')],
    ['label' => \Yii::t('app','Настройки')],
    ['label' => \Yii::t('app','DragonPay')],
];
$this->title = \Yii::t('app','Администрирование DragonPay');
?>
<div class="page-content" id="add-scan">
    <?=Alert::widget()?>

    <h1><?=$this->title?></h1>
    <?php $form = ActiveForm::begin()?>
    <div class="col-md-12 col-lg-12">
        <h2>Настройки доступа</h2>
        <div class="col-md-4 col-lg-4">
            <?= $form->field($model,'merchant_id')->textInput(['placeholder'=>$component->merchant_id])?>
        </div>
        <div class="col-md-4 col-lg-4">
            <?= $form->field($model,'merchant_password')->textInput(['placeholder'=>$component->merchant_password])?>
        </div>
    </div>
    <div class="col-md-12 col-lg-12">
        <h2>Настройки путей для оплаты</h2>
        <div class="col-md-4 col-lg-4">
            <?= $form->field($model,'payment_url')->textInput(['placeholder'=>$component->paymentUrl])?>
        </div>
        <div class="col-md-4 col-lg-4">
            <?= $form->field($model,'request_base_url')->textInput(['placeholder'=>$component->requestBaseUrl])?>
        </div>
    </div>
    <div class="col-md-12 col-lg-12">
        <h2>Настройки типов синхронизаций</h2>
        <div class="col-md-8 col-lg-8">
            <?= $form->field($model,'lifetime_enabled')->widget(SwitchInput::class, []);?>
        </div>
        <div class="col-md-8 col-lg-8">
            <?= $form->field($model,'refno_enabled')->widget(SwitchInput::class, []);?>
        </div>
    </div>
    <div class="col-md-12 col-lg-12">
        <h2>Документация и логотип</h2>
        <div class="col-md-8 col-lg-8">
            <?= $form->field($model,'logo_url')->textInput();?>
        </div>
        <div class="col-md-8 col-lg-8">
            <?= $form->field($model,'documentation_url')->textInput();?>
        </div>
    </div>
    <div class="col-md-12 col-lg-12">
        <?=Html::submitButton(\Yii::t('app','Сохранить'),['class'=>'btn btn success'])?>
    </div>
    <?php ActiveForm::end()?>
</div>
