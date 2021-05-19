<?php
use backend\modules\report_state_credit_bureau\components\Report;
use backend\modules\crmfo_kernel_modules\Entity\Customer;
use backend\modules\crmfo_kernel_modules\Entity\Questionnaire;

return [
    'recordType'=>[
        'label' => 'Record Type',
        'value' => Report::RECORD_TYPE_CI
    ],
    'providerCode'=>[
        'label'=>'Provider Code',
        'value'=>function($model)
        {
            /** @var $model Report*/
            return $model->isTest ? Report::TEST_PROVIDER_CODE : Report::PROVIDER_CODE;
        }
    ],
    'branchCode'=>[
        'value'=>Report::EMPTY_FIELD
    ],
    'contractReferenceDate' => [
        'label' => 'Contract Reference Date',
        'value'=>function($model)
        {
            /** @var $model Report*/
            return $model->fileReferenceDate;
        }
    ],
    'providerSubjectNo'=>[
        'label'=>'Provider Subject No',
        'value'=>function($model, $customer, $questionnaire)
        {
            /** @var $customer Customer */
            /** @var $model Report*/
            return $customer->id;
        }
    ],
    'role' => [
        'label' => 'Role',
        'value' => Report::ROLE
    ],
    'providerContractNo' => [
        'label' => 'Provider Contract No',
        'value'=>function($model, $customer, $questionnaire)
        {
            /**
             * @var Customer $customer
             * @var Questionnaire $questionnaire
             * @var Report $model
             */
            return $questionnaire->number;
        }
    ],
    'contractType' => [
        'label' => 'Contract Type',
        'value' => Report::CONTRACT_TYPE
    ],
    'contractPhase' => [
        'label' => 'Contract Phase',
        'value'=>function($model, $customer, $questionnaire)
        {
            /**
             * @var Customer $customer
             * @var Questionnaire $questionnaire
             * @var Report $model
             */
            return $model->getContractPhaseDomain($questionnaire);
        }
    ],
    'Contract Status'=>[
        'value'=>Report::EMPTY_FIELD
    ],
    'currency' => [
        'label' => 'Currency',
        'value' =>Report::CURRENCY
    ],
    'originalCurrency' => [
        'label' => 'Original Currency',
        'value' =>Report::CURRENCY
    ],
    'contractStartDate' => [
        'label' => 'Contract Start Date',
        'value'=>function($model, $customer, $questionnaire)
        {
            /**
             * Дата выдачи займа dmY
             * @var Customer $customer
             * @var Questionnaire $questionnaire
             * @var Report $model
             */
            return date('dmY',strtotime($questionnaire->statusQueStart->status_time));
        }
    ],
    'Contract Request Date'=>[
        'value'=>Report::EMPTY_FIELD
    ],
    'contractEndPlannedDate' => [
        'label' => 'Contract End Planned Date',
        'value'=>function($model, $customer, $questionnaire)
        {
            /**
             * Планируемая Дата погашения займа dmY
             * @var Customer $customer
             * @var Questionnaire $questionnaire
             * @var Report $model
             */
            return date('dmY',(strtotime($questionnaire->statusQueStart->status_time) + 86400*$questionnaire->agreed_period_loan));
        }
    ],
    'contractEndActualDate' => [
        'label' => 'Contract End Actual Date',
        'value'=>function($model, $customer, $questionnaire)
        {
            /**
             * Фактическая Дата погашения займа dmY
             * @var Customer $customer
             * @var Questionnaire $questionnaire
             * @var Report $model
             */
            return $model->getContractEndActualDate($questionnaire);
        }
    ],
    'Last Payment Date'=>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Reorganized Credit Code'=>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Board Resolution flag'=>[
        'value'=>Report::EMPTY_FIELD
    ],
    'financedAmount' => [
        'label' => 'Financed Amount',
        'value'=>function($model, $customer, $questionnaire): int
        {
            /**
             * Сумма займа
             * @var Customer $customer
             * @var Questionnaire $questionnaire
             * @var Report $model
             */
            $value = !!$questionnaire->agreed_loan_amount ? $questionnaire->agreed_loan_amount : Report::COUNT_ZERO;
            return Report::round($value);
        }
    ],
    'installmentsNumber' => [
        'label'=>'Installments Number',
        'value' => Report::INSTALLMENTS_NUMBER
    ],
    'transactionType' => [
        'label' => 'Transaction Type / Sub-facility',
        'value' => Report::TRANSACTION_TYPE
    ],
    'Purpose of credit'=>[
        'value'=>Report::EMPTY_FIELD
    ],
    'paymentPeriod' => [
        'label' => 'Payment Periodicity',
        'value' => Report::PAYMENT_PERIOD
    ],
    'Payment Method'=>[
        'value'=>Report::EMPTY_FIELD
    ],
    'monthlyPaymentAmount' => [
        'label' => 'Monthly Payment Amount',
        'value'=>function($model, $customer, $questionnaire): int
        {
            /**
             * сумма к возврату по договору
             * @var Customer $customer
             * @var Questionnaire $questionnaire
             * @var Report $model
             */
            return $model->getLoanReturnAmount($questionnaire);
        }
    ],
    'First Payment Date'=>[
        'value'=>Report::EMPTY_FIELD
    ],
    'lastPaymentAmount' => [
        'label' => 'Last Payment Amount',
        'value'=>function($model, $customer, $questionnaire): int
        {
            /**
             * Последний платеж.
             * @var Customer $customer
             * @var Questionnaire $questionnaire
             * @var Report $model
             */
           return $model->getLastPaymentAmount($questionnaire);
        }
    ],
    'Next Payment Date'=>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Next Payment'=>[
        'value'=>Report::EMPTY_FIELD
    ],
    'outstandingPaymentsNumber' => [
        'label' => 'Outstanding Payments Number',
        'value' => Report::OUTSTANDING_PAYMENTS_NUMBER
    ],
    'outstandingBalance' => [
        'label' => 'Outstanding Balance',
        'value'=>function($model, $customer, $questionnaire): int
        {
            /**
             * Общий долг
             * @var Customer $customer
             * @var Questionnaire $questionnaire
             * @var Report $model
             */
            return $model->getOutstandingBalance($questionnaire);
        }
    ],
    'overduePaymentNumber' => [
        'label' => 'Overdue Payments Number',
        'value'=>function($model, $customer, $questionnaire)
        {
            /**
             * Если есть просрочка ставим 1
             * @var Customer $customer
             * @var Questionnaire $questionnaire
             * @var Report $model
             */

            return $model->getOverduePaymentsNumber($questionnaire);
        }
    ],
    'overduePaymentsAmount' => [
        'label' => 'Overdue Payments Amount',
        'value'=>function($model, $customer, $questionnaire)
        {
            /**
             * Если есть просрочка пишем общий долг
             * @var Customer $customer
             * @var Questionnaire $questionnaire
             * @var Report $model
             */
            if ($model->getOverduePaymentsNumber($questionnaire) === Report::OVERDUE_PAYMENTS_NUMBER) {
                return $model->getOverduePaymentsAmount($questionnaire);
            } else {
                return Report::EMPTY_FIELD;
            }
        }
    ],
    'overdueDays' => [
        'label' => 'Overdue Days',
        'value'=>function($model, $customer, $questionnaire)
        {
            /**
             *
             * @var Customer $customer
             * @var Questionnaire $questionnaire
             * @var Report $model
             */

            if ($model->getOverduePaymentsNumber($questionnaire) === Report::OVERDUE_PAYMENTS_NUMBER) {
                return  $model->overdueDays($questionnaire);
            } else {
                return Report::EMPTY_FIELD;
            }
        }
    ],
    'Good Type'=>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Good Value' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'New/Used Code' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Good Brand' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Manufacturing Date' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Registration number' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Provider Guarantee No 1' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Provider Subject No (Guarantor)' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Guarantor Name' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Guaranteed Amount' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Currency' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Validity Start Date' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Validity End Date' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Guarantee Type' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Asset Code' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Asset Description' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Asset Location' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Asset Appraised Value' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Asset Registry External Link' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Customer Type' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Provider Guarantee No 2' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Provider Subject No (Guarantor)|BE' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Guarantor Name|BF' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Guaranteed Amount|BG' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Currency|BH' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Validity Start Date|BI' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Validity End Date|BJ' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Guarantee Type|BK' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Asset Code|BL' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Asset Description|BM' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Asset Location|BN' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Asset Appraised Value|BO' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Asset Registry External Link|BP' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Customer Type|BQ' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Provider Guarantee No 3|BR' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Provider Subject No (Guarantor)|BS' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Guarantor Name|BT' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Guaranteed Amount|BU' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Currency|BV' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Validity Start Date|BW' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Validity End Date|BX' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Guarantee Type|BY' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Asset Code|BZ' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Asset Description|CA' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Asset Location|CB' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Asset Appraised Value|CC' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Asset Registry External Link|CD' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Customer Type|CE' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Provider Guarantee No 4' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Provider Subject No (Guarantor)|CG' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Guarantor Name|CH' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Guaranteed Amount|CI' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Currency|CJ' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Validity Start Date|CK' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Validity End Date|CL' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Guarantee Type|CM' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Asset Code|CN' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Asset Description|CO' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Asset Location|CP' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Asset Appraised Value|CQ' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Asset Registry External Link|CR' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Customer Type|CS' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Provider Guarantee No 5' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Provider Subject No (Guarantor)|CU' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Guarantor Name|CV' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Guaranteed Amount|CW' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Currency|CX' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Validity Start Date|CY' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Validity End Date|CZ' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Guarantee Type|DA' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Asset Code |DB' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Asset Description |DC' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Asset Location|DD' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Asset Appraised Value|DE' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Asset Registry External Link|DF' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Customer Type|DG' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Provider Guarantee No 6' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Provider Subject No (Guarantor)|DI' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Guarantor Name|DJ' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Guaranteed Amount|DK' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Currency|DL' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Validity Start Date|DM' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Validity End Date|DN' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Guarantee Type|DO' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Asset Code |DP' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Asset Description |DQ' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Asset Location |DR' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Asset Appraised Value |DS' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Asset Registry External Link|DT' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Customer Type|DU' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Provider Subject No (Linked Subject 1)' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Role' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Name of the Linked Subject' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Provider Subject No (Linked Subject 2)' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Role|DZ' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Name of the Linked Subject|EA' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Provider Subject No (Linked Subject 3)' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Role|EC' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Name of the Linked Subject|ED' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Provider Subject No (Linked Subject 4)' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Role|EF' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Name of the Linked Subject|EG' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Provider Subject No (Linked Subject 5)' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Role|EI' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Name of the Linked Subject|EJ' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Provider Subject No (Linked Subject 6)' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Role|EL' =>[
        'value'=>Report::EMPTY_FIELD
    ],
    'Name of the Linked Subject|EM' =>[
        'value'=>Report::EMPTY_FIELD
    ],

];
