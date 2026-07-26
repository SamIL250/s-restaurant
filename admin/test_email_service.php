<?php
// Test script to call the email service directly
echo "Testing email service directly...\n";

// Simulate a POST request to the email service
$post_data = [
    'purchase_order_id' => 1  // Use an actual PO ID from your database
];

// Set up cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/restaurant_stock/src/services/purchase_orders/send_po_email_simple.php');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, false);

// Execute the request
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

curl_close($ch);

echo "HTTP Code: " . $http_code . "\n";
if ($error) {
    echo "cURL Error: " . $error . "\n";
}

echo "Response:\n";
echo $response . "\n";

// Try to decode JSON response
$json_response = json_decode($response, true);
if ($json_response) {
    echo "\nDecoded Response:\n";
    print_r($json_response);
} else {
    echo "\nFailed to decode JSON response\n";
}
?>
