<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Error handling configuration
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Log errors to file
function logError($message, $type = 'ERROR') {
    $log_file = __DIR__ . '/logs/api_errors.log';
    $dir = dirname($log_file);
    
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[$timestamp] [$type] $message" . PHP_EOL;
    error_log($log_message, 3, $log_file);
}

// For debugging
function logMessage($message) {
    $log_file = __DIR__ . '/logs/debug.log';
    $dir = dirname($log_file);
    
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[$timestamp] $message" . PHP_EOL;
    error_log($log_message, 3, $log_file);
}

logMessage("API request received: " . json_encode($_POST));

// Response handler function
function sendResponse($status, $message, $data = []) {
    http_response_code($status);
    echo json_encode([
        'status' => $status,
        'response' => $message,
        'data' => $data
    ]);
    exit;
}

// Database configuration
$config = [
    'db' => [
        'host' => 'localhost',
        'username' => 'root',
        'password' => '',
        'name' => 'project_management_website',
        'charset' => 'utf8mb4'
    ],
    'api' => [
        'mistral' => [
            'key' => 'KjqO5jEyPYFsbQoCEMhEF0QSQYMeL0FE',
            'url' => 'https://api.mistral.ai/v1/chat/completions',
            'model' => 'mistral-small'
        ]
    ],
    'security' => [
        'dangerous_keywords' => [
            'delete', 'drop', 'truncate', 'alter', 'insert', 'update', 'create', 
            'modify', 'rename', 'grant', 'revoke', 'exec', 'execute', 'union', 
            'into outfile', 'load_file', 'sleep', 'benchmark'
        ]
    ],
    'cache' => [
        'enabled' => true,
        'directory' => __DIR__ . '/cache',
        'expiry' => 3600 // 1 hour
    ]
];

// Available tables information to assist AI in generating better queries
$tables_info = "
Tables and their columns:
- student: sr_no(int), registration_no(varchar), name(varchar), email(varchar), mobile_no(varchar), password(varchar), section(varchar), semester(varchar), year(varchar), image(varchar), failed_attempts(int), lock_until(datetime)
- faculty: sr_no(int), fid(varchar), password(varchar), fname(varchar), email(varchar), mobile(varchar), specialization(varchar), designation(varchar), image(varchar), failed_attempts(int), lock_until(datetime)
- project: p_id(varchar), pname(varchar), pdesc(varchar), project_type(varchar), project_domain_type(varchar), fid(varchar), semester(varchar), max_student(int), no_of_student_allocated(varchar)
- notifications: id(int), registration_no(varchar), message(text), p_id(varchar), datetime(datetime), semester(varchar)
- feedback: id(int), registration_no(varchar), ticket_id(varchar), fid(varchar), name(varchar), email(varchar), message(text), submitted_at(timestamp), status(varchar)
- allocated_project: id(int), registration_no(varchar), p_id(varchar), fid(varchar), year(varchar), semester(varchar), section(varchar), action(varchar)
- circular_notices: id(int), fid(varchar), notice_date(date), title(varchar), description(text), type(varchar), semester(varchar)
";

/**
 * Simple cache implementation for API responses
 */
class SimpleCache {
    private $cacheDir;
    private $expiry;
    private $enabled;
    
    public function __construct($config) {
        $this->cacheDir = $config['cache']['directory'];
        $this->expiry = $config['cache']['expiry'];
        $this->enabled = $config['cache']['enabled'];
        
        if ($this->enabled && !is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    private function getFilename($key) {
        return $this->cacheDir . '/' . md5($key) . '.cache';
    }
    
    public function get($key) {
        if (!$this->enabled) return null;
        
        $file = $this->getFilename($key);
        
        if (file_exists($file)) {
            $data = unserialize(file_get_contents($file));
            
            if ($data['expires'] > time()) {
                return $data['content'];
            }
            
            // Remove expired cache
            unlink($file);
        }
        
        return null;
    }
    
    public function set($key, $content) {
        if (!$this->enabled) return false;
        
        $file = $this->getFilename($key);
        $data = [
            'expires' => time() + $this->expiry,
            'content' => $content
        ];
        
        return file_put_contents($file, serialize($data)) !== false;
    }
}

/**
 * Make API call to AI service
 * @param string $url API endpoint
 * @param array $data Request payload
 * @param string $api_key Authorization key
 * @return array Response data
 */
function callAIService($url, $data, $api_key, $cache) {
    // Create cache key from the request data
    $cacheKey = 'ai_' . md5(json_encode($data));
    
    // Try to get from cache first
    $cachedResponse = $cache->get($cacheKey);
    if ($cachedResponse !== null) {
        logMessage("Retrieved response from cache");
        return $cachedResponse;
    }
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $api_key", "Content-Type: application/json"]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $err = curl_error($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($err || $httpcode != 200) {
        logError("API error: $err, HTTP code: $httpcode");
        return null;
    }
    
    $responseData = json_decode($response, true);
    
    // Cache successful responses
    if ($responseData) {
        $cache->set($cacheKey, $responseData);
    }
    
    return $responseData;
}

/**
 * Generate SQL query using Mistral AI
 * @param string $user_query User's question
 * @return string|null SQL query or null on failure
 */
function generateSQLQuery($user_query, $config, $tables_info, $cache) {
    $data = [
        "model" => $config['api']['mistral']['model'],
        "messages" => [
            [
                "role" => "system", 
                "content" => "You are a project management database expert that generates precise SQL SELECT queries only. 
                Never generate DELETE, DROP, INSERT, UPDATE, or any other data-modifying queries. 
                Always start your response with a valid SQL query without markdown formatting.
                Use proper table prefixes and return only the SQL query, nothing else.
                " . $tables_info
            ],
            [
                "role" => "user", 
                "content" => "Generate a SQL SELECT query for: " . $user_query
            ]
        ],
        "max_tokens" => 200,
        "temperature" => 0.2
    ];
    
    $response_data = callAIService($config['api']['mistral']['url'], $data, $config['api']['mistral']['key'], $cache);
    
    if (!$response_data || !isset($response_data['choices'][0]['message']['content'])) {
        return null;
    }
    
    // Extract SQL query and clean it
    $sql_query = trim($response_data['choices'][0]['message']['content']);
    
    // Remove markdown formatting if present
    $sql_query = preg_replace('/^```sql\s*(.*?)\s*```$/is', '$1', $sql_query);
    $sql_query = str_replace(['```sql', '```'], '', $sql_query);
    
    return trim($sql_query);
}

/**
 * Generate project recommendations based on domain using Mistral AI
 * @param string $domain Project domain
 * @param array $projects List of existing projects
 * @return string HTML content with recommendations
 */
function generateProjectRecommendations($domain, $projects, $config, $cache) {
    // Create a project list string
    $project_list = "";
    foreach ($projects as $index => $project) {
        $project_list .= ($index + 1) . ". " . $project['pname'] . "\n";
    }
    
    $data = [
        "model" => $config['api']['mistral']['model'],
        "messages" => [
            [
                "role" => "system", 
                "content" => "You are a project management advisor for academic students. Your response should be formatted in simple HTML with project names in bold tags."
            ],
            [
                "role" => "user", 
                "content" => "Based on the domain '$domain', here are some existing projects: \n$project_list\n\nProvide 3 new project ideas with brief descriptions (2-3 sentences each) that would be suitable for students in this domain. Format your response in HTML with project names in bold."
            ]
        ],
        "max_tokens" => 500,
        "temperature" => 0.7
    ];
    
    $response_data = callAIService($config['api']['mistral']['url'], $data, $config['api']['mistral']['key'], $cache);
    
    if (!$response_data || !isset($response_data['choices'][0]['message']['content'])) {
        return "<p>Unable to generate project recommendations at this time.</p>";
    }
    
    return "<h4>Project Recommendations:</h4>" . $response_data['choices'][0]['message']['content'];
}

/**
 * Generate external information when database doesn't have the answer
 * @param string $query User's query
 * @param array $chat_history Previous conversation history
 * @return string HTML formatted response
 */
function generateExternalInformation($query, $chat_history, $config, $cache) {
    // Prepare conversation history for context
    $messages = [
        [
            "role" => "system", 
            "content" => "You are a helpful project management assistant that provides information about academic projects, programming, and technology. Format your responses in simple HTML when appropriate."
        ]
    ];
    
    // Add chat history for context
    if (!empty($chat_history) && is_array($chat_history)) {
        foreach ($chat_history as $message) {
            $messages[] = [
                "role" => $message['role'],
                "content" => $message['content']
            ];
        }
    }
    
    // Add the current query
    $messages[] = [
        "role" => "user",
        "content" => $query
    ];
    
    $data = [
        "model" => $config['api']['mistral']['model'],
        "messages" => $messages,
        "max_tokens" => 800,
        "temperature" => 0.7
    ];
    
    $response_data = callAIService($config['api']['mistral']['url'], $data, $config['api']['mistral']['key'], $cache);
    
    if (!$response_data || !isset($response_data['choices'][0]['message']['content'])) {
        return "<p>I'm sorry, I couldn't find information about that. Please try asking in a different way.</p>";
    }
    
    return $response_data['choices'][0]['message']['content'];
}

/**
 * Detect query intent to determine how to process it
 * @param string $query User's query
 * @return string Intent type (database, domain, deadline, external)
 */
function detectQueryIntent($query) {
    $query = strtolower($query);
    
    // Domain-specific project queries
    if (preg_match('/(project|idea|suggestion).*(domain|area|field)/i', $query) ||
        preg_match('/(domain|area|field).*(project|idea|suggestion)/i', $query)) {
        return 'domain';
    }
    
    // Database-related queries
    if (preg_match('/(list|show|find|get|display|count|how many|total)/i', $query) &&
        preg_match('/(project|deadline|notification|feedback|student|faculty)/i', $query)) {
        return 'database';
    }
    
    // Deadline-specific queries
    if (preg_match('/(deadline|due date|when is|next|upcoming)/i', $query)) {
        return 'deadline';
    }
    
    // Default to external knowledge for complex or non-database questions
    return 'external';
}

/**
 * Format deadline information with visual elements
 * @param array $deadlines List of deadlines
 * @return string HTML formatted deadlines
 */
function formatDeadlines($deadlines) {
    if (empty($deadlines)) {
        return "<p>No upcoming deadlines found.</p>";
    }
    
    $html = "<h4>Upcoming Deadlines</h4>";
    $html .= "<div class='deadline-container'>";
    
    $current_date = new DateTime();
    
    foreach ($deadlines as $deadline) {
        $deadline_date = new DateTime($deadline['deadline_date']);
        $days_remaining = $current_date->diff($deadline_date)->days;
        $is_past = $deadline_date < $current_date;
        
        // Determine urgency class
        $urgency_class = 'normal';
        if ($is_past) {
            $urgency_class = 'past';
        } else if ($days_remaining <= 3) {
            $urgency_class = 'urgent';
        } else if ($days_remaining <= 7) {
            $urgency_class = 'soon';
        }
        
        $html .= "<div class='deadline-item deadline-{$urgency_class}'>";
        $html .= "<div class='deadline-header'>";
        $html .= "<span class='deadline-title'>" . htmlspecialchars($deadline['title']) . "</span>";
        
        if ($is_past) {
            $html .= "<span class='deadline-days past'>Overdue</span>";
        } else {
            $html .= "<span class='deadline-days'>{$days_remaining} days left</span>";
        }
        
        $html .= "</div>";
        $html .= "<div class='deadline-details'>";
        $html .= "<span class='deadline-date'>" . htmlspecialchars($deadline_date->format('M d, Y')) . "</span>";
        
        if (isset($deadline['project_name'])) {
            $html .= "<span class='deadline-project'>Project: " . htmlspecialchars($deadline['project_name']) . "</span>";
        }
        
        $html .= "</div>";
        $html .= "</div>";
    }
    
    $html .= "</div>";
    $html .= "<style>
        .deadline-container { display: flex; flex-direction: column; gap: 10px; margin-top: 10px; }
        .deadline-item { border-left: 4px solid #ddd; padding: 8px 12px; background-color: #f9f9f9; border-radius: 4px; }
        .deadline-urgent { border-left-color: #ff4444; background-color: #ffeeee; }
        .deadline-soon { border-left-color: #ffaa33; background-color: #fff8ee; }
        .deadline-past { border-left-color: #999; background-color: #f3f3f3; }
        .deadline-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px; }
        .deadline-title { font-weight: bold; }
        .deadline-days { font-size: 12px; color: #333; }
        .deadline-days.past { color: #999; }
        .deadline-details { font-size: 12px; color: #666; display: flex; justify-content: space-between; }
    </style>";
    
    return $html;
}

/**
 * Extract domain from a query
 * @param string $query User's query
 * @return string|null Extracted domain or null
 */
function extractDomain($query) {
    if (preg_match('/(project|idea|suggestion).*(domain|area|field).*?([a-zA-Z\s]+)/i', $query, $matches) || 
        preg_match('/(domain|area|field).*?(project|idea|suggestion).*?([a-zA-Z\s]+)/i', $query, $matches)) {
        
        $domain = isset($matches[3]) ? trim($matches[3]) : '';
        
        // If domain is too short or generic, try to extract it differently
        if (strlen($domain) < 3 || in_array(strtolower($domain), ['in', 'on', 'for', 'about', 'of'])) {
            if (preg_match('/(?:in|on|for|about)\s+([a-zA-Z\s]+)/i', $query, $domain_matches)) {
                $domain = trim($domain_matches[1]);
            }
        }
        
        return $domain;
    }
    
    return null;
}

/**
 * Process deadline-related queries
 * @param PDO $pdo Database connection
 * @param string $query User's query
 * @return string HTML formatted response
 */
function processDeadlineQuery($pdo, $query) {
    // Detect if query is about past, upcoming, or specific project deadlines
    $is_past = preg_match('/(past|previous|completed|missed)/i', $query);
    $is_upcoming = preg_match('/(upcoming|next|future|soon)/i', $query);
    $project_match = [];
    $has_project = preg_match('/for\s+project\s+([a-zA-Z0-9\s]+)/i', $query, $project_match);
    
    try {
        if ($has_project) {
            $project_name = trim($project_match[1]);
            
            // Get deadlines for specific project
            $stmt = $pdo->prepare("
                SELECT cn.id, cn.notice_date as deadline_date, cn.title, p.pname as project_name 
                FROM circular_notices cn
                JOIN project p ON cn.fid = p.fid
                WHERE p.pname LIKE :project_name
                AND cn.type = 'deadline'
                ORDER BY cn.notice_date ASC
            ");
            $stmt->execute(['project_name' => '%' . $project_name . '%']);
        } else if ($is_past) {
            // Get past deadlines
            $stmt = $pdo->prepare("
                SELECT cn.id, cn.notice_date as deadline_date, cn.title, p.pname as project_name 
                FROM circular_notices cn
                LEFT JOIN project p ON cn.fid = p.fid
                WHERE cn.type = 'deadline'
                AND cn.notice_date < CURDATE()
                ORDER BY cn.notice_date DESC
                LIMIT 5
            ");
            $stmt->execute();
        } else {
            // Get upcoming deadlines (default)
            $stmt = $pdo->prepare("
                SELECT cn.id, cn.notice_date as deadline_date, cn.title, p.pname as project_name 
                FROM circular_notices cn
                LEFT JOIN project p ON cn.fid = p.fid
                WHERE cn.type = 'deadline'
                AND cn.notice_date >= CURDATE()
                ORDER BY cn.notice_date ASC
                LIMIT 5
            ");
            $stmt->execute();
        }
        
        $deadlines = $stmt->fetchAll();
        
        if (count($deadlines) > 0) {
            return formatDeadlines($deadlines);
        } else {
            if ($has_project) {
                return "<p>No deadlines found for project '" . htmlspecialchars($project_name) . "'.</p>";
            } else if ($is_past) {
                return "<p>No past deadlines found.</p>";
            } else {
                return "<p>No upcoming deadlines found.</p>";
            }
        }
    } catch (PDOException $e) {
        logError("Deadline query error: " . $e->getMessage());
        return "<p>I encountered an error while retrieving deadlines. Please try again later.</p>";
    }
}

// Main API logic
try {
    // Initialize cache
    $cache = new SimpleCache($config);
    
    // Check if we received a query
    if (!isset($_POST['query']) || empty($_POST['query'])) {
        sendResponse(400, "Please enter a question about your projects.");
    }
    
    $user_query = trim($_POST['query']);
    logMessage("Processing user query: " . $user_query);
    
    // Get chat history if available
    $chat_history = [];
    if (isset($_POST['history']) && !empty($_POST['history'])) {
        $chat_history = json_decode($_POST['history'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $chat_history = [];
        }
    }
    
    // Security check for dangerous operations
    foreach ($config['security']['dangerous_keywords'] as $keyword) {
        if (stripos($user_query, $keyword) !== false) {
            sendResponse(403, "I'm sorry, I can only help with retrieving information from the database.");
        }
    }
    
    // Detect query intent
    $intent = detectQueryIntent($user_query);
    logMessage("Detected intent: " . $intent);
    
    // Try to connect to the database for database-dependent intents
    if (in_array($intent, ['database', 'domain', 'deadline'])) {
        try {
            $dsn = "mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset={$config['db']['charset']}";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $pdo = new PDO($dsn, $config['db']['username'], $config['db']['password'], $options);
            logMessage("Database connection successful");
        } catch (PDOException $e) {
            logError("Database connection error: " . $e->getMessage());
            
            // Fall back to external information
            $external_info = generateExternalInformation($user_query, $chat_history, $config, $cache);
            sendResponse(200, $external_info, ['source' => 'external']);
        }
    }
    
    // Process based on intent
    switch ($intent) {
        case 'domain':
            $domain = extractDomain($user_query);
            
            if (!$domain) {
                sendResponse(400, "I couldn't identify which domain you're interested in. Please specify a domain like 'AI', 'Web Development', etc.");
            }
            
            try {
                // Search for projects in the specified domain
                $stmt = $pdo->prepare("SELECT * FROM project WHERE project_domain_type LIKE :domain LIMIT 10");
                $stmt->execute(['domain' => '%' . $domain . '%']);
                $projects = $stmt->fetchAll();
                
                if (count($projects) > 0) {
                    // Build response with existing projects
                    $response_html = "<h3>Projects in " . htmlspecialchars($domain) . " Domain:</h3>";
                    $response_html .= "<table class='chatbot-table'>";
                    $response_html .= "<tr><th>Project Name</th><th>Description</th><th>Type</th><th>Semester</th></tr>";
                    
                    foreach ($projects as $project) {
                        $response_html .= "<tr>";
                        $response_html .= "<td>" . htmlspecialchars($project['pname']) . "</td>";
                        $response_html .= "<td>" . htmlspecialchars($project['pdesc']) . "</td>";
                        $response_html .= "<td>" . htmlspecialchars($project['project_type']) . "</td>";
                        $response_html .= "<td>" . htmlspecialchars($project['semester']) . "</td>";
                        $response_html .= "</tr>";
                    }
                    
                    $response_html .= "</table>";
                    $response_html .= "<p>Found " . count($projects) . " projects in this domain.</p>";
                    
                    // Add project recommendations using Mistral AI
                    $recommendations = generateProjectRecommendations($domain, $projects, $config, $cache);
                    $response_html .= $recommendations;
                    
                    sendResponse(200, $response_html, ['source' => 'database', 'domain' => $domain]);
                } else {
                    // No projects found in database, get external ideas
                    $external_query = "Suggest 5 project ideas for students in the {$domain} domain";
                    $external_info = generateExternalInformation($external_query, [], $config, $cache);
                    sendResponse(200, "<h3>Project Ideas in " . htmlspecialchars($domain) . " Domain:</h3>" . $external_info, 
                        ['source' => 'external', 'domain' => $domain]);
                }
            } catch (PDOException $e) {
                logError("Domain query error: " . $e->getMessage());
                sendResponse(500, "I encountered an error while searching for projects. Please try again later.");
            }
            break;
            
        case 'deadline':
            $deadline_response = processDeadlineQuery($pdo, $user_query);
            sendResponse(200, $deadline_response, ['source' => 'database', 'type' => 'deadline']);
            break;
            
        case 'database':
            $sql_query = generateSQLQuery($user_query, $config, $tables_info, $cache);
            
            if (!$sql_query) {
                sendResponse(500, "I couldn't process your request. Please try asking in a different way.");
            }
            
            // Final security check
            $is_select = preg_match('/^\s*SELECT\s+/i', $sql_query);
            if (!$is_select) {
                sendResponse(403, "I can only help with retrieving information. Please ask a question about your projects.");
            }
            
            // Execute SQL Query with PDO for better security
            try {
                $stmt = $pdo->prepare($sql_query);
                $stmt->execute();
                $results = $stmt->fetchAll();
                
                // Process query results
                if (count($results) > 0) {
                    // Build an HTML table
                    $response_html = "<h3>Query Results:</h3>";
                    $response_html .= "<table class='chatbot-table'>";
                    
                    // Add table headers
                    $response_html .= "<tr>";
                    foreach (array_keys($results[0]) as $column) {
                        $response_html .= "<th>" . htmlspecialchars($column) . "</th>";
                    }
                    $response_html .= "</tr>";
                    
                    // Add table rows
                    foreach ($results as $row) {
                        $response_html .= "<tr>";
                        foreach ($row as $key => $value) {
                            $display_value = $value !== null ? htmlspecialchars($value) : "<em>None</em>";
                            $response_html .= "<td>" . $display_value . "</td>";
                        }
                        $response_html .= "</tr>";
                    }
                    
                    $response_html .= "</table>";
                    
                    // Add record count
                    $response_html .= "<p>Found " . count($results) . " record" . (count($results) > 1 ? "s" : "") . ".</p>";
                    
                    sendResponse(200, $response_html, ['source' => 'database']);
                } else {
                    // No results, try external information
                    $external_info = generateExternalInformation($user_query, $chat_history, $config, $cache);
                    sendResponse(200, $external_info, ['source' => 'external']);
                }
            } catch (PDOException $e) {
                logError("SQL execution error: " . $e->getMessage() . " - Query: " . $sql_query);
                
                // Fall back to external information
                $external_info = generateExternalInformation($user_query, $chat_history, $config, $cache);
                sendResponse(200, $external_info, ['source' => 'external']);
            }
            break;
            
        case 'external':
            // For non-database questions, use external knowledge
            $external_info = generateExternalInformation($user_query, $chat_history, $config, $cache);
            sendResponse(200, $external_info, ['source' => 'external']);
            break;
    }
    
} catch (Exception $e) {
    logError("General error: " . $e->getMessage());
    sendResponse(500, "Sorry, I encountered an error processing your request. Please try again later.");
}

// If the request method is not POST
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    sendResponse(405, "Method not allowed. This endpoint only accepts POST requests.");
}
?>










