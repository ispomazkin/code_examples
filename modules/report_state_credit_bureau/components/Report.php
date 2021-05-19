<?php

namespace backend\modules\report_state_credit_bureau\components;

use backend\helpers\BackendKernel;
use backend\modules\crmfo_kernel_modules\Entity\AccountDebt;
use backend\modules\crmfo_kernel_modules\Entity\Address;
use backend\modules\crmfo_kernel_modules\Entity\Customer;
use backend\modules\crmfo_kernel_modules\Entity\Phone;
use backend\modules\crmfo_kernel_modules\Entity\PhonesType;
use backend\modules\crmfo_kernel_modules\Entity\Questionnaire;
use backend\modules\grace_loan_time\components\settings\GracePeriodProductsSettings;
use common\helpers\DocumentNumberMaskHelper;
use yii\base\Component;
use yii\base\ErrorException;
use yii\db\Query;
use yii\helpers\ArrayHelper;
use backend\modules\manual_reg_kz\models\DocKzPassportData as Passport;
use yii\helpers\FileHelper;
use yii\web\BadRequestHttpException;
use backend\modules\crmfo_kernel_modules\Entity\AccountHistory;

/**
 * Класс генерации отчетов
 *
 * @property-read array $fields
 * @property-read Customer[] $customers
 * @property-read Questionnaire[] $questionnaires
 * @property-read string $fileReferenceDate
 * @property-read string $uploadPath
 * @property-read string $reportFileName
 * @property-read array $ids
 * @property-read array $validCustomerIds
 * @property-read array $overdueDays
 * @property-read GracePeriodProductsSettings $productSettings
 */
class Report extends Component
{
    const COUNT_ZERO = 0;

    const DELIMITER = '|';

    const OUTPUT_CHARSET = 'utf8';

    const TEST_PROVIDER_CODE = 'PF017810';

    const PROVIDER_CODE = 'FT000001';

    const TEST_USERNAME = 'FITI9NNP';

    const RECORD_TYPE_HD = 'HD';

    const RECORD_TYPE_ID = 'ID';

    const RECORD_TYPE_CI = 'CI';

    const RECORD_TYPE_FT = 'FT';

    const VERSION = '1.0';

    const EOL = "\n";

    const STANDART_SUBMISSION_TYPE = '0';

    const GENDER_MALE = 'M';

    const GENDER_FEMALE = 'F';

    const ADDRESS_TYPE_1 = 'MI';

    const ADDRESS_TYPE_2 = 'AI';

    const LIMIT_QUESTIONNAIRE_SELECTION = 300;

    const STATUS_ACTIVE = [6, 19, 26, 27];

    const STATUS_CLOSED = [10];

    const EXCLUDE_STATUS_IF_CLOSED = 27;

    const SECTION_PRIMARY = 'primary';

    const SECTION_SECONDARY = 'secondary';

    const SSS_NO = '11';

    const UMID_ID_NO = '15';

    const TIN_NO = '10';

    const PHILHEALTH_NO = '13';

    const PRC_ID_NO = '13';

    const PASSPORT_NO = '12';

    const DRIVERS_LICENSE_NO = '10';

    const POSTAL_ID_NO = '16';

    const EMPTY_FIELD = '';

    const TYPE_MAIN_PHONE = '3';

    const TYPE_MAIN_EMAIL = '7';

    const ROLE = 'B';

    const CURRENCY = 'PHP';

    const CONTRACT_TYPE = '16';

    const CONTRACT_ACTIVE = 'AC';

    const CONTRACT_CLOSED = 'CL';

    const CONTRACT_CLOSED_IN_ADVANCE = 'CA';

    const INSTALLMENTS_NUMBER = '1';

    const TRANSACTION_TYPE = 'NA';

    const PAYMENT_PERIOD = 'I';

    const OUTSTANDING_PAYMENTS_NUMBER = '1';

    const OVERDUE_PAYMENTS_NUMBER = '1';

    const STATUS_OVERDUE = 6;

    const OVERDUEO = 0;

    const OVERDUE1TO30 = 1;

    const OVERDUE31TO60 = 2;

    const OVERDUE61TO90 = 3;

    const OVERDUE91TO180 = 4;

    const OVERDUE181TO365 = 5;

    const OVERDUEMORE365 = 6;

    const TITLE_MR = 10;

    const TITLE_MS = 11;

    const NATIONALITY = 'PH';

    const STATUS_MARRIED = 2;

    const STATUS_NOT_MARRIED = 1;

    const STORAGE_PATH = '/srv/storage';

    const UNIX_TIMESTAMP_010320201 = 1614556800;

    const POSITION_CUSTOMER_ID = 4;

    const POSITION_QUESTIONNAIRE_NUMBER = 6;

    const PHONE_NUMBER_PATTERN = '\+63\s?\d{2,3}\s?\d{2,3}\s?\d{2,3}\s?\d{2,3}';

    /**
     * При тестировании подставляются тестовые
     * provider_code и username
     * @var bool
     */
    public $isTest = true;

    /**
     * Не выгружать заявки с типом документа
     */
    const EXCLUDE_DOCUMENT_TYPE = 'VOTERS_ID';

    /**
     * Дата начала формирования отчета
     * @var string 2019-05-06 17:10:58
     */
    public $date_from;

    /**
     * Дата окончания формирования отчета
     * @var string 2019-05-06 17:10:58
     */
    public $date_to;

    /**
     * Массив, описывающий структуру отчета
     * @var array
     */
    protected $_fields;

    /**
     * @var string
     */
    protected $_fileReferenceDate;

    /**
     * ИД пользвателей, у которых валидно заполнены доки
     * @var array
     */
    protected $_validCustomerIds;

    /**
     * процентные ставки кредитов
     * @var GracePeriodProductsSettings
     */
    protected $_product_settings;

    /**
     * Переменная, куда записывается содержимое файла
     * @var string
     */
    protected $fileContent;

    /**
     * Список заявок, попадающих под выгрузку
     * @var Questionnaire[]
     */
    protected $_questionnaires;

    /**
     * @var array Статусы заявок
     */
    protected $_status_questionnaires = [];

    /**
     * Название файла отчета
     * @var string
     */
    protected $_reportFileName;

    /**
     * Путь выгружаемого файла
     * @var string
     */
    protected $_uploadPath;

    /**
     * Получатели займов
     * @var Customer[]
     */
    protected $_customers;

    /**
     * Список ИД для формированя полей
     * questionnaireIds и customerIds
     *
     * @var array
     */
    protected $_ids;

    /**
     * ИД заявок, выгруженные из файла отчета
     * @var array
     */
    protected $questionnaireIds;

    /**
     * ИД пользователей, выгруженные из файла отчета
     * @var array
     */
    protected $customerIds;

    /**
     * Массив объектов AccountDebts по ключу ИД заявки
     * @var array
     */
    protected $_lastAccountDebts=[];

    /**
     * Массив объектов AccountHistory по ключу ИД заявки
     * @var array
     */
    protected $_lastAccountHistories=[];

    /**
     * Производит подготовку к повторной выгрузке отчета на основании переданного
     * файла. Задает нужные ИД заявок и юзеров
     * @param string $filename
     * @return self
     * @throws BadRequestHttpException
     */
    public function prepareIds(string $filename): self
    {
        $filePath = $this->uploadPath . '/' . $filename;

        if (!file_exists($filePath)) {
            throw new BadRequestHttpException('file is not exists');
        }

        list($this->questionnaireIds, $this->customerIds) = $this->getQuestionnaireAndCustomerIds($filePath);
        return $this;
    }

    /**
     * @param string $filePath
     * @return array
     */
    protected function getQuestionnaireAndCustomerIds(string $filePath): array
    {
        $resource = fopen($filePath, 'r');
        $questionnaireNumbers = $customerIds = [];
        foreach ($this->rows($resource) as $row) {
            $arr = explode(self::DELIMITER, $row[0]);
            if ($this->sectionIsId($arr)) {
                $customerIds[] = $arr[self::POSITION_CUSTOMER_ID];
            } elseif ($this->sectionIsCi($arr)) {
                $questionnaireNumbers[] = $arr[self::POSITION_QUESTIONNAIRE_NUMBER];
            }
        }
        $questionnaireIds = Questionnaire::find()->select('id')->where(['number' => $questionnaireNumbers])->column();
        return [$questionnaireIds, $customerIds];
    }

    /**
     * @param array $arr
     * @return bool
     */
    protected function sectionIsId(array $arr): bool
    {
        return $arr[0] === self::RECORD_TYPE_ID;
    }

    /**
     * @param array $arr
     * @return bool
     */
    protected function sectionIsCi(array $arr): bool
    {
        return $arr[0] === self::RECORD_TYPE_CI;
    }

    /**
     * @param resource $resource
     * @return \Generator|void
     */
    protected function rows($resource)
    {
        while (!feof($resource)) {
            $row = fgetcsv($resource, 4096);
            yield $row;
        }
        return;
    }

    /**
     * @param string|null $phone
     * @return bool
     */
    public function isPhoneNumberValid(string $phone = null): bool
    {
        return !!preg_match('/\d{12}/', $phone);
    }

    /**
     * @return string
     */
    public function getReportFileName(): string
    {
        if ($this->_reportFileName === null) {
            $this->_reportFileName = time() . '_report.csv';
        }
        return $this->_reportFileName;
    }

    /**
     * @param $filename
     *
     * @return string
     */
    public function getReportDate($filename): string
    {
        return date('d.m.Y H:i', $this->getReportUnix($filename));
    }

    /**
     * @param $filename
     *
     * @return mixed
     */
    protected function getReportUnix($filename): int
    {
        $parts = explode('_', $filename);
        return intval($parts[0]);
    }


    /**
     * Получить список созданных отчетов
     * С сортировке по времени создания
     * @return array
     */
    public function getListOfCreatedFiles(): array
    {
        $files = scandir($this->uploadPath);
        $createdFiles = [];
        foreach ($files as $file) {
            if ($file == '.' or $file == '..' or !$this->isFilenameValid($file)) {
                continue;
            }
            $createdFiles[$this->getReportUnix($file)] = $file;
        }
        sort($createdFiles);
        return $createdFiles;
    }

    /**
     * @param $filename
     *
     * @return bool
     */
    protected function isFilenameValid($filename): bool
    {
        return !!preg_match('/\d{10}_report\.csv/',$filename);
    }


    /**
     * This is the Reference date to which are referred the data reported in the file.
     * It could be considered as the date when provider or processor updated information on records or when the
     * Submission Data file was prepared or extracted. Typically this date is the end of the month.
     * The format of this date should be DDMMYYYY
     *
     * @return string
     */
    public function getFileReferenceDate(): string
    {
        if ($this->_fileReferenceDate === null) {
            $this->_fileReferenceDate = date('dmY', time()); //TODO
        }
        return $this->_fileReferenceDate;
    }

    /**
     * @param $date
     *
     * @return $this
     */
    public function setFileReferenceDate($date)
    {
        $this->_fileReferenceDate = $date;
        return $this;
    }

    /**
     * @param Questionnaire $questionnaire
     *
     * @return AccountHistory
     */
    public function getLastAccountHistory(Questionnaire $questionnaire)
    {
        if (!isset($this->_lastAccountHistories[$questionnaire->id]))
        {
            $this->_lastAccountHistories[$questionnaire->id] = AccountHistory::find()
                ->where(['<=','record_time', $this->getDateFileReferenceDate()])
                ->andWhere(['type_record_id'=>AccountHistory::RecordTypePayment])
                ->andWhere(['account_id'=>$questionnaire->account_id])
                ->orderBy('record_time desc')
                ->limit(1)->one();

        }
        return $this->_lastAccountDebts[$questionnaire->id];
    }


    /**
     * @param Questionnaire $questionnaire
     *
     * @return mixed
     */
    public function getLastAccountDebt(Questionnaire $questionnaire)
    {
        if (!isset($this->_lastAccountDebts[$questionnaire->id]))
        {
            $this->_lastAccountDebts[$questionnaire->id] = AccountDebt::find()
                ->where(['<=','change_time',$this->getDateFileReferenceDate()])
                ->andWhere(['account_id'=>$questionnaire->account_id])
                ->orderBy('change_time desc')
                ->limit(1)->one();
        }
        return $this->_lastAccountDebts[$questionnaire->id];
    }

    /**
     * @param string $phone
     * @return string
     */
    public function clearPhone(string $phone): string
    {
        return preg_replace('/\W/', '', $phone);
    }

    /**
     * @return Customer[]
     */
    public function getCustomers(): array
    {
        if ($this->_customers === null) {
            $this->_customers = Customer::findAll(['id' => $this->validCustomerIds]);
        }
        return $this->_customers;
    }

    /**
     * Открытые займы (За исключением кривых номеров ИД - подсвеченные красным)
     *
     * http://gitlab.dengigroup.kz/dm/phl_SaasCredit/-/issues/56#note_31462
     * @return array
     */
    public function getValidCustomerIds() {
       if ($this->_validCustomerIds === null) {
           $this->_validCustomerIds =[];
           foreach ($this->ids as $k=>$item) {
               $customer_id = $item['customer_id'];
               if (DocumentNumberMaskHelper::checkValueByMaskWihoutData($customer_id)) {
                   $this->_validCustomerIds[] = $customer_id;
               } else {
                   unset($this->_ids[$k]);
               }
           }
       }
       return $this->_validCustomerIds;
    }

    /**
     * @return Questionnaire[]
     */
    public function getQuestionnaires(): array
    {
        if ($this->_questionnaires === null) {
            $this->_questionnaires = Questionnaire::findAll(['id' => array_unique(ArrayHelper::getColumn($this->ids, 'questionnaire_id'))]);
        }
        return $this->_questionnaires;
    }

    /**
     * Получить список ид пользователей и заявок
     * для дальнейшей выгрузки
     *
     * @return array
     * @throws \Exception
     */
    protected function getIds(): array
    {
        if ($this->_ids !== null)
            return $this->_ids;

        $query = (new Query())
            ->select([
                'q.id questionnaire_id',
                'c.id customer_id'
            ])
            ->from('questionnaires q')
            ->leftJoin('customers c', 'c.id=q.customer_id')
            ->leftJoin('doc_kz_paspot_data passport', 'c.id=passport.customer_id')
           //ид самого свежего статуса
            ->innerJoin(['status' =>
                            (new Query())
                                ->select([
                                    'max(status.id) as max_status_id',
                                    'status.questionnaires_id max_status_questionnaires_id',
                                         ])
                                ->from('status_questionnaires status')
                                ->where(['<=','status.status_time',  $this->getDateFileReferenceDate()])
                                ->groupBy('status.questionnaires_id')
                       ], 'status.max_status_questionnaires_id=q.id')
            //сопоставим значение статуса для предыдущей таблицы
            ->innerJoin(['status2' =>
                             (new Query())
                                 ->select([
                                              'status2.status_type_id',
                                              'status2.id',
                                              'status2.status_time',
                                          ])
                                 ->from('status_questionnaires status2')
                        ], 'status.max_status_id=status2.id')
            //отфильтруем по тф
            ->innerJoin(['phones'=>
                (new Query())
                    ->select('all_phones.customer_id')
                    ->from('phones all_phones')
                    ->where('all_phones.number_phone ~ \''.self::PHONE_NUMBER_PATTERN.'\'')
                    ->groupBy('all_phones.customer_id')
                ],'phones.customer_id=c.id')
            ->where(['not in', 'passport.doc_type', self::EXCLUDE_DOCUMENT_TYPE]);

        $query->andWhere(['<=','status2.status_time', $this->getDateFileReferenceDate()]);
        $cloneQuery = clone($query);
        $where = 'status2.status_type_id in (' . implode(',', self::STATUS_ACTIVE) . ')';
        $query->andWhere($where);
        $ids = $query->all();
        $this->_ids = array_merge($this->findIdsAfter010320201($cloneQuery),$ids);
        return $this->_ids;
    }

    /**
     * Если дата составления отчета 1 марта 2021 или позже:
     *  1. Берем все открытые на дату отчета
     *  2. Берем закрытые, если они были открыты в феврале 2021 или позже и закрылись в выгружаемом месяце
     *  http://gitlab.dengigroup.kz/dm/phl_SaasCredit/-/issues/56#note_31710
     * @param $query Query
     * @return array
     * @throws \Exception
     */
    protected function findIdsAfter010320201(Query $query) {
        $ids = [];
        if ($this->isReportDateAfter010320201()) {
            $startDate = $this->getDateTimeFromUnix(self::UNIX_TIMESTAMP_010320201);
            $reportDate = $this->getDateTimeFromUnix( $this->getTimestampFileReferenceDate($this->date_to));
            $query->innerJoin('account_history ac','q.account_id=ac.account_id');
            //статусы закрыт
            $query->andWhere('status2.status_type_id in (' . implode(',', self::STATUS_CLOSED) . ')');
            //Открытие после 01032021 00 00 00
            $query->andWhere(['ac.type_record_id' => AccountHistory::RecordTypeTransfer]);
            $query->andWhere(['>=','ac.record_time', $startDate]);
            //закрытие в выгружаемом месяце
            $query->andWhere(['<=','status2.status_time', $this->getLastDateOfThisMonth($reportDate)]);
            $query->andWhere(['>=','status2.status_time', $this->getFirstDateOfThisMonth($reportDate)]);
            $ids = $query->all();
        }
        return $ids;
    }

    /**
     * @return bool
     */
    protected function isReportDateAfter010320201(): bool {
        return self::UNIX_TIMESTAMP_010320201 < $this->getTimestampFileReferenceDate();
    }

    /**
     * Формирует шапку переданного разела файла
     *
     * @param array $columnSettings
     * @return string
     * @deprecated
     */
    protected function writeLabels(array $columnSettings): string
    {
        $labels = ArrayHelper::getColumn($columnSettings, 'label');
        return $this->createRow($labels);
    }

    /**
     * @param array $columns
     * @return string
     */
    protected function createRow(array $columns): string
    {
        return implode(self::DELIMITER, $columns) . self::EOL;
    }

    /**
     * @param Questionnaire $questionnaire
     * @return string
     * @throws ErrorException
     */
    public function getContractPhaseDomain(Questionnaire $questionnaire): string
    {
        $status_type_ids = $this->getStatusTypeIds($questionnaire);
        $last_status = $status_type_ids[count($status_type_ids) - 1];
        if (in_array($last_status, self::STATUS_ACTIVE)) {
            return self::CONTRACT_ACTIVE;
        }
        elseif (in_array($last_status, self::STATUS_CLOSED) && in_array(self::EXCLUDE_STATUS_IF_CLOSED, $status_type_ids)) {
            return self::CONTRACT_CLOSED_IN_ADVANCE;
        }
        elseif (in_array($last_status, self::STATUS_CLOSED)) {
            return self::CONTRACT_CLOSED;
        }
        else throw new ErrorException('Status ' . $last_status . ' can not be converted to needle value. Questionnaire is ' . $questionnaire->number);
    }

    /**
     * @param Questionnaire $questionnaire
     * @return array
     */
    public function getStatusTypeIds(Questionnaire $questionnaire): array
    {
        if (!isset($this->_status_questionnaires[$questionnaire->id])) {
            $statusQuestionnaires = $questionnaire->getStatusQuestionnaires()
                ->andWhere(['<=','status_time', $this->getDateFileReferenceDate()])
                ->all();
            $this->_status_questionnaires[$questionnaire->id] =
                ArrayHelper::getColumn($statusQuestionnaires,'status_type_id');
        }
        return $this->_status_questionnaires[$questionnaire->id];
    }


    /**
     * Вычисление конкретного элемента
     *
     * @param $data
     * @param Customer|null $customer
     * @param Questionnaire|null $questionnaire
     * @return string
     */
    protected function calculateValue($data, $customer = null, $questionnaire = null)
    {
        if (is_callable($data)) {
            return call_user_func($data, $this, $customer, $questionnaire);
        }
        return $data;
    }


    /**
     * Формирование HD части документа
     *
     * @param array $columnSettings
     * @param boolean $isFooter
     */
    protected function generateHeader(array $columnSettings, $isFooter = false)
    {
        if (!$isFooter) {
            $this->fileContent = '';
        }
        $columns = [];
        foreach ($columnSettings as $columnSetting) {
            $columns[] = $this->calculateValue($columnSetting['value']);
        }
        $this->fileContent .= $this->createRow($columns);
    }

    /**
     * @return \Generator
     */
    protected function generateCustomers(): \Generator
    {
        foreach ($this->customers as $customer) {
            yield $customer;
        }
    }

    /**
     * @return \Generator
     */
    protected function generateQuestionnaires(): \Generator
    {
        foreach ($this->questionnaires as $questionnaire) {
            yield $questionnaire;
        }
    }


    /**
     * Формировние ID части документа
     * @param array $columnSettings
     */
    protected function generateId(array $columnSettings)
    {
        foreach ($this->generateCustomers() as $customer) {
            /** @var Customer $customer */
            $columns = [];
            foreach ($columnSettings as $columnSetting) {
                $columns[] = $this->calculateValue($columnSetting['value'], $customer);
            }
            $this->fileContent .= $this->createRow($columns);
        }
    }

    /**
     * @param array $columnSettings
     */
    protected function generateFt(array $columnSettings)
    {
        $this->generateHeader($columnSettings, true);
    }


    /**
     * Формировние CI части документа
     * @param array $columnSettings
     */
    protected function generateCi(array $columnSettings)
    {
        foreach ($this->generateQuestionnaires() as $questionnaire) {
            /** @var Questionnaire $questionnaire */
            $columns = [];
            $customer = null;
            foreach ($columnSettings as $columnSetting) {
                if (!$customer) {
                    $customer = $questionnaire->customer;
                }
                /** @var Customer $customer */
                $columns[] = $this->calculateValue($columnSetting['value'], $customer, $questionnaire);
            }
            $this->fileContent .= $this->createRow($columns);
        }
    }

    /**
     * @param Customer $customer
     * @return string
     * @throws ErrorException
     */
    public function getIssuedBy(Customer $customer): string
    {
        $issuedArray = require __DIR__ . '/../config/issuedBy.php';
        $primary = intval($this->getIdentificationTypeDomain(self::SECTION_PRIMARY, $customer));
        $secondary = intval($this->getIdentificationTypeDomain(self::SECTION_SECONDARY, $customer));
        return $issuedArray[$primary] ?? $issuedArray[$secondary] ?? self::EMPTY_FIELD;
    }


    /**
     * Получить номер для поля идентификации  зависимости от переданной секции
     *
     * @param string $section 'primary' | 'secondary'
     * @param Customer $customer
     * @return string
     * @throws ErrorException
     */
    public function getIdentificationTypeDomain(string $section, Customer $customer): string
    {
        if ($section === self::SECTION_PRIMARY) {
            switch ($customer->passportData->doc_type) {
                case Passport::SOCIAL_SECURITY_SYSTEM_SSS:
                    return self::SSS_NO;
                    break;
                case Passport::UMID_ID:
                    return self::UMID_ID_NO;
                    break;
                case Passport::TIN:
                    return self::TIN_NO;
                    break;
                case Passport::PHILHEALTH_ID:
                    return self::PHILHEALTH_NO;
                    break;
                default:
                    return self::EMPTY_FIELD;
            }
        } elseif ($section === self::SECTION_SECONDARY) {
            switch ($customer->passportData->doc_type) {
                case Passport::PROFESSIONAL_REGULATION_COMMISSION_PAS_ID:
                    return self::PRC_ID_NO;
                    break;
                case Passport::PASSPORT:
                    return self::PASSPORT_NO;
                    break;
                case Passport::DRIVER_LICENSE:
                    return self::DRIVERS_LICENSE_NO;
                    break;
                case Passport::POSTAL_ID;
                    return self::POSTAL_ID_NO;
                    break;
                default:
                    return self::EMPTY_FIELD;
            }
        } else throw new ErrorException('section is not allowed');
    }

    /**
     * Получить значение для поля идентификации  зависимости от переданной секции
     *
     * @param string $section
     * @param Customer $customer
     * @return string
     * @throws ErrorException
     */
    public function getIdentificationValueDomain(string $section, Customer $customer): string
    {
        return $this->getIdentificationTypeDomain($section, $customer) === self::EMPTY_FIELD ? self::EMPTY_FIELD :
            $customer->passportData->number;
    }

    /**
     * Сохранение файла
     */
    public function saveFile(): self
    {
        $filePath = $this->uploadPath . '/' . $this->reportFileName;
        file_put_contents($filePath, $this->fileContent);
        return $this;
    }

    /**
     * @return string
     * @throws \yii\base\Exception
     */
    public function getUploadPath(): string
    {
        if ($this->_uploadPath === null) {
            $this->_uploadPath = \Yii::getAlias(self::STORAGE_PATH . '/credit_reports');
            FileHelper::createDirectory($this->_uploadPath);
        }
        return $this->_uploadPath;
    }

    /**
     * Непосредственно процесс генерация файла,
     * который разбит на состаявляющие
     */
    public function generateReport()
    {
        foreach ($this->fields as $fileComponent => $columnSettings) {
            $this->{"generate" . ucfirst($fileComponent)}($columnSettings);
        }
        return $this;
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return $this->fileContent;
    }

    /**
     * Массив, задающий структуру файла
     * @return array
     */
    public function getFields(): array
    {
        if ($this->_fields === null) {
            $this->_fields = require __DIR__ . '/../columns/columns.php';
        }
        return $this->_fields;

    }

    /**
     * @param Address $address
     * @return string
     */
    public function getFullAddress(Address $address): string
    {
        if ($address->detailed_address) {
            return implode(' ', [
                $address->detailed_address,
                $address->city,
                $address->district,
                $address->country
            ]);
        }
        return implode(' ', [
            $address->house_number,
            $address->street,
            $address->city,
            $address->district,
            $address->country
        ]);
    }

    /**
     * @param Questionnaire $questionnaire
     * @return float
     */
    public function getLoanReturnAmount(Questionnaire $questionnaire): float
    {
        $dayPay = ($questionnaire->agreed_loan_amount * $questionnaire->creditProduct->credit_procent) / 100;
        $allPay = $dayPay * $questionnaire->agreed_period_loan;
        $rule = $this->productSettings->getProductPeriod($questionnaire->credit_product_id);
        if ($rule) {
            $graceDays = intval($rule['max']) - intval($rule['min']) + 1;
            $allPay = $dayPay * ($questionnaire->agreed_period_loan - $graceDays);
            $gracePay = $questionnaire->agreed_period_loan * $rule['percent'] * $graceDays / 100;
            $allPay += $gracePay;
        }
        $total = self::round($allPay + $questionnaire->agreed_loan_amount);
        return $total;
    }

    /**
     * @return GracePeriodProductsSettings
     */
    protected function getProductSettings(): GracePeriodProductsSettings
    {
        if ($this->_product_settings === null) {
            $this->_product_settings = BackendKernel::GetKernel()->GetSettings('grace_products_settings');
        }
        return $this->_product_settings;
    }

    /**
     * @param float $num
     * @return int
     */
    public static function round(float $num): float
    {
        return round($num);
    }

    /**
     * @param Customer $customer
     * @return string
     */
    public function getExpireDate(Customer $customer): string
    {
        $expire_date = $customer->passportData->expire_date;
        if (is_string($expire_date)) {
            //смена формата  2020-03-19 => 19032020
            $parts = explode('-', $expire_date);
            $parts = array_reverse($parts);
            return implode('', $parts);
        }
        return Report::EMPTY_FIELD;
    }

    /**
     * @param null $value
     * @return string
     */
    public function getOverdueDaysDomain(int $value): int
    {
        if ($value == 0) {
            return self::OVERDUEO;
        }
        elseif ($value >= 1 and $value <= 30) {
            return self::OVERDUE1TO30;
        }
        elseif ($value >= 31 and $value <= 60) {
            return self::OVERDUE31TO60;
        }
        elseif ($value >= 61 and $value <= 90) {
            return self::OVERDUE61TO90;
        }
        elseif ($value >= 91 and $value <= 180) {
            return self::OVERDUE91TO180;
        }
        elseif ($value >= 181 and $value <= 365) {
            return self::OVERDUE181TO365;
        }
        return self::OVERDUEMORE365;
    }

    /**
     * Обратить внимание на поля, значения которых зависят от даты составления отчета
     * http://gitlab.dengigroup.kz/dm/phl_SaasCredit/-/issues/56#note_31683
     *
     * @return integer
     * @throws \yii\db\Exception
     */
    public function getOverdueDays(Questionnaire $questionnaire)
    {
        $overdueDays =  \Yii::$app->db->createCommand("select coalesce(sum(count),0) as sum from(
            SELECT tab.account_id,
                count(DISTINCT tab.change_time::date) AS count,
                min(tab.change_time)::date AS min,
                max(tab.change_time)::date AS max
               FROM (SELECT account_debt.account_id,
                        account_debt.change_time,
                        account_debt.change_time::date - dense_rank() OVER (ORDER BY account_debt.change_time::date)::integer AS g
                       FROM account_debt
                      join questionnaires q on q.account_id = account_debt.account_id and number=:number
                      WHERE account_debt.penalties_debt > 0::numeric and account_debt.account_id = q.account_id and account_debt.change_time::date <= :status_time
                      GROUP BY account_debt.account_id, account_debt.change_time
                      ORDER BY account_debt.change_time) tab
              GROUP BY tab.account_id, tab.g)res",[
                  ':status_time' => $this->getDateFileReferenceDate(),
                  ':number' => $questionnaire->number
        ])->queryScalar();
        return $overdueDays ? $overdueDays : Report::COUNT_ZERO;
    }


    /**
     * @param Questionnaire $questionnaire
     * @return float|int
     */
    public function getLastPaymentAmount(Questionnaire $questionnaire) {
        $payments = $questionnaire
            ->getPayments()
            ->andWhere(['<=','record_time',$this->getDateFileReferenceDate()])
            ->all();
        $payment = array_pop($payments);
        $record_rum = $payment->record_sum ?? null;
        $value = ($record_rum ? $record_rum: Report::COUNT_ZERO);
        return Report::round($value);
    }


    /**
     * @param Questionnaire $questionnaire
     * @return float|int
     */
    public function getOutstandingBalance(Questionnaire $questionnaire) {
        $lastDebt = $this->getLastAccountDebt($questionnaire);
        /** @var $lastDebt AccountDebt */
        $value = $lastDebt->total_debt ?? $questionnaire->agreed_loan_amount ?? Report::COUNT_ZERO;
        return  Report::round($value);
    }

    /**
     * @param Questionnaire $questionnaire
     * @return float|int
     */
    public function getOverduePaymentsAmount(Questionnaire $questionnaire) {
        $value =  in_array(Report::STATUS_OVERDUE,$this->getStatusTypeIds($questionnaire)) ?
            $this->getOutstandingBalance($questionnaire):Report::COUNT_ZERO;
        return Report::round($value);
    }

    /**
     * @param Questionnaire $questionnaire
     * @return false|string
     * @throws ErrorException
     */
    public function getContractEndActualDate(Questionnaire $questionnaire) {
        $dateTime = $questionnaire->statusQueClose->status_time ?? null;
        $contractPhaseDomain = $this->getContractPhaseDomain($questionnaire);
        if (is_null($dateTime) or !in_array($contractPhaseDomain,[self::CONTRACT_CLOSED, self::CONTRACT_CLOSED_IN_ADVANCE])
        ) {
            return Report::EMPTY_FIELD;
        }
        $closeTimestamp = $this->getTimestampFromDateTime($dateTime);
        return $this->getDateTimeFromUnix($closeTimestamp, 'dmY');
    }

    /**
     * Получить таймстамп, эквивалетный дате формирования отчета
     * @param string|null $dmY
     * @return int
     */
    public function getTimestampFileReferenceDate(string $dmY = null): int
    {
        $dmY = $dmY ?? $this->fileReferenceDate;
        return $this->getTimestampFromDateTime($dmY, 'dmY');
    }

    /**
     * @param string $date
     * @param string $format
     * @return int
     */
    protected function getTimestampFromDateTime(string $date, string $format='Y-m-d H:i:s.u'): int {
        $dateTime = \DateTime::createFromFormat($format, $date);
        if ($dateTime === false) {
            $dateTime = \DateTime::createFromFormat('Y-m-d H:i:s', $date);
        }
        return  $dateTime->getTimestamp();
    }

    /**
     * На какую дату формируется отчет
     *
     * @param string $format
     *
     * @return string
     */
    public function getDateFileReferenceDate($format='Y-m-d'): string
    {
        return date($format, $this->getTimestampFileReferenceDate()).' 23:59:59';
    }

    /**
     * Проверяет, явялется ли переданная дата последним днем месяца
     *
     * @param string|null $date переданная дата в формате YYYY-MM-DD
     * @return bool
     * @throws \Exception
     */
    protected function isLastMonthDay($date): bool
    {
        $dateTime = new \DateTime($date);
        $dateTime->modify('last day of this month');
        return $dateTime->format('dmY') === date('dmY', strtotime($date));
    }

    /**
     * Получить дату, соответствующую последнему дню текущего месяца
     *
     * @param string|null $date переданная дата в формате YYYY-MM-DD
     * @return string
     * @throws \Exception
     */
    protected function getLastDateOfThisMonth($date): string
    {
        $dateTime = new \DateTime($date);
        $dateTime->modify('last day of this month');
        return $dateTime->format('Y-m-d') . ' 23:59:59';
    }

    /**
     * Получить дату, соответствующую первому дню текущего месяца
     *
     * @param string $date
     * @return string|null
     * @throws \Exception
     */
    protected function getFirstDateOfThisMonth($date): string
    {
        $dateTime = new \DateTime($date);
        $dateTime->modify('first day of this month');
        return $dateTime->format('Y-m-d') . ' 00:00:00';
    }

    /**
     * @param int $timestamp
     * @param string $format
     * @return string
     */
    protected function getDateTimeFromUnix(int $timestamp,string $format = 'Y-m-d H:i:s'): string {
        return  date($format, $timestamp);
    }

    /**
     * @param Customer $customer
     * @return mixed
     */
    public function getContact1Value(Customer $customer) {
        $phoneNumber = Report::EMPTY_FIELD;
        $phone = $customer->getPhones()->andWhere(['phone_type_id' => PhonesType::TYPE_MOBILE])->one();
        /** @var Phone $phone */
        if ($phone) {
            $phoneNumber = $this->clearPhone($phone->number_phone);
            $phoneNumber =  $this->isPhoneNumberValid($phoneNumber) ? $phoneNumber : Report::EMPTY_FIELD;
        }
        return $phoneNumber;
    }

    /**
     * @param Questionnaire $questionnaire
     * @return string
     */
    public function getOverduePaymentsNumber(Questionnaire $questionnaire): string {
        return in_array(Report::STATUS_OVERDUE,$this->getStatusTypeIds($questionnaire)) ? Report::OVERDUE_PAYMENTS_NUMBER:
            Report::EMPTY_FIELD;
    }

    /**
     * @param Questionnaire $questionnaire
     * @return int
     * @throws \yii\db\Exception
     */
    public function overdueDays(Questionnaire $questionnaire): int {
        if (in_array(Report::STATUS_OVERDUE,$this->getStatusTypeIds($questionnaire))) {
            $overdueDays =  $this->getOverdueDays($questionnaire);
        }
        else return Report::COUNT_ZERO;
        return  $this->getOverdueDaysDomain($overdueDays);
    }
}
