<?php

namespace common\modules\pay\models;

use backend\modules\multi_organization\entity\Organizations;
use yii\base\Model;
use yii\helpers\ArrayHelper;

/**
 * Класс для обработки multiple-элементов из формы редактирования
 * драйверами
 *
 * @property  array $payDriverToOrganizations
 * @property-read  PayDriverList $driver
 */
class MultipleFormModel extends Model
{
    /**
     * @var array
     */
    public $payDriverToOrganizations;

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
    public function getFormDropdownItems(): array
    {
        $data = Organizations::find()->select(['id','short_name'])->asArray()->orderBy('id asc')->all();
        $data =  array_column($data,'short_name','id');
        return $data;
    }

    /**
     * Заполняет модель по заданному драйверу для виджета
     * multipleinput
     */
    public function loadData()
    {
        $ids = ArrayHelper::getColumn($this->driver->organizations,'id');
        foreach ($ids as $organization_id) {
            $this->payDriverToOrganizations[] = ['organization_id'=>$organization_id];
        }
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            ['payDriverToOrganizations', 'required'],
        ];
    }

    /**
     * Сохранение из формы
     */
    public function save()
    {
        $connection = \Yii::$app->db;
        $transaction = $connection->beginTransaction();
        $sql=[];
        try {
            PayDriverToOrganizations::deleteAll(['pay_driver_list_id' => $this->driver->id]);
            foreach ($this->payDriverToOrganizations['organization_id'] as $organization_id) {
                $sql[]=[
                    'pay_driver_list_id' => $this->driver->id,
                    'organization_id' => $organization_id
                ];
            }
            $connection->createCommand()->batchInsert(PayDriverToOrganizations::tableName(),
                array_keys($sql[0]), $sql)->execute();

            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            //сообщение в сентри
            \Yii::error($e->getMessage());
            //сообщение  форме виджета
            $this->addError('payDriverToOrganizations', json_encode($e->getMessage()));
        }
        $this->payDriverToOrganizations = [];
    }

}