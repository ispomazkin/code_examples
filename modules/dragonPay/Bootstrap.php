<?php

namespace backend\modules\dragonPay;

use backend\modules\crmfo_kernel_modules\Entity\AccountHistory;
use backend\modules\crmfo_kernel_modules\Entity\StatusQuestionnaires;
use backend\modules\dragonPay\components\DragonPayComponent;
use backend\modules\dragonPay\components\DragonPayRequest;
use backend\modules\dragonPay\jobs\DeactivateLifetimeIdJob;
use backend\modules\dragonPay\jobs\GetLifetimeIdJob;
use yii\base\BootstrapInterface;
use yii\base\Event;
use yii\httpclient\RequestEvent;
use backend\modules\dragonPay\jobs\StoreRequestInJournalJob;


/**
 * Загрузчик модуля dragonpay
 */
class Bootstrap implements BootstrapInterface
{

    /**
     * Конфигурирование модуля dragonpay
     * на этапе инициализации приложения
     *
     * @param \yii\base\Application $app
     */
    public function bootstrap($app)
    {
        //Событие после отправки запроса
        Event::on(DragonPayRequest::class, DragonPayRequest::EVENT_AFTER_SEND, function ($event) {
            /** @var RequestEvent $event */
            \Yii::$app->queue->push(new StoreRequestInJournalJob(['event'=>$event]));
        });

        //событие после выдачи займа
        Event::on(AccountHistory::class, AccountHistory::EVENT_AFTER_INSERT, function ($event) {
            /** @var Event $event */
            $sender = $event->sender;
            if ($sender instanceof AccountHistory && $sender->type_record_id == AccountHistory::RecordTypeTransfer) {
                \Yii::$app->queue->push(new GetLifetimeIdJob(['event'=>$event]));
            }

        });

        //событие после закрытия займа
        Event::on(StatusQuestionnaires::class, StatusQuestionnaires::EVENT_AFTER_INSERT, function ($event) {
            /** @var Event $event */
            $sender = $event->sender;
            $questionnaire =  $sender->questionnaire;
            if ($questionnaire->statusQueClose === null) {
                return;
            }
            if ($sender instanceof StatusQuestionnaires) {
                \Yii::$app->queue->push(new DeactivateLifetimeIdJob(['event'=>$event]));
            }

        });

    }

}
