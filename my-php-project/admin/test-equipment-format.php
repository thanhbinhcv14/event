<?php
// Test equipment data format
header('Content-Type: text/plain');

echo "=== TEST EQUIPMENT DATA FORMAT ===\n\n";

// Test data format từ frontend
$testEquipment = [
    [
        'type' => 'equipment',
        'id' => 1,
        'name' => 'Test Equipment',
        'price' => 1000000,
        'quantity' => 1,
        'unit' => 'cái'
    ],
    [
        'type' => 'combo',
        'id' => 1,
        'name' => 'Test Combo',
        'price' => 5000000,
        'quantity' => 1,
        'unit' => 'combo'
    ]
];

echo "Test Equipment Data:\n";
echo json_encode($testEquipment, JSON_PRETTY_PRINT) . "\n\n";

echo "Expected format:\n";
echo "- type: 'equipment' or 'combo'\n";
echo "- id: equipment/combo ID\n";
echo "- name: equipment/combo name\n";
echo "- price: price per unit\n";
echo "- quantity: number of units\n";
echo "- unit: unit of measurement\n\n";

echo "=== TEST COMPLETED ===\n";
?>

