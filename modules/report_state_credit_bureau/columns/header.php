<?php

declare(strict_types=1);

use backend\modules\report_state_credit_bureau\components\Report;

return [
    'recordType'=>[
        'label'=>'Record Type',
        'value'=>Report::RECORD_TYPE_HD
    ],
    'providerCode'=>[
        'label'=>'Provider Code',
        'value'=>function($model)
        {
            /** @var $model Report*/
            return $model->isTest ? Report::TEST_PROVIDER_CODE : Report::PROVIDER_CODE;
        }
    ],
    'fileReferenceDate'=>[
        'label'=>'File Reference Date',
        'value'=>function($model)
        {
            /** @var $model Report*/
            return $model->fileReferenceDate;
        }
    ],
    'version'=>[
        'label'=>'Version',
        'value'=>Report::VERSION
    ],
    'submissionType'=>[
        'label'=>'Submission Type',
        'value'=>Report::STANDART_SUBMISSION_TYPE
    ],
    'ProviderComments'=>[
         'value'=>Report::EMPTY_FIELD
    ]

];
