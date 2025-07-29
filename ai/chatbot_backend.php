<?php
// chatbot_backend.php - Backend logic for the AI chatbot

// Set headers for JSON response
header('Content-Type: application/json');

// Database connection parameters
$db_host = 'localhost';
$db_user = 'your_username';
$db_pass = 'your_password';
$db_name = 'chatbot_db';

// Get user query
$user_message = isset($_POST['message']) ? $_POST['message'] : '';

if (empty($user_message)) {
    echo json_encode([
        'response' => 'Sorry, I didn\'t receive your message. Please try again.',
        'source' => 'system'
    ]);
    exit;
}

// Process the message
$response = processQuery($user_message);

// Return response as JSON
echo json_encode($response);

/**
 * Process user query - first check the database, then fallback to intelligent response
 * 
 * @param string $query The user's query
 * @return array Response array with answer and source
 */
function processQuery($query) {
    // Clean and normalize the query
    $cleaned_query = cleanQuery($query);
    
    // First, check our pre-defined answers (no database required)
    $predefined_result = checkPredefinedAnswers($cleaned_query);
    
    if ($predefined_result) {
        return [
            'response' => $predefined_result['answer'],
            'source' => 'knowledge base'
        ];
    }
    
    // Try the database if available
    $db_result = queryDatabase($cleaned_query);
    
    if ($db_result) {
        return [
            'response' => $db_result['answer'],
            'source' => 'database'
        ];
    }
    
    // Generate a more intelligent fallback response
    return generateIntelligentResponse($cleaned_query);
}

/**
 * Check for predefined answers (no database required)
 * 
 * @param string $query Cleaned user query
 * @return array|null Answer array or null if not found
 */
function checkPredefinedAnswers($query) {
    // Common questions and their answers
    $predefined = [
        'hello' => [
            'answer' => 'Hello there! How can I assist you today?',
            'confidence' => 1.0
        ],
        'hi' => [
            'answer' => 'Hi! I\'m your AI assistant. What can I help you with?',
            'confidence' => 1.0
        ],
        'who are you' => [
            'answer' => 'I\'m an AI assistant chatbot designed to answer your questions using information from my knowledge base and database.',
            'confidence' => 1.0
        ],
        'today date' => [
            'answer' => 'Today is ' . date('F j, Y') . '.',
            'confidence' => 1.0
        ],
        'what time is it' => [
            'answer' => 'The current server time is ' . date('h:i A') . '.',
            'confidence' => 1.0
        ],
        'what is artificial intelligence' => [
            'answer' => 'Artificial Intelligence (AI) refers to computer systems designed to perform tasks that typically require human intelligence. These tasks include learning, reasoning, problem-solving, perception, and language understanding.',
            'confidence' => 0.95
        ],
        'how does machine learning work' => [
            'answer' => 'Machine learning works by using algorithms to analyze data, learn from it, and make predictions or decisions. There are three main types: supervised learning (using labeled data), unsupervised learning (finding patterns in unlabeled data), and reinforcement learning (learning through trial and error with rewards).',
            'confidence' => 0.9
        ],
        'what is the difference between ai and machine learning' => [
            'answer' => 'AI is the broader concept of machines being able to carry out tasks in a "smart" way, while machine learning is a subset of AI that focuses on the ability of machines to receive data and learn from it without being explicitly programmed.',
            'confidence' => 0.85
        ],
        'who created the first ai program' => [
            'answer' => 'The first AI program is generally considered to be the Logic Theorist, created by Allen Newell, Herbert A. Simon, and Cliff Shaw in 1956. It was designed to mimic the problem-solving skills of a human.',
            'confidence' => 0.8
        ],
    ];
    
    // Check for exact matches
    if (isset($predefined[$query])) {
        return $predefined[$query];
    }
    
    // Check for partial matches
    foreach ($predefined as $key => $value) {
        if (strpos($query, $key) !== false || strpos($key, $query) !== false) {
            return $value;
        }
    }
    
    return null;
}

/**
 * Clean and normalize the query
 * 
 * @param string $query Raw user query
 * @return string Cleaned query
 */
function cleanQuery($query) {
    // Remove extra spaces, convert to lowercase
    $cleaned = trim(strtolower($query));
    
    // Remove any special characters except basic punctuation
    $cleaned = preg_replace('/[^\w\s\?\!\.\,]/', '', $cleaned);
    
    // Remove question marks at the end
    $cleaned = rtrim($cleaned, '?!.,');
    
    return $cleaned;
}

/**
 * Query the database for matching answers
 * 
 * @param string $query Cleaned user query
 * @return array|null Answer array or null if not found
 */
function queryDatabase($query) {
    global $db_host, $db_user, $db_pass, $db_name;
    
    try {
        // Connect to database
        $conn = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Prepare search terms for query
        $search_terms = explode(' ', $query);
        $search_condition = '';
        $params = [];
        
        foreach ($search_terms as $index => $term) {
            // Only include meaningful words (ignore short words like "a", "the", etc.)
            if (strlen($term) > 2) {
                if (!empty($search_condition)) {
                    $search_condition .= " OR ";
                }
                $search_condition .= "keywords LIKE :term$index";
                $params["term$index"] = "%$term%";
            }
        }
        
        // If we have search terms, perform the query
        if (!empty($search_condition)) {
            $stmt = $conn->prepare("SELECT answer, confidence FROM qa_pairs WHERE $search_condition ORDER BY confidence DESC LIMIT 1");
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // If we got a result and confidence is acceptable
            if ($result && $result['confidence'] > 0.7) {
                return [
                    'answer' => $result['answer'],
                    'confidence' => $result['confidence']
                ];
            }
        }
        
        return null;
    } catch (PDOException $e) {
        // Log the error but don't expose details to the client
        error_log("Database error: " . $e->getMessage());
        return null;
    }
}

/**
 * Generate a more intelligent response when no answer is found
 * 
 * @param string $query The user's query
 * @return array Response array with answer and source
 */
function generateIntelligentResponse($query) {
    // Keywords to categorize the query
    $tech_keywords = ['programming', 'code', 'software', 'developer', 'computer', 'app', 'technology', 'website', 'internet'];
    $science_keywords = ['science', 'physics', 'chemistry', 'biology', 'scientific', 'experiment', 'theory', 'research'];
    $math_keywords = ['math', 'mathematics', 'equation', 'formula', 'calculation', 'number', 'algebra', 'geometry'];
    $history_keywords = ['history', 'historical', 'ancient', 'century', 'war', 'civilization', 'king', 'queen', 'empire'];
    $entertainment_keywords = ['movie', 'film', 'music', 'song', 'artist', 'actor', 'actress', 'celebrity', 'entertainment'];
    
    // Create themed responses
    if (containsAny($query, $tech_keywords)) {
        return [
            'response' => "I understand you're asking about " . $query . ". This seems to be a technology-related question. I can provide information about programming languages, software development, computer systems, and other tech topics. Could you specify what exactly you'd like to know?",
            'source' => 'assistant'
        ];
    } elseif (containsAny($query, $science_keywords)) {
        return [
            'response' => "Your question about " . $query . " relates to science. I can help with scientific concepts, theories, and discoveries. Would you like me to explain a specific scientific principle or provide information about a particular field of study?",
            'source' => 'assistant'
        ];
    } elseif (containsAny($query, $math_keywords)) {
        return [
            'response' => "I see you're asking about " . $query . ", which seems to be a mathematics question. I can help with mathematical concepts, formulas, and problems. Could you provide more details about what you'd like to know?",
            'source' => 'assistant'
        ];
    } elseif (containsAny($query, $history_keywords)) {
        return [
            'response' => "Your question about " . $query . " appears to be history-related. I can provide information about historical events, figures, and periods. What specific aspect of history are you interested in?",
            'source' => 'assistant'
        ];
    } elseif (containsAny($query, $entertainment_keywords)) {
        return [
            'response' => "I see you're asking about " . $query . ", which relates to entertainment. I can provide information about movies, music, celebrities, and other entertainment topics. What specific information are you looking for?",
            'source' => 'assistant'
        ];
    } elseif (strlen($query) < 10) {
        // For very short queries
        return [
            'response' => "I noticed your message was quite brief. Could you provide more details about what you'd like to know? I'm here to help with a wide range of topics!",
            'source' => 'assistant'
        ];
    } else {
        // Default response
        return [
            'response' => "Thank you for your question about '" . $query . "'. I don't have specific information on this topic in my current knowledge base. Could you provide more details or perhaps rephrase your question? I'm designed to help with a wide range of topics including technology, science, mathematics, history, and more.",
            'source' => 'assistant'
        ];
    }
}

/**
 * Helper function to check if a string contains any words from an array
 * 
 * @param string $haystack String to search in
 * @param array $needles Array of words to look for
 * @return bool True if any word is found
 */
function containsAny($haystack, $needles) {
    foreach ($needles as $needle) {
        if (strpos($haystack, $needle) !== false) {
            return true;
        }
    }
    return false;
}
?>