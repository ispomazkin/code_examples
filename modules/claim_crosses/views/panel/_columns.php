<?php
use yii\helpers\Url;
use yii\helpers\Html;

return [
    [
        'class' => 'kartik\grid\SerialColumn',
        'width' => '30px',
    ],
    [
        'attribute'=>'failed_cross',
        'width' => '400px',
        'content' => function($model) {
            return mb_strtoupper($model['failed_cross']);
        }
    ],
    [
        'attribute'=>'full_user_name',
        'width' => '200px',
    ],
    [
        'attribute'=>'comment',
        'width' => '300px',
    ],
    [
        'attribute'=>'is_approved',
        'filter' => [0 => 'Отклонено', 1 => 'Одобрено', 2 => 'Новые'],
        'width' => '30px',
        'content' => function($model) {
            if (is_null($model['is_approved'])) {
                return 'Не рассмотрен';
            }
            return $model['is_approved'] ? 'Одобрено' : 'Отклонено';
        }

    ],
    [
        'attribute'=>'created_at',
        'width' => '50px',
        'content' => function($model) {
            return date('d.m.Y H:i', strtotime($model['created_at']));
        },
        'filter' => false
    ],
    [
        'class' => 'kartik\grid\ActionColumn',
        'dropdown' => false,
        'vAlign'=>'middle',
        'template' => '{accept} &nbsp; &nbsp;  {decline}',
        'buttons' => [
            'accept' => function ($url, $model) {
                if ($model['is_approved'] === null or $model['is_approved'] === 0) {
                    return Html::a(
                        '<span class="glyphicon glyphicon-ok"></span>',
                        Url::to(['panel/accept', 'id' => $model['id']]), [
                        'title' => 'Одобрить',
                        'role' => 'modal-remote', 'data-toggle' => 'tooltip',
                        'data-request-method' => 'post'
                    ]);
                }
            },
            'decline' => function ($url, $model) {
                if ($model['is_approved'] === null or $model['is_approved'] === 1) {
                    return Html::a(
                        '<span class="glyphicon glyphicon-remove"></span>',
                        Url::to(['panel/decline', 'id' => $model['id']]), [
                        'title' => 'Отклонить',
                        'role' => 'modal-remote',
                        'data-request-method' => 'post',
                    ]);
                }
            },
        ],
    ],

];   