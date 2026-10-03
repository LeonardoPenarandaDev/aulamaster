<?php

/*
| Contratos con firma electrónica (parte 6 del plan de mejoras).
*/

return [

    // Días que dura el enlace de firma a distancia.
    'link_days' => (int) env('CONTRACT_LINK_DAYS', 7),

    // Minutos de validez del código de verificación que llega por correo.
    'otp_minutes' => (int) env('CONTRACT_OTP_MINUTES', 15),

    // Intentos permitidos para escribir el código antes de pedir uno nuevo.
    'otp_max_attempts' => 5,

    // Tamaño máximo de cada foto del documento de identidad, en KB.
    'id_photo_max_kb' => 5120,

    // Pendiente de decisión (parte 6.8): si la foto del documento es
    // obligatoria también en la firma a distancia.
    'remote_id_photo_required' => (bool) env('CONTRACT_REMOTE_ID_PHOTO_REQUIRED', false),

    // Minutos durante los cuales la foto tomada en la oficina sirve para los
    // siguientes contratos del mismo firmante, sin volver a tomarla.
    'office_photo_reuse_minutes' => 120,

];
