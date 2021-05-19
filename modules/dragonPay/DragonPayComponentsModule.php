<?php

namespace backend\modules\dragonPay;

use backend\helpers\BackendKernel;
use backend\modules\crmfo_kernel_api\src\Module\AbstractModule;
use backend\modules\crmfo_kernel_api\api\Module\ModuleInfo;
use backend\modules\dragonPay\cron\RetrieveListChannels;

/**
 * Конфигурация модуля dragonpay
 */
class DragonPayComponentsModule extends AbstractModule {

    protected static $is_enabled;

    /**
     * @return array|string
     */
    public function GetComponents(): array
    {
        return [
            //cron
            RetrieveListChannels::class
        ];
    }

    /**
     * @return array|string
     */
    public function GetControllers(): array {
        return [
            controllers\SettingsController::class,
        ];
    }

    /**
     * Массив настроек модуля
     *
     * @return array
     */
    public static function getSettingsAsArray(): array
    {
        return [
            'name'=>'DRAGON_PAY',
            'description'=>'Платежная система DragonPay',
            'version'=>'1.0 beta',
            'class'=>DragonPayModule::class,
            'component_class'=>self::class,
            'enabled'=>true
        ];
    }

    /**
     * @return ModuleInfo
     */
    public static function GetModuleInfo(): ModuleInfo {
        $moduleInfo = new ModuleInfo();
        $settings = self::getSettingsAsArray();
        $moduleInfo->setDescription($settings['description']);
        $moduleInfo->setName($settings['name']);
        $moduleInfo->setVersion($settings['version']);
        $moduleInfo->setLoadData(['dragonpay'=>['class' => DragonPayModule::class]]);
        return $moduleInfo;
    }

    /**
     * Проверка, активен ли модуль
     *
     * @return bool
     */
    public static function isEnabled(): bool
    {
        if (self::$is_enabled===null) {
            $settings = self::getSettingsAsArray();
            self::$is_enabled = (bool)(\Yii::$app->getModule("loader")->crmfo_modules_loader->CheckModule($settings['name']));
        }
        return  self::$is_enabled;
    }

}
