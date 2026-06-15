<?php

namespace App\Service;

use App\Repository\PriceRepository;
use App\Http\Resources\PriceResource;
use App\Models\Price;
use App\Models\User;

class PriceService
{
    private PriceRepository $priceRepository;

    public function __construct(PriceRepository $priceRepository)
    {
        $this->priceRepository = $priceRepository;
    }

    public function createPrice(float $price)
    {
        $data = [
            'price' => $price,
            'valid_from' => now()
        ];
        $price = $this->priceRepository->create($data);
        return $price->price_id;
    }

    public function updatePrice(string $prevPriceId, string $newPrice)
    {
        $prevData = Price::find($prevPriceId);
        if ($prevData) {
            $prevData->valid_to = now();
            $prevData->save();
        }
        $newData = [
            'price'      => $newPrice,
            'valid_from' => now(),
        ];
        $newPriceRecord =  $this->priceRepository->create($newData);

        return $newPriceRecord->price_id;
    }

    public function getPrice(string $id)
    {
        return $this->priceRepository->findPriceByField('price_id', $id);
    }
}
