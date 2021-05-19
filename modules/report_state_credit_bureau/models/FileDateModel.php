<?php
declare(strict_types=1);

namespace backend\modules\report_state_credit_bureau\models;

use yii\base\Model;

/**
 * Модель для подгрузки даты формирования отчета из формы
 */
class FileDateModel extends Model
{
    /**
     * @var string
     */
    public $date_to;

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            ['date_to','string'],
            ['date_to','required','message'=>'Field is required'],
            ['date_to','filter','filter'=>function($value){
                return date('dmY', strtotime($value));
            }]
        ];
    }

    /**
     * @return array
     */
    public function attributeLabels(): array
    {
        return [
            'date_to'=>'Create report on date'
        ];
    }

}

