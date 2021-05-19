<?php

namespace backend\modules\dragonPay\jobs;

use backend\modules\dragonPay\components\DragonPayComponent;
use backend\modules\dragonPay\DragonPayModule;
use yii\base\BaseObject;
use yii\queue\JobInterface;
use backend\modules\crmfo_kernel_modules\Entity\AccountHistory;
use backend\modules\dragonPay\DragonPayComponentsModule;


/**
 * Класс для асинхронного получения lifetimeId
 */
class GetLifetimeIdJob extends BaseObject implements JobInterface
{

    public $event;

    /**
     * Получение lifetimeId после выдачи займа
     *
     * @param $queue
     */
    public function execute($queue)
    {
        if (!DragonPayComponentsModule::isEnabled()) {
            return;
        }
        $sender = $this->event->sender;
        try {
            //отправляем запрос на генерацию lifetimeid
            //ответ будет сохранен в другой очереди
            $component = new DragonPayComponent(
                [
                    'questionnaire' => $sender->questionnaire,
                ]
            );

            if ($component->lifetime_enabled) {
                $component->createLifetimeId();
            }

        } catch (\Exception $e) {
            \Yii::error($e->getMessage());
        }
    }

}


