<?php

namespace common\modules\pay\components\interfaces;

/**
 * Интерфейс драйвера платежной системы, которая
 * позволяет внести наличные на счет мерчанта, т.е.
 * на SC
 *
 * Пример кода
 *
 * $questionnaire = Questionnaire::findOne(123);
 * $amount = $_POST['amount'];
 * $driver = new ProcessingKZ();
 *    // обязательные для работы модуля параметры
 *   ->setQuestionnaire($questionnaire)
 *   ->setAmount($amount)
 *    //другие необходимые  параметры
 *    //......
 *           ;
 *     //создаем инстанс модуля
 * return (new Pay())
 *   //задаем нужный драйвера
 *   ->setDriver($driver)
 *   //садим платеж. В этом методе модуль проводит платеж в саас
 *   ->addPayment();
 *
 *
 * @rule ('cash-in')
 * @rule_description ('Внесение денежных средств')
 */
interface DriverCreatePaymentOperationsInterface extends BaseDriverInterface
{
    /**
     * В этом методе необходимо реализовать
     * всю логику работы драйвера по приему денежных средств (если есть необходимость)
     * Метод будет вызываться через модуль
     *
     * @param array|null $args
     * @return mixed
     */
    public function createPayment(array $args = null);

    /**
     * Номер квитанции
     * @return string | null
     */
    public function getReceiptNumber();

    /**
     * Номер квитанции подтверждения
     * @return string | null
     */
    public function getAkwdReceiptNumber();

    /**
     * Период продления
     * @return int
     */
    public function getPeriod(): int;
}
