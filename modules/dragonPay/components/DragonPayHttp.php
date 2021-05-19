<?php

namespace backend\modules\dragonPay\components;

use yii\httpclient\Client;
use yii\httpclient\Exception;
use yii\httpclient\Request;
use yii\httpclient\Response;

/**
 * Кастомизация клиента http для модуля dragonpay
 *
 * @property-read Response $response
 */
class DragonPayHttp extends Client
{
    /**
     * @var array
     */
    public $requestConfig=[
        'class'=>'backend\modules\dragonPay\components\DragonPayRequest'
    ];


}