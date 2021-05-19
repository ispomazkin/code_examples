<?php

namespace backend\modules\claim_crosses\models;

use Yii;
use common\models\Nomenclature;

/**
 * Модель для таблицы списка жалоб на аналог
 *
 * @property int $id
 * @property string|null $hash Хеш позиции, на которую ссылается кросс
 * @property string|null $claimed_hash Кросс
 * @property string|null $user_login_guid ИД пользователя, подавщего жалобу
 * @property string|null $comment Сопровождающее письмо
 * @property int|null $is_approved Признак одобрено/отклонено
 * @property string|null $created_at
 * @property string|null $updated_at
 *
 * @property Nomenclature $nomenclature
 */
class ClaimCrosses extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'claim_crosses';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['is_approved'], 'default', 'value' => null],
            [['is_approved'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['hash', 'claimed_hash', 'user_login_guid', 'comment'], 'string', 'max' => 255],
            [['hash', 'claimed_hash'], 'unique', 'targetAttribute' => ['hash', 'claimed_hash']],
            [['hash'], 'exist', 'skipOnError' => true, 'targetClass' => Nomenclature::className(), 'targetAttribute' => ['hash' => 'hash']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'hash' => 'Hash',
            'claimed_hash' => 'Claimed Hash',
            'user_login_guid' => 'User Login Guid',
            'comment' => 'Comment',
            'is_approved' => 'Is Approved',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * Gets query for [[Hash0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getNomenclature()
    {
        return $this->hasOne(Nomenclature::className(), ['hash' => 'hash']);
    }
}
