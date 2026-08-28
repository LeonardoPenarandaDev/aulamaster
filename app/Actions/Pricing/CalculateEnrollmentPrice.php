<?php

namespace App\Actions\Pricing;

use App\Models\Level;
use App\Models\Promotion;
use App\Models\Referral;
use App\Models\Student;

class CalculateEnrollmentPrice
{
    /**
     * Reproduce el ejemplo de la sección 29 del plan: precio base del nivel,
     * menos el descuento de la promoción, menos el descuento por referido.
     * El precio final siempre se conserva como el valor realmente aplicado
     * en la matrícula, no como una referencia al precio actual del nivel.
     *
     * @return array{base_price: float, promotion_discount: float, referral_discount: float, final_price: float}
     */
    public function handle(Level $level, Student $student, ?Promotion $promotion, ?Referral $referral): array
    {
        $basePrice = (float) $level->price;

        $promotionDiscount = $promotion ? $promotion->discountFor($basePrice) : 0.0;

        $referralDiscount = 0.0;
        if ($referral) {
            $referralDiscount = $referral->referred_student_id === $student->id
                ? (float) $referral->referred_discount
                : (float) $referral->referrer_discount;
        }

        $finalPrice = max(0, $basePrice - $promotionDiscount - $referralDiscount);

        return [
            'base_price' => $basePrice,
            'promotion_discount' => $promotionDiscount,
            'referral_discount' => $referralDiscount,
            'final_price' => $finalPrice,
        ];
    }
}
