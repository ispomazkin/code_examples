<?php

namespace backend\modules\report_state_credit_bureau;

use backend\modules\crmfo_kernel_api\src\Module\AbstractModule;
use backend\modules\crmfo_kernel_api\api\Module\ModuleInfo;
use backend\modules\report_state_credit_bureau\controllers\SettingController;

/**
 * Класс конфигурации для модуля отчетности в гос. кредит бюро
 */
class ReportsComponentsModule extends AbstractModule
{
    public function GetComponents()
    {
        return[
        ];
    }
    
    public function GetControllers()
    {
        return [
            SettingController::class
        ];
    }

    public static function GetModuleInfo()
    {
        $moduleInfo = new ModuleInfo();
        $moduleInfo->setDescription('Модуль отчетов в гос. кредит бюро');
        $moduleInfo->setLoadData(['reports_state_credit' => ['class' => ReportsModule::class]]);
        $moduleInfo->setName('reports_state_credit');
        return $moduleInfo;
    }
}
