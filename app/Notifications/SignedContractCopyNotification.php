<?php

namespace App\Notifications;

use App\Models\ContractSignature;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Copia en PDF del contrato para quien lo firmó a distancia (parte 6.8 del
 * plan de mejoras).
 */
class SignedContractCopyNotification extends BaseNotification
{
    public function __construct(protected ContractSignature $signature)
    {
        $this->signature->loadMissing(['template:id,name', 'student:id,name']);
    }

    public function title(): string
    {
        return "Copia de tu contrato: {$this->signature->template->name}";
    }

    public function lines(): array
    {
        return [
            "Hola {$this->signature->signer_name}, adjuntamos la copia en PDF de «{$this->signature->template->name}» que firmaste para {$this->signature->student->name}.",
            'Guárdala para tus registros.',
        ];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $mail = parent::toMail($notifiable);

        if ($this->signature->pdf_path && Storage::disk('local')->exists($this->signature->pdf_path)) {
            $mail->attach(Storage::disk('local')->path($this->signature->pdf_path), [
                'as' => Str::slug($this->signature->template->name).'.pdf',
                'mime' => 'application/pdf',
            ]);
        }

        return $mail;
    }
}
