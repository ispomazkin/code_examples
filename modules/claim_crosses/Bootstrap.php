<?php

namespace backend\modules\claim_crosses;

use backend\modules\claim_crosses\providers\ClaimCrossesDataProvider;
use backend\modules\claim_crosses\providers\ClaimCrossesDataProviderInterface;
use yii\base\BootstrapInterface;
use yii\db\Connection;

class Bootstrap implements BootstrapInterface
{

    /**
     * @inheritDoc
     */
    public function bootstrap($app)
    {
        $user = $app->user->identity;
        \Yii::$container->set(\yii\web\IdentityInterface::class, function() use ($user) {
            return $user;
        });
        \Yii::$container->set(ClaimCrossesDataProviderInterface::class, ClaimCrossesDataProvider::class);
    }
}