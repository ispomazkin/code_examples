<?php

namespace common\modules\pay\components;

use yii\helpers\ArrayHelper;
use yii\helpers\Html;

/**
 * Класc для генерации компонентов Fomantic
 */
class FomanticHtml extends Html
{
    /**
     * @param string $label
     * @param bool $checked
     * @param array $options
     * @return string
     */
    public static function toggleButton(string $label,bool $checked = false, array $options = []): string
    {
        return self::tag('div',
            self::input('checkbox') .
            self::label($label),
            ArrayHelper::merge(['class'=>'ui toggle checkbox ' . ($checked ? 'checked':'')], $options));
    }

}
