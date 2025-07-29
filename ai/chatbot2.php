<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Chatbot</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }

        body {
            background-color: #f5f7fb;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .chat-container {
            width: 100%;
            max-width: 800px;
            height: 90vh;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .chat-header {
            background-color: #4e54c8;
            color: white;
            padding: 20px;
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            position: relative;
        }

        .pulse {
            height: 10px;
            width: 10px;
            background-color: #4caf50;
            border-radius: 50%;
            position: absolute;
            top: 28px;
            right: 130px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(76, 175, 80, 0.7);
            }
            70% {
                transform: scale(1);
                box-shadow: 0 0 0 10px rgba(76, 175, 80, 0);
            }
            100% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(76, 175, 80, 0);
            }
        }

        .chat-messages {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
        }

        .message {
            margin-bottom: 20px;
            padding: 12px 18px;
            border-radius: 18px;
            max-width: 75%;
            word-wrap: break-word;
            position: relative;
            animation: fadeIn 0.3s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .user-message {
            background-color: #e1f5fe;
            color: #0277bd;
            margin-left: auto;
            border-bottom-right-radius: 0;
        }

        .bot-message {
            background-color: #f1f1f1;
            color: #333;
            margin-right: auto;
            border-bottom-left-radius: 0;
        }

        .thinking {
            display: flex;
            padding: 12px 18px;
            background-color: #f1f1f1;
            border-radius: 18px;
            border-bottom-left-radius: 0;
            margin-bottom: 20px;
            width: fit-content;
        }

        .dot {
            height: 8px;
            width: 8px;
            margin: 0 4px;
            background-color: #999;
            border-radius: 50%;
            animation: bounce 1.5s infinite;
        }

        .dot:nth-child(1) { animation-delay: 0s; }
        .dot:nth-child(2) { animation-delay: 0.2s; }
        .dot:nth-child(3) { animation-delay: 0.4s; }

        @keyframes bounce {
            0%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-10px); }
        }

        .chat-input {
            padding: 20px;
            background-color: #f9f9f9;
            border-top: 1px solid #eee;
            display: flex;
        }

        #message-input {
            flex: 1;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 25px;
            outline: none;
            font-size: 16px;
            resize: none;
            height: 50px;
            transition: all 0.3s;
        }

        #message-input:focus {
            border-color: #4e54c8;
            box-shadow: 0 0 0 2px rgba(78, 84, 200, 0.2);
        }

        #send-button {
            margin-left: 10px;
            background-color: #4e54c8;
            color: white;
            border: none;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            justify-content: center;
            align-items: center;
            transition: all 0.2s;
        }

        #send-button:hover {
            background-color: #5c64e8;
            transform: scale(1.05);
        }

        #send-button:active {
            transform: scale(0.95);
        }

        .source-info {
            font-size: 12px;
            color: #888;
            text-align: right;
            margin-top: 5px;
        }

        @media (max-width: 600px) {
            .chat-container {
                height: 100vh;
                border-radius: 0;
            }
            
            .message {
                max-width: 85%;
            }
        }
    </style>
</head>
<body>
    <div class="chat-container">
        <div class="chat-header">
            AI Assistant
            <div class="pulse"></div>
        </div>
        <div class="chat-messages" id="chat-messages">
            <div class="message bot-message">
                Hello! I'm your AI assistant. Ask me anything, and I'll try to help you with answers from our database or the internet.
            </div>
        </div>
        <div class="chat-input">
            <textarea id="message-input" placeholder="Type your message here..." rows="1"></textarea>
            <button id="send-button">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M22 2L11 13" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M22 2L15 22L11 13L2 9L22 2Z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const chatMessages = document.getElementById('chat-messages');
            const messageInput = document.getElementById('message-input');
            const sendButton = document.getElementById('send-button');

            // Automatically resize the textarea based on content
            messageInput.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
                if (this.scrollHeight > 150) {
                    this.style.height = '150px';
                }
            });

            // Handle send message button click
            sendButton.addEventListener('click', sendMessage);

            // Handle Enter key to send message (but Shift+Enter for new line)
            messageInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    sendMessage();
                }
            });

            function sendMessage() {
                const message = messageInput.value.trim();
                if (message.length === 0) return;

                // Add user message to chat
                addMessage(message, 'user');
                
                // Clear input
                messageInput.value = '';
                messageInput.style.height = '50px';
                
                // Show thinking animation
                const thinkingDiv = document.createElement('div');
                thinkingDiv.className = 'thinking';
                thinkingDiv.innerHTML = `
                    <div class="dot"></div>
                    <div class="dot"></div>
                    <div class="dot"></div>
                `;
                chatMessages.appendChild(thinkingDiv);
                chatMessages.scrollTop = chatMessages.scrollHeight;
                
                // Send message to backend
                fetch('chatbot_backend.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'message=' + encodeURIComponent(message)
                })
                .then(response => response.json())
                .then(data => {
                    // Remove thinking animation
                    if (thinkingDiv.parentNode) {
                        thinkingDiv.parentNode.removeChild(thinkingDiv);
                    }
                    
                    // Add bot response to chat
                    let sourceInfo = '';
                    if (data.source) {
                        sourceInfo = `<div class="source-info">Source: ${data.source}</div>`;
                    }
                    
                    addMessage(data.response + sourceInfo, 'bot');
                })
                .catch(error => {
                    // Remove thinking animation
                    if (thinkingDiv.parentNode) {
                        thinkingDiv.parentNode.removeChild(thinkingDiv);
                    }
                    
                    // Show error message
                    addMessage("Sorry, I encountered an error processing your request. Please try again later.", 'bot');
                    console.error('Error:', error);
                });
            }

            function addMessage(message, sender) {
                const messageDiv = document.createElement('div');
                messageDiv.className = `message ${sender}-message`;
                messageDiv.innerHTML = message;
                chatMessages.appendChild(messageDiv);
                
                // Scroll to bottom
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
        });
    </script>
</body>
</html>