<?php

namespace backend\modules\dragonPay\models;

use backend\modules\dragonPay\components\DragonPayComponent;
use Yii;
use yii\base\ErrorException;

/**
 * Модель для таблицы списка транзакций системы dragonpay
 *
 * @property int $id
 * @property string|null $response тело ответ
 * @property string|null $txnid id транзакции
 * @property string|null $type_number Тип реферального номера lifetimeID или refnumber
 * @property string|null $number Значение реферального номера
 * @property string|null $status код операции (appendix 3)
 * @property string|null $message код операции (appendix 2)
 * @property int|null $list_channel_id id канала
 * @property number|null $amount Сумма к погашению
 * @property string | null $questionnaire_number Номер заявки
 * @property string|null $created_at
 * @property string|null $updated_at
 *
 * @property DragonpayListChannels $listChannel
 */
class DragonpayPayments extends \yii\db\ActiveRecord
{
    /** @var string*/
    public $refno;
    /** @var string*/
    public $digest;
    /** @var string*/
    public $procid;
    /** @var string*/
    public $ccy;

    const SUCCESS_OPERATION_STATUS='S';

    const SCENARIO_LOAD_API_DATA='api';

    const SCENARIO_SAVE_PAYMENT='save';

    /**
     * @return bool
     * @throws ErrorException
     */
    public function beforeValidate()
    {
        if (!in_array($this->scenario,[
            self::SCENARIO_LOAD_API_DATA,
            self::SCENARIO_SAVE_PAYMENT
        ])) {
            throw new ErrorException('You did not set the scenario');
        }
        return parent::beforeDelete();
    }


    /**
     * @param bool $insert
     *
     * @return bool
     * @throws ErrorException
     */
    public function beforeSave($insert)
    {
        if ($this->scenario!==self::SCENARIO_SAVE_PAYMENT) {
            throw new ErrorException('You did not set the correct scenario');
        }
        return parent::beforeSave($insert);
    }


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%dragonpay_payments}}';
    }

    /**
     * @param $attr
     */
    public function validateDigest($attr)
    {
        $component = new DragonPayComponent();
        if (!$component->isPostBackSHA1DigestCorrect(
            [
                'txnid'   => $this->txnid,
                'refno'   => $this->refno,
                'status'  => $this->status,
                'message' => $this->message,
                'digest'  => $this->digest,
            ]
        )) {
            return $this->addError($attr,'Invalid check sum');
        }
    }


    /**
     * @param $attr
     */
    public function validateStatus($attr)
    {
        if ($this->$attr!==self::SUCCESS_OPERATION_STATUS) {
            return $this->addError($attr,'Invalid status');
        }
    }


    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            /** SCENARIO_LOAD_API_DATA */
            [['txnid','refno','status','message','digest','amount'],'required','on'=>self::SCENARIO_LOAD_API_DATA],
            [['txnid','refno','status','message','digest','ccy','procid', 'questionnaire_number'],'string','on'=>self::SCENARIO_LOAD_API_DATA],
            ['digest','validateDigest','on'=>self::SCENARIO_LOAD_API_DATA],
            ['status','validateStatus','on'=>self::SCENARIO_LOAD_API_DATA],

            /** SCENARIO_SAVE_PAYMENT */
            [['txnid', 'number','status','message'], 'required', 'on' => self::SCENARIO_SAVE_PAYMENT],
            [['list_channel_id'], 'integer', 'on' => self::SCENARIO_SAVE_PAYMENT],
            [['txnid', 'type_number', 'number', 'status', 'message','response','questionnaire_number'], 'string', 'max' => 255, 'on' => self::SCENARIO_SAVE_PAYMENT],
            [['list_channel_id'], 'exist', 'skipOnError' => true,
                                           'targetClass' => DragonpayListChannels::className(),
                                           'targetAttribute' => ['list_channel_id' => 'id'],
                 'on' => self::SCENARIO_SAVE_PAYMENT
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'response' => \Yii::t('app','тело ответ'),
            'txnid' => \Yii::t('app','id транзакции'),
            'type_number' => \Yii::t('app','Тип реферального номера lifetimeID или refnumber'),
            'number' => \Yii::t('app','Значение реферального номера'),
            'status' => \Yii::t('app','код операции (appendix 3)'),
            'message' => \Yii::t('app','код операции (appendix 2)'),
            'list_channel_id' => \Yii::t('app','id канала'),
            'created_at' => \Yii::t('app','Создан'),
            'updated_at' => \Yii::t('app','Обновлен'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getListChannel()
    {
        return $this->hasOne(DragonpayListChannels::className(), ['id' => 'list_channel_id']);
    }

    /**
     * Получить список ИД каналов по ИД процесса
     */
    public function getListChannelIdFromProcId()
    {
        $this->list_channel_id = DragonpayListChannels::find()
            ->select('id')
             ->where(['procid'=>$this->procid])
             ->scalar();
    }

    /**
     * Получить номер заявки из номера транзакции
     */
    public function getNumberFromTxnid()
    {
        $parts = explode('-', $this->txnid);
        $this->number = $parts[0];
    }
}
