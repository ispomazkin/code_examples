<?php

namespace backend\modules\claim_crosses\requests;

use backend\modules\claim_crosses\models\ClaimCrosses;
use common\models\Nomenclature;
use yii\base\Model;
use yii\web\BadRequestHttpException;

/**
 * Класс для обработки запросов модуля жалобы на аналог
 */
class ClaimRequest extends Model
{
    /**
     * Хеш искомой номенклатуры
     * @var string
     */
    public $hash;
    /**
     * Хеш, номенклатуры, который
     * не соответсвует типу кросс для $hash
     * Хеш искомой номенклатуры
     * @var string
     */
    public $claimed_hash;

    /**
     * @var string
     */
    public $comment;

    /**
     * @var int
     */
    public $claim_id;

    /**
     * Для возможности тестипования
     * @var array
     */
    protected $_rules;

    const SCENARIO_ADD='add';

    const SCENARIO_EDIT='edit';

    public function __construct($config = [])
    {
        if (empty($config)) {
            $config = \Yii::$app->request->getBodyParams();
        }
        parent::__construct($config);
    }

    public function rules()
    {
        if (is_array($this->_rules)) {
            return $this->_rules;
        }
        return [
            [['hash','claimed_hash'], 'required', 'on'=>self::SCENARIO_ADD],
            [['claim_id'], 'required', 'on'=>self::SCENARIO_EDIT],
            [['hash','claimed_hash','comment'],'string'],
            ['claim_id', 'integer'],
            ['claim_id', 'exist', 'targetClass' => ClaimCrosses::class, 'targetAttribute' => ['claim_id' => 'id']],
            ['hash', 'exist', 'targetClass' => Nomenclature::class, 'targetAttribute' => ['hash' => 'hash']],
            ['claimed_hash', 'exist', 'targetClass' => Nomenclature::class, 'targetAttribute' => ['claimed_hash' => 'hash']],
        ];
    }

    public function getStringErrors(): ?string
    {
        if ($this->hasErrors()) {
            $message = '';
            foreach ($this->errors as $error) {
              $message.= implode(' ', $error). ' ';
            }
            return $message;
        }
        return null;
    }

    public function __set($name, $value)
    {
        throw new BadRequestHttpException('Invalid Params');
    }

    public function setRules(array $rules)
    {
        $this->_rules = $rules;
    }
}