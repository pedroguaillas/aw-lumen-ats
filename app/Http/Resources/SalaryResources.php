<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SalaryResources extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'atts' => [
                'user_id' => $this->year,
                'year' => $this->year,
                'month' => $this->month,
                'amount' => $this->amount,
                'cheque' => $this->note,
                'amount_cheque' => $this->type,
                'balance' => $this->voucher,
            ]
        ];
    }
}
