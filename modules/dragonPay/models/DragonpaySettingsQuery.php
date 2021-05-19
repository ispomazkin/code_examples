<?php

namespace backend\modules\dragonPay\models;

/**
 * Класс для кастомизации запросов модели DragonpaySettings
 */
class DragonpaySettingsQuery extends \yii\db\ActiveQuery
{
    /**
     * @param null $db
     *
     * @return DragonpaySettings|null
     */
    public function first($db = null)
    {
        return parent::all($db)[0] ?? null;
    }

    /**
     * {@inheritdoc}
     * @return DragonpaySettings[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * {@inheritdoc}
     * @return DragonpaySettings|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
