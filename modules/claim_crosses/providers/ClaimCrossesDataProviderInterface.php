<?php

namespace backend\modules\claim_crosses\providers;

interface ClaimCrossesDataProviderInterface
{
    /**
     * Добавить с список жалоб кросс
     * @return mixed
     */
    public function add(string $hash, string $claimed_hash, string $comment = null, string $user_login_guid = null);

    /**
     * Одобрить жалобу
     * @return mixed
     */
    public function accept(int $claim_id);

    /**
     * Отклонить жалобу
     * @return mixed
     */
    public function decline(int $claim_id);

}