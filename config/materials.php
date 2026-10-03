<?php

/*
| Material de clase por tipo (parte 12 del plan de mejoras).
*/

return [

    // Tamaño máximo de un PDF, en KB.
    'pdf_max_kb' => (int) env('MATERIAL_PDF_MAX_KB', 5120),

    // Tamaño máximo de una imagen, en KB.
    'image_max_kb' => (int) env('MATERIAL_IMAGE_MAX_KB', 2560),

];
