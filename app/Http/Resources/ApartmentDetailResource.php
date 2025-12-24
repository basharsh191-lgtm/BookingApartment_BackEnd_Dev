<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApartmentDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'owner_id' => $this->owner_id,
            'apartment_description' => $this->apartment_description,
            'floorNumber' => $this->floorNumber,
            'roomNumber' => $this->roomNumber,
            'free_wifi' => (bool) $this->free_wifi,
            'available_from' => $this->available_from,
            'available_to' => $this->available_to,
            'status' => $this->status,
            'governorate_id' => $this->governorate_id,
            'governorate_name' => $this->whenLoaded('governorate', fn() => $this->governorate->name),
            'city' => $this->city,
            'area' => $this->area,
            'price' => $this->price,
            'avg_rating' => $this->when(isset($this->avg_rating), $this->avg_rating),  
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
