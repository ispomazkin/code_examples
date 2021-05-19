<?php

namespace backend\modules\dragonPay\models;

use backend\modules\dragonPay\DragonPayModule;
use Yii;
use yii\db\Expression;

/**
 * Модель для упралвения списком каналов dragonpay
 *
 * @property int $id
 * @property string|null $procid уникальный код канала
 * @property string|null $created_at
 * @property string|null $updated_at
 *
 * @property DragonpayPayments[] $dragonpayPayments
 * @property DragonpayStatusChannels $dragonpayStatusChannel
 * @property-read DragonpayStatusChannels $info
 */
class DragonpayListChannels extends \yii\db\ActiveRecord
{

    /** @var DragonpayStatusChannels */
    protected $_info;
    /** @var boolean */
    protected $_is_available;
    /** @var array */
    protected static $_available_channel_ids;

    /**
     * @return DragonpayStatusChannels|null
     */
    public function getInfo()
    {
        if ($this->_info === null) {
            $this->_info = $this->dragonpayStatusChannel;
        }
        return $this->_info;
    }

    /**
     * @return bool
     * @throws \yii\db\Exception
     */
    public function getIs_Available():bool
    {
        if ($this->_is_available === null) {
            $this->_is_available = in_array($this->id, $this->getAvailablePaymentChannelsIds());
        }
        return $this->_is_available;
    }



    /**
     * @return array
     */
    public function fields(): array
    {
        return [
            'procid',
            'is_available',
            'info'
        ];
    }


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%dragonpay_list_channels}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['procid'], 'required'],
            [['procid'], 'string', 'max' => 255],
            [['procid'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'procid' => \Yii::t('app','уникальный код канала'),
            'created_at' => \Yii::t('app','Создан'),
            'updated_at' => \Yii::t('app','Обновлен'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDragonpayPayments()
    {
        return $this->hasMany(DragonpayPayments::className(), ['list_channel_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDragonpayStatusChannel()
    {
        return $this->hasOne(DragonpayStatusChannels::className(), ['list_channel_id' => 'id']);
    }


    /**
     * Возвращает строку для выборки списка каналов по дням
     * @return string
     */
    public function calculateDayComparisonString(): string
    {
        $defaultString = array_map(function(){
            return '_';
        },range(0,6));
        $key = date('N');
        $defaultString[$key]='x';
        return implode('',$defaultString);
    }


    /**
     * Метод возвращает список id доступных каналов для
     * оплаты на текущий момент
     *
     * @return array
     * @throws \yii\db\Exception
     */
    public function getAvailablePaymentChannelsIds(): array
    {
        if (!is_array(self::$_available_channel_ids)) {
            $sql=<<<SQL
select
	"channel" . id
from
	"dragonpay_list_channels" "channel"
inner join "dragonpay_status_channels" "status" on
	status.list_channel_id = channel.id
where
	status.dayofweek ilike :allowedDayString
	and ( ("starttime" = :midnight
	and "endtime" = :midnight)
	or (endtime <> starttime
	and (now()>concat(current_date, ' ', starttime::time)::timestamp
	and now()<concat(current_date, ' ', endtime::time)::timestamp)) )
SQL;
            self::$_available_channel_ids =  Yii::$app->db->createCommand($sql,[
                ':midnight'=>DragonpayStatusChannels::MIDNIGHT_FLAG,
                ':allowedDayString'=>$this->calculateDayComparisonString()
            ])->queryColumn();
        }
        return self::$_available_channel_ids;
    }

}
