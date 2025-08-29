<?php
header('Content-Type: application/json; charset=utf-8');
$term = isset($_GET['term']) ? $_GET['term'] : '';
echo json_encode([
    'success' => true,
    'data' => [
        [
            'id' => 9999,
            'destination' => 'Teste ' . $term,
            'start_time' => date('Y-m-d H:i:s'),
            'prefix' => 'V-999',
            'vehicle_name' => 'TEST/VEICULO'
        ]
    ]
], JSON_UNESCAPED_UNICODE);