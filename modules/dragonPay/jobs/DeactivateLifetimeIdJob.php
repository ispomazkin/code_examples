<?php

namespace backend\modules\dragonPay\jobs;

use backend\modules\crmfo_kernel_modules\Entity\StatusQuestionnaires;
use backend\modules\dragonPay\components\DragonPayComponent;
use backend\modules\dragonPay\DragonPayModule;
use yii\base\BaseObject;
use yii\queue\JobInterface;
use backend\modules\crmfo_kernel_modules\Entity\AccountHistory;
use backend\modules\dragonPay\DragonPayComponentsModule;

/**
 * Деактивация lifetimeId после Закрытия займа
 *
 */
class DeactivateLifetimeIdJob extends BaseObject implements JobInterface
{

    public $event;

    /**
     *
     * @param $queue
     */
    public function execute($queue)
    {
        if (!DragonPayComponentsModule::isEnabled()) {
            return;
        }
        $sender = $this->event->sender;
        $questionnaire =  $sender->questionnaire;
        try {
            $component = new DragonPayComponent(
                [
                    'questionnaire' => $questionnaire,
                ]
            );
            if ($component->lifetime_enabled) {
                $lifetimeId = $component->lifetimeId;
                //отправляем запрос на деактивацию lifetimeid
                //ответ будет сохранен в другой очереди
                $component->deactivateLifetimeId($lifetimeId);
            }
        } catch (\Exception $e) {
            \Yii::error($e->getMessage());
        }
    }

}


