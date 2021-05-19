<?php

namespace backend\modules\dragonPay\components;

use backend\modules\crmfo_kernel_modules\Entity\Questionnaire;

/**
 * Кастомизация класса запроса для модуля dragonpay
 *
 * @property-read Questionnaire $questionnaire
 * @property-read string $number
 * @property-read string|null $typeOperation
 * @property-read string|null $typeNumber
 * @property-read string|null $storeContentPlace
 */
class DragonPayRequest extends \yii\httpclient\Request
{
    /** @var Questionnaire*/
    protected $_questionnaire;
    /** @var string*/
    protected $_type_operation;
    /** @var string*/
    protected $_type_number;
    /** @var string*/
    protected $_number;
    /** @var string*/
    protected $_storeContentPlace;

    /**
     * @return Questionnaire | null
     */
    public function getQuestionnaire()
    {
        return $this->_questionnaire;
    }

    /**
     * @return string | null
     */
    public function getTypeOperation()
    {
        return $this->_type_operation;
    }

    /**
     * @param string $storeContentPlace
     * @return $this
     */
    public function setStoreContentPlace(string $storeContentPlace): self
    {
        $this->_storeContentPlace = $storeContentPlace;
        return $this;
    }

    /**
     * @return string
     */
    public function getStoreContentPlace()
    {
        return $this->_storeContentPlace;
    }


    /**
     * @return string | null
     */
    public function getTypeNumber()
    {
        return $this->_type_number;
    }

    /**
     * @return string | null
     */
    public function getNumber()
    {
        return $this->_number;
    }


    /**
     * @param string $typeNumber
     * @return $this
     */
    public function setTypeNumber(string $typeNumber): self
    {
        $this->_type_number = $typeNumber;
        return $this;
    }

    /**
     * @param string $number
     * @return $this
     */
    public function setNumber(string $number): self
    {
        $this->_number = $number;
        return $this;
    }


    /**
     * @param string $typeOperation
     * @return $this
     */
    public function setTypeOperation(string $typeOperation): self
    {
        $this->_type_operation = $typeOperation;
        return $this;
    }


    /**
     * @param Questionnaire $questionnaire
     * @return self
     */
    public function setQuestionnaire(Questionnaire $questionnaire): self
    {
        $this->_questionnaire = $questionnaire;
        return $this;
    }

}
