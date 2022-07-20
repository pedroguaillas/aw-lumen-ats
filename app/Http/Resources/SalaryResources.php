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
                'user_id' => $this->user_id,
                'year' => $this->year,
                'month' => $this->month,
                'amount' => $this->amount,
                'cheque' => $this->cheque,
                'amount_cheque' => $this->amount_cheque,
                'balance' => $this->balance,
                'paid' => $this->paid,
            ]
        ];
    }
}
