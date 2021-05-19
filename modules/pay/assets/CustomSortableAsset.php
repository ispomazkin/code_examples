<?php

namespace common\modules\pay\assets;

use yii\web\AssetBundle;
use kartik\sortable\SortableAsset;

/**
 * Класс для кастомизации стилей kartik\sortable
 */
class CustomSortableAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';

    public $js = [
        'js/pay-module-sortable-custom.js'
    ];
    public $depends = [
        SortableAsset::class
    ];
}
