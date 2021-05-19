<?php

namespace backend\modules\dragonPay\jobs;

use backend\helpers\BackendKernel;
use backend\modules\api_v2\controllers\DragonPayController;
use backend\modules\commission\components\operations\TakeCommission;
use backend\modules\crmfo_kernel_modules\Entity\Questionnaire;
use backend\modules\dragonPay\components\DragonPayComponent;
use backend\modules\dragonPay\models\DragonpayJournalRequests;
use backend\modules\dragonPay\models\DragonpayPayments;
use yii\base\BaseObject;
use yii\base\ErrorException;
use yii\queue\JobInterface;
use backend\modules\crmfo_kernel_modules\Entity\AccountHistory;
use backend\modules\dragonPay\DragonPayComponentsModule;
use yii\web\BadRequestHttpException;

/**
 * Проведение платежа в CRM при приеме
 * успешного уведомления от системы dragonpay
 */
class AddPaymentJob extends BaseObject implements JobInterface
{
    /** @var DragonPayComponent*/
    public $component;
    /** @var DragonpayPayments*/
    public $model;

    /**
     * @param $queue
     */
    public function execute($queue)
    {
        if (!DragonPayComponentsModule::isEnabled()) {
            return;
        }
        $component = $this->component;

        $model = $this->model;

        if (!$this->model instanceof DragonpayPayments) {
            throw new ErrorException('The model must be instane of ' . DragonpayPayments::class.', '.gettype($model).' given');
        }
        if ($model->type_number === DragonpayJournalRequests::TYPE_REFNUMBER && !$component->refno_enabled) {
            throw new BadRequestHttpException('RefNo integration is disabled, but DP send payment');
        }
        if ($model->type_number === DragonpayJournalRequests::TYPE_LIFETIME && !$component->lifetime_enabled) {
            throw new BadRequestHttpException('Lifetime integration is disabled, but DP send payment');
        }

        $record_sum = $model->amount;
        $record_sum = $this->commissionIsTaken($component->questionnaire, $record_sum);

        if (false === $record_sum) {
            return;
        }
        try {
            $this->model->updateAttributes(['response'=>json_encode('Payment job is started with amount '.$record_sum)]);
            \Yii::$app->getModule("loader")->crmfo_kernel->GetAuth()->AuthSystemUser();
            \Yii::$app->getModule("loader")->crmfo_kernel->GetOperations()->AddPayment(
                $component->questionnaire->account_id,
                AccountHistory::RecordTypePayment,
                $record_sum,
                0,
                DragonPayComponentsModule::getSettingsAsArray()['name']);
            $this->model->updateAttributes(['response'=>json_encode(DragonPayController::RESPONSE_OK)]);
        } catch (\Exception $e) {
            $this->model->updateAttributes(['response'=>json_encode(
                sprintf('%s [%s:%s]', $e->getMessage(), $e->getFile(), $e->getLine())
            )]);
        }

    }

    /**
     * удержание комиссии
     *
     * @param Questionnaire $questionnaire
     * @param float $record_sum
     * @return false|float|int|mixed|null
     */
    protected function commissionIsTaken(Questionnaire $questionnaire, float $record_sum)
    {
        try {
            $takeCommissionProcess = new TakeCommission($questionnaire);
            if ($takeCommissionProcess->checkCommissionPaymentTime(TakeCommission::REPAYMENT)) {
                $commissionAmount = $takeCommissionProcess->calculateCommissionAmount($record_sum);
                if ($commissionAmount > 0) {
                    $commissionResult = $takeCommissionProcess->take($commissionAmount);
                    if ($commissionResult['result'] == 'success') {
                        $record_sum -= $commissionAmount;
                        if ($record_sum <= 0) {
                            $this->model->updateAttributes(['response'=>
                                    json_encode('Оплачена только комиссия!')]
                            );
                            return false;
                        }
                    } else {
                        \Yii::error(json_encode(
                            $commissionResult
                        ));
                        $this->model->updateAttributes(['response'=>json_encode(
                            $commissionResult
                        )]);
                    }
                }
            }
        } catch (\Exception $e) {
            \Yii::error($e->getMessage());
            $this->model->updateAttributes(['response'=>json_encode(
                sprintf('%s [%s:%s]', $e->getMessage(), $e->getFile(), $e->getLine())
            )]);
            return false;
        }
        return $record_sum;
    }

}


