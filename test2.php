<?php
session_start();

$server = "localhost";
$username = "root";
$password = "";
$dbname = "project_management_website";

$con = mysqli_connect($server, $username, $password, $dbname);
if ($con->connect_error) {
    die("Connection failed: " . $con->connect_error);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Management Assistant</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="chatbot_styles.css">
    <style>
        :root {
    --primary-color: #ff7700;
    --primary-light: #ff9940;
    --primary-dark: #cc5f00;
    --text-color: #333333;
    --light-bg: #f8f8f8;
    --white: #ffffff;
    --gray: #e0e0e0;
    --dark-gray: #aaaaaa;
    --shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

/* Chatbot Icon */
.chatbot-icon {
    position: fixed;
    bottom: 20px;
    right: 20px;
    width: 60px;
    height: 60px;
    background-color: var(--primary-color);
    border-radius: 50%;
    display: flex;
    justify-content: center;
    align-items: center;
    cursor: pointer;
    box-shadow: var(--shadow);
    z-index: 999;
    transition: all 0.3s ease;
}

.chatbot-icon i {
    color: var(--white);
    font-size: 24px;
}

.chatbot-icon:hover {
    background-color: var(--primary-dark);
    transform: scale(1.05);
}

.notification-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background-color: red;
    color: white;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    font-size: 12px;
    display: flex;
    justify-content: center;
    align-items: center;
    font-weight: bold;
    display: none;
}

/* Chatbot Container */
.chatbot-container {
    position: fixed;
    bottom: 90px;
    right: 20px;
    width: 350px;
    height: 500px;
    background-color: var(--white);
    border-radius: 10px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    box-shadow: var(--shadow);
    z-index: 998;
    transition: all 0.3s ease;
    display: none;
}

.expanded {
    width: 80%;
    height: 80%;
    bottom: 10%;
    right: 10%;
}

/* Chatbot Header */
.chatbot-header {
    background-color: var(--primary-color);
    color: var(--white);
    padding: 15px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.chatbot-title {
    font-weight: bold;
    font-size: 16px;
}

.chatbot-actions {
    display: flex;
    gap: 10px;
}

.action-button {
    background: none;
    border: none;
    color: var(--white);
    cursor: pointer;
    font-size: 14px;
}

.action-button:hover {
    color: var(--gray);
}

/* Chatbot Messages */
.chatbot-messages {
    flex: 1;
    padding: 15px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.message {
    max-width: 80%;
    padding: 10px 15px;
    border-radius: 15px;
    margin-bottom: 5px;
}

.user-message {
    align-self: flex-end;
    background-color: var(--primary-light);
    color: var(--white);
    border-bottom-right-radius: 5px;
}

.bot-message {
    align-self: flex-start;
    background-color: var(--light-bg);
    color: var(--text-color);
    border-bottom-left-radius: 5px;
}

/* Tables in bot responses */
.bot-message table {
    border-collapse: collapse;
    width: 100%;
    margin: 10px 0;
    font-size: 14px;
}

.bot-message th, .bot-message td {
    border: 1px solid var(--dark-gray);
    padding: 6px;
    text-align: left;
}

.bot-message th {
    background-color: var(--primary-light);
    color: var(--white);
}

/* Chatbot Input */
.chatbot-input {
    padding: 15px;
    display: flex;
    gap: 10px;
    border-top: 1px solid var(--gray);
}

.chatbot-input input {
    flex: 1;
    padding: 10px 15px;
    border: 1px solid var(--gray);
    border-radius: 20px;
    outline: none;
}

.chatbot-input input:focus {
    border-color: var(--primary-color);
}

.chatbot-input button {
    background-color: var(--primary-color);
    color: var(--white);
    border: none;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    justify-content: center;
    align-items: center;
    transition: background-color 0.3s ease;
}

.chatbot-input button:hover {
    background-color: var(--primary-dark);
}
    </style>
</head>
<body>
    <!-- Chatbot Icon -->
    <div id="chatbot-icon" class="chatbot-icon">
        <i class="fas fa-robot"></i>
        <span class="notification-badge" id="notification-badge">0</span>
    </div>

    <!-- Chatbot Interface -->
    <div id="chatbot-container" class="chatbot-container">
        <div class="chatbot-header">
            <div class="chatbot-title">Project Assistant</div>
            <div class="chatbot-actions">
                <button id="minimize-btn" class="action-button"><i class="fas fa-minus"></i></button>
                <button id="expand-btn" class="action-button"><i class="fas fa-expand"></i></button>
            </div>
        </div>
        <div class="chatbot-messages" id="chatbot-messages">
            <div class="message bot-message">
                <div class="message-content">Hello! I'm your project management assistant. Ask me about projects, notifications, or anything else related to your system.</div>
            </div>
        </div>
        <div class="chatbot-input">
            <input type="text" id="user-input" placeholder="Type your question here...">
            <button id="send-btn"><i class="fas fa-paper-plane"></i></button>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
    const chatbotIcon = document.getElementById('chatbot-icon');
    const chatbotContainer = document.getElementById('chatbot-container');
    const minimizeBtn = document.getElementById('minimize-btn');
    const expandBtn = document.getElementById('expand-btn');
    const userInput = document.getElementById('user-input');
    const sendBtn = document.getElementById('send-btn');
    const messagesContainer = document.getElementById('chatbot-messages');
    const notificationBadge = document.getElementById('notification-badge');

    // Toggle chatbot visibility
    chatbotIcon.addEventListener('click', function() {
        chatbotContainer.style.display = chatbotContainer.style.display === 'none' ? 'flex' : 'none';
        if (chatbotContainer.style.display === 'flex') {
            notificationBadge.style.display = 'none';
            notificationBadge.textContent = '0';
            userInput.focus();
            
            // Fetch unread notifications count when opening chat
            fetchUnreadNotifications();
        }
    });

    // Minimize chatbot
    minimizeBtn.addEventListener('click', function() {
        chatbotContainer.style.display = 'none';
    });

    // Expand chatbot
    expandBtn.addEventListener('click', function() {
        chatbotContainer.classList.toggle('expanded');
    });

    // Send message on Enter key
    userInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            sendMessage();
        }
    });

    // Send message on button click
    sendBtn.addEventListener('click', sendMessage);

    // Function to send user message
    function sendMessage() {
        const message = userInput.value.trim();
        if (message === '') return;

        // Add user message to chat
        addMessage(message, 'user');
        userInput.value = '';

        // Show typing indicator
        const typingIndicator = document.createElement('div');
        typingIndicator.className = 'message bot-message typing-indicator';
        typingIndicator.innerHTML = '<div class="message-content">Thinking...</div>';
        messagesContainer.appendChild(typingIndicator);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;

        // Process the message
        processMessage(message)
            .then(response => {
                // Remove typing indicator
                messagesContainer.removeChild(typingIndicator);
                
                // Add bot response
                addMessage(response, 'bot');
            })
            .catch(error => {
                // Remove typing indicator
                messagesContainer.removeChild(typingIndicator);
                
                // Add error message
                addMessage("Sorry, I encountered an error. Please try again.", 'bot');
                console.error('Error:', error);
            });
    }

    // Function to add message to chat
    function addMessage(content, sender) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${sender}-message`;
        
        const messageContent = document.createElement('div');
        messageContent.className = 'message-content';
        
        // Check if content is HTML (for tables, etc.)
        if (content.includes('<table>') || content.includes('<ul>')) {
            messageContent.innerHTML = content;
        } else {
            messageContent.textContent = content;
        }
        
        messageDiv.appendChild(messageContent);
        messagesContainer.appendChild(messageDiv);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    // Function to process user message
    async function processMessage(message) {
        try {
            const response = await fetch('chatbot_backend.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `message=${encodeURIComponent(message)}`
            });
            
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            
            const data = await response.json();
            return data.response;
        } catch (error) {
            console.error('Error:', error);
            return "Sorry, I couldn't process your request. Please try again.";
        }
    }
    
    // Function to fetch unread notifications
    async function fetchUnreadNotifications() {
        try {
            const response = await fetch('get_notifications.php');
            const data = await response.json();
            
            if (data.count > 0) {
                notificationBadge.textContent = data.count;
                notificationBadge.style.display = 'flex';
                
                // Add notification message
                addMessage(`You have ${data.count} new notification(s).`, 'bot');
            }
        } catch (error) {
            console.error('Error fetching notifications:', error);
        }
    }
    
    // Check for notifications periodically
    setInterval(function() {
        if (chatbotContainer.style.display === 'none') {
            fetchUnreadNotifications();
        }
    }, 60000); // Check every minute
    
    // Initial notifications check
    fetchUnreadNotifications();
});
    </script>
</body>
</html>