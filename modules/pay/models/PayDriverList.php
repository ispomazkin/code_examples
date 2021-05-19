<?php

namespace common\modules\pay\models;

use api\constants\MoneyType;
use api\modules\v1\modules\money\modules\wallet\records\Wallet;
use backend\modules\crmfo_kernel_modules\Entity\BankDetails;
use common\modules\pay\components\interfaces\BaseDriverInterface;
use common\modules\pay\exceptions\MoneyTypeUndefinedException;
use yii\base\ErrorException;
use yii\db\ActiveQuery;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;
use backend\modules\multi_organization\entity\Organizations;

/**
 * Модель для таблицы "pay_driver_list", которая управляет списком
 * драйверов и их активностью
 *
 * @property int $id
 * @property string|null $driver_name Уникальное название драйвера
 * @property string|null $class Уникальный класс драйвера
 * @property bool|null $is_active Признак активности драйвера
 * @property string|null $created_at
 * @property string|null $updated_at
 *
 * @property PayJournalRequest $payJournalRequest
 * @property PayDriverRules[] $payDriverRules
 * @property BaseDriverInterface $driverInstance
 * @property Organizations[] $organizations
 * @property array $annotationRules
 */
class PayDriverList extends \yii\db\ActiveRecord
{

    const SCENARIO_UPDATE = 'scenario update';

    /**
     * @var BaseDriverInterface | null
     */
    protected $_instance;

    /**
     * @var array | null
     */
    protected $_annotationRules;

    /**
     * @return array
     */
    public function behaviors(): array
    {
        return [
            [
                'class' => TimestampBehavior::className(),
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    /**
     * @param bool $insert
     * @param array $changedAttributes
     * @throws ErrorException
     */
    public function afterSave($insert, $changedAttributes)
    {
        if ($this->needUpdateRules($insert)) {
            $this->updateRules();
        }
        parent::afterSave($insert, $changedAttributes);
    }

    /**
     * @param $insert
     * @return bool
     */
    protected function needUpdateRules($insert): bool
    {
        return ($this->scenario === self::SCENARIO_DEFAULT && ($insert or !$this->hasRules()));
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%pay_driver_list}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['is_active'], 'boolean'],
            [['driver_name','class'], 'string', 'max' => 255],
            [['driver_name','class'],'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'driver_name' => \Yii::t('app','Название'),
            'class' => 'Class',
            'is_active' => \Yii::t('app','Активен'),
            'created_at' => \Yii::t('app','Создан'),
            'updated_at' => \Yii::t('app','Обновлен'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPayJournalRequest()
    {
        return $this->hasOne(PayJournalRequest::className(), ['pay_driver_list_id' => 'id']);
    }

    /**
     * @param string $className
     * @return PayDriverList|null
     */
    public static function findByClassName(string $className)
    {
        return self::findOne(['class'=>$className]);
    }

    /**
     * @return ActiveQuery
     */
    public function getPayDriverRules(): ActiveQuery
    {
        return $this->hasMany(PayDriverRules::className(),['pay_driver_list_id'=>'id']);
    }

    /**
     * @return BaseDriverInterface
     */
    public function getDriverInstance(): BaseDriverInterface
    {
        if ($this->_instance === null) {
            $this->_instance = new $this->class;
        }
        return new $this->_instance;
    }

    /**
     * Метод возвращает все драйвера в базе,
     * попутно проверяя наличие классов в конфиге
     * контейнера. Если найден неактуальный драйвер
     * (файл физически удален, а запись в БД осталсь),
     * то запись этого драйвера удаляется из БД
     *
     * @return array
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public static function getAll(): array
    {
        $drivers=[];
        foreach (PayDriverList::find()->all() as $driver)
        {
            if ($driver->isValidDriverClass($driver->class))  {
                $drivers[]=$driver;
            }
            else {
                $driver->delete();
            }
        }
        return $drivers;
    }

    /**
     * @param $class
     * @return bool
     * @throws \ReflectionException
     */
    public function isValidDriverClass($class): bool
    {
        $container = \Yii::$container;
        return $container->has($class) && class_exists($class) && in_array(BaseDriverInterface::class,
                self::getInterfaceNames($class));
    }

    /**
     * @param $class
     * @return array
     * @throws \ReflectionException
     */
    protected static function getInterfaceNames($class): array {
        return (new \ReflectionClass($class))->getInterfaceNames();
    }

    /**
     * @return ActiveQuery
     * @throws \yii\base\InvalidConfigException
     */
    public function getOrganizations(): ActiveQuery
    {
        return $this->hasMany(Organizations::class,['id'=>'organization_id'])
            ->viaTable(PayDriverToOrganizations::tableName(),['pay_driver_list_id'=>'id']);
    }

    /**
     * Проверка, может ли драйвер быть использован с заданной организацией
     *
     * @param Organizations $organization
     * @return bool
     * @throws \yii\base\InvalidConfigException
     */
    public function hasOrganizationPermissions(Organizations $organization): bool
    {
        return $this->getOrganizations()
            ->andWhere(['sc_organizations.id'=>$organization->id])->exists();
    }

    /**
     * Проверка, может ли драйвер работать с кошельком или р/с
     *
     * @param Wallet|BankDetails|null $money
     * @return bool
     * @throws MoneyTypeUndefinedException
     * @throws \ReflectionException
     */
    public function isAcceptedMyMoneyType($money=null): bool {
        if ($money === null) {
            return true;
        }
        $expectedInterface = $this->getExpectedInterface($money);
        return in_array($expectedInterface, self::getInterfaceNames($this->driverInstance));
    }

    /**
     * @param Wallet|BankDetails $money
     * @return string
     * @throws MoneyTypeUndefinedException
     */
    public function getExpectedInterface($money): string {
        if ($money instanceof Wallet) {
            $expectedInterface = $this->createInterfaceNameSpace($money->type);
        } elseif ($money instanceof BankDetails) {
            $expectedInterface = $this->createInterfaceNameSpace(MoneyType::BANK_ACCOUNT);
        } else {
            throw new MoneyTypeUndefinedException('unsupported money type :'.gettype($money));
        }
        return $expectedInterface;
    }

    /**
     * @param string $type
     * @return string
     */
    protected function createInterfaceNameSpace(string $type): string
    {
        $parts = explode('-', $type);
        $parts = array_map(
            function ($a) {
                return ucfirst($a);
            },
            $parts
        );

        return 'common\modules\pay\components\interfaces\\' . implode('', $parts) . 'MoneyTypeInterface';
    }

    /**
     * @param string $str
     * @return string | null
     */
    protected function getAnnotationRuleName(string $str)
    {
        preg_match('/@rule\s+?\(\'(.+)\'\)/sU', $str, $matches);
        return $matches[1] ?? null;
    }

    /**
     * @param string $str
     * @return string | null
     */
    protected function getAnnotationRuleDescription(string $str)
    {
        preg_match('/@rule_description\s+?\(\'(.+)\'\)/sU', $str, $matches);
        return $matches[1] ?? null;
    }

    /**
     * @return bool
     */
    public function hasRules(): bool
    {
        return $this->payDriverRules !== null;
    }

    /**
     * @throws ErrorException
     */
    public function updateRules()
    {
        if ($this->isNewRecord) {
            throw new ErrorException('Trying to access id field on unsaved model');
        }
        $connection = \Yii::$app->db;
        $transaction = $connection->beginTransaction();
        try
        {
            PayDriverRules::deleteAll(['pay_driver_list_id'=>$this->id]);
            $insertArray = [];
            $lastPriorityValue=[];
            foreach ($this->annotationRules as $annotationRule)
            {
                $ruleName = $annotationRule['rule'];
                if (isset($lastPriorityValue[$ruleName] )) {
                    $priority = $lastPriorityValue[$ruleName] + PayDriverRules::PRIORITY_STEP_SIZE;
                } else {
                    $databasePriorityValues = PayDriverRules::getNextPriorityValues();
                    $priority =  $databasePriorityValues[$ruleName]['max_priority'] ??
                        PayDriverRules::PRIORITY_STEP_SIZE;
                }
                $lastPriorityValue[$ruleName] = $priority;
                $insertArray[]=[
                    'rule' => $ruleName,
                    'rule_description' => $annotationRule['rule_description'],
                    'pay_driver_list_id' => $this->id,
                    'priority' => $priority
                ];
            }

            $connection->createCommand()->batchInsert(
                PayDriverRules::tableName(),
                array_keys($insertArray[0]),
                $insertArray
            )->execute();
        } catch (\Exception $e) {
            $transaction->rollBack();
            \Yii::error($e->getMessage());
            throw new \Exception($e->getMessage());
        }
        $transaction->commit();
    }

    /**
     * @throws \ReflectionException
     */
    public function getAnnotationRules(): array
    {
        if ($this->_annotationRules!==null) {
            return $this->_annotationRules;
        }
        $interfaces = (new \ReflectionClass($this->class))->getInterfaceNames();
        $rules=[];
        foreach ($interfaces as $interface) {
            $ref = new \ReflectionClass($interface);
            $data = $ref->getDocComment();
            $rule = $this->getAnnotationRuleName($data);
            $description = $this->getAnnotationRuleDescription($data);

            if ($rule) {
                $rules[]=[
                    'interface' => $interface,
                    'class' => $this->class,
                    'rule' => $rule,
                    'rule_description'=>$description
                ];
            }
        }
        $this->_annotationRules = $rules;
        return $rules;
    }


    /**
     * @param $class
     * @return PayDriverList|null
     */
    public static function findArmByClass($class)
    {
        return self::findOne(['class'=>$class]);
    }
}
