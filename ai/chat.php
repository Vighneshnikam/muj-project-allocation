<?php
header("Content-Type: text/html");

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "project_management_website";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// AI API
$api_key = "KjqO5jEyPYFsbQoCEMhEF0QSQYMeL0FE";  
$url = "https://api.mistral.ai/v1/chat/completions";  

$data = [
    "model" => "mistral-small",
    "messages" => [
        ["role" => "system", "content" => "You are an AI that converts natural language queries into SQL. Only return the SQL query, nothing else."],
        ["role" => "user", "content" => "Show me all students."]
    ],
    "max_tokens" => 100,
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $api_key", "Content-Type: application/json"]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

$response = curl_exec($ch);
curl_close($ch);

$response_data = json_decode($response, true);

if (!$response_data) {
    die("Error: Unable to parse JSON response. Raw response: " . htmlspecialchars($response));
}

if (!isset($response_data['choices'][0]['message']['content'])) {
    die("Error: Unexpected API response format. Full response: " . htmlspecialchars(json_encode($response_data)));
}

$sql_query = trim($response_data['choices'][0]['message']['content']);

// Extract only the SQL query from AI response
$sql_query = preg_replace('/.*```sql\s*(.*?)\s*```/is', '$1', $sql_query);
$sql_query = strip_tags($sql_query); // Remove any HTML tags

echo "Generated SQL: " . htmlspecialchars($sql_query);
?>
