<?php
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WhatsappTemplateResource extends JsonResource {
    public function toArray(Request $request): array {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'message_template' => $this->message_template,
            'is_active' => (bool) $this->is_active,
            'attach_invoice' => (bool) $this->attach_invoice,
        ];
    }
}