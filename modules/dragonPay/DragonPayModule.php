<?php

namespace backend\modules\dragonPay;

use yii\base\Module;
use yii\helpers\ArrayHelper;

/**
 * Модуль платежной системы dragonpay
 */
class DragonPayModule extends Module
{
    const MODULE_ID = 'dragonpay';

    /** категория ошибок для сентри */
    const SENTRY_CATEGORY_ERROR = 'dragon_pay_module';
}
