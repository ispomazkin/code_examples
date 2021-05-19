<?php

namespace backend\modules\dragonPay\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * Модель для таблицы списка настроек системы dragonpay
 *
 * @property int $id
 * @property string|null $merchant_id Логин в системе DP
 * @property string|null $merchant_password Пароль в системе DP
 * @property string|null $payment_url      Путь для оплаты
 * @property string|null $request_base_url Базовй путь апи
 * @property string|null $logo_url Урл логотипа
 * @property string|null $documentation_url Урл документации
 * @property int|null $lifetime_enabled Включена ли интеграция по lifetimeid 0|1
 * @property int|null $refno_enabled Включена ли интеграция по refno 0|1
 * @property string|null $created_at
 * @property string|null $updated_at
 *
 */
class DragonpaySettings extends \yii\db\ActiveRecord
{

    const ENABLED=1;

    const DISABLED=0;

    /**
     * @return array
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::className(),
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%dragonpay_settings}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['refno_enabled', 'lifetime_enabled'], 'integer'],
            [['refno_enabled', 'lifetime_enabled'], 'in','range'=>[self::ENABLED, self::DISABLED]],
            [['refno_enabled', 'lifetime_enabled'], 'default','value'=>self::ENABLED],
            [['merchant_id', 'merchant_password','payment_url','request_base_url','logo_url','documentation_url'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'refno_enabled' => Yii::t('app','Интеграция по refno'),
            'lifetime_enabled' => Yii::t('app','Интеграция по lifetime'),
            'merchant_id' => Yii::t('app','Логин'),
            'merchant_password' => Yii::t('app','Пароль'),
            'payment_url' => Yii::t('app','Путь для оплаты'),
            'request_base_url' => Yii::t('app','Базовый путь апи'),
            'logo_url' => Yii::t('app','УРЛ логотипа'),
            'documentation_url' => Yii::t('app','УРЛ документации'),
            'created_at' => Yii::t('app','Дата создания'),
            'updated_at' => Yii::t('app','Дата обновления'),
        ];
    }

    /**
     * {@inheritdoc}
     * @return DragonpaySettingsQuery
     */
    public static function find()
    {
        return new DragonpaySettingsQuery(get_called_class());
    }

    /**
     * Получить модель настроек
     *
     * @return static
     */
    public static function getModel(): self
    {
        return  DragonpaySettings::find()->first() ?? new self([
                'lifetime_enabled' => DragonpaySettings::ENABLED,
                'refno_enabled' => DragonpaySettings::ENABLED
            ]);
    }
}
