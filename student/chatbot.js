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
    
    function fetchBotResponse(userMessage) {
      // Create form data
      const formData = new FormData();
      formData.append('query', userMessage);
      
      // Debug log
      console.log("Sending request to server with message:", userMessage);
      
      fetch('./chatbot-api.php', {  
        method: 'POST',
        body: formData,
        credentials: 'same-origin' 
      })
      .then(response => {
        // Log raw response for debugging
        console.log("Raw response status:", response.status);
        
        if (!response.ok) {
          throw new Error('Network response was not ok: ' + response.status);
        }
        
        // Clone the response for debugging
        const clonedResponse = response.clone();
        
        // Log the raw text for debugging
        clonedResponse.text().then(text => {
          console.log("Raw response body:", text);
        });
        
        return response.json();
      })
      .then(data => {
        // Remove loading indicator
        removeLoadingIndicator();
        
        // Log the parsed data
        console.log("Parsed response data:", data);
        
        // Handle multiple possible response formats
        let responseContent = "";
        
        if (data && data.response) {
          responseContent = data.response;
        } else if (data && data.message) {
          responseContent = data.message;
        } else if (data && typeof data === 'string') {
          responseContent = data;
        } else {
          responseContent = "I received your message but couldn't format the response properly.";
        }
        
        // Render bot response
        renderMessage({
          type: 'bot',
          content: responseContent
        });
      })
      .catch(error => {
        // Remove loading indicator
        removeLoadingIndicator();
        
        console.error('Error in fetch operation:', error);
        
        // Show error message
        renderMessage({
          type: 'bot',
          content: "Sorry, I'm having trouble connecting to the server. Please try again later. (Error: " + error.message + ")"
        });
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
    
    // Add additional styles for new components
    const style = document.createElement('style');
    style.textContent = `
     
/* Quick Suggestions */
.quick-suggestions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin: 10px 0 15px 45px;
  max-width: 80%;
}

.suggestion-button {
  background-color: white;
  border: 1px solid #e45f06;
  border-radius: 16px;
  color: #e45f06;
  padding: 8px 12px;
  font-size: 12px;
  cursor: pointer;
  transition: all 0.2s ease;
  white-space: nowrap;
}

.suggestion-button:hover {
  background-color: #fff0e8;
  border-color: #ff6600;
  transform: translateY(-1px);
}

/* Loading Indicator Animation */
.loading-message .typing-indicator {
  display: flex;
  align-items: center;
  justify-content: flex-start;
  padding: 5px 0;
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
    `;
    
    document.head.appendChild(style);
});