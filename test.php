<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database connection
$server = "localhost";
$username = "root";
$password = "";
$dbname = "project_management_website";

$con = mysqli_connect($server, $username, $password, $dbname);
if ($con->connect_error) {
    die("Connection failed: " . $con->connect_error);
}

// Get current user information from session
$currentUser = isset($_SESSION['registration_no']) ? $_SESSION['registration_no'] : null;

// Function to sanitize input
function sanitize($input) {
    global $con;
    return mysqli_real_escape_string($con, $input);
}

// Check if this is an AJAX request for the chatbot API
if (isset($_POST['query'])) {
    // Get the user query from POST request
    $userQuery = isset($_POST['query']) ? sanitize(strtolower($_POST['query'])) : "";
    $response = "";
    
    if (!empty($userQuery)) {
        // Extract potential domain from query
        $domain = null;
        if (strpos($userQuery, "domain") !== false) {
            $domainKeywords = ["ai", "web", "mobile", "iot", "blockchain", "data", "machine learning", 
                               "android", "ios", "security", "network", "cloud", "database"];
            foreach ($domainKeywords as $keyword) {
                if (strpos($userQuery, $keyword) !== false) {
                    $domain = $keyword;
                    break;
                }
            }
        }
        
        // Match query to appropriate function
        if (strpos($userQuery, "list of all project") !== false || strpos($userQuery, "show all project") !== false) {
            $response = getAllProjects($con);
        } 
        else if (strpos($userQuery, "total number of project") !== false || strpos($userQuery, "how many project") !== false) {
            $response = getTotalProjects($con);
        }
        else if ((strpos($userQuery, "project") !== false && strpos($userQuery, "domain") !== false) || 
                 (strpos($userQuery, "suggest") !== false && $domain)) {
            $response = getProjectsByDomain($con, $domain);
        }
        else if (strpos($userQuery, "notification") !== false) {
            $response = getNewNotifications($con, $currentUser);
        }
        else if (strpos($userQuery, "project idea") !== false || strpos($userQuery, "suggest project") !== false) {
            $response = getProjectIdeas($con, $domain);
        }
        else if (strpos($userQuery, "hello") !== false || strpos($userQuery, "hi") !== false || strpos($userQuery, "hey") !== false) {
            $response = "Hello! How can I assist you with your projects today?";
        }
        else if (strpos($userQuery, "deadline") !== false || strpos($userQuery, "due date") !== false) {
            // Query for upcoming deadlines
            $query = "SELECT * FROM circular_notices WHERE notice_type = 'deadline' ORDER BY date DESC LIMIT 1";
            $result = mysqli_query($con, $query);
            
            if (mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_assoc($result);
                $response = "The next project submission deadline is on <b>" . htmlspecialchars($row['date']) . "</b>. " . 
                             htmlspecialchars($row['notice_content']);
            } else {
                $response = "I couldn't find any specific deadline information. Please check the Notifications section for updates.";
            }
        }
        else if (strpos($userQuery, "guide") !== false || strpos($userQuery, "supervisor") !== false || strpos($userQuery, "faculty") !== false) {
            // If student is logged in, get their guide
            if ($currentUser) {
                $query = "SELECT f.name, f.email, f.specialization 
                         FROM faculty f 
                         JOIN project p ON f.id = p.faculty_id 
                         JOIN student s ON p.id = s.project_id 
                         WHERE s.registration_no = '$currentUser'";
                $result = mysqli_query($con, $query);
                
                if (mysqli_num_rows($result) > 0) {
                    $row = mysqli_fetch_assoc($result);
                    $response = "Your project guide is <b>" . htmlspecialchars($row['name']) . "</b>.<br>";
                    $response .= "Email: " . htmlspecialchars($row['email']) . "<br>";
                    $response .= "Specialization: " . htmlspecialchars($row['specialization']) . "<br><br>";
                    $response .= "You should schedule regular meetings with them for feedback.";
                } else {
                    $response = "I couldn't find information about your project guide. Please check the 'List of Projects' section.";
                }
            } else {
                $response = "Please log in to view information about your project guide.";
            }
        }
        else {
            $response = "I'm not sure about that. You can ask me about:
                        <br>• List of all projects
                        <br>• Total number of projects
                        <br>• Project ideas or suggestions
                        <br>• Projects in a specific domain
                        <br>• Notifications
                        <br>• Project deadlines
                        <br>• Information about your guide";
        }
    } else {
        $response = "Hello! I'm your project assistant. I can help you find project ideas or answer questions about your current projects. What can I help you with today?";
    }
    

}

// Function to get all projects
function getAllProjects($con) {
    $query = "SELECT * FROM project ORDER BY p_id DESC LIMIT 10";
    $result = mysqli_query($con, $query);
    
    if (mysqli_num_rows($result) > 0) {
        $response = "Here are the latest projects:<br><br>";
        while ($row = mysqli_fetch_assoc($result)) {
            $response .= "<b>" . htmlspecialchars($row['project_title']) . "</b> - " . 
                         htmlspecialchars($row['project_domain']) . "<br>";
        }
    } else {
        $response = "No projects found in the database.";
    }
    
    return $response;
}

// Function to get total number of projects
function getTotalProjects($con) {
    $query = "SELECT COUNT(*) as total FROM project";
    $result = mysqli_query($con, $query);
    $row = mysqli_fetch_assoc($result);
    
    return "There are <b>" . $row['total'] . "</b> projects in the database.";
}

// Function to suggest projects by domain
function getProjectsByDomain($con, $domain) {
    $domain = sanitize($domain);
    $query = "SELECT * FROM project WHERE project_domain LIKE '%$domain%' LIMIT 5";
    $result = mysqli_query($con, $query);
    
    if (mysqli_num_rows($result) > 0) {
        $response = "Here are some projects in the <b>$domain</b> domain:<br><br>";
        while ($row = mysqli_fetch_assoc($result)) {
            $response .= "<b>" . htmlspecialchars($row['project_title']) . "</b><br>";
            $response .= "<i>Description:</i> " . substr(htmlspecialchars($row['project_description']), 0, 100) . "...<br><br>";
        }
    } else {
        $response = "I couldn't find any projects in the $domain domain. Try a different domain or check 'List of Projects' for available domains.";
    }
    
    return $response;
}

// Function to get new notifications
function getNewNotifications($con, $currentUser) {
    if (!$currentUser) {
        return "Please log in to view your notifications.";
    }
    
    $query = "SELECT * FROM notifications WHERE target_user = '$currentUser' OR target_user = 'all' ORDER BY date DESC LIMIT 5";
    $result = mysqli_query($con, $query);
    
    if (mysqli_num_rows($result) > 0) {
        $response = "Here are your recent notifications:<br><br>";
        while ($row = mysqli_fetch_assoc($result)) {
            $response .= "<b>" . htmlspecialchars($row['title']) . "</b><br>";
            $response .= htmlspecialchars($row['content']) . "<br>";
            $response .= "<i>Date: " . htmlspecialchars($row['date']) . "</i><br><br>";
        }
    } else {
        $response = "You don't have any new notifications.";
    }
    
    return $response;
}

// Function to get project ideas
function getProjectIdeas($con, $domain = null) {
    // First check if we have projects in the specified domain
    $domainFilter = "";
    if ($domain) {
        $domain = sanitize($domain);
        $domainFilter = "WHERE project_domain LIKE '%$domain%'";
    }
    
    // Get some existing project titles to base suggestions on
    $query = "SELECT project_domain FROM project $domainFilter GROUP BY project_domain LIMIT 10";
    $result = mysqli_query($con, $query);
    
    $domains = [];
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $domains[] = $row['project_domain'];
        }
    }
    
    // If no specific domain or no projects found in that domain, use general ideas
    if (empty($domains)) {
        return "Here are some project ideas you might consider:<br><br>
                1. <b>AI-based Student Performance Prediction</b><br>
                2. <b>Smart Campus Navigation System</b><br>
                3. <b>Blockchain for Academic Credentials</b><br>
                4. <b>IoT-based Smart Classroom</b><br>
                5. <b>Virtual Reality Lab Simulator</b>";
    }
    
    // Otherwise, provide ideas based on domains in the database
    $response = "Based on our database, here are some project ideas";
    $response .= $domain ? " in the <b>$domain</b> domain" : "";
    $response .= ":<br><br>";
    
    $ideas = [
        "AI" => [
            "AI-powered Student Attendance System using Facial Recognition",
            "Predictive Analysis Tool for Academic Performance",
            "Smart Content Recommendation System for Learning",
            "Automated Essay Grading System",
            "Chatbot for Student Queries"
        ],
        "Web" => [
            "Advanced Project Management Dashboard",
            "Student Portfolio Website Builder",
            "Interactive Learning Platform",
            "Alumni Network Portal",
            "Course Feedback and Rating System"
        ],
        "Mobile" => [
            "Campus Navigation App",
            "Student Productivity Tracker",
            "Attendance Tracking Mobile App",
            "Class Schedule and Reminder Application",
            "Study Group Finder App"
        ],
        "IoT" => [
            "Smart Classroom Energy Management",
            "Campus Security System using IoT",
            "Laboratory Equipment Monitoring System",
            "Smart Library Management System",
            "Environmental Monitoring on Campus"
        ],
        "Blockchain" => [
            "Secure Academic Certificate Verification",
            "Transparent Research Funding Tracker",
            "Intellectual Property Rights Management",
            "Secure Student Record System",
            "Transparent Grading System"
        ],
        "Data" => [
            "Student Performance Analytics Dashboard",
            "Curriculum Gap Analysis Tool",
            "Research Trend Analysis Platform",
            "Campus Resource Utilization Tracker",
            "Predictive Admission Analysis System"
        ]
    ];
    
    $count = 1;
    foreach ($domains as $d) {
        // Find the best matching category
        $bestMatch = "Web"; // Default
        foreach (array_keys($ideas) as $category) {
            if (stripos($d, $category) !== false) {
                $bestMatch = $category;
                break;
            }
        }
        
        // Add a random idea from the matched category
        if (!empty($ideas[$bestMatch])) {
            $randIndex = array_rand($ideas[$bestMatch]);
            $response .= "$count. <b>" . $ideas[$bestMatch][$randIndex] . "</b> for " . htmlspecialchars($d) . "<br>";
            unset($ideas[$bestMatch][$randIndex]);
            
            if (empty($ideas[$bestMatch])) {
                $ideas[$bestMatch] = ["Custom " . $bestMatch . " Solution for " . $d];
            }
            
            $count++;
            if ($count > 5) break;
        }
    }
    
    return $response;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Management Chatbot</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* Reset and Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f5f5;
        }
        
        /* Chatbot Toggle Button */
        #chatbot-toggle {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 60px;
            height: 60px;
            background-color: #e45f06;
            border-radius: 50%;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            z-index: 1000;
            transition: all 0.3s ease;
        }
        
        #chatbot-toggle:hover {
            background-color: #ff6600;
            transform: scale(1.05);
        }
        
        /* Chatbot Interface */
        #chatbot-interface {
            position: fixed;
            bottom: 90px;
            right: 20px;
            width: 350px;
            height: 500px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            display: none;
            flex-direction: column;
            overflow: hidden;
            z-index: 999;
            transition: all 0.3s ease;
        }
        
        #chatbot-interface.expanded {
            width: 500px;
            height: 600px;
        }
        
        /* Chatbot Header */
        #chatbot-header {
            background-color: #e45f06;
            color: white;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        #chatbot-title {
            font-weight: bold;
            font-size: 16px;
        }
        
        #chatbot-controls {
            display: flex;
            gap: 10px;
        }
        
        #chatbot-controls i {
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        #chatbot-controls i:hover {
            color: #ffd1b3;
        }
        
        /* Chatbot Messages Area */
        #chatbot-messages {
            flex-grow: 1;
            padding: 15px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        /* Message Styling */
        .bot-message, .user-message {
            display: flex;
            gap: 10px;
            max-width: 80%;
        }
        
        .bot-message {
            align-self: flex-start;
        }
        
        .user-message {
            align-self: flex-end;
            flex-direction: row-reverse;
        }
        
        .message-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: #e45f06;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .user-message .message-avatar {
            background-color: #0078d4;
        }
        
        .message-bubble {
            background-color: #f0f0f0;
            padding: 10px 15px;
            border-radius: 18px;
            position: relative;
        }
        
        .user-message .message-bubble {
            background-color: #e6f2ff;
        }
        
        .message-content {
            font-size: 14px;
            line-height: 1.4;
        }
        
        .message-time {
            font-size: 11px;
            color: #888;
            margin-top: 5px;
            text-align: right;
        }
        
        /* Chatbot Input Area */
        #chatbot-input-area {
            padding: 15px;
            border-top: 1px solid #eee;
            display: flex;
            gap: 10px;
        }
        
        #chatbot-input {
            flex-grow: 1;
            border: 1px solid #ddd;
            border-radius: 20px;
            padding: 10px 15px;
            outline: none;
            font-size: 14px;
        }
        
        #chatbot-input:focus {
            border-color: #e45f06;
        }
        
        #chatbot-send {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #e45f06;
            color: white;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        #chatbot-send:hover {
            background-color: #ff6600;
            transform: scale(1.05);
        }
        
        /* Loading/Typing Indicator */
        .loading-message .typing-indicator {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px 0;
        }
        
        .loading-message .typing-indicator span {
            height: 8px;
            width: 8px;
            margin: 0 2px;
            background-color: #e45f06;
            border-radius: 50%;
            display: inline-block;
            animation: typing-animation 1.4s infinite ease-in-out both;
        }
        
        .loading-message .typing-indicator span:nth-child(1) {
            animation-delay: -0.32s;
        }
        
        .loading-message .typing-indicator span:nth-child(2) {
            animation-delay: -0.16s;
        }
        
        @keyframes typing-animation {
            0%, 80%, 100% { transform: scale(0); }
            40% { transform: scale(1); }
        }
        
        /* Quick Suggestions */
        .quick-suggestions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin: 10px 0 15px 45px;
            max-width: 80%;
        }
        
        .suggestion-button {
            background-color: #fff;
            border: 1px solid #e45f06;
            border-radius: 16px;
            color: #e45f06;
            padding: 6px 12px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
            white-space: nowrap;
        }
        
        .suggestion-button:hover {
            background-color: #fff0e8;
            border-color: #ff6600;
        }
    </style>
</head>
<body>
    <h1 style="text-align: center; margin-top: 30px; color: #e45f06;">Project Management System</h1>
    <p style="text-align: center; margin: 20px auto; max-width: 600px;">
        This is your project management dashboard. Click the chat icon in the bottom right to get help with your projects.
    </p>
    
    <!-- Chatbot Toggle Button -->
    <div id="chatbot-toggle">
        <i class="fas fa-comment-alt"></i>
    </div>
    
    <!-- Chatbot Interface -->
    <div id="chatbot-interface">
        <div id="chatbot-header">
            <div id="chatbot-title">Project Assistant</div>
            <div id="chatbot-controls">
                <i id="chatbot-expand" class="fas fa-expand-alt"></i>
                <i id="chatbot-close" class="fas fa-times"></i>
            </div>
        </div>
        <div id="chatbot-messages">
            <!-- Messages will be added here dynamically -->
        </div>
        <div id="chatbot-input-area">
            <input type="text" id="chatbot-input" placeholder="Type your message here...">
            <button id="chatbot-send">
                <i class="fas fa-paper-plane"></i>
            </button>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const chatbotToggle = document.getElementById('chatbot-toggle');
            const chatbotInterface = document.getElementById('chatbot-interface');
            const chatbotClose = document.getElementById('chatbot-close');
            const chatbotExpand = document.getElementById('chatbot-expand');
            const chatbotInput = document.getElementById('chatbot-input');
            const chatbotSend = document.getElementById('chatbot-send');
            const chatbotMessages = document.getElementById('chatbot-messages');
            
            // Initial greeting message
            const initialMessage = {
              type: 'bot',
              content: "Hello! I'm your project assistant. I can help you with:<br>• List of all projects<br>• Total number of projects<br>• Project ideas<br>• Domain-specific projects<br>• Notifications<br>• Deadlines<br>What would you like to know?"
            };
            
            // Render the initial message
            renderMessage(initialMessage);
            
            // Toggle chat interface
            chatbotToggle.addEventListener('click', function() {
              chatbotInterface.style.display = chatbotInterface.style.display === 'flex' ? 'none' : 'flex';
            });
            
            // Close chat interface
            chatbotClose.addEventListener('click', function() {
              chatbotInterface.style.display = 'none';
            });
            
            // Expand/collapse chat interface
            chatbotExpand.addEventListener('click', function() {
              chatbotInterface.classList.toggle('expanded');
              
              // Toggle icon between expand and compress
              const icon = chatbotExpand.querySelector('i');
              if (icon.classList.contains('fa-expand-alt')) {
                icon.classList.remove('fa-expand-alt');
                icon.classList.add('fa-compress-alt');
              } else {
                icon.classList.remove('fa-compress-alt');
                icon.classList.add('fa-expand-alt');
              }
            });
            
            // Get current time for message timestamp
            function getCurrentTime() {
              const now = new Date();
              let hours = now.getHours();
              let minutes = now.getMinutes();
              const ampm = hours >= 12 ? 'PM' : 'AM';
              
              hours = hours % 12;
              hours = hours ? hours : 12; // the hour '0' should be '12'
              minutes = minutes < 10 ? '0' + minutes : minutes;
              
              return hours + ':' + minutes + ' ' + ampm;
            }
            
            // Render a message (bot or user)
            function renderMessage(message) {
              const messageElement = document.createElement('div');
              messageElement.className = message.type === 'bot' ? 'bot-message' : 'user-message';
              
              // Create message HTML based on type
              if (message.type === 'bot') {
                messageElement.innerHTML = `
                  <div class="message-avatar">
                    <i class="fas fa-robot"></i>
                  </div>
                  <div class="message-bubble">
                    <div class="message-content">${message.content}</div>
                    <div class="message-time">${getCurrentTime()}</div>
                  </div>
                `;
              } else {
                messageElement.innerHTML = `
                  <div class="message-bubble">
                    <div class="message-content">${message.content}</div>
                    <div class="message-time">${getCurrentTime()}</div>
                  </div>
                  <div class="message-avatar">
                    <i class="fas fa-user"></i>
                  </div>
                `;
              }
              
              chatbotMessages.appendChild(messageElement);
              
              // Scroll to bottom
              chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
            }
            
            // Show loading indicator
            function showLoadingIndicator() {
              const loadingElement = document.createElement('div');
              loadingElement.className = 'bot-message loading-message';
              loadingElement.id = 'loading-indicator';
              
              loadingElement.innerHTML = `
                <div class="message-avatar">
                  <i class="fas fa-robot"></i>
                </div>
                <div class="message-bubble">
                  <div class="message-content">
                    <div class="typing-indicator">
                      <span></span>
                      <span></span>
                      <span></span>
                    </div>
                  </div>
                </div>
              `;
              
              chatbotMessages.appendChild(loadingElement);
              chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
            }
            
            // Remove loading indicator
            function removeLoadingIndicator() {
              const loadingElement = document.getElementById('loading-indicator');
              if (loadingElement) {
                loadingElement.remove();
              }
            }
            
            // Send message function
            function sendMessage() {
              const message = chatbotInput.value.trim();
              if (message === '') return;
              
              // Render user message
              renderMessage({
                type: 'user',
                content: message
              });
              
              // Clear input
              chatbotInput.value = '';
              
              // Show loading indicator
              showLoadingIndicator();
              
              // Send request to backend
              fetchBotResponse(message);
            }
            
            // Fetch response from backend
            function fetchBotResponse(userMessage) {
              // Create form data
              const formData = new FormData();
              formData.append('query', userMessage);
              
              // Use fetch API to communicate with backend
              fetch('', {  // Empty string fetches current URL
                method: 'POST',
                body: formData
              })
              .then(response => {
                if (!response.ok) {
                  throw new Error('Network response was not ok');
                }
                return response.json();
              })
              .then(data => {
                // Remove loading indicator
                removeLoadingIndicator();
                
                // Render bot response
                renderMessage({
                  type: 'bot',
                  content: data.response
                });
              })
              .catch(error => {
                // Remove loading indicator
                removeLoadingIndicator();
                
                // Show error message
                renderMessage({
                  type: 'bot',
                  content: "Sorry, I'm having trouble connecting to the server. Please try again later. Error: " + error.message
                });
                
                console.error('Error:', error);
              });
            }
            
            // Add quick suggestion buttons
            function addQuickSuggestions() {
              const suggestions = [
                "List of all projects",
                "Total number of projects",
                "Project ideas in AI",
                "Show me new notifications",
                "When is the next deadline?"
              ];
              
              const suggestionsContainer = document.createElement('div');
              suggestionsContainer.className = 'quick-suggestions';
              
              suggestions.forEach(suggestion => {
                const button = document.createElement('button');
                button.className = 'suggestion-button';
                button.textContent = suggestion;
                button.addEventListener('click', function() {
                  // Set input value to suggestion
                  chatbotInput.value = suggestion;
                  
                  // Send message
                  sendMessage();
                });
                
                suggestionsContainer.appendChild(button);
              });
              
              // Add suggestions after the initial greeting
              chatbotMessages.appendChild(suggestionsContainer);
            }
            
            // Add quick suggestions after initial greeting
            addQuickSuggestions();
            
            // Send message on button click
            chatbotSend.addEventListener('click', sendMessage);
            
            // Send message on Enter key
            chatbotInput.addEventListener('keypress', function(e) {
              if (e.key === 'Enter') {
                sendMessage();
              }
            });
        });
    </script>
</body>
</html>