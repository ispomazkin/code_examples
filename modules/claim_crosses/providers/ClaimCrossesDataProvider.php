<?php

namespace backend\modules\claim_crosses\providers;

use yii\db\Connection;

/**
 * Класс для операций с БД всего, что связано с жалобами на аналог
 */
class ClaimCrossesDataProvider implements ClaimCrossesDataProviderInterface
{
    const APPROVED = 1;

    const DECLINED = 0;

    /**
     * @var Connection
     */
    protected $connection;

    public function __construct()
    {
        $this->connection = \Yii::$app->db;
    }

    /**
     * @inheritDoc
     */
    public function add(string $hash, string $claimed_hash, string $comment = null, string $user_login_guid = null): int
    {
        $this->connection
            ->createCommand("insert into claim_crosses (hash, claimed_hash, user_login_guid, comment) 
VALUES (:hash,:claimed_hash, :user_login_guid, :comment) on conflict do nothing",[
                ':hash' => $hash,
                ':claimed_hash' => $claimed_hash,
                ':user_login_guid' => $user_login_guid,
                ':comment' => $comment
            ])->execute();
        return $this->connection->getLastInsertID();
    }

    /**
     * @inheritDoc
     */
    public function accept(int $claim_id): int
    {
        return $this->connection->createCommand('update claim_crosses set is_approved=:approved, updated_at=CURRENT_TIMESTAMP WHERE id=:id',[
            ':id' => $claim_id,
            ':approved' => self::APPROVED
        ])->execute();
    }

    /**
     * @inheritDoc
     */
    public function decline(int $claim_id): int
    {
        return $this->connection->createCommand('update claim_crosses set is_approved=:declined, updated_at=CURRENT_TIMESTAMP WHERE id=:id',[
            ':id' => $claim_id,
            ':declined' => self::DECLINED
        ])->execute();
    }
}