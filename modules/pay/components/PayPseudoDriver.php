<?php

namespace common\modules\pay\components;

use backend\modules\multi_organization\entity\Organizations;
use common\modules\pay\models\PayDriverList;
use common\modules\pay\models\PayDriverRules;
use common\modules\pay\exceptions\DriverInactiveException;
use common\modules\pay\exceptions\DriverInvalidPermissionsException;
use common\modules\pay\exceptions\MoneyTypeUndefinedException;
use yii\base\ErrorException;
use yii\base\UnknownPropertyException;

/**
 * Класс драйвера по умолчанию.
 * Используется в том случае, если для выполнения операции не будет задан явно
 * какой-либо драйвер, поскольку для работы модуля нужен объект драйвера со всеми параметрами
 * (заявка, сумма операции и т д)
 * Для работы необходимо задать правило, по которому будет
 * производиться поиск модулем по иерархии
 * ближайшего подходящего по признакам:
 *
 * - Активность драйвера
 * - Соответствие драйвера заданному правилу [[PayDriverRules]]
 * - Соответствие драйвера организации [[PayDriverRulesToOrganizations]]
 *
 * Дефолтный драйвер позволяет задать произвольный набор параметров, однако
 * в случае обращения к несуществующему (не заданному ранее) свойству,
 * будет выброшено исключение. Также дефолтный драйвер перехватывает все вызовы  к реальному
 *
 * Class PayDefaultDriver
 * @package backend\modules\pay\src
 * @property PayDriverList $realDriver
 * @property PayDriverRules $rule
 */
class PayPseudoDriver extends BaseDriver
{

    /**
     * Тип правила для выбора дефолтного драйвера
     * @var PayDriverRules
     */
    protected $_rule;

    /**
     * Класс реального драйвера
     * @var PayDriverList
     */
    protected $_realDriver;

    /**
     * Возвращает объект правила, по которму
     * будет производиться поиск дефолтного драйвера
     * для обработки заднного действия. Если правило не найдено, выкидывается исключение
     *
     * @return PayDriverRules
     * @throws ErrorException
     */
    public function getRule(): PayDriverRules
    {
        if ($this->_rule === null) {
            throw new ErrorException('You did not set the rule');
        }
        return $this->_rule;
    }

    /**
     * Задает правило для дальнейшего использования
     * в выборе драйверов.
     *
     * @param $ruleName string
     * @throws ErrorException
     */
    public function setRuleByName(string $ruleName)
    {
        $this->_rule = PayDriverRules::findOne(['rule' => $ruleName]);
        if ($this->_rule === null) {
            throw new ErrorException('The rule is not specified');
        }
    }

    /**
     * @param $driver PayDriverList
     */
    public function setRealDriver(PayDriverList $driver)
    {
        $this->_realDriver = $driver;
    }

    /**
     * @return mixed
     * @throws DriverInactiveException
     * @throws DriverInvalidPermissionsException
     * @throws MoneyTypeUndefinedException
     * @throws \ReflectionException
     * @throws \yii\base\InvalidConfigException
     */
    public function getRealDriver()
    {
        if ($this->_realDriver === null) {
            $this->loadRealDriverByPriority();
        }
        return $this->_realDriver;
    }

    /**
     * Задает дефолтный драйвер, для обработки
     * определеннго правила. Если таковой не находится выкидывает исключение
     *
     * @throws DriverInactiveException
     * @throws DriverInvalidPermissionsException
     * @throws MoneyTypeUndefinedException
     * @throws \ReflectionException
     * @throws \yii\base\InvalidConfigException
     */
    protected function loadRealDriverByPriority()
    {
        $rule = $this->rule;

        //выборка всех активных драйверов, исходя из заданного правила - выдача или погашение
        $drivers = $this->getDriverListByRule($this->rule);

        if (empty($drivers)) {
            throw new DriverInactiveException('Driver list is empty Is there any enabled drivers?');
        }

        $questionnaire = $this->getQuestionnaire();
        $moneyType = null;

        //Если производится автоподбор драйвера для выдачи,
        //необходимо определить, куда вывести денежные средства.
        if ($rule->isCashOutRule()) {
            $moneyType = $questionnaire->lastDisbursementRequest->money ?? null;
            if (!$moneyType) {
                throw new MoneyTypeUndefinedException('Money type must be set');
            }
        }

        $organization = $questionnaire->creditProduct->organization;
        $realDriver = $this->filterDriverList($drivers, $organization, $moneyType);

        if ($realDriver === null) {
            $message = 'Can not set default driver for execution rule ' . $this->rule->rule .
                ' Is there any enabled drivers with allowed organizations [ expected ' . $organization->full_name . ']';
            if ($moneyType) {
                $message .= ' and accepted money types
                [ expected ' . (new PayDriverList())->getExpectedInterface($moneyType). ']?';
            }
            throw new DriverInvalidPermissionsException($message);
        }

        $this->setRealDriver($realDriver);
    }

    /**
     * проверка на соответствие драйвера заданной организации и
     * типу выдачи (кошелек, расчетный счет)
     *
     * @param array $drivers
     * @param Organizations $organization
     * @param null $moneyType
     * @return PayDriverList|null
     * @throws MoneyTypeUndefinedException
     * @throws \ReflectionException
     * @throws \yii\base\InvalidConfigException
     */
    protected function filterDriverList(array $drivers, Organizations $organization, $moneyType=null)
    {
        foreach ($drivers as $driver) {
            /** @var $organization Organizations */
            /** @var PayDriverList $driver */
            if ($driver->hasOrganizationPermissions($organization) && $driver->isAcceptedMyMoneyType($moneyType)) {
                return $driver;
            }
        }
        return null;
    }

    /**
     * выборка всех активных драйверов,
     * исходя из заданного правила - выдача или погашение
     *
     * @param PayDriverRules $rule
     * @return array
     */
    protected function getDriverListByRule(PayDriverRules $rule): array
    {
        return $rule->getDrivers()
            ->leftJoin(PayDriverRules::tableName(), 'pay_driver_rules.pay_driver_list_id=pay_driver_list.id')
            ->andWhere(['is_active' => true])->orderBy('priority asc')->all();
    }

    /**
     * Активность подобранного драйвера.
     * Записывается в переменную, чтобы избежать при повторном вызове
     * ненужных запросов к базе со стороны реального драйвера
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->realDriver->getDriverInstance()->isActive();
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->realDriver->getDriverInstance()->getName();
    }

    /**
     * Возвращает либо геттер для заданного имени,
     * либо свойство из реального драйвера
     *
     * @param $name
     * @return mixed
     * @throws UnknownPropertyException
     */
    public function __get($name)
    {
        $getter = 'get' . $name;
        if (method_exists($this, $getter)) {
            return $this->$getter();
        }
        if ($this->_realDriver !== null) {
            return $this->_realDriver->{$name};
        }
        throw new UnknownPropertyException('Getting unknown property: ' . get_class($this) . '::' . $name);
    }

    /**
     * Прокидывает выполнение метода в реальный драйвер
     *
     * @param $method
     * @param array $arguments
     * @return false|mixed
     * @throws ErrorException
     */
    public function __call($method, array $arguments)
    {
        if (!method_exists($this->realDriver->getDriverInstance(), $method)) {
            throw new ErrorException('Method is not exists in ' . get_class($this->realDriver->getDriverInstance()));
        }
        return call_user_func([$this->realDriver->getDriverInstance(), $method], array_shift($arguments));
    }

    /**
     * Сохранение запроса в БД
     * Прокидываем на сторону выбранного драйвера
     *
     * @return false|mixed
     */
    public function storeRequestInPayModuleJournal()
    {
        return call_user_func([$this->realDriver->getDriverInstance(), 'storeRequestInPayModuleJournal']);
    }

    /**
     * Возвращает список методов-геттеров
     * @return array
     * @throws \ReflectionException
     */
    public function listOfGetMethods(): array {
        $pseudoDriverReflection = new \ReflectionClass($this);
        $methods = [];
        foreach ($pseudoDriverReflection->getMethods() as $method) {
            $methodName = $method->name;
            if (stripos($methodName,'get') === 0) {
                $methods[] = strtolower(substr($methodName,3));
            }
        }
        return $methods;
    }

}