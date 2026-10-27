<?php

namespace App\Services\Tax;

class GSTCalculator
{
    /**
     * Calculate base amount, CGST, SGST, total GST, and net amount for a given gross amount and GST percentage.
     * Round all values to 2 decimal places using round().
     *
     * @param float $amount
     * @param float $gstPercent
     * @return array
     */
    public static function calculate(float $amount, float $gstPercent): array
    {
        $gstAmount = round($amount * ($gstPercent / 100), 2);
        
        // Tamil Nadu intra-state: CGST and SGST are half of total GST
        $cgst = round($gstAmount / 2, 2);
        $sgst = $gstAmount - $cgst; // Ensure they perfectly sum up to total GST

        $netAmount = round($amount + $gstAmount, 2);

        return [
            'base' => round($amount, 2),
            'cgst' => $cgst,
            'sgst' => $sgst,
            'total_gst' => $gstAmount,
            'net_amount' => $netAmount,
        ];
    }
}
