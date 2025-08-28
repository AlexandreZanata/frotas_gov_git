<?php

if (!defined('SYSTEM_LOADED')) {
    die('Acesso direto não permitido.');
}

class ReportCalculations
{
    /**
     * Calcula os totais e o consumo médio com base nos dados fornecidos.
     * @param array $runs Array com os dados das corridas.
     * @param array $fuelings Array com os dados de abastecimento.
     * @return array Retorna um array com 'total_km', 'total_litros', 'total_valor' e 'consumo_medio'.
     */
    public static function calculateSummary(array $runs, array $fuelings): array
    {
        // Calcula o total de KM rodados
        $total_km = 0;
        foreach ($runs as $r) {
            if (is_numeric($r['end_km']) && is_numeric($r['start_km'])) {
                $km_registro = ($r['end_km'] > $r['start_km']) ? ($r['end_km'] - $r['start_km']) : 0;
                $total_km += $km_registro;
            }
        }

        // Calcula os totais de abastecimento
        $total_litros = array_sum(array_column($fuelings, 'liters'));
        $total_valor_abastecido = array_sum(array_column($fuelings, 'total_value'));

        // Calcula o consumo médio
        $consumo_medio = 'N/A';
        if ($total_litros > 0 && $total_km > 0) {
            $consumo_medio = number_format($total_km / $total_litros, 2, ',', '.') . ' km/l';
        }

        return [
            'total_km' => $total_km,
            'total_litros' => $total_litros,
            'total_valor' => $total_valor_abastecido,
            'consumo_medio' => $consumo_medio,
        ];
    }
}