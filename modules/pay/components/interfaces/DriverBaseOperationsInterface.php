<?php

namespace common\modules\pay\components\interfaces;

use backend\modules\crmfo_kernel_modules\Entity\Questionnaire;

/**
 * Базовый интерфейс для модулей платежных систем
 * Не нужно реадизовывать модуль напрямую на основе этого интерфейса
 * Вместо него используйте тот (те), что подходят по функцоналу:
 * -- @see DriverCashOutOperationsInterface
 * -- @see DriverCreatePaymentOperationsInterface
 *
 * Interface DriverBaseOperationsInterface
 * @package common\modules\pay\components\interfaces
 */
interface DriverBaseOperationsInterface
{
    /**
     * Ключ для массива настроек
     * Логотип ПС
     */
    const LOGO = 'logo';

    /**
     * Ключ для массива настроек
     * Путь к документации
     */
    const DOCUMENTATION_URL = 'api_url';

    /**
     * Путь к настройкам драйвера
     * (логин, пароль и т д)
     */
    const SETTINGS_URL = 'settings_url';


    /**
     * Проверяет статус включен ли модуль палтежной системы (драйвер)
     * @return bool
     */
    public function isActive(): bool;

    /**
     * @return PayModuleInterface
     */
    public function getModule(): PayModuleInterface;

    /**
     * @param PayModuleInterface $module
     * @return mixed
     */
    public function setModule(PayModuleInterface $module);

    /**
     * @return Questionnaire
     */
    public function getQuestionnaire(): Questionnaire;

    /**
     * @param Questionnaire $questionnaire
     *
     * @return mixed
     */
    public function setQuestionnaire(Questionnaire $questionnaire);

    /**
     * @param float $amount
     *
     * @return mixed
     */
    public function setAmount(float $amount);


    /**
     *
     * @return float
     */
    public function getAmount(): float;


    /**
     * Возвращает Название драйвера
     * @return string
     * пример
     * return 'sberbank';
     */
    public function getName(): string;


    /**
     * Возвращает массив настроек драйвера
     * см. Публичные константы, заданные выше, для унификации
     * основных настроек, напр. изображение, путь к документации и пр.
     *
     * @return array
     * пример кода возвращаемого массиа
     * [
     *    self::LOGO => 'https://sberbank.ru/logo.gif',
     *    self::DOCUMENTATION_URL => 'https://ws.sberbank.ru/docs',
     *    self::SETTINGS_URL => 'https://test.dengigroup.kz/index.php/sberbank_module/settings',
     * ]
     */
    public function getSettings(): array;


    /**
     * Сохранение запроса в таблицу запросов модуля [[PayJournalRequest]]
     * Метод не принимает каких-либо параметров, поскольку вызывается из модуля.
     * Все необходимые данные нужно получить внутри
     * @return mixed
     */
    public function storeRequestInPayModuleJournal();



}
