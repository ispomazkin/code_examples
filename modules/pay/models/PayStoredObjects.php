<?php

namespace common\modules\pay\models;

use common\modules\pay\components\Pay;
use common\modules\pay\components\PayPseudoDriver;

/**
 * Модель для таблицы pay_stored_objects, в которой хранятся сериализованные объекты
 * драйверов и модуля pay. Сериализация необходима для восстановления объекта
 * и использовании его в другом процессе, где модуль не инициализировался. Например, при выдаче денежных средств -
 * через модуль производится запрос к платежной системе, а через некоторое время
 * приходят уведомления.
 *
 * @property int $id
 * @property int|null $questionnaire_id
 * @property string|null $data
 * @property string|null $created_at
 */
class PayStoredObjects extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%pay_stored_objects}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['questionnaire_id'], 'default', 'value' => null],
            [['questionnaire_id'], 'integer'],
            [['data'], 'string'],
            [['created_at'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'questionnaire_id' => 'Questionnaire ID',
            'data' => 'Data',
            'created_at' => 'Created At',
        ];
    }

    /**
     * Сериализация объекта модуля pay или драйвера
     *
     * @param $obj Pay | PayPseudoDriver
     * @param int $questionnaire_id
     */
    public static function storeObj($obj,int $questionnaire_id) {
        $data = base64_encode(serialize($obj));
        (new self([
            'data' => $data,
            'questionnaire_id' => $questionnaire_id
        ]))->save();
    }

    /**
     * @param int $questionnaire_id
     * @return mixed
     */
    public static function restoreObj(int $questionnaire_id) {
        $payStoredObject =  self::find()->where(['questionnaire_id' => $questionnaire_id])
            ->orderBy('id desc')->limit(1)->one();
        /** @var $payStoredObject self */
        $data = unserialize(base64_decode($payStoredObject->data));
        return $data;
    }
}