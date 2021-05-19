<?php

namespace common\modules\pay;

use yii\base\Module;

/**
 * Модуль предназначен для управления платежными системами
 * (драйверами) и предоставляет единый инерфейс
 * для их подключения
 */
abstract class PayModule extends Module
{
}
