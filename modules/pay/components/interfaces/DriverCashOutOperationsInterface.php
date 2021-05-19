<?php

namespace common\modules\pay\components\interfaces;

use api\modules\v1\modules\loan\records\DisbursementRequest;

/**
 * Интерфейс драйвера платежной системы, которая
 * позволяет вывести денежные средства на счет клиента, т.е.
 * через которую можно выдать займ
 *
 * @rule ('cash-out')
 * @rule_description ('Вывод денежных средств')
 */
interface DriverCashOutOperationsInterface extends BaseDriverInterface
{
    /**
     * В этом методе необходимо реализовать всю логику вывода
     * денег на сторону ПС. Метод будет вызываться из модуля
     * Параметр $moneyType предназначен идентификации способа
     * вывода денег на стороне драйвера
     *
     * @param $disbursementRequest DisbursementRequest @see consts of [[MoneyType]] Тип вывода денег - кошелек, расчетный счет
     * @return mixed
     */
    public function processCashOut(DisbursementRequest $disbursementRequest);

    /**
     * При выдаче возвращать срок займа
     * @return int
     */
    public function getPeriod(): int;

}
