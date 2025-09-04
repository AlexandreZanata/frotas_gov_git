<?php

if (!defined('SYSTEM_LOADED')) {
    die('Acesso direto não permitido.');
}

class StatisticsHelper
{
    /**
     * Calcula a projeção de gastos futuros usando regressão linear simples.
     * @param array $data Um array de dados históricos, onde cada item é um array com chaves 'x' (período) e 'y' (valor).
     * @return array Um array com os dados da linha de tendência.
     */
    public static function linearRegression(array $data)
    {
        $n = count($data);
        if ($n < 2) {
            return []; // Regressão precisa de pelo menos 2 pontos.
        }

        $sumX = 0;
        $sumY = 0;
        $sumXY = 0;
        $sumX2 = 0;

        foreach ($data as $point) {
            $sumX += $point['x'];
            $sumY += $point['y'];
            $sumXY += $point['x'] * $point['y'];
            $sumX2 += $point['x'] * $point['x'];
        }

        $denominator = ($n * $sumX2 - $sumX * $sumX);
        if ($denominator == 0) {
            return []; // Evita divisão por zero.
        }

        // Inclinação (m) e Interceptação (b) da linha: y = mx + b
        $m = ($n * $sumXY - $sumX * $sumY) / $denominator;
        $b = ($sumY - $m * $sumX) / $n;

        // Gera os pontos da linha de tendência para o gráfico
        $trendLine = [];
        foreach ($data as $point) {
            $trendLine[] = ['x' => $point['x'], 'y' => $m * $point['x'] + $b];
        }

        return $trendLine;
    }
}