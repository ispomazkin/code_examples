<?php

namespace backend\modules\claim_crosses\services;

use backend\modules\claim_crosses\managers\SearchResponseManager;
use backend\modules\claim_crosses\providers\ClaimCrossesDataProviderInterface;
use backend\modules\claim_crosses\requests\ClaimRequest;
use yii\web\IdentityInterface;

/**
 * Класс для управления функционалом
 * пожаловаться на аналог
 *
 */
final class CrossesClaimService
{
    /**
     * @var IdentityInterface
     */
    protected $user;

    /**
     * @var ClaimCrossesDataProviderInterface
     */
    protected $dataProvider;

    /**
     * @var SearchResponseManager
     */
    protected $responseManager;

    public function __construct(ClaimCrossesDataProviderInterface $dataProvider, SearchResponseManager $manager, IdentityInterface $user = null )
    {
        $this->user = $user;
        $this->dataProvider = $dataProvider;
        $this->responseManager = $manager;
    }

    public function add(ClaimRequest $request): ?int
    {
        $hash = $request->hash;
        $claimed_hash = $request->claimed_hash;
        $login_guid = $this->user->login_guid ?? null;
        if ($hash && $claimed_hash) {
            return $this->dataProvider->add($request->hash, $request->claimed_hash, $request->comment, $login_guid);
        } else {
            return null;
        }
    }

    public function decline(ClaimRequest $request)
    {
        $claim_id = $request->claim_id;
        if ($claim_id) {
            return $this->dataProvider->decline($claim_id);
        } else {
            return null;
        }
    }

    public function accept(ClaimRequest $request)
    {
        $claim_id = $request->claim_id;
        if ($claim_id) {
            return  $this->dataProvider->accept($claim_id);
        } else {
            return null;
        }
    }

    public function addClaimedHash(array $products, \backend\modules\nomenclature\components\Search $searchDataProvider): array
    {
       if (!$this->shouldAddClaimedHash($searchDataProvider)) {
           return $products;
       }
       $exactProducts = &$products['exact']['products'] ?? [];
       $analogProducts = &$products['analogs']['products'] ?? [];
       $hash = $searchDataProvider->getHash();
       if (!empty($exactProducts)) {
           $this->responseManager->updateStructure($exactProducts, $hash);
       }
       if (!empty($analogProducts)) {
           $this->responseManager->updateStructure($analogProducts, $hash);
       }
       return $products;
    }


    protected function shouldAddClaimedHash($searchDataProvider): bool
    {
        return $searchDataProvider->getArticle() && $searchDataProvider->getBrand() && $searchDataProvider->getHash();
    }

}