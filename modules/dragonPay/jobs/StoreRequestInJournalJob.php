<?php

namespace backend\modules\dragonPay\jobs;

use backend\modules\dragonPay\components\DragonPayComponent;
use backend\modules\dragonPay\components\DragonPayHttp;
use backend\modules\dragonPay\components\DragonPayRequest;
use backend\modules\dragonPay\DragonPayComponentsModule;
use backend\modules\dragonPay\models\DragonpayListChannels;
use yii\base\BaseObject;
use yii\helpers\Json;
use yii\httpclient\RequestEvent;
use yii\queue\JobInterface;
use backend\modules\dragonPay\models\DragonpayJournalRequests;
use yii\web\Request;

/**
 * Класс для фсинхронного сохранения запроса в журнале dragonpay
 *
 * @property-read $decodedResponse
 */
class StoreRequestInJournalJob  extends BaseObject implements JobInterface
{
    /** @var RequestEvent $event*/
    public $event;
    /** @var mixed */
    protected $_decodedResponse;

    /**
     * @param $queue
     */
    public function execute($queue)
    {
        if (!DragonPayComponentsModule::isEnabled()) {
            return;
        }
        $requestObj = $this->event->request;
        $responseObj = $this->event->response;
        /** @var \yii\web\Response */

        $requestData = [
            'headers'=>$requestObj->headers,
            'data'=>$requestObj->data,
            'method'=>$requestObj->method,
            'url'=>$requestObj->url,
            'options'=>$requestObj->options
        ];

        $responseData = [
            'content'=>$responseObj->content
        ];

        $journal = new DragonpayJournalRequests([
            /** @var $requestObj DragonPayRequest */
            'customer_id' => $requestObj->questionnaire->customer->id ?? null,
            'questionnaire_id' => $requestObj->questionnaire->id ?? null,
            'request_time' => $requestObj->responseTime(),
            'request' => Json::encode($requestData),
            'response' => Json::encode($responseData),
            'type_number'=>$requestObj->typeNumber,
            'type_operation'=>$requestObj->typeOperation,
            'number'=>$requestObj->number ?? $this->decodedResponse['RefNo'] ??  $responseObj->content ?? null,
            'redirect_url'=> $this->decodedResponse['Url'] ?? null,
            'status'=> $this->decodedResponse['Status'] ?? null,
            'message'=> $this->decodedResponse['Message'] ?? null,
        ]);
        $journal->save();
        if ($journal->hasErrors()) {
           \Yii::error(json_encode($journal->errors));
        }

        switch ($requestObj->storeContentPlace) {
            case DragonPayComponent::STORE_LIST_CHANNELS:
                DragonPayComponent::storeListAndStatusChannels($this->decodedResponse);
                break;
            default:
        }

    }

    /**
     * @return mixed
     */
    protected function getDecodedResponse()
    {
        if ($this->_decodedResponse === null) {
            try {
                $this->_decodedResponse = Json::decode($this->event->response->content);
            } catch (\Exception $e) {
                $this->_decodedResponse=[];
                \Yii::error($e->getMessage());
            }
        }
        return $this->_decodedResponse;
    }

}
