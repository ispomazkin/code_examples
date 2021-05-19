<?php

declare(strict_types=1);

use backend\modules\report_state_credit_bureau\components\Report;
use backend\modules\crmfo_kernel_modules\Entity\Customer;
use backend\modules\crmfo_kernel_modules\Entity\Questionnaire;
use backend\modules\crmfo_kernel_modules\Entity\CustomerGender;

return [
    'recordType'=>[
        'label'=>'Record Type',
        'value'=>Report::RECORD_TYPE_ID
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
    'subjectReferenceDate'=>[
        'label'=>'Subject Reference Date',
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
            return  $customer->id;
        }
    ],
    'title' => [
        'value'=>function($model, $customer, $questionnaire)
        {
            /** @var $customer Customer */
            return $customer->gender_id === CustomerGender::ID_FEMALE ? Report::TITLE_MS :
                Report::TITLE_MR;
        }
    ],
    'firstName'=>[
        'label'=>'First Name',
        'value'=>function($model, $customer, $questionnaire)
        {
            /** @var $customer Customer */
            return $customer->first_name;
        }
    ],
    'lastName'=>[
        'label'=>'Last Name',
        'value'=>function($model, $customer, $questionnaire)
        {
            /** @var $customer Customer */
            return $customer->last_name;
        }
    ],
    'middleName' => [
        'value'=>function($model, $customer, $questionnaire)
        {
            /** @var $customer Customer */
            return $customer->middle_name;
        }
    ],
    'suffix' => [
        'value' => Report::EMPTY_FIELD
    ],
    'nickname' => [
        'value' => Report::EMPTY_FIELD
    ],
    'previousLastName' => [
        'value' => Report::EMPTY_FIELD
    ],
    'gender'=>[
        'label'=>'Gender',
        'value'=>function($model, $customer, $questionnaire)
        {
            /** @var $customer Customer */
            return $customer->gender_id === \backend\modules\crmfo_kernel_modules\Entity\CustomerGender::ID_FEMALE ?
                Report::GENDER_FEMALE:Report::GENDER_MALE;
        }
    ],
    'dateOfBirth'=>[
        'label'=>'Date of Birth',
        'value'=>function($model, $customer, $questionnaire)
        {
            /**
             * The format of this date should be DDMMYYYY.
             *  Age should be between 18 and 100 years
             * @var Customer $customer
             */
            return date('dmY',strtotime($customer->birth_date));
        }
    ],
    'placeOfBirth' => [
        'value' => Report::EMPTY_FIELD
    ],
    'countryOfBirth' => [
        'value' => Report::EMPTY_FIELD
    ],
    'nationality' => [
        'value' => Report::NATIONALITY
    ],
    'resident' => [
        'value' => Report::EMPTY_FIELD
    ],
    'civilStatus' => [
        'value' => function($model, $customer, $questionnaire){
            /** @var Customer $customer */
            return $customer->marital_status === 'married' ? Report::STATUS_MARRIED :
                Report::STATUS_NOT_MARRIED;
        }
    ],
    'numberOfDependents' => [
        'value' => Report::EMPTY_FIELD
    ],
    'carsOwned' => [
        'value' => Report::EMPTY_FIELD
    ],
    'spouseFirstName' => [
        'value' => Report::EMPTY_FIELD
    ],
    'spouseLastName' => [
        'value' => Report::EMPTY_FIELD
    ],
    'spouseMiddleName' => [
        'value' => Report::EMPTY_FIELD
    ],
    'mothersMaidenFirstName' => [
        'value' => Report::EMPTY_FIELD
    ],
    'mothersMaidenLastName' => [
        'value' => Report::EMPTY_FIELD
    ],
    'mothersMaidenMiddleName'  => [
        'value' => Report::EMPTY_FIELD
    ],
    'fatherFirstName' => [
        'value' => Report::EMPTY_FIELD
    ],
    'fatherLastName' => [
        'value' => Report::EMPTY_FIELD
    ],
    'fatherMiddleName' => [
        'value' => Report::EMPTY_FIELD
    ],
    'fatherSuffix' => [
        'value' => Report::EMPTY_FIELD
    ],
    'address1'=>[
        'label'=>'Address 1: Address Type',
        'value'=> Report::ADDRESS_TYPE_1
    ],
    'address1FullAddress'=>[
        'label'=>'Address 1: FullAddress',
        'value'=> function($model, $customer, $questionnaire)
        {
            /**
             * The field "FullAddress" should be filled in place of the following fields:
             * "StreetNo", "PostalCode", "Subdivision", "Barangay", "City", "Province", "Country".
             * Both cannot be filled in; therefore:
            - If Both "FullAddress" and some single fields (StreetNo or PostalCode or Subdivision or Barangay or City
             * or Province or Country) are filled in, then "FullAddress" willl be ignored, and only single fields are considered
             *
             * @var Customer $customer
             * @var Report $model
             */
            $addressObj = $customer->pasports[0]->addressReg ?? $customer->pasports[0]->addressAct ?? null;
            if (!$addressObj) {
                return Report::EMPTY_FIELD;
            }
            return $model->getFullAddress($addressObj);
        }
    ],
    'Address 1: StreetNo' => [
        'value' => Report::EMPTY_FIELD
    ],
    'Address 1: PostalCode' => [
        'value' => Report::EMPTY_FIELD
    ],
    'Address 1: Subdivision' => [
        'value' => Report::EMPTY_FIELD
    ],
    'Address 1: Barangay' => [
        'value' => Report::EMPTY_FIELD
    ],
    'Address 1: City' => [
        'value' => Report::EMPTY_FIELD
    ],
    'Address 1: Province' => [
        'value' => Report::EMPTY_FIELD
    ],
    'Address 1: Country' => [
        'value' => Report::EMPTY_FIELD
    ],
    'Address 1: House OwnerLessee' => [
        'value' => Report::EMPTY_FIELD
    ],
    'Address 1: Occupied Since' => [
        'value' => Report::EMPTY_FIELD
    ],
    'address2'=>[
        'label'=>'Address 2: Address Type',
        'value'=> Report::ADDRESS_TYPE_2
    ],
    'address2FullAddress'=>[
        'label'=>'Address 2: FullAddress',
        'value'=> function($model, $customer, $questionnaire)
        {
            /**
             * The field "FullAddress" should be filled in place of the following fields:
             * "StreetNo", "PostalCode", "Subdivision", "Barangay", "City", "Province", "Country".
             * Both cannot be filled in; therefore:
            - If Both "FullAddress" and some single fields (StreetNo or PostalCode or Subdivision or Barangay or City
             * or Province or Country) are filled in, then "FullAddress" willl be ignored, and only single fields are considered
             *
             * @var Customer $customer
             * @var Report $model
             */
            $addressObj = $customer->pasports[0]->addressReg ?? $customer->pasports[0]->addressAct ?? null;
            if (!$addressObj) {
                return Report::EMPTY_FIELD;
            }
            return $model->getFullAddress($addressObj);
        }
    ],
    'Address 2: StreetNo' => [
        'value' => Report::EMPTY_FIELD
    ],
    'Address 2: PostalCode' => [
        'value' => Report::EMPTY_FIELD
    ],
    'Address 2: Subdivision' => [
        'value' => Report::EMPTY_FIELD
    ],
    'Address 2: Barangay' => [
        'value' => Report::EMPTY_FIELD
    ],
    'Address 2: City' => [
        'value' => Report::EMPTY_FIELD
    ],
    'Address 2: Province' => [
        'value' => Report::EMPTY_FIELD
    ],
    'Address 2: Country' => [
        'value' => Report::EMPTY_FIELD
    ],
    'Address 2: House OwnerLessee' => [
        'value' => Report::EMPTY_FIELD
    ],
    'Address 2: Occupied Since' => [
        'value' => Report::EMPTY_FIELD
    ],
    'identification1Type' => [
        'label'=>'Identification 1: Type',
        'value'=>function($model, $customer, $questionnaire)
        {
            /**
             * @var Customer $customer
             * @var Report $model
             */
            return $model->getIdentificationTypeDomain(Report::SECTION_PRIMARY, $customer);
        }
    ],
    'identification1Number' => [
        'label'=>'Identification 1: Number',
        'value'=>function($model, $customer, $questionnaire)
        {
            /**
             * @var Customer $customer
             * @var Report $model
             */
            return $model->getIdentificationValueDomain(Report::SECTION_PRIMARY, $customer);
        }
    ],
    'Identification 2: Type' => [
        'value' => Report::EMPTY_FIELD
    ],
    'Identification 2: Number' => [
        'value' => Report::EMPTY_FIELD
    ],
    'Identification 3: Type' => [
        'value' => Report::EMPTY_FIELD
    ],
    'Identification 3: Number' => [
        'value' => Report::EMPTY_FIELD
    ],
    'idType' => [
        'label'=>'ID 1: Type',
        'value'=>function($model, $customer, $questionnaire)
        {
            /**
             * @var Customer $customer
             * @var Report $model
             */
            return $model->getIdentificationTypeDomain(Report::SECTION_SECONDARY, $customer);
        }
    ],
    'idNumber' => [
        'label'=>'ID 1: Number',
        'value'=>function($model, $customer, $questionnaire)
        {
            /**
             * @var Customer $customer
             * @var Report $model
             */
            return $model->getIdentificationValueDomain(Report::SECTION_SECONDARY, $customer);
        }
    ],
    'ID 1: IssueDate' => [
        'value'=> Report::EMPTY_FIELD
    ],
    'ID 1: IssueCountry' => [
        'value' => Report::NATIONALITY
    ],
    'ID 1: ExpiryDate' => [
        'value'=> Report::EMPTY_FIELD
    ],
    'ID 1: Issued By' => [
        'value'=>function($model, $customer, $questionnaire){
            /**
             * @var Customer $customer
             * @var Report $model
             */
            return $model->getIssuedBy($customer);
        }
    ],
    'ID 2: Type' => [
        'value' => Report::EMPTY_FIELD
    ],
    'ID 2: Number' => [
        'value' => Report::EMPTY_FIELD
    ],
    'ID 2: IssueDate' => [
        'value' => Report::EMPTY_FIELD
    ],
    'ID 2: IssueCountry' => [
        'value' => Report::EMPTY_FIELD
    ],
    'ID 2: ExpiryDate' => [
        'value' => Report::EMPTY_FIELD
    ],
    'ID 2: Issued By' => [
        'value' => Report::EMPTY_FIELD
    ],
    'ID 3: Type' => [
        'value' => Report::EMPTY_FIELD
    ],
    'ID 3: Number' => [
        'value' => Report::EMPTY_FIELD
    ],
    'ID 3: IssueDate' => [
        'value' => Report::EMPTY_FIELD
    ],
    'ID 3: IssueCountry' => [
        'value' => Report::EMPTY_FIELD
    ],
    'ID 3: ExpiryDate' => [
        'value' => Report::EMPTY_FIELD
    ],
    'ID 3: Issued By' => [
        'value' => Report::EMPTY_FIELD
    ],
    'contact1Type' => [
        'label'=>'Contact 1: Type',
        'value'=> Report::TYPE_MAIN_PHONE
    ],
    'contact1Value' => [
        'label'=>'Contact 1: Value',
        'value'=>function($model, $customer, $questionnaire)
        {
            /**
             * @var Customer $customer
             * @var Report $model
             */
            return $model->getContact1Value($customer);
        }
    ],
    'Contact 2: Type' => [
        'value' => Report::EMPTY_FIELD
    ],
    'Contact 2: Value' => [
        'value' => Report::EMPTY_FIELD
    ],
    'Employment: Trade Name' => [
        'value' => Report::EMPTY_FIELD
    ],
    'Employment: TIN' => [
        'value' => Report::EMPTY_FIELD
    ],
    'Employment: Phone Number' => [
        'value' => Report::EMPTY_FIELD
    ],
    'Employment:  PSIC' => [
        'value' => Report::EMPTY_FIELD
    ],
    'Employment: GrossIncome' => [
        'value' => Report::EMPTY_FIELD
    ],
    'Employment: Annual/Monthly Indicator' => [
        'value' => Report::EMPTY_FIELD
    ],
    'Employment: Currency' => [
        'value' => Report::EMPTY_FIELD
    ],
    'Employment: OccupationStatus' => [
        'value' => Report::EMPTY_FIELD
    ],
    'Employment: DateHiredFrom' => [
        'value' => Report::EMPTY_FIELD
    ],
    'Employment: DateHiredTo' => [
        'value' => Report::EMPTY_FIELD
    ],
    'Employment: Occupation' => [
        'value' => Report::EMPTY_FIELD
    ],
    'Sole Trader:  TradeName' => [
        'value' => Report::EMPTY_FIELD
    ],
    'Sole Trader 1: Address Type' => [
        'value' => Report::EMPTY_FIELD
    ],
    'Sole Trader 1: FullAddress' => [
        'value' => Report::EMPTY_FIELD
    ],
    'Sole Trader 1: StreetNo' => [
        'value' => Report::EMPTY_FIELD
    ],
    'Sole Trader 1: PostalCode' => [
        'value' => Report::EMPTY_FIELD
    ],
    'Sole Trader 1: Subdivision' => [
        'value' => Report::EMPTY_FIELD
    ],
    'Sole Trader 1: Barangay' => [
        'value' => Report::EMPTY_FIELD
    ],
    'Sole Trader 1: City' => [
        'value' => Report::EMPTY_FIELD
    ],
    'Sole Trader 1: Province' => [
        'value' => Report::EMPTY_FIELD
    ],
    'Sole Trader 1: Country' => [
        'value' => Report::EMPTY_FIELD
    ],
    'Sole Trader 1: House Owner/Lessee' => [
        'value' => Report::EMPTY_FIELD
    ],
    'Sole Trader 1: Occupied Since' => [
        'value' => Report::EMPTY_FIELD
    ],
    'Sole Trader 2: Address Type' => [
        'value' => Report::EMPTY_FIELD
    ],
    'Sole Trader 2: FullAddress' => [
        'value' => Report::EMPTY_FIELD
    ],
    'Sole Trader 2: StreetNo' => [
        'value' => Report::EMPTY_FIELD
    ],
    'Sole Trader 2: PostalCode' => [
        'value' => Report::EMPTY_FIELD
    ],
    'Sole Trader 2: Subdivision' => [
        'value' => Report::EMPTY_FIELD
    ],
    'Sole Trader 2: Barangay' => [
        'value' => Report::EMPTY_FIELD
    ],
    'Sole Trader 2: City' => [
        'value' => Report::EMPTY_FIELD
    ],
    'Sole Trader 2: Province' => [
        'value' => Report::EMPTY_FIELD
    ],
    'Sole Trader 2: Country' => [
        'value' => Report::EMPTY_FIELD
    ],
    'Sole Trader 2: House Owner/Lessee' => [
        'value' => Report::EMPTY_FIELD
    ],
    'Sole Trader 2: Occupied Since' => [
        'value' => Report::EMPTY_FIELD
    ],
    'Sole Trader 1:  Identification Type' => [
        'value' => Report::EMPTY_FIELD
    ],
    'Sole Trader 1:  Identification Number' => [
        'value' => Report::EMPTY_FIELD
    ],
    'Sole Trader 2:  Identification Type' => [
        'value' => Report::EMPTY_FIELD
    ],
    'Sole Trader 2: Identification Number' => [
        'value' => Report::EMPTY_FIELD
    ],
    'Sole Trader 1: Contact Type' => [
        'value' => Report::EMPTY_FIELD
    ],
    'Sole Trader 1: Contact Value' => [
        'value' => Report::EMPTY_FIELD
    ],
    'Sole Trader 2: Contact Type' => [
        'value' => Report::EMPTY_FIELD
    ],
    'Sole Trader 2: Contact Value' => [
        'value' => Report::EMPTY_FIELD
    ],
];
