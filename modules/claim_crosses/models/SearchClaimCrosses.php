<?php

namespace backend\modules\claim_crosses\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\modules\claim_crosses\models\ClaimCrosses;
use yii\db\Expression;

/**
 * SearchClaimCrosses represents the model behind the search form about `backend\modules\claim_crosses\models\ClaimCrosses`.
 */
class SearchClaimCrosses extends ClaimCrosses
{

    public $failed_cross;

    public $full_user_name;
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'is_approved'], 'integer'],
            [['failed_cross','full_user_name','comment'],'string'],
            [['created_at', 'updated_at'], 'safe'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'failed_cross' => 'Жалоба на крос',
            'comment' => 'Комментарий',
            'full_user_name' => 'Инициатор',
            'is_approved' => 'Статус',
            'created_at' => 'Дата подачи запроса',
            'updated_at' => 'Рассмотрен',
        ];
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($dataProvider, $params)
    {
        $query = self::find()
            ->select([
                'cc.id',
                'cc.comment',
                'cc.is_approved',
                'cc.created_at',
                'cc.updated_at',
                new Expression('concat(n1.article,\' \',  n1.brand, \' =/= \', n2.article, \' \', n2.brand) as failed_cross'),
                new Expression('concat(u.username,\' (\',  u.name1c, \' )\') as full_user_name'),
            ])
            ->from('claim_crosses cc')
            ->innerJoin('nomenclature n1','n1.hash=cc.hash')
            ->innerJoin('nomenclature n2','n2.hash=cc.claimed_hash')
            ->leftJoin('user u','u.login_guid=cc.user_login_guid');

        $this->load($params);

        $withQuery = (new \yii\db\Query())
            ->select('*')
            ->from('claim_crosses')
            ->withQuery(clone($query), 'claim_crosses');

        $dataProvider->query = $withQuery;

        //сортировка по связанным полям
        $dataProvider->setSort([
            'defaultOrder'=>[
                'created_at'=>[
                    'asc' => ['created_at' => SORT_ASC],
                    'desc' => ['created_at' => SORT_DESC],
                ]
            ],
            'attributes' => [
                'failed_cross',
                'full_user_name',
                'is_approved',
                'comment',
                'created_at',
            ]
        ]);

        if ($this->is_approved == 2) {
            $withQuery->andWhere('is_approved is null');
        } else {
            $withQuery->andFilterWhere([
                'is_approved' => $this->is_approved,
            ]);
        }
        $withQuery->andFilterWhere(['ilike', 'full_user_name', $this->full_user_name])
            ->andFilterWhere(['ilike', 'failed_cross', $this->failed_cross])
            ->andFilterWhere(['ilike', 'comment', $this->comment]);

        return $dataProvider;
    }
}
