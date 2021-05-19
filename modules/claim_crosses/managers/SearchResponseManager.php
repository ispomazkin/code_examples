<?php

namespace backend\modules\claim_crosses\managers;

/**
 * Класс корректирует структуру ответа, чтобы можно было показать форму
 * жалобы на аналог
 */
final class SearchResponseManager
{
    public function updateStructure(array &$products,string $originHash): void
    {
        $products = array_map(function($product) use($originHash){
            if($product['hash']!==$originHash) {
                $product['claim'] = [
                    'hash' => $originHash,
                    'claimed_hash' => $product['hash']
                ];
            }
            return $product;
        }, $products);
    }
}

