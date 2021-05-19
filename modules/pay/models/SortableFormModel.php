<?php

namespace common\modules\pay\models;

use yii\base\Model;

/**
 * Клас для обработки sortable-элементов из формы редактирования
 * драйверами
 *
 * @property PayDriverList $driver
 * @property array $sortableElements
 */
class SortableFormModel extends Model
{
    /**
     * @var array
     */
    public $sortableElements;

    /**
     * @var PayDriverList
     */
    protected $_driver;

    /**
     * @param PayDriverList $driver
     */
    public function setDriver(PayDriverList $driver)
    {
        $this->_driver = $driver;
    }

    /**
     * @return PayDriverList
     */
    public function getDriver(): PayDriverList
    {
        return $this->_driver;
    }

    /**
     * @return array
     */
    public function rules(){
        return [
          ['sortableElements', 'required'],
        ];
    }

    /**
     * Сохранение из формы
     */
    public function save()
    {
        $connection = \Yii::$app->db;
        $transaction = $connection->beginTransaction();
        try
        {
            $sql = [];
            foreach ($this->sortableElements as $rule=>$ids) {
                //находим первое попавшеся правило
                $ruleObject = PayDriverRules::findOne(['rule'=>$rule]);
                if (!$ruleObject) {
                    throw new \Exception('rule '. $rule .' is not exists');
                }

                //удаляем все правила
                 PayDriverRules::deleteAll(['rule'=>$rule]);

                 //Начальный приоритет
                $priority = 0;

                //формируем корректный массив новых правил для вставки одним запросом
                $partSql = array_map(function($a) use ($ruleObject, &$priority){
                    $priority += PayDriverRules::PRIORITY_STEP_SIZE;
                    /** @var PayDriverRules $ruleObject */
                    return [
                        'rule' => $ruleObject->rule,
                        'rule_description' => $ruleObject->rule_description,
                        'pay_driver_list_id' => $a,
                        'priority' => $priority,

                    ];
                }, $ids);

                $sql = array_merge($sql, $partSql);
            }

            $connection->createCommand()->batchInsert(PayDriverRules::tableName(),array_keys($sql[0]),$sql)
                ->execute();
            $transaction->commit();
        }
        catch (\Exception $e)
        {
            $transaction->rollBack();
            //сообщение в сентри
            \Yii::error($e->getMessage());
            //сообщение  форме виджета
            $this->addError('sortableElements', json_encode($e->getMessage()));
        }
    }

}