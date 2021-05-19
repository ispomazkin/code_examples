<?php

namespace backend\modules\claim_crosses\controllers;

use backend\modules\claim_crosses\requests\ClaimRequest;
use backend\modules\claim_crosses\services\CrossesClaimService;
use Yii;
use backend\modules\claim_crosses\models\SearchClaimCrosses;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\filters\VerbFilter;
use \yii\web\Response;

/**
 * Управление списком жалоб на кросы
 */
class PanelController extends Controller
{
    const INFO = 'Запросы с формы пожаловаться на аналог';

    /**
     * @var CrossesClaimService
     */
    protected $service;


    public function __construct(
        $id,
        $module,
        CrossesClaimService $service,
        $config = [])
    {
        $this->service = $service;
        parent::__construct($id, $module, $config);
    }

    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['post'],
                    'accept' => ['post'],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $searchModel = new SearchClaimCrosses();
        $dataProvider = new ActiveDataProvider();
        $dataProvider = $searchModel->search($dataProvider, Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionDecline($id)
    {
        $request = $this->getRequest($id);
        \Yii::$app->response->format = Response::FORMAT_JSON;

        if (!$request->validate()) {
            return [
                'message' => $request->getStringErrors()
            ];
        } else {
            $this->service->decline($request);
        }

        return ['forceClose'=>true,'forceReload'=>'#crud-datatable-pjax'];
    }

    protected function getRequest($id)
    {
        $request = new ClaimRequest(['claim_id'=>$id]);
        $request->setScenario(ClaimRequest::SCENARIO_EDIT);
        return $request;
    }

    public function actionAccept($id)
    {
        $request = $this->getRequest($id);
        \Yii::$app->response->format = Response::FORMAT_JSON;

        if (!$request->validate()) {
            return [
                'message' => $request->getStringErrors()
            ];
        } else {
            $this->service->accept($request);
        }
        return ['forceClose'=>true,'forceReload'=>'#crud-datatable-pjax'];
    }

}
