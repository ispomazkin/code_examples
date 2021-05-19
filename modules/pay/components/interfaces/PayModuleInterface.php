<?php

namespace common\modules\pay\components\interfaces;

/**
 * Интерфейс модуля Pay
 */
interface PayModuleInterface
{
    /**
     * @param mixed $driver
     */
    public function setDriver($driver);

    /**
     * @return BaseDriverInterface
     */
    public function getDriver(): BaseDriverInterface;

    /**
     * Внести оплату
     * @return mixed
     */
    public function addPayment();

    /**
     * Выдать ДС
     * @return mixed
     */
    public function issueLoan();

}

