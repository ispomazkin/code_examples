<?php

namespace backend\modules\dragonPay\components;

use backend\modules\crmfo_kernel_modules\Entity\Questionnaire;
use backend\modules\dragonPay\DragonPayComponentsModule;
use backend\modules\dragonPay\jobs\AddPaymentJob;
use backend\modules\dragonPay\jobs\StoreRequestForPayModule;
use backend\modules\dragonPay\models\DragonpayPayments;
use backend\modules\dragonPay\models\DragonpaySettings;
use common\modules\pay\components\interfaces\DriverCreatePaymentOperationsInterface;
use common\modules\pay\components\interfaces\PayModuleInterface;

/**
 * Драйвер для подключения к модулю pay
 *
 * @package backend\modules\dragonPay\components
 */
class DragonPayDriver implements DriverCreatePaymentOperationsInterface
{
    /**
     * @var DragonPayComponent
     */
    protected $component;

    /**
     * @var number
     */
    protected $amount;

    /**
     * @var Questionnaire
     */
    protected $questionnaire;

    /**
     * @var PayModuleInterface
     */
    protected $module;

    /**
     * @var DragonpayPayments
     */
    protected $paymentModel;


    /**
     * @inheritDoc
     */
    public function isActive(): bool
    {
        return DragonPayComponentsModule::isEnabled();
    }

    /**
     * @inheritDoc
     */
    public function getModule(): PayModuleInterface
    {
        return $this->module;
    }

    /**
     * @inheritDoc
     */
    public function setModule(PayModuleInterface $module)
    {
        $this->module = $module;
    }

    /**
     * @inheritDoc
     */
    public function getQuestionnaire(): Questionnaire
    {
        return $this->component->questionnaire;
    }

    /**
     * @inheritDoc
     */
    public function setQuestionnaire(Questionnaire $questionnaire)
    {
        $this->questionnaire = $questionnaire;
    }

    /**
     * @inheritDoc
     */
    public function setAmount(float $amount)
    {
        $this->amount = $amount;
    }

    /**
     * @inheritDoc
     */
    public function getAmount(): float
    {
        return $this->amount;
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'DragonPay';
    }

    /**
     * @inheritDoc
     */
    public function getSettings(): array
    {
        $settings = DragonpaySettings::getModel();
        return [
            self::LOGO => $settings->logo_url ?? null,
            self::DOCUMENTATION_URL => $settings->documentation_url ?? null,
            self::SETTINGS_URL => '/dragonpay/settings/index'
        ];
    }

    /**
     * @inheritDoc
     */
    public function storeRequestInPayModuleJournal()
    {
        \Yii::$app->queue->push(new StoreRequestForPayModule([
            'driver'=>$this,
        ]));

    }

    /**
     * @param DragonpayPayments $paymentModel
     */
    public function setPaymentModel(DragonpayPayments $paymentModel)
    {
        $this->paymentModel = $paymentModel;
    }

    /**
     * @return DragonpayPayments
     */
    public function getPaymentModel() : DragonpayPayments
    {
        return $this->paymentModel;
    }


    /**
     * @inheritDoc
     */
    public function createPayment(array $args = null)
    {
        \Yii::$app->queue->push(new AddPaymentJob([
            'component'=>$this->component,
            'model'=>$this->getPaymentModel()
        ]));
    }

    /**
     * @inheritDoc
     */
    public function getPeriod(): int
    {
        return 0;
    }

    /**
     * @inheritDoc
     */
    public function getReceiptNumber()
    {
       return null;
    }

    /**
     * @inheritDoc
     */
    public function getAkwdReceiptNumber()
    {
        return null;
    }

    /**
     * @param DragonPayComponent $component
     */
    public function setComponent(DragonPayComponent $component)
    {
        $this->component = $component;
    }

    /**
     * @return DragonPayComponent
     */
    public function getComponent(): DragonPayComponent
    {
        return $this->component;
    }
}
