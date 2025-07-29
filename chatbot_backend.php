<?php
session_start();
header('Content-Type: application/json');

include('../connection/connection.php');

require 'vendor/autoload.php'; // For NLP library if using composer

// Get user message
$message = isset($_POST['message']) ? $_POST['message'] : '';
if (empty($message)) {
    echo json_encode(['response' => 'Sorry, I didn\'t receive your message.']);
    exit;
}

// Get current user information from session
$current_user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$user_type = isset($_SESSION['user_type']) ? $_SESSION['user_type'] : null; // 'student', 'faculty', etc.

// Process the message and generate response
$response = processMessage($message, $current_user_id, $user_type, $con);

echo json_encode(['response' => $response]);

/**
 * Process user message and generate appropriate response
 */
function processMessage($message, $user_id, $user_type, $con) {
    // Convert message to lowercase for easier matching
    $message_lower = strtolower($message);
    
    // Simple intent detection for common queries
    if (preg_match('/(list|show|all|get)\s+projects/', $message_lower)) {
        return getAllProjects($user_id, $user_type, $con);
    } 
    else if (preg_match('/(total|count|number)\s+of\s+projects/', $message_lower)) {
        return getTotalProjects($user_id, $user_type, $con);
    }
    else if (preg_match('/suggest\s+project\s+on\s+(.+)/', $message_lower, $matches)) {
        $domain = $matches[1];
        return suggestProjectsByDomain($domain, $con);
    }
    else if (preg_match('/(show|get|list|new)\s+notifications/', $message_lower)) {
        return getNotifications($user_id, $user_type, $con);
    }
    else if (preg_match('/project\s+idea\s+on\s+(.+)/', $message_lower, $matches)) {
        $domain = $matches[1];
        return getProjectIdeas($domain, $con);
    }
    else if (preg_match('/(show|list|all)\s+faculty/', $message_lower)) {
        return getAllFaculty($con);
    }
    else if (preg_match('/(show|list|all)\s+notices/', $message_lower)) {
        return getCircularNotices($con);
    }
    // Use more advanced NLP for complex queries
    else {
        return processComplexQuery($message, $user_id, $user_type, $con);
    }
}

/**
 * Get all projects based on user type and permissions
 */
function getAllProjects($user_id, $user_type, $con) {
    try {
        // Different queries based on user type
        if ($user_type == 'admin') {
            $query = "SELECT id, title, domain, status FROM project ORDER BY id DESC";
            $params = [];
        } 
        else if ($user_type == 'faculty') {
            $query = "SELECT id, title, domain, status FROM project WHERE faculty_id = ? ORDER BY id DESC";
            $params = [$user_id];
        }
        else { // student or other
            $query = "SELECT p.id, p.title, p.domain, p.status 
                     FROM project p 
                     JOIN student s ON p.id IN (SELECT project_id FROM student_project WHERE student_id = ?)
                     ORDER BY p.id DESC";
            $params = [$user_id];
        }
        
        $stmt = $con->prepare($query);
        
        if (!empty($params)) {
            $stmt->bind_param(str_repeat('s', count($params)), ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 0) {
            return "No projects found.";
        }
        
        $table = "<table border='1'><tr><th>ID</th><th>Title</th><th>Domain</th><th>Status</th></tr>";
        while ($row = $result->fetch_assoc()) {
            $table .= "<tr>
                <td>{$row['id']}</td>
                <td>{$row['title']}</td>
                <td>{$row['domain']}</td>
                <td>{$row['status']}</td>
            </tr>";
        }
        $table .= "</table>";
        
        return "Here are the projects: " . $table;
    } catch (Exception $e) {
        return "Error retrieving projects: " . $e->getMessage();
    }
}

/**
 * Get total number of projects
 */
function getTotalProjects($user_id, $user_type, $con) {
    try {
        // Different queries based on user type
        if ($user_type == 'admin') {
            $query = "SELECT COUNT(*) as total FROM project";
            $params = [];
        } 
        else if ($user_type == 'faculty') {
            $query = "SELECT COUNT(*) as total FROM project WHERE faculty_id = ?";
            $params = [$user_id];
        }
        else { // student or other
            $query = "SELECT COUNT(*) as total 
                     FROM student_project
                     WHERE student_id = ?";
            $params = [$user_id];
        }
        
        $stmt = $con->prepare($query);
        
        if (!empty($params)) {
            $stmt->bind_param(str_repeat('s', count($params)), ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        $total = $row['total'];
        
        if ($user_type == 'admin') {
            return "There are a total of $total projects in the system.";
        } else if ($user_type == 'faculty') {
            return "You are supervising a total of $total projects.";
        } else {
            return "You are participating in a total of $total projects.";
        }
    } catch (Exception $e) {
        return "Error counting projects: " . $e->getMessage();
    }
}

/**
 * Suggest projects by domain
 */
function suggestProjectsByDomain($domain, $con) {
    try {
        $query = "SELECT id, title, description FROM project WHERE domain LIKE ? LIMIT 5";
        $domain_param = "%$domain%";
        
        $stmt = $con->prepare($query);
        $stmt->bind_param("s", $domain_param);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 0) {
            return "No projects found in the '$domain' domain. Try a different domain or a more general term.";
        }
        
        $response = "Here are some projects in the '$domain' domain:<ul>";
        while ($row = $result->fetch_assoc()) {
            $response .= "<li><strong>{$row['title']}</strong> - " . substr($row['description'], 0, 100) . "...</li>";
        }
        $response .= "</ul>";
        
        return $response;
    } catch (Exception $e) {
        return "Error searching for projects: " . $e->getMessage();
    }
}

/**
 * Get notifications for the current user
 */
function getNotifications($user_id, $user_type, $con) {
    try {
        $query = "SELECT id, title, message, created_at FROM notifications 
                 WHERE (user_id = ? OR user_id IS NULL) AND (user_type = ? OR user_type IS NULL)
                 ORDER BY created_at DESC LIMIT 5";
        
        $stmt = $con->prepare($query);
        $stmt->bind_param("ss", $user_id, $user_type);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 0) {
            return "You don't have any new notifications.";
        }
        
        $response = "Here are your recent notifications:<ul>";
        while ($row = $result->fetch_assoc()) {
            $date = date('M d, Y', strtotime($row['created_at']));
            $response .= "<li><strong>{$row['title']}</strong> ($date) - {$row['message']}</li>";
        }
        $response .= "</ul>";
        
        return $response;
    } catch (Exception $e) {
        return "Error retrieving notifications: " . $e->getMessage();
    }
}

/**
 * Get project ideas based on domain
 */
function getProjectIdeas($domain, $con) {
    try {
        // First check if we have existing projects in this domain
        $query = "SELECT title, description FROM project WHERE domain LIKE ? LIMIT 3";
        $domain_param = "%$domain%";
        
        $stmt = $con->prepare($query);
        $stmt->bind_param("s", $domain_param);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $response = "Here are some project ideas related to '$domain':<ul>";
        
        // If we have existing projects, use them as reference
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $response .= "<li><strong>{$row['title']}</strong> - " . substr($row['description'], 0, 100) . "...</li>";
            }
        } else {
            // Generate generic project ideas based on domain
            $ideas = generateIdeasForDomain($domain);
            foreach ($ideas as $idea) {
                $response .= "<li><strong>{$idea['title']}</strong> - {$idea['description']}</li>";
            }
        }
        
        $response .= "</ul>";
        return $response;
    } catch (Exception $e) {
        return "Error generating project ideas: " . $e->getMessage();
    }
}

/**
 * Generate generic project ideas for a domain
 */
function generateIdeasForDomain($domain) {
    // Pre-defined ideas for common domains
    $domains = [
        'web' => [
            ['title' => 'E-commerce Platform', 'description' => 'Build a full-featured e-commerce website with product catalog, shopping cart, and payment processing.'],
            ['title' => 'Social Media Dashboard', 'description' => 'Create a dashboard to manage and analyze social media accounts across multiple platforms.'],
            ['title' => 'Content Management System', 'description' => 'Develop a custom CMS for specific industry needs with flexible templates and user roles.']
        ],
        'mobile' => [
            ['title' => 'Health Tracking App', 'description' => 'Create a mobile app for tracking health metrics, exercise, and nutrition with personalized recommendations.'],
            ['title' => 'Augmented Reality Guide', 'description' => 'Develop an AR app that provides information about landmarks or objects when the camera is pointed at them.'],
            ['title' => 'Local Event Finder', 'description' => 'Build a location-based app that helps users discover events and activities in their vicinity.']
        ],
        'ai' => [
            ['title' => 'Sentiment Analysis Tool', 'description' => 'Create an AI system that analyzes customer feedback and social media mentions for sentiment and key themes.'],
            ['title' => 'Smart Document Classifier', 'description' => 'Build a machine learning model that automatically categorizes and routes documents based on content.'],
            ['title' => 'Predictive Maintenance System', 'description' => 'Develop an AI solution that predicts equipment failures before they occur using sensor data analysis.']
        ],
        'iot' => [
            ['title' => 'Smart Home Energy Monitor', 'description' => 'Create an IoT system that tracks and optimizes home energy usage across multiple devices.'],
            ['title' => 'Agricultural Sensor Network', 'description' => 'Develop a network of sensors to monitor soil conditions, weather, and crop health for precision farming.'],
            ['title' => 'Smart Campus Navigation', 'description' => 'Build an IoT-based indoor navigation system for large campuses with real-time occupancy tracking.']
        ],
        'data' => [
            ['title' => 'Predictive Analytics Dashboard', 'description' => 'Create a system that visualizes business data and predicts future trends using statistical models.'],
            ['title' => 'Data Integration Platform', 'description' => 'Develop a solution that combines data from multiple sources into a unified, searchable database.'],
            ['title' => 'Automated Report Generator', 'description' => 'Build a tool that automatically generates customized reports from various data sources.']
        ]
    ];
    
    // Check if we have pre-defined ideas for this domain
    foreach ($domains as $key => $ideas) {
        if (stripos($domain, $key) !== false) {
            return $ideas;
        }
    }
    
    // Generic ideas if no matching domain found
    return [
        ['title' => $domain.' Project Management System', 'description' => 'Create a specialized project management tool tailored for '.$domain.' workflows.'],
        ['title' => 'Interactive '.$domain.' Learning Platform', 'description' => 'Develop an educational platform focused on teaching '.$domain.' concepts through interactive exercises.'],
        ['title' => $domain.' Analytics Tool', 'description' => 'Build a data analysis system specifically designed for '.$domain.' applications and use cases.']
    ];
}

/**
 * Get all faculty members
 */
function getAllFaculty($con) {
    try {
        $query = "SELECT id, name, department, email FROM faculty ORDER BY department, name";
        $result = $con->query($query);
        
        if ($result->num_rows == 0) {
            return "No faculty members found in the database.";
        }
        
        $table = "<table border='1'><tr><th>ID</th><th>Name</th><th>Department</th><th>Email</th></tr>";
        while ($row = $result->fetch_assoc()) {
            $table .= "<tr>
                <td>{$row['id']}</td>
                <td>{$row['name']}</td>
                <td>{$row['department']}</td>
                <td>{$row['email']}</td>
            </tr>";
        }
        $table .= "</table>";
        
        return "Here is the faculty list: " . $table;
    } catch (Exception $e) {
        return "Error retrieving faculty list: " . $e->getMessage();
    }
}

/**
 * Get circular notices
 */
function getCircularNotices($con) {
    try {
        $query = "SELECT id, title, content, DATE_FORMAT(publish_date, '%M %d, %Y') as formatted_date 
                 FROM circular_notices 
                 ORDER BY publish_date DESC LIMIT 5";
        $result = $con->query($query);
        
        if ($result->num_rows == 0) {
            return "No notices found in the database.";
        }
        
        $response = "Here are the recent notices:<ul>";
        while ($row = $result->fetch_assoc()) {
            $response .= "<li><strong>{$row['title']}</strong> ({$row['formatted_date']}) - " . 
                         substr($row['content'], 0, 100) . "...</li>";
        }
        $response .= "</ul>";
        
        return $response;
    } catch (Exception $e) {
        return "Error retrieving notices: " . $e->getMessage();
    }
}

/**
 * Process more complex queries using NLP
 * This is a simplified version - production systems would use a more sophisticated NLP model
 */
function processComplexQuery($message, $user_id, $user_type, $con) {
    // Convert to lowercase and remove punctuation for simpler processing
    $processed_message = strtolower(preg_replace('/[^\w\s]/', '', $message));
    $words = explode(' ', $processed_message);
    
    // Extract key entities and intents
    $entities = [];
    $intents = [];
    
    // Simple entity extraction
    foreach ($words as $word) {
        // Check for domain keywords
        if (in_array($word, ['web', 'mobile', 'ai', 'ml', 'iot', 'blockchain', 'security', 'data'])) {
            $entities['domain'] = $word;
        }
        
        // Check for project status
        if (in_array($word, ['completed', 'ongoing', 'pending', 'approved', 'rejected'])) {
            $entities['status'] = $word;
        }
        
        // Check for time-related keywords
        if (in_array($word, ['today', 'week', 'month', 'year', 'recent', 'latest', 'new'])) {
            $entities['time'] = $word;
        }
    }
    
}

