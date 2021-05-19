<?php

use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model backend\modules\claim_crosses\models\ClaimCrosses */
?>
<div class="claim-crosses-view">
 
    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'hash',
            'claimed_hash',
            'user_login_guid',
            'comment',
            'is_approved',
            'created_at',
            'updated_at',
        ],
    ]) ?>

</div>
