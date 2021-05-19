<?php

namespace backend\modules\dragonPay\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * Модель для таблицы списка каналов оплаты системы dragonpay
 *
 * @property int $id
 * @property int|null $list_channel_id id канала таблицы list
 * @property string|null $shortname короткое наименование канала
 * @property string|null $longname полное наименование канала
 * @property string|null $logo url логотипа канала
 * @property string|null $currencies валюта
 * @property int|null $type bitmask
 * @property string|null $status доступность канала
 * @property string|null $remarks описание канала
 * @property string|null $dayofweek дни работы канала оплаты
 * @property string|null $starttime время начала работы канала
 * @property string|null $endtime время окончания работы канала
 * @property float|null $minamount минимальная сумма для оплаты через канал
 * @property float|null $maxamount максимальная сумма для оплаты через канал
 * @property bool|null $mustredirect признак возможности редиректа на сайт канала
 * @property float|null $surcharge коммиссия
 * @property bool|null $hasaltrefno признак наличия альтернативного refnumber при оплате
 * @property string|null $created_at
 * @property string|null $updated_at
 *
 * @property DragonpayListChannels $listChannel
 */
class DragonpayStatusChannels extends \yii\db\ActiveRecord
{
    const MIDNIGHT_FLAG='00:00';

    const ONE_MINUTE_TO_MIDNIGHT='23:59';

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
     * @return array
     */
    public function fields(): array
    {
        return [
            'shortname',
            'longname',
            'logo',
            'currencies',
            'type',
            'status',
            'remarks',
            'minamount',
            'maxamount',
            'dayofweek',
            'starttime',
            'endtime',
        ];
    }

    public function afterFind()
    {
        $this->minamount = floatval($this->minamount);
        $this->maxamount = floatval($this->maxamount);
        parent::afterFind();
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%dragonpay_status_channels}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['list_channel_id', 'type'], 'default', 'value' => null],
            [['list_channel_id', 'type'], 'integer'],
            [['minamount', 'maxamount', 'surcharge'], 'number'],
            [['mustredirect', 'hasaltrefno'], 'boolean'],
            [['shortname', 'longname', 'logo', 'currencies', 'status', 'dayofweek', 'starttime', 'endtime'], 'string', 'max' => 255],
            [['remarks'], 'string'],
            [['list_channel_id'], 'exist', 'skipOnError' => true, 'targetClass' => DragonpayListChannels::className(), 'targetAttribute' => ['list_channel_id' => 'id']],
        ];
    }


    /**
     * If startTime=endTime, then this procId is available 24-hrs a day. If
     * endTime is “00:00”, but startTime is not “00:00”, then endTime should be
     * interpreted as the stroke of midnight.
     *
     * @param bool $insert
     *
     * @return bool
     */
    public function beforeSave($insert)
    {
        if ($this->endtime == self::MIDNIGHT_FLAG && $this->starttime != self::MIDNIGHT_FLAG) {
            $this->endtime = self::ONE_MINUTE_TO_MIDNIGHT;
        }
        return parent::beforeSave($insert);
    }


    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'list_channel_id' => Yii::t('app','id канала таблицы list'),
            'shortname' => Yii::t('app','короткое наименование канала'),
            'longname' => Yii::t('app','полное наименование канала'),
            'logo' => Yii::t('app','url логотипа канала'),
            'currencies' => Yii::t('app','валюта'),
            'type' => Yii::t('app','bitmask'),
            'status' =>Yii::t('app', 'доступность канала'),
            'remarks' =>Yii::t('app', 'описание канала'),
            'dayofweek' => Yii::t('app','дни работы канала оплаты'),
            'starttime' => Yii::t('app','время начала работы канала'),
            'endtime' => Yii::t('app','время окончания работы канала'),
            'minamount' =>Yii::t('app', 'минимальная сумма для оплаты через канал'),
            'maxamount' => Yii::t('app','максимальная сумма для оплаты через канал'),
            'mustredirect' => Yii::t('app','признак возможности редиректа на сайт канала'),
            'surcharge' => Yii::t('app','коммиссия'),
            'hasaltrefno' => Yii::t('app','признак наличия альтернативного refnumber при оплате'),
            'created_at' => Yii::t('app','Создан'),
            'updated_at' => Yii::t('app','Обновлен'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getListChannel()
    {
        return $this->hasOne(DragonpayListChannels::className(), ['id' => 'list_channel_id']);
    }

}
