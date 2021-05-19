<?php

namespace backend\modules\dragonPay\models;

/**
 * Кастомизация запросов для модели DragonpayJournalRequests
 */
class DragonpayJournalRequestsQuery extends \yii\db\ActiveQuery
{

    /**
     * {@inheritdoc}
     * @return DragonpayJournalRequests[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * {@inheritdoc}
     * @return DragonpayJournalRequests|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }

    /**
     * @return string|null
     */
    public function lifetimeId()
    {
        return $this->andWhere(['type_number'=>DragonpayJournalRequests::TYPE_LIFETIME])
            ->select('number')
            ->orderBy('id DESC')
            ->scalar();
    }

    /**
     * @return DragonpayJournalRequests|null
     */
    public function last()
    {
        return $this->orderBy('id desc')->limit(1)->one();
    }
}
