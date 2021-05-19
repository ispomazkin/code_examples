<?php

namespace backend\modules\dragonPay\models;

use Yii;
use backend\modules\crmfo_kernel_modules\Entity\Customer;
use backend\modules\crmfo_kernel_modules\Entity\Questionnaire;

/**
 * Модель таблицы лога запросов dragonpay
 *
 * @property int $id
 * @property int|null $customer_id id клиента
 * @property int|null $questionnaire_id id заявки
 * @property string|null $request тело запроса
 * @property string|null $response тело ответа
 * @property string|null $type_operation тип операции (создание/деактивация)
 * @property string|null $type_number Тип реферального номера lifetimeID или refnumber
 * @property string|null $number Значение реферального номера
 * @property string|null $request_time Время выполнения запроса
 * @property string|null $redirect_url УРЛ, на который необходимо перенаправить пользователя для проведения оплаты
 * @property string|null $message Содержит доп информациб по ответу, если статус F
 * @property string|null $status Статус инициализации запроса на оплату S или F
 * @property string|null $created_at
 *
 * @property Customer $customer
 * @property Questionnaire $questionnaire
 */
class DragonpayJournalRequests extends \yii\db\ActiveRecord
{
    const TYPE_LIFETIME='lifetimeID';

    const TYPE_REFNUMBER ='refnumber';

    const ACTIVATION_OPERATION='activation';

    const DEACTIVATION_OPERATION='deactivation';


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%dragonpay_journal_requests}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['customer_id', 'questionnaire_id'], 'default', 'value' => null],
            [['customer_id', 'questionnaire_id'], 'integer'],
            [['number'],'filter','filter'=>function($value){
                return trim($value,'"');
            }],
            [['request', 'response', 'request_time', 'created_at'], 'safe'],
            [['type_operation', 'type_number', 'number','redirect_url'], 'string', 'max' => 255],
            [['message'], 'string', 'max' => 128],
            [['status'], 'string', 'max' => 1],
            ['type_operation','in','range'=>[self::ACTIVATION_OPERATION, self::DEACTIVATION_OPERATION]],
            ['type_number','in','range'=>[self::TYPE_LIFETIME, self::TYPE_REFNUMBER]],
            [['customer_id'], 'exist', 'skipOnError' => true, 'targetClass' => Customer::className(), 'targetAttribute' => ['customer_id' => 'id']],
            [['questionnaire_id'], 'exist', 'skipOnError' => true, 'targetClass' => Questionnaire::className(), 'targetAttribute' => ['questionnaire_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'customer_id' => Yii::t('app','id клиента'),
            'questionnaire_id' => Yii::t('app','id заявки'),
            'request' => Yii::t('app','тело запроса'),
            'response' => Yii::t('app','тело ответа'),
            'type_operation' => Yii::t('app','тип операции (создание/деактивация)'),
            'type_number' => Yii::t('app','Тип реферального номера lifetimeID или refnumber'),
            'number' => Yii::t('app','Значение реферального номера'),
            'request_time' => Yii::t('app','Время выполнения запроса'),
            'created_at' => Yii::t('app','Создан'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCustomer()
    {
        return $this->hasOne(Customer::className(), ['id' => 'customer_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getQuestionnaire()
    {
        return $this->hasOne(Questionnaire::className(), ['id' => 'questionnaire_id']);
    }

    /**
     * {@inheritdoc}
     * @return DragonpayJournalRequestsQuery
     */
    public static function find()
    {
        return new DragonpayJournalRequestsQuery(get_called_class());
    }
}
