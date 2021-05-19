<?php

namespace backend\modules\dragonPay\cron;

use backend\modules\crmfo_kernel_api\src\Cron\Components\AbstractCronOperation;
use backend\modules\crmfo_kernel_api\src\Cron\Annotations\CronOperationAnnotation;
use backend\modules\crmfo_kernel_api\exceptions\Kernel\ComponentNotExistsException;
use backend\modules\dragonPay\components\DragonPayComponent;
use backend\modules\dragonPay\DragonPayComponentsModule;
use backend\modules\dragonPay\DragonPayModule;

/**
 * Получение списка каналов для платежной системы DragonPay
 *
 * @backend\modules\crmfo_kernel_api\src\Cron\Annotations\CronOperationAnnotation(name="cron_retrieve_list_channels")
 */
class RetrieveListChannels extends AbstractCronOperation{

    /**
     * @param string         $timezone
     * @param \DateTime|null $rundate
     *
     * @throws \yii\httpclient\Exception
     */
    public function Execute($timezone = "", \DateTime $rundate = null) {
        if (DragonPayComponentsModule::isEnabled()) {
            (new DragonPayComponent())->createAvailableProcessors();
        }
    }

    /**
     * @return string
     */
    public function GetSchedullerTime() {
        return "*/30 * * * *";
    }

}
