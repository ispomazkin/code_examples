<?php

namespace common\modules\pay\models;

use Yii;
use yii\db\ActiveQuery;

/**
 * Модель для таблицы "pay_driver_rules", содержащей
 * признаки разделения драйверов по их назначению (выдача, погашение), а
 * также значения приоритета
 *
 *
 * @property int $id
 * @property string|null $rule Название правила (см. аннотацию @rule интерфейса драйвера)
 * @property string|null $rule_description Описание правила (см. аннотацию @rule_description интерфейса драйвера)
 * @property int|null $pay_driver_list_id ИД драйвера
 * @property int|null $priority Приоритет (чем меньше, тем выше)
 *
 * @property-read  PayDriverList[] $drivers
 */
class PayDriverRules extends \yii\db\ActiveRecord
{
    /**
     * Интервал разбиения по весу
     */
    const PRIORITY_STEP_SIZE = 10;
    /**
     * Классификация правила - выдача
     */
    const RULE_CASH_OUT = 'cash-out';
    /**
     * Классификация правила - погашение
     */
    const RULE_CASH_IN = 'cash-in';

    /**
     * Массив ИД драйверов по заданному
     * названию правила
     * @var array
     */
    protected static $intersectedDriverIdsByRule = [];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%pay_driver_rules}}';
    }

    /**
     * Получить актуальный вес в таблице приоритетов
     * @throws \yii\db\Exception
     */
    public static function getNextPriorityValues(): array
    {
        return (new yii\db\Query())
            ->select([
                '(max(priority)+' . self::PRIORITY_STEP_SIZE . ') max_priority',
                'rule'
            ])
            ->from(self::tableName())
            ->indexBy('rule')
            ->groupBy('rule')
            ->all();

    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['pay_driver_list_id', 'priority'], 'default', 'value' => null],
            [['pay_driver_list_id', 'priority'], 'integer'],
            [['rule', 'rule_description'], 'string', 'max' => 255],
            [['rule', 'priority'], 'unique', 'targetAttribute' => ['rule', 'priority']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'rule' => 'Rule',
            'rule_description' => 'Rule Description',
            'pay_driver_list_id' => 'Pay Driver List ID',
            'priority' => 'Priority',
        ];
    }

    /**
     * @param bool $insert
     * @param array $changedAttributes
     */
    public function afterSave($insert, $changedAttributes)
    {
        self::$intersectedDriverIdsByRule = [];
        parent::afterSave($insert, $changedAttributes);
    }

    /**
     * @param PayDriverRules $rule
     * @return array
     */
    public static function getIntersectedDriverIds(PayDriverRules $rule): array
    {
        if (!isset(self::$intersectedDriverIdsByRule[$rule->rule])) {
            self::$intersectedDriverIdsByRule[$rule->rule] = self::find()->select('pay_driver_list_id')->where(['rule' => $rule->rule])->column();
        }
        return self::$intersectedDriverIdsByRule[$rule->rule];
    }

    /**
     * @return ActiveQuery
     */
    public function getDrivers()
    {
        return PayDriverList::find()->where(['pay_driver_list.id' => self::getIntersectedDriverIds($this)]);
    }


    /**
     * Для драйверов выдач можно ввести доп. фильтрацию по
     * способу выдачи денежных средств - кошелек, расчетный счет и т.д.
     *
     * @see [[MoneyType]]
     * @return bool
     */
    public function isCashOutRule(): bool {
        return $this->rule === self::RULE_CASH_OUT;
    }

}
