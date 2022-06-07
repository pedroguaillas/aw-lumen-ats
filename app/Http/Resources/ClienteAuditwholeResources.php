<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ClienteAuditwholeResources extends JsonResource
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
            'ruc' => $this->ruc,
            'atts' => [
                'razonsocial' => $this->razonsocial,
                'name' => $this->name,
                'amount' => $this->amount,
            ]
        ];
    }
}