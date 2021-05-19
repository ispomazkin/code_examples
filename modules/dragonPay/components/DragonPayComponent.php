<?php

namespace backend\modules\dragonPay\components;

use backend\modules\crmfo_kernel_modules\Entity\Customer;
use backend\modules\crmfo_kernel_modules\Entity\Questionnaire;
use backend\modules\dragonPay\DragonPayModule;
use backend\modules\dragonPay\models\DragonpayJournalRequests;
use backend\modules\dragonPay\models\DragonpayListChannels;
use backend\modules\dragonPay\models\DragonpaySettings;
use backend\modules\dragonPay\models\DragonpayStatusChannels;
use Codeception\Lib\Generator\Helper;
use yii\base\Component;
use yii\base\ErrorException;
use yii\helpers\ArrayHelper;
use yii\httpclient\CurlTransport;
use yii\httpclient\RequestEvent;
use yii\httpclient\Transport;
use backend\modules\dragonPay\components\DragonPayHttp as Client;
use yii\web\Response;
use Yii;

/**
 * Компонент модуля dragonpay
 *
 * @property-read DragonPayHttp $client
 * @property-read Customer $customer
 * @property-read DragonPayRequest $request
 * @property-read Questionnaire $questionnaire
 * @property-read boolean $lifeTimeIntegrationEnabled
 * @property-read boolean $referenceNumberIntegrationEnabled
 * @property-read string $lifetimeId
 * @property-read Response $response
 */
class DragonPayComponent extends Component
{

    const STORE_LIST_CHANNELS='store_list_channels';

    /** @var string */
    public $merchant_id;

    /** @var string */
    public $merchant_password;

    /** @var string */
    public $requestBaseUrl;

    /** @var string */
    public $paymentUrl;

    /** @var string */
    public $lifetimeIdUri = '/lifetimeid/create';

    /**
     * These are OPTIONAL the parameters passed by the Merchant via JSON/REST to request for a payment
     *  - MobileNo(Varchar(20) ) :  mobile no of customer
     *  - Param1 (Varchar(80)):  [OPTIONAL] value that will be posted  back to merchant postback/return url when completed
     *  - Param2 (Varchar(80)):  [OPTIONAL] value that will be posted  back to merchant postback/return url when completed
     *  - Expiry (DateTime) : payment expiry period (best effort)
     *  - BillingDetails (BillingInfo) :  billing details of the customer needed for credit card transactions
     *  - RecipientShippingDetails (ShippingInfo) :   shipping details of the  sender needed for COD transactions
     *  - SenderShippingDetails (ShippingInfo) :   shipping details of the  recipient needed for COD transactions
     *  - IpAddress (Varchar(16)) :  IP address of end-user
     *  - UserAgent (Varchar(256) ) :  Browser user agent of enduser
     *
     * @var array
     */
    public $customPaymentOptions = [];


    /** @var string */
    public $prefix = 'FM';

    /** @var int */
    public $refno_enabled=1;

    /** @var int */
    public $lifetime_enabled=1;

    /** @var Customer */
    protected $_customer;

    /** @var Questionnaire */
    protected $_questionnaire;

    /** @var Client */
    protected $_client = 'backend\modules\dragonPay\components\DragonPayHttp';

    /** @var Transport  */
    protected $_transport = 'yii\httpclient\CurlTransport';

    /** @var DragonPayRequest */
    protected $_request;

    /** @var string*/
    protected $_lifetime_id;

    public function init()
    {
        parent::init();
        $settings = DragonpaySettings::find()->first();
        \Yii::configure($this,[
            'merchant_id'=>$settings->merchant_id ?? null,
            'merchant_password'=>$settings->merchant_password ?? null,
            'requestBaseUrl'=>$settings->request_base_url ?? null,
            'paymentUrl'=>$settings->payment_url ?? null,
            'refno_enabled'=>$settings->refno_enabled ?? $this->refno_enabled,
            'lifetime_enabled'=>$settings->lifetime_enabled ?? $this->lifetime_enabled,
        ]);
    }


    /**
     * @return Questionnaire
     * @throws ErrorException
     */
    public function getQuestionnaire()
    {
        if ($this->_questionnaire!==null && !($this->_questionnaire instanceof Questionnaire)) {
            throw new ErrorException('Property Questionnaire must be instance of \'backend\modules\crmfo_kernel_modules\Entity\Questionnaire\', '
                . gettype($this->_questionnaire) . ' given'
            );
        }
        return $this->_questionnaire;
    }

    /**
     * @param Questionnaire $questionnaire
     */
    public function setQuestionnaire(Questionnaire $questionnaire)
    {
        $this->_questionnaire = $questionnaire;
    }


    /**
     * @param $client
     */
    public function setClient($client)
    {
        $this->_client = $client;
    }

    /**
     * @return Customer
     */
    public function getCustomer(): Customer
    {
        if ($this->_customer === null) {
            $this->_customer = $this->questionnaire->customer;
        }
        return $this->_customer;
    }


    /**
     * @throws \yii\base\InvalidConfigException
     */
    public function getClient()
    {
        if (!is_object($this->_client)) {
            $this->_client = \Yii::createObject($this->_client,[
                'transport'=>$this->_transport
            ]);
        }
        return $this->_client;
    }

    /**
     * @return DragonPayRequest
     * @throws \yii\base\InvalidConfigException
     */
    public function getRequest(): DragonPayRequest
    {
        if ($this->_request === null) {
            $this->_request = $this->client->createRequest();
            if ($this->_questionnaire!==null) {
                $this->_request
                    ->setQuestionnaire($this->questionnaire);
            }
            $this->_request->setHeaders($this->getHeaders());
        }
        return $this->_request;
    }

    /**
     * @return mixed
     */
    public function getResponse()
    {
        return $this->client->response;
    }


    /**
     * @return string|null
     */
    protected function getStoredLifetimeId()
    {
        return $this->questionnaire->getDragonPayJournalRequests()->lifetimeId();
    }


    /**
     * @return string|null
     */
    public function getLifetimeId()
    {
        if ($this->_lifetime_id === null) {
            $this->_lifetime_id = $this->getStoredLifetimeId();
        }
        return $this->_lifetime_id;
    }


    /**
     * @return array
     */
    protected function getHeaders():array
    {
        return [
            'Content-Type' =>'application/json',
            'Authorization'=>'Basic ' . base64_encode($this->merchant_id.':'.$this->merchant_password)
        ] ;
    }

    /**
     * Сформирование lifetimeId
     *
     * @throws \yii\httpclient\Exception
     */
    public function createLifetimeId()
    {
        $request = $this->request;
        /** @var DragonPayRequest $request **/
        $request->setMethod('POST')
            ->setUrl($this->requestBaseUrl . $this->lifetimeIdUri)
            ->setTypeOperation(DragonpayJournalRequests::ACTIVATION_OPERATION)
            ->setTypeNumber(DragonpayJournalRequests::TYPE_LIFETIME)
            ->setData([
                'Prefix'=>$this->prefix,
                'Name'=>$this->questionnaire->customer->fullName,
                'Email'=>$this->customer->email,
                'Remarks'=>$this->questionnaire->number,
            ])
            ->send();
    }

    /**
     * Деактивировать lifetimeId
     *
     * @param $lifetimeId
     * @throws \yii\httpclient\Exception
     */
    public function deactivateLifetimeId($lifetimeId)
    {
        $request = $this->request;
        /** @var DragonPayRequest $request **/
        $request->setUrl($this->requestBaseUrl .'/lifetimeid/deactivate/'.$lifetimeId)
            ->setMethod('GET')
            ->setTypeOperation(DragonpayJournalRequests::DEACTIVATION_OPERATION)
            ->setTypeNumber(DragonpayJournalRequests::TYPE_LIFETIME)
            ->send();
    }

    /**
     * Извлечь значение lifetimeId из сервиса
     *
     * @param $lifetimeId
     * @throws \yii\httpclient\Exception
     */
    public function retrieveLifetimeId($lifetimeId)
    {
        $this->request
            ->setUrl($this->requestBaseUrl .'/lifetimeid/'.$lifetimeId)
            ->setTypeNumber(DragonpayJournalRequests::TYPE_LIFETIME)
            ->setNumber($lifetimeId)
            ->setMethod('GET')
            ->send();
    }

    /**
     * История транзакций
     *
     * @param $startDate
     * @param $endDate
     * @throws \yii\httpclient\Exception
     */
    public function transactionHistory($startDate, $endDate)
    {
        $this->request
            ->setUrl($this->requestBaseUrl .'/transactions/'.$startDate .'/' .$endDate)
            ->setMethod('GET')
            ->send();
    }

    /**
     * Запрос на получение списка успешных транзакций
     *
     * @param $startDate
     * @param $endDate
     * @throws \yii\httpclient\Exception
     */
    public function successfullyCompletedTransactionHistory($startDate, $endDate)
    {
        $this->request
            ->setUrl($this->requestBaseUrl .'/transactions/settled/'.$startDate .'/' .$endDate)
            ->setMethod('GET')
            ->send();
    }

    /**
     * фильтрация данных из post запроса
     *
     * @param array $post
     *
     * @return array
     */
    protected function filterPostValues(array $post): array
    {
        $parameters=[];
        $fields = array(
            'txnid' => array(
                'filter' => FILTER_SANITIZE_STRING,
                'filter_flags' => array(FILTER_FLAG_STRIP_LOW),
            ),
            'amount' => array(
                'filter' => FILTER_SANITIZE_NUMBER_FLOAT,
                'filter_flags' => array(FILTER_FLAG_ALLOW_THOUSAND, FILTER_FLAG_ALLOW_FRACTION),
            ),
            'description' => array(
                'filter' => FILTER_SANITIZE_STRING,
                'filter_flags' => array(FILTER_FLAG_STRIP_LOW),
            ),
            'email' => array(
                'filter' => FILTER_SANITIZE_EMAIL,
                'filter_flags' => array(),
            )
        );

        foreach ($fields as $key => $value) {
            if (isset($post[$key])) {
                $parameters[$key] = filter_var($post[$key],$value['filter'], $value['filter_flags']);
                if ($key==='amount')
                {
                    $parameters[$key] =  number_format($parameters[$key], 2, '.', '');
                }
            }
        }
        return $parameters;
    }


    /**
     * The digest is computed using the SHA1 algorithm.
     *
     * @param $txnId
     * @param $amount
     * @param $currency
     * @param $description
     * @param $email
     *
     * @return string
     */
    public function getSHA1DigestForPayment($txnid,$amount,$currency,$description,$email): string
    {
        $data=[
            'txnid'       => $txnid,
            'amount'      => $amount,
            'description' => $description,
            'email'       => $email,
        ];
        $data  = $this->filterPostValues($data);

        $digest_string = implode(':', [
            $this->merchant_id,
            $data['txnid'],
            $data['amount'],
            $currency,
            $data['description'],
            $data['email'],
            $this->merchant_password
        ]);
        return sha1($digest_string);
    }


    /**
     * Check response SHA1Digest
     *
     * @param array $post
     *
     * @return boolean
     */
    public function isPostBackSHA1DigestCorrect($post): bool
    {
        $digest_string = implode(':', [
            $post['txnid'],
            $post['refno'],
            $post['status'],
            $post['message'],
            $this->merchant_password
        ]);
        return sha1($digest_string) === $post['digest'];
    }


    /**
     * The merchant can programmatically inquire the status of a transaction by using this
     * function.
     * @param string $refno A unique Dragonpay refno assigned to the
     * specific transaction from the merchant side
     * @throws \yii\httpclient\Exception
     * @see also [[transactionStatusInquiryByTxnid]]
     */
    public function transactionStatusInquiryByRef(string $refno)
    {
        $this->request
            ->setUrl($this->requestBaseUrl .'/refno/'.$refno)
            ->setNumber($refno)
            ->setMethod('GET')
            ->send();
    }


    /**
     * Alternatively, if you do not have the Dragonpay refno,
     * you may use the merchantassigned transaction id using this endpoint.
     *
     * @param string $txnid A unique id identifying this specific transaction
     * from the merchant side
     * @throws \yii\httpclient\Exception
     */
    public function transactionStatusInquiryByTxnid(string $txnid)
    {
        $this->request
            ->setUrl($this->requestBaseUrl .'/settled/'.$txnid)
            ->setMethod('GET')
            ->send();
    }


    /**
     * A unique id identifying this specific transaction
     * from the merchant side
     * @return string
     */
    protected function getTxnid():string
    {
        return $this->questionnaire->number .'-'. time();
    }

    /**
     * @return string
     */
    protected function getDefaultPaymentDescription(): string
    {
        return $this->questionnaire->number;
    }


    /**
     * These are the parameters passed by the Merchant via JSON/REST to request for a payment
     * to be collected. Make sure to set the header Content-Type to application/json.
     * You may keep the ProcId blank if you want the user to perform the selection at
     * Dragonpay’s side (recommended). If you wish to pre-select the channel, please
     * refer further to Section 5.4 of this document on advanced controls.
     * For credit card transactions, merchant system must pass the BillingDetails field. For
     * Cash on Delivery (COD) transactions, merchant system must pass the sender and
     * recipient shipping addresses.
     *
     * Endpoint: <baseurl>/<txnid>/post
     *
     * @param $amount
     * @param null $description
     * @param null $email
     * @param string $currency
     * @param null $procId
     */
    public function requestingPayment($amount, $description = null , $email = null,  $currency = "PHP", $procId=null)
    {
        $data = ArrayHelper::merge($this->customPaymentOptions,[
            'Amount'=>$amount,
            'Currency'=>$currency,
            'Description'=>$description ?? $this->getDefaultPaymentDescription(),
            'Email'=>$email ?? $this->questionnaire->customer->email,
            'ProcId'=>$procId
        ]);
        $this->request
            ->setUrl($this->requestBaseUrl . '/' .$this->getTxnid() .'/post')
            ->setMethod('POST')
            ->setTypeNumber(DragonpayJournalRequests::TYPE_REFNUMBER)
            ->setTypeOperation(DragonpayJournalRequests::ACTIVATION_OPERATION)
            ->setData($data)
            ->send();
    }


    /**
     * Метод возвращает ссылку для редиректа
     * Пользователя на сторону плтежной системы DP.
     * Данный метод используется для проведения платежа через набор ГЕТ-параметров
     *
     * @param        $amount
     * @param        $description
     * @param null   $email
     * @param string $currency
     * @param null   $procId
     * @param null   $param1
     * @param null   $param2
     *
     * @return string
     */
    public function createPaymentRequestUrl($amount, $description = null , $email = null,  $currency = "PHP", $procId=null, $param1=null, $param2=null): string
    {
        $email = $email ?? $this->questionnaire->customer->email;
        $param2 = $param2 ?? $this->questionnaire->number;
        $description = $description ?? $this->getDefaultPaymentDescription();
        $uriData = [
            'amount'=>$amount,
            'ccy'=>$currency,
            'description'=>$description,
            'email'=>$email,
            'merchantid'=>$this->merchant_id,
            'txnid'=>$this->getTxnid(),
            'procid'=>$procId,
            'param1'=>$param1,
            'param2'=>$param2,
            'digest'=>$this->getSHA1DigestForPayment($this->getTxnid(),$amount,$currency,$description,$email)
        ];

        return  $this->paymentUrl .'?'. http_build_query($uriData);
    }

    /**
     *
     * It is recommended that the GetAvailableProcessors web service be invoked by
     * a scheduled cron job every 30 mins to every hour with amount = -1000.
     * While the field values generally will not change, the status can change during
     * the day for various reasons. For example, a bank partner may have an
     * unscheduled downtime. If Merchant does not refresh its internal copy of this
     * list, it may think the channel is still active whereas it has already been
     * deactivated temporarily (or permanently) on Dragonpay’s side.
     *
     * @param int $amount amount The amount of the transaction
     * @throws \yii\httpclient\Exception
     */
    public function createAvailableProcessors($amount=-1000)
    {
        $this->request
            ->setUrl($this->requestBaseUrl .'/processors/available/'.$amount)
            ->setStoreContentPlace(self::STORE_LIST_CHANNELS)
            ->setMethod('GET')
            ->send();
    }


    /**
     * @return DragonpayListChannels[]
     * @throws \yii\db\Exception
     */
    public function getAvailableProcessors()
    {
        return DragonpayListChannels::findAll((new DragonpayListChannels())
                ->getAvailablePaymentChannelsIds());
    }


    /**
     * The merchant can programmatically cancel a pending transaction by using this
     * function.
     *
     * @param string $txnid A unique id identifying this specific transaction
     * from the merchant side
     * @throws \yii\httpclient\Exception
     */
    public function cancellationOfTransaction(string $txnid)
    {
        $this->request
            ->setUrl($this->requestBaseUrl .'/void/'.$txnid)
            ->setMethod('GET')
            ->send();
    }


    /**
     * @param $number
     *
     * @return DragonPayComponent
     * @throws ErrorException
     */
    public static function initByNumber($number)
    {
        $journal = DragonpayJournalRequests::find()->where(['number'=>$number])->last();
        if (!$journal) {
            throw new ErrorException('The journal record is not exists. Can not detect questionnaire');
        }
        return new self([
            'questionnaire'=>(Questionnaire::findOne($journal->questionnaire_id) ?? Questionnaire::findOne(['number'=>$number]))
                        ]);
    }


    /**
     * Сохранение списка каналов, запрашиваемых из системы dragonpay
     * с заданной периодичностью
     *
     * @param array $data
     * @throws \yii\db\Exception
     */
    public static function storeListAndStatusChannels(array $data)
    {
        $connection = Yii::$app->db;
        $transaction = $connection->beginTransaction();
        try
        {
            foreach($data as $listChannel)
            {
                $listChannelParam = ['procid'=>$listChannel['procId']];
                $listChannelObj = DragonpayListChannels::findOne($listChannelParam) ??  new DragonpayListChannels($listChannelParam);
                if ($listChannelObj->isNewRecord) {
                    $listChannelObj->save();
                }
                if ($listChannelObj->hasErrors()) {
                    Yii::error(json_encode($listChannelObj->errors), DragonPayModule::SENTRY_CATEGORY_ERROR);
                } else {
                    $statusChannelObj = $listChannelObj->dragonpayStatusChannel ?? new DragonpayStatusChannels([
                            'list_channel_id'=>$listChannelObj->id,
                        ]);
                    $statusChannelObj->setAttributes([
                        'shortname'=>$listChannel['shortName'],
                        'longname'=>$listChannel['longName'],
                        'logo'=>$listChannel['logo'],
                        'currencies'=>$listChannel['currencies'],
                        'type'=>$listChannel['type'],
                        'status'=>$listChannel['status'],
                        'remarks'=>$listChannel['remarks'],
                        'dayofweek'=>$listChannel['dayOfWeek'],
                        'starttime'=>$listChannel['startTime'],
                        'endtime'=> $listChannel['endTime'],
                        'minamount'=>$listChannel['minAmount'],
                        'maxamount'=>$listChannel['maxAmount'],
                        'mustredirect'=>$listChannel['mustRedirect'],
                        'surcharge'=>$listChannel['surcharge'],
                        'hasaltrefno'=>$listChannel['hasAltRefNo'],
                    ]);
                    $statusChannelObj->save();
                    if ($statusChannelObj->hasErrors()) {
                        Yii::error(json_encode($statusChannelObj->errors), DragonPayModule::SENTRY_CATEGORY_ERROR);
                    }
                }
            }
        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::error( $e->getMessage(),DragonPayModule::SENTRY_CATEGORY_ERROR);
        }
        $transaction->commit();
    }
}
