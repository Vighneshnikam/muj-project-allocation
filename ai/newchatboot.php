<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Management Assistant</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2980b9;
            --background-color: #f5f8fa;
            --text-color: #333;
            --light-gray: #ecf0f1;
            --dark-gray: #7f8c8d;
            --success-color: #2ecc71;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: var(--background-color);
            color: var(--text-color);
            height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        header {
            background-color: var(--primary-color);
            color: white;
            padding: 15px 0;
            text-align: center;
            box-shadow: var(--shadow);
        }

        .chat-container {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            background-color: white;
            border-radius: 10px;
            box-shadow: var(--shadow);
            overflow: hidden;
            margin-top: 20px;
        }

        .chat-header {
            background-color: var(--primary-color);
            color: white;
            padding: 15px;
            display: flex;
            align-items: center;
        }

        .chat-header img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
        }

        .chat-header h2 {
            font-size: 18px;
        }

        .chat-messages {
            flex-grow: 1;
            padding: 20px;
            overflow-y: auto;
            height: 60vh;
        }

        .message {
            margin-bottom: 15px;
            display: flex;
            align-items: flex-start;
        }

        .message.bot {
            justify-content: flex-start;
        }

        .message.user {
            justify-content: flex-end;
        }

        .message-content {
            max-width: 70%;
            padding: 12px 15px;
            border-radius: 18px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            word-wrap: break-word;
        }

        .bot .message-content {
            background-color: var(--light-gray);
            border-bottom-left-radius: 5px;
        }

        .user .message-content {
            background-color: var(--primary-color);
            color: white;
            border-bottom-right-radius: 5px;
        }

        .message-time {
            font-size: 12px;
            color: var(--dark-gray);
            margin-top: 5px;
            text-align: right;
        }

        .typing-indicator {
            display: flex;
            padding: 12px 15px;
            background-color: var(--light-gray);
            border-radius: 18px;
            border-bottom-left-radius: 5px;
            margin-bottom: 15px;
            align-items: center;
            display: none;
        }

        .typing-dot {
            height: 8px;
            width: 8px;
            border-radius: 50%;
            background-color: var(--dark-gray);
            margin: 0 2px;
            animation: typing-animation 1.5s infinite ease-in-out;
        }

        .typing-dot:nth-child(1) {
            animation-delay: 0s;
        }

        .typing-dot:nth-child(2) {
            animation-delay: 0.5s;
        }

        .typing-dot:nth-child(3) {
            animation-delay: 1s;
        }

        @keyframes typing-animation {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-5px); }
            100% { transform: translateY(0px); }
        }

        .chat-input {
            display: flex;
            padding: 15px;
            background-color: var(--light-gray);
            border-top: 1px solid #ddd;
        }

        .chat-input input {
            flex-grow: 1;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 25px;
            font-size: 16px;
            outline: none;
            transition: border-color 0.3s;
        }

        .chat-input input:focus {
            border-color: var(--primary-color);
        }

        .chat-input button {
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 25px;
            padding: 12px 20px;
            margin-left: 10px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .chat-input button:hover {
            background-color: var(--secondary-color);
        }

        .chat-input button:disabled {
            background-color: var(--dark-gray);
            cursor: not-allowed;
        }

        .suggestions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
            padding: 0 15px 15px;
        }

        .suggestion {
            background-color: var(--light-gray);
            padding: 8px 15px;
            border-radius: 18px;
            font-size: 14px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .suggestion:hover {
            background-color: #dde4e7;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            font-size: 14px;
        }

        .data-table th,
        .data-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        .data-table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }

        .data-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .message-content pre {
            background-color: #f6f8fa;
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
            margin: 5px 0;
            font-family: monospace;
            color: var(--text-color);
        }

        @media screen and (max-width: 768px) {
            .message-content {
                max-width: 85%;
            }
        }
    </style>
</head>
<body>
    <header>
        <h1>Project Management System</h1>
    </header>

    <div class="container">
        <div class="chat-container">
            <div class="chat-header">
                <img src="/api/placeholder/40/40" alt="Bot Avatar">
                <h2>Project Management Assistant</h2>
            </div>
            <div class="chat-messages" id="chatMessages">
                <div class="message bot">
                    <div class="message-content">
                        Hello! I'm your Project Management Assistant. How can I help you today?
                        <div class="message-time">Now</div>
                    </div>
                </div>
            </div>
            <div class="suggestions">
                <div class="suggestion" onclick="selectSuggestion('Show all projects')">Show all projects</div>
                <div class="suggestion" onclick="selectSuggestion('List faculty members')">List faculty members</div>
                <div class="suggestion" onclick="selectSuggestion('Recent notifications')">Recent notifications</div>
                <div class="suggestion" onclick="selectSuggestion('My allocated project')">My allocated project</div>
            </div>
            <div class="chat-input">
                <input type="text" id="userInput" placeholder="Type your question here..." autocomplete="off">
                <button id="sendButton"><i class="fas fa-paper-plane"></i> Send</button>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            const chatMessages = $('#chatMessages');
            const userInput = $('#userInput');
            const sendButton = $('#sendButton');
            let isWaitingForResponse = false;

            // Send message when button is clicked
            sendButton.on('click', sendMessage);

            // Send message when Enter key is pressed
            userInput.on('keypress', function(e) {
                if (e.which === 13) {
                    sendMessage();
                }
            });

            // Enable/disable send button based on input
            userInput.on('input', function() {
                sendButton.prop('disabled', userInput.val().trim() === '');
            });
            
            // Initialize button state
            sendButton.prop('disabled', true);

            function selectSuggestion(text) {
                userInput.val(text);
                sendButton.prop('disabled', false);
                sendMessage();
            }
            
            function sendMessage() {
                if (isWaitingForResponse || userInput.val().trim() === '') {
                    return;
                }
                
                const userMessage = userInput.val();
                
                // Add user message to chat
                addMessage('user', userMessage);
                
                // Clear input field
                userInput.val('');
                sendButton.prop('disabled', true);
                
                // Show typing indicator
                showTypingIndicator();
                
                // Set waiting flag
                isWaitingForResponse = true;
                
                // Make AJAX request to process query
                $.ajax({
                    url: './procces_query.php',
                    method: 'POST',
                    data: { query: userMessage },
                    dataType: 'json',
                    success: function(response) {
                        // Remove typing indicator and add bot response
                        hideTypingIndicator();
                        addMessage('bot', response.message, response.data);
                        isWaitingForResponse = false;
                    },
                    error: function() {
                        // Handle error
                        hideTypingIndicator();
                        addMessage('bot', "I'm sorry, I couldn't process your request. Please try again later.");
                        isWaitingForResponse = false;
                    }
                });
            }
            
            function addMessage(sender, message, data = null) {
                const messageDiv = $('<div>').addClass('message').addClass(sender);
                const contentDiv = $('<div>').addClass('message-content');
                
                // Add text message
                contentDiv.text(message);
                
                // Format the time
                const now = new Date();
                const timeStr = now.getHours().toString().padStart(2, '0') + ':' + 
                                now.getMinutes().toString().padStart(2, '0');
                
                // Add time
                const timeDiv = $('<div>').addClass('message-time').text(timeStr);
                contentDiv.append(timeDiv);
                
                // Add data table if provided
                if (data && data.length > 0) {
                    // Create table
                    const table = $('<table>').addClass('data-table');
                    
                    // Add headers
                    const headerRow = $('<tr>');
                    Object.keys(data[0]).forEach(key => {
                        headerRow.append($('<th>').text(key));
                    });
                    table.append($('<thead>').append(headerRow));
                    
                    // Add data rows
                    const tbody = $('<tbody>');
                    data.forEach(row => {
                        const dataRow = $('<tr>');
                        Object.values(row).forEach(value => {
                            dataRow.append($('<td>').text(value));
                        });
                        tbody.append(dataRow);
                    });
                    table.append(tbody);
                    
                    // Replace text content with formatted message and table
                    contentDiv.html(message + '<br>');
                    contentDiv.append(table);
                    contentDiv.append(timeDiv); // Re-add time after table
                }
                
                messageDiv.append(contentDiv);
                chatMessages.append(messageDiv);
                
                // Scroll to bottom
                chatMessages.scrollTop(chatMessages[0].scrollHeight);
            }
            
            function showTypingIndicator() {
                const indicator = $('<div>').addClass('typing-indicator');
                for (let i = 0; i < 3; i++) {
                    indicator.append($('<div>').addClass('typing-dot'));
                }
                chatMessages.append($('<div>').addClass('message bot').append(indicator));
                chatMessages.scrollTop(chatMessages[0].scrollHeight);
            }
            
            function hideTypingIndicator() {
                chatMessages.find('.typing-indicator').parent().remove();
            }

            // Make the selectSuggestion function globally accessible
            window.selectSuggestion = selectSuggestion;
        });
    </script>
</body>
</html>