<?php

namespace backend\modules\claim_crosses;

use backend\common\models\BaseModuleProject;

/**
 * Модуль раздела Пожаловаться на аналог
 */
class Module extends BaseModuleProject
{
    /**
     * {@inheritdoc}
     */
    public $controllerNamespace = 'backend\modules\claim_crosses\controllers';

    /**
     * Возращает описание модуля
     *        return  [
     *              'label' => 'Наименование модуля',
     *              'icon' => 'иконка модуля, например: file-code-o',
     *              'url' => '#',
     *              'desc'=>'Описание модуля'
     *              ];
     * @return array
     */
    public static function getModuleInfo()
    {
        return [
            'label' => 'Кросы',
            'icon'  => 'book',
            'url'   => '#',
            'desc'  => 'Модуль управляния кросами сайта'
        ];
    }

    /**
     * Возращает масив и контолеров и экшенов модуля для построяния меню
     *          return [
     *              'label' => 'Нзвание экшена',
     *              'icon' => 'иконка экшена, например: circle-o',
     *              'url' => пример : ['site/login'],
     *              'desc'=>'Описание экшена',
     *               Необезательный параметр, можно групировать меню любой вложености
     *              'items'=> [
     *                  'label' => 'Нзвание экшена',
     *                  'icon' => 'иконка экшена, например: circle-o',
     *                  'url' => пример : ['site/login'],
     *                  'desc'=>'Описание экшена',
     *                  'items'=> [
     *                      ..........
     *                  ]
     *              ]
     *          ]
     * @return array
     */
    public static function getMenuRoutesModuleInfo()
    {
        return [
            [
                'label' => 'Запросы из формы Пожаловаться на аналог',
                'icon'  => 'file-text-o',
                'url'   => ['/claim_crosses/panel'],
                'desc'  => 'Жалобы на некорректный крос',
            ],
        ];
    }
}
