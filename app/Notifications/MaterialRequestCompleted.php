<?php

namespace App\Notifications;

use App\Models\MaterialRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MaterialRequestCompleted extends Notification
{
    use Queueable;

    public function __construct(public MaterialRequest $materialRequest) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $this->materialRequest->loadMissing('items:id,material_request_id,sap_material_code');

        return [
            'material_request_id' => $this->materialRequest->id,
            'request_number' => $this->materialRequest->request_number,
            'material_codes' => $this->materialRequest->items
                ->pluck('sap_material_code')
                ->filter()
                ->values()
                ->all(),
            'message' => "Pengajuan {$this->materialRequest->request_number} telah selesai diproses.",
        ];
    }
}
