<?php

namespace common\modules\pay\assets;

use backend\assets\FomanticAsset;
use yii\web\AssetBundle;

/**
 * Класс для подключения скриптов и стилей
 * модуля pay
 */
class PayModuleAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        'css/pay-module-custom.css'
    ];
    public $js = [
        'js/pay-module-custom.js'
    ];
    public $depends = [
        FomanticAsset::class
    ];
}
