<?php

namespace common\modules\pay\models;

/**
 * Модель для таблицы "pay_journal_request",
 * содержащей логи запросов модуля pay
 *
 * @property int $id
 * @property string $driver_name Название драйвера
 * @property string|null $start_time Время начала запроса
 * @property string|null $end_time Время окончания запроса
 * @property string|null $http_request_url Урл Запроса
 * @property string|null $http_request_body Тело запроса
 * @property string|null $http_request_type Тип Запроса (GET/POST и пр)
 * @property string|null $http_response_body Тело ответа
 * @property int|null $http_response_status Статус ответа (200/400 и пр)
 * @property string|null $operation_type Тип операции (погашение/выдача и др)
 *
 * @property PayDriverList $payDriverList
 */
class PayJournalRequest extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%pay_journal_request}}';
    }

    public function init()
    {
        $this->start_time = $this->getCurrentDateTime();
        parent::init();
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['driver_name', 'http_response_status'], 'default', 'value' => null],
            [['http_response_status'], 'integer'],
            [['start_time', 'end_time', 'http_request_body', 'http_response_body'], 'safe'],
            [['http_request_body', 'http_response_body'],'filter','filter'=>function($data){
                return json_decode($data) === null ? json_encode($data) : $data;
            }],
            [['http_request_url', 'http_request_type', 'operation_type','driver_name'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'driver_name' => 'driver_name',
            'start_time' => 'Start Time',
            'end_time' => 'End Time',
            'http_request_url' => 'Http Request Url',
            'http_request_body' => 'Http Request Body',
            'http_request_type' => 'Http Request Type',
            'http_response_body' => 'Http Response Body',
            'http_response_status' => 'Http Response Status',
            'operation_type' => 'Operation Type',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPayDriverList()
    {
        return $this->hasOne(PayDriverList::class, ['driver_name' => 'driver_name']);
    }

    /**
     * @param bool $insert
     * @return bool
     */
    public function beforeSave($insert)
    {
        if ($insert && !$this->end_time) {
            $this->end_time = $this->getCurrentDateTime();
        }
        return parent::beforeSave($insert);
    }

    /**
     * @return false|string
     */
    public function getCurrentDateTime()
    {
        return date('Y-m-d H:i:s', time());
    }

}
