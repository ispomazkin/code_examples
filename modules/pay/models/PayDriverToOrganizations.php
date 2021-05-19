<?php

namespace common\modules\pay\models;

use backend\modules\multi_organization\entity\Organizations;

/**
 * Модель для таблицы "pay_driver_to_organizations".
 *
 * @property int $id
 * @property int|null $pay_driver_list_id ИД таблицы pay_driver_list
 * @property int|null $organization_id ИД таблицы sc_organizations
 *
 * @property Organizations $organization
 * @property PayDriverList $payDriverList
 */
class PayDriverToOrganizations extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%pay_driver_to_organizations}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['pay_driver_list_id', 'organization_id'], 'default', 'value' => null],
            [['pay_driver_list_id', 'organization_id'], 'integer'],
            [['pay_driver_list_id', 'organization_id'], 'unique', 'targetAttribute' => ['pay_driver_list_id', 'organization_id']],
            [['pay_driver_list_id'], 'exist', 'skipOnError' => true, 'targetClass' => PayDriverList::className(), 'targetAttribute' => ['pay_driver_list_id' => 'id']],
            [['organization_id'], 'exist', 'skipOnError' => true, 'targetClass' => Organizations::className(), 'targetAttribute' => ['organization_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'pay_driver_list_id' => 'Pay Driver List ID',
            'organization_id' => 'Organization ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrganization()
    {
        return $this->hasOne(Organizations::className(), ['id' => 'organization_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPayDriverList()
    {
        return $this->hasOne(PayDriverList::className(), ['id' => 'pay_driver_list_id']);
    }
}
