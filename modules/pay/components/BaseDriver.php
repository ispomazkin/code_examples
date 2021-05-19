<?php

namespace common\modules\pay\components;

use backend\modules\crmfo_kernel_modules\Entity\Questionnaire;
use common\modules\pay\components\interfaces\BaseDriverInterface;
use common\modules\pay\components\interfaces\PayModuleInterface;

/**
 * Абстрактный класс для создания на его основе
 * драйвера
 *
 * @package backend\modules\pay\src
 */
abstract class BaseDriver implements BaseDriverInterface
{

    /**
     * @inheritDoc
     */
    abstract public function isActive(): bool;

    /**
     * @return mixed
     */
    abstract public function storeRequestInPayModuleJournal();

    /**
     * @var PayModuleInterface
     */
    protected $_module;

    /**
     * @var Questionnaire
     */
    protected $questionnaire;

    /**
     * @var float
     */
    protected $amount;

    /**
     * @param PayModuleInterface $module
     *
     * @return mixed|void
     */
    public function setModule(PayModuleInterface $module)
    {
        $this->_module = $module;
    }

    /**
     * @return PayModuleInterface
     */
    public function getModule(): PayModuleInterface
    {
        return $this->_module;
    }

    /**
     * @param Questionnaire $questionnaire
     *
     * @return mixed|void
     */
    public function setQuestionnaire(Questionnaire $questionnaire)
    {
        $this->questionnaire = $questionnaire;
    }

    /**
     * @return Questionnaire
     */
    public function getQuestionnaire(): Questionnaire
    {
        return $this->questionnaire;
    }

    /**
     * @param float $amount
     *
     * @return mixed|void
     */
    public function setAmount(float $amount)
    {
        $this->amount = $amount;
    }

    /**
     * @return float
     */
    public function getAmount(): float
    {
        return $this->amount;
    }

    /**
     * @return array
     */
    public function getSettings(): array
    {
        return [];
    }

}
