<?php

namespace common\modules\pay\components;

use backend\modules\crmfo_kernel_modules\Entity\AccountHistory;
use backend\modules\crmfo_kernel_modules\Entity\Questionnaire;
use common\modules\pay\models\PayDriverList;
use common\modules\pay\models\PayStoredObjects;
use common\modules\pay\exceptions\DoubleIssueException;
use common\modules\pay\exceptions\DriverInactiveException;
use common\modules\pay\exceptions\DriverInvalidPermissionsException;
use common\modules\pay\exceptions\MoneyTypeUndefinedException;
use yii\base\ErrorException;
use yii\base\Event;
use yii\base\Module;
use yii\db\Connection;
use yii\di\Container;
use Yii;
use yii\base\Component;
use common\modules\pay\components\interfaces\BaseDriverInterface;
use common\modules\pay\components\interfaces\PayModuleInterface;
use common\modules\pay\components\interfaces\DriverCashOutOperationsInterface;
use common\modules\pay\components\interfaces\DriverCreatePaymentOperationsInterface;
use yii\di\Instance;

/**
 * Основной класс модуля Pay
 * Выполняет функции подбора драйверов, проверки их на соответствие
 * требуемому функционалу
 *
 * @property Container $container
 * @property Connection $connection
 * @property Event $event
 * @property BaseDriver|BaseDriverInterface $driver
 *
 */
class Pay extends Component implements PayModuleInterface
{
    /**
     * Событие после инициализации модуля
     */
    const EVENT_AFTER_INIT = 'pay_module_event_after_init';

    /**
     * Событие после выдачи займа
     */
    const EVENT_AFTER_LOAN_ISSUANCE = 'pay_module_event_after_loan_issuance';

    /**
     * Событие до выдачи займа
     */
    const EVENT_BEFORE_LOAN_ISSUANCE = 'pay_module_event_before_loan_issuance';

    /**
     * Событие до проведения платежа
     */
    const EVENT_BEFORE_ADD_PAYMENT = 'pay_module_event_before_add_payment';

    /**
     * Событие после проведения платежа
     */
    const EVENT_AFTER_ADD_PAYMENT = 'pay_module_after_before_add_payment';

    /**
     * Вызывать ли в модуле функцию, которая проводит платеж в CRM
     * По умолчанию true. Если проставить false, то необходимо реализовать
     * этот функционал вручную, например через события модуля
     * @var bool
     */
    public $callAddPaymentInCRM = true;

    /**
     * Параметр, отвечающий за вызов обработки выдачи займа
     * на стороне CRM. Обработка может быть вызвана со стороны модуля Pay, в случае
     *  $callIssueLoanInCRM = true;
     * либо со стороны драйвера, либо через события
     *  $callIssueLoanInCRM = false;
     * Пример вызова выдачи займа из драйвера:
     *  $this->getModule()->issueLoanInCRM($this)
     *
     * @var bool
     */
    public $callIssueLoanInCRM = false;

    /**
     * Вызывать метод [[storeRequestInPayModuleJournal]] у драйвера,
     * который доолжен сохранять результат запроса в журнале модуля.
     * Если задать false, то необходимо реализовать сохранение
     * инмым способом
     * @var bool
     */
    public $storeRequestInJournalJob = true;

    /**
     * Объект драйвера, подобранного автоматически или
     * заданного вручную
     *
     * @var BaseDriverInterface | DriverCashOutOperationsInterface | DriverCreatePaymentOperationsInterface
     */
    protected $_driver;

    /**
     * Объект события, который генерируется
     * в ходе жизненного цикла модуля
     *
     * @var Event | null
     */
    protected $_event;

    /**
     * Список разрешений для драйвера
     * @var array
     */
    protected $driverPermissions;

    /**
     * @return Container
     */
    public function getContainer(): Container
    {
        return Yii::$container;
    }

    /**
     * @return Connection
     */
    public function getConnection(): Connection
    {
        return Yii::$app->db;
    }

    /**
     * Получает нужный драйвер для выполнения операции.
     * Драйвер может быть задан конкретно [[setDriver]],
     * если он не задан, то производится поиск первого попавшегося
     *
     * @return BaseDriverInterface
     * @throws DriverInvalidPermissionsException
     * @throws ErrorException
     * @throws \ReflectionException
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\di\NotInstantiableException
     */
    public function getDriver(): BaseDriverInterface
    {
        if ($this->_driver instanceof PayPseudoDriver) {
            $pseudoDriver = $this->_driver;
            $this->setDriver($this->_driver->realDriver->class);
            $this->configureRealDriver($pseudoDriver);
        }
        //Вообще никакой не задан - ошибка
        if (!($this->_driver instanceof BaseDriverInterface)) {
            throw new ErrorException('at least one of the ' . PayPseudoDriver::class . ' or' . BaseDriverInterface::class .
                ' must be specified');
        }
        //Драйвер не подходит по организациям и/или активности - ошибка
        if (!$this->isDriverHavePermissions($this->_driver)) {
            throw new DriverInvalidPermissionsException('Invalid organization or driver is inactive');
        }
        return $this->_driver;
    }

    /**
     * Перенос всех свойств псевдо драйвера на рельный
     * @param PayPseudoDriver $driver
     * @throws \ReflectionException
     */
    protected function configureRealDriver(PayPseudoDriver $driver)
    {
        $methods = $driver->listOfGetMethods();
        foreach ($methods as $method) {
            $setter = 'set'.$method;
            $getter = 'get'.$method;
            if ($this->isRealDriverCanBeConfigured($setter) && $this->isValueExists($driver, $getter)) {
                $param = $driver->$getter();
                $this->_driver->$setter($param);
            }
        }
    }

    /**
     * Проверка на существование сеттера
     *
     * @param $setter
     * @return bool
     */
    protected function isRealDriverCanBeConfigured($setter): bool
    {
        return (method_exists($this->_driver,$setter));
    }

    /**
     * Проверка на наличие возвращаемого
     * значения геттера
     *
     * @param PayPseudoDriver $driver
     * @param string $getter
     * @return bool
     */
    protected function isValueExists(PayPseudoDriver $driver,string $getter): bool
    {
        return 'getmodule' !== $getter && null !== $driver->$getter();
    }

    /**
     * @inheritDoc
     * @return Pay
     * @throws ErrorException
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\di\NotInstantiableException
     * @var $driver BaseDriverInterface | PayDriverList | string
     */
    public function setDriver($driver): self
    {
        if ($driver instanceof PayPseudoDriver) {
            //Псевдо драйвер перезаписывается в методе getDriver
            $this->_driver = $driver;
        } else {
            $this->_driver = Instance::ensure($driver);
            $this->_driver->setModule($this);
        }
        return $this;
    }


    /**
     * Проверка на наличие ограничений по активности и организациям
     *
     * @param BaseDriverInterface $identity
     * @return bool
     * @throws ErrorException
     * @throws \yii\base\InvalidConfigException
     */
    protected function isDriverHavePermissions(BaseDriverInterface $identity): bool
    {
        //на этом этапе проверки свойство $driver еще не определено, находим ActiveRecord
        $called_class = get_class($identity);
        if (!isset($this->driverPermissions[$called_class])) {

            $driverArm = PayDriverList::findArmByClass($called_class);
            if (!$driverArm) {
                throw new ErrorException('Driver ARM is not found for class ' . $called_class);
            }

            $organization = $identity->getQuestionnaire()->creditProduct->organization;

            $this->driverPermissions[$called_class] =
                $identity->isActive() && $driverArm->hasOrganizationPermissions($organization);
        }
        return $this->driverPermissions[$called_class];
    }

    /**
     * @return \yii\base\Module
     */
    public function getLoader(): Module
    {
        return \Yii::$app->getModule("loader");
    }

    /**
     * Авторизоваться под системным пользователем
     * для проведения операции
     */
    protected function authSystemUser()
    {
        $this->getLoader()->crmfo_kernel->GetAuth()->AuthSystemUser();
    }

    /**
     * Возвращает имя класса, к которому принадлежит объект драйвера
     *
     * @return false|string
     */
    protected function getDriverClass(): string
    {
        return get_class($this->driver);
    }


    /**
     * Возварщает список интерфейсов для заданного драйвера
     *
     * @return string[]
     * @throws \ReflectionException
     */
    protected function getDriverInterfaces(): array
    {
        return (new \ReflectionClass($this->getDriverClass()))->getInterfaceNames();
    }

    /**
     * Получить модель ActiveRecord для заданного драйвера
     *
     * @return PayDriverList
     */
    protected function getDriverArm(): PayDriverList
    {
        return PayDriverList::findArmByClass($this->getDriverClass());
    }

    /**
     * Проверка, может ли драйвер производить выдачу
     *
     * @return bool
     * @throws \ReflectionException
     */
    protected function isDriverCanIssueLoan(): bool
    {
        return in_array(DriverCashOutOperationsInterface::class, $this->getDriverInterfaces());
    }


    /**
     * Проверка, может ли драйвер производить погашение
     *
     * @return bool
     * @throws \ReflectionException
     */
    protected function isDriverCanAddPayment(): bool
    {
        return in_array(DriverCreatePaymentOperationsInterface::class, $this->getDriverInterfaces());
    }

    /**
     * Запускает все процессы погашений
     *
     * @inheritDoc
     * @return mixed
     * @throws DriverInactiveException
     * @throws DriverInvalidPermissionsException
     * @throws ErrorException
     * @throws \ReflectionException
     */
    public function addPayment()
    {
        $this->trigger(self::EVENT_BEFORE_ADD_PAYMENT, $this->getEvent());

        $response = null;

        if (!$this->getDriverArm()->is_active) {
            throw new DriverInactiveException('The driver is disabled in pay module');
        }

        if (!$this->isDriverCanAddPayment()) {
            throw new DriverInvalidPermissionsException('The driver can not add payment');
        }

        $driver = $this->driver;
        /** @var $driver DriverCreatePaymentOperationsInterface */

        $transaction = $this->connection->beginTransaction();
        try {
            //Вызываем метод у драйвера
            $response = $driver->createPayment();
            //Фукционал со стороны CRM
            if ($this->callAddPaymentInCRM) {
                $this->addPaymentInCRM();
            }
            $transaction->commit();
        } catch (\Exception $e) {
            Yii::error($e->getMessage());
            $transaction->rollBack();
            throw new ErrorException($e->getMessage());
        }

        //логируем запрос
        if ($this->storeRequestInJournalJob) {
            $driver->storeRequestInPayModuleJournal();
        }

        $this->trigger(self::EVENT_AFTER_ADD_PAYMENT, $this->getEvent());
        return $response;

    }

    /**
     * Тип записи - погашение
     * @return int
     */
    protected function getRecordTypePayment(): int
    {
        return AccountHistory::RecordTypePayment;
    }

    /**
     * Тип записи - выдача
     * @return int
     */
    protected function getRecordTypeTransfer(): int
    {
        return AccountHistory::RecordTypeTransfer;
    }

    /**
     * Внесение оплаты в CRM
     */
    public function addPaymentInCRM()
    {
        $driver = $this->driver;
        /** @var DriverCreatePaymentOperationsInterface */
        $this->authSystemUser();
        $this->getLoader()->crmfo_kernel->GetOperations()->AddPayment(
            $driver->getQuestionnaire()->account_id,
            $this->getRecordTypePayment(),
            $driver->getAmount(),
            $driver->getPeriod(),
            $driver->getName(),
            $driver->getReceiptNumber(),
            $driver->getAkwdReceiptNumber()
        );
    }

    /**
     * @param Questionnaire $questionnaire
     * @return bool
     *
     * @throws DoubleIssueException
     */
    protected function checkIsLoanIssueAccepted(Questionnaire $questionnaire): bool
    {
        if ($this->isDoubleIssuance($questionnaire)) {
            throw new DoubleIssueException('The questionnaire '.$questionnaire->number.' have already had an issue');
        }
        return true;
    }

    /**
     * @param Questionnaire $questionnaire
     * @return bool
     */
    protected function isDoubleIssuance(Questionnaire $questionnaire): bool
    {
        return !!$questionnaire->issue;
    }

    /**
     * Выдача в CRM
     *
     * @param DriverCashOutOperationsInterface $driver
     * @throws ErrorException
     */
    public function issueLoanInCRM(DriverCashOutOperationsInterface $driver)
    {
        /** @var Questionnaire $questionnaire */
        $questionnaire = $driver->getQuestionnaire();
        $this->authSystemUser();
        $this->getLoader()->crmfo_kernel->GetOperations()->AddPayment(
            $questionnaire->account_id,
            $this->getRecordTypeTransfer(),
            $driver->getAmount(),
            $driver->getPeriod(),
            $driver->getName()
        );

    }

    /**
     * Запускает все процессы выдачи денежных средств
     *
     * @inheritDoc
     */
    public function issueLoan()
    {
        $this->trigger(self::EVENT_BEFORE_LOAN_ISSUANCE, $this->getEvent());
        $driver = $this->driver;

        $questionnaire = $driver->getQuestionnaire();

        //способ вариант выдачи денег
        $disbursementRequest = $questionnaire->lastDisbursementRequest;

        if (!$disbursementRequest) {
            throw new MoneyTypeUndefinedException('The disbursement request is not set for questionnaire '. $questionnaire->number);
        }

        if (!$this->getDriverArm()->is_active) {
            throw new DriverInactiveException('The driver is disabled in pay module');
        }

        if (!$this->isDriverCanIssueLoan()) {
            throw new DriverInvalidPermissionsException('The driver can not issue loan');
        }

        $this->trigger(self::EVENT_BEFORE_LOAN_ISSUANCE, $this->event);
        $response = null;

        if ($this->checkIsLoanIssueAccepted($questionnaire)) {
            $response = $this->callDriverLoanIssuance($driver, $disbursementRequest);
            //лог транзакции
            if ($this->storeRequestInJournalJob) {
                $driver->storeRequestInPayModuleJournal();
            }

            $this->trigger(self::EVENT_AFTER_LOAN_ISSUANCE, $this->getEvent());
        }
        return $response;
    }

    /**
     * Получить результат выполнения выдачи средств
     * со стороны драйвера. Если процесс выдачи происходит в два этапа - запрос к платежной системе,
     * и прием оповещений, необходимо callIssueLoanInCRM=false, при этом оборачивание в транзакцию
     * будет отключено
     *
     * @param $driver
     * @param $disbursementRequest
     * @return mixed
     * @throws ErrorException
     */
    protected function callDriverLoanIssuance($driver, $disbursementRequest)
    {
        return $this->callIssueLoanInCRM ? $this->issueWithinTransactionScope($driver, $disbursementRequest):
            $this->createIssue($driver, $disbursementRequest);
    }

    /**
     * Процесс выдачи займа
     *
     * @param $driver
     * @param $disbursementRequest
     * @return mixed
     * @throws ErrorException
     */
    protected function createIssue($driver,$disbursementRequest)
    {
        /** @var $driver DriverCashOutOperationsInterface */
        $response = $driver->processCashOut($disbursementRequest);
        if ($this->callIssueLoanInCRM) {
            $this->issueLoanInCRM($driver);
        }
        return $response;
    }

    /**
     * Функционал выдачи займа с использованием транзакций
     *
     * @param $driver
     * @param $disbursementRequest
     * @return mixed
     * @throws \Exception
     */
    protected function issueWithinTransactionScope($driver,$disbursementRequest)
    {
        $transaction = $this->connection->beginTransaction();
        try {
            $response = $this->createIssue($driver,$disbursementRequest);
        } catch (\Exception $e) {
            Yii::error($e->getMessage());
            $transaction->rollBack();
            throw new \Exception($e->getMessage());
        }
        $transaction->commit();
        return $response;
    }

    public function init()
    {
        parent::init();
        $this->setEvent(new Event(['sender' => $this]));
        $this->trigger(self::EVENT_AFTER_INIT, $this->getEvent());
    }

    /**
     * @return Event
     */
    public function getEvent(): Event
    {
        return $this->_event;
    }

    /**
     * @param Event $event
     */
    public function setEvent(Event $event)
    {
        $this->_event = $event;
    }

    /**
     * Сериализует объект модуля и сохраняет его
     * в базе по ключу ИД заявки для последующего использования
     * в других процессах, например, при приеме уведомлений от платежных систем.
     */
    public  function storeObj()
    {
        PayStoredObjects::storeObj($this, $this->driver->getQuestionnaire()->id);
    }

    /**
     * Восстанавливает последний последний объект модуля по ключу
     * ИД заявки
     *
     * @param int $questionnaire_id
     * @return static
     */
    public static function restoreObj(int $questionnaire_id): self
    {
        return PayStoredObjects::restoreObj($questionnaire_id);
    }

}
