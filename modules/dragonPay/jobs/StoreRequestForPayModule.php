<?php

namespace backend\modules\dragonPay\jobs;

use backend\modules\dragonPay\components\DragonPayComponent;
use backend\modules\dragonPay\components\DragonPayHttp;
use backend\modules\dragonPay\components\DragonPayRequest;
use yii\base\BaseObject;
use yii\queue\JobInterface;
use backend\modules\dragonPay\components\DragonPayDriver;
use common\modules\pay\models\PayJournalRequest;
use common\modules\pay\components\Pay;

/**
 * Сохранение запроса в таблице dragonPay
 */
class StoreRequestForPayModule  extends BaseObject implements JobInterface
{
    /**
     * @var DragonPayDriver
     */
    public $driver;

    /**
     * @param $queue
     */
    public function execute($queue)
    {
        $driver = $this->driver;
        /** @var DragonPayDriver $driver */
        $module = $this->driver->getModule();
        /** @var Pay $module*/
        $component = $driver->getComponent();
        /** @var DragonPayComponent $component*/
        $request = $component->getRequest();
        /** @var DragonPayRequest $request*/
        $requestTime = $request->responseTime();

        $model = new PayJournalRequest([
            'driver_name' => $driver->getName(),
            'http_response_body' => json_encode($request->getContent()),
            'end_time' => date('m-d-Y H:i:s',(time() + $requestTime)),
            'http_request_body' => json_encode($request->getData()),
            'http_request_url' => $request->getFullUrl(),
            'operation_type' => '',
        ]);

        $model->save();
        if ($model->hasErrors()) {
            \Yii::error(json_encode($model->errors));
        }
    }


}
