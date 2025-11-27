<?php
/**
 * Update Plan Prices to Philippine Peso
 * This script will update existing plans to use the new pricing structure
 */

require_once 'config/database.php';
require_once 'models/Plan.php';

$database = new Database();
$plan = new Plan();

echo "<h2>Updating Plan Prices to Philippine Peso</h2>";

// Define the new pricing structure
$new_plans = [
    [
        'plan_name' => 'Monthly Basic',
        'duration_months' => 1,
        'price' => 900.00,
        'description' => 'Access to fitness club equipment during regular hours'
    ],
    [
        'plan_name' => 'Monthly Premium', 
        'duration_months' => 1,
        'price' => 1200.00,
        'description' => 'All access plus group classes and personal trainer'
    ],
    [
        'plan_name' => '3-Month Basic',
        'duration_months' => 3,
        'price' => 2550.00,
        'description' => '3 months basic membership - 5% discount'
    ],
    [
        'plan_name' => '3-Month Premium',
        'duration_months' => 3,
        'price' => 3450.00,
        'description' => '3 months premium membership - 5% discount'
    ],
    [
        'plan_name' => '6-Month Basic',
        'duration_months' => 6,
        'price' => 4860.00,
        'description' => '6 months basic membership - 10% discount'
    ],
    [
        'plan_name' => '6-Month Premium',
        'duration_months' => 6,
        'price' => 6480.00,
        'description' => '6 months premium membership - 10% discount'
    ],
    [
        'plan_name' => 'Annual Basic',
        'duration_months' => 12,
        'price' => 9000.00,
        'description' => 'Full year basic membership - 17% discount'
    ],
    [
        'plan_name' => 'Annual Premium',
        'duration_months' => 12,
        'price' => 12000.00,
        'description' => 'Full year premium membership - 17% discount'
    ]
];

try {
    // Clear existing plans
    $clear_stmt = $database->pdo->prepare("DELETE FROM plans");
    $clear_stmt->execute();
    echo "<p style='color: orange;'>Cleared existing plans</p>";
    
    // Insert new plans
    $insert_count = 0;
    foreach ($new_plans as $plan_data) {
        if ($plan->create($plan_data)) {
            $insert_count++;
            echo "<p style='color: green;'>✓ Added: {$plan_data['plan_name']} - ₱{$plan_data['price']}</p>";
        } else {
            echo "<p style='color: red;'>✗ Failed to add: {$plan_data['plan_name']}</p>";
        }
    }
    
    echo "<h3 style='color: blue;'>Update Complete!</h3>";
    echo "<p>Successfully updated {$insert_count} plans with Philippine Peso pricing</p>";
    echo "<p><a href='plans/index.php'>View Plans Section</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Update Plan Prices</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h2, h3 { color: #333; }
        p { margin: 5px 0; }
        a { color: #0066cc; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    
</body>
</html>
