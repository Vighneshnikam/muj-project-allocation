<?php
// process_query.php - Backend to process user queries

// Database connection
function connectDB() {
    $servername = "localhost";
    $username = "root"; // Change as needed
    $password = ""; // Change as needed
    $dbname = "project_management_website";
    
    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    return $conn;
}

// Initialize response array
$response = [
    'message' => '',
    'data' => null
];

// Get the user query
$userQuery = isset($_POST['query']) ? trim($_POST['query']) : '';

if (empty($userQuery)) {
    $response['message'] = "I didn't receive a question. Please ask something about projects, faculty, or students.";
    echo json_encode($response);
    exit;
}

// Normalize the query to lowercase for easier matching
$normalizedQuery = strtolower($userQuery);

// Connect to database
$conn = connectDB();

// Process the query based on its content
try {
    // Check for project-related queries
    if (stripos($normalizedQuery, 'project') !== false) {
        if (stripos($normalizedQuery, 'all project') !== false || stripos($normalizedQuery, 'list project') !== false || stripos($normalizedQuery, 'show project') !== false) {
            // Query for all projects
            $sql = "SELECT p_id, pname, pdesc, project_type, project_domain_type, fid FROM project";
            $result = $conn->query($sql);
            
            if ($result->num_rows > 0) {
                $data = [];
                while($row = $result->fetch_assoc()) {
                    $data[] = $row;
                }
                $response['message'] = "Here are all the projects:";
                $response['data'] = $data;
            } else {
                $response['message'] = "No projects found in the database.";
            }
        } 
        elseif (preg_match('/project(?:s)? (?:by|from) faculty (\w+)/i', $normalizedQuery, $matches)) {
            // Projects by specific faculty ID
            $facultyId = $matches[1];
            $sql = "SELECT p_id, pname, pdesc, project_type FROM project WHERE fid = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $facultyId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $data = [];
                while($row = $result->fetch_assoc()) {
                    $data[] = $row;
                }
                $response['message'] = "Here are the projects by faculty ID $facultyId:";
                $response['data'] = $data;
            } else {
                $response['message'] = "No projects found for faculty ID $facultyId.";
            }
        }
        elseif (preg_match('/project(?:s)? in (\w+) domain/i', $normalizedQuery, $matches)) {
            // Projects by domain
            $domain = $matches[1];
            $sql = "SELECT p_id, pname, pdesc, project_type, fid FROM project WHERE project_domain_type LIKE ?";
            $stmt = $conn->prepare($sql);
            $domainParam = "%$domain%";
            $stmt->bind_param("s", $domainParam);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $data = [];
                while($row = $result->fetch_assoc()) {
                    $data[] = $row;
                }
                $response['message'] = "Here are projects in the $domain domain:";
                $response['data'] = $data;
            } else {
                $response['message'] = "No projects found in the $domain domain.";
            }
        }
        elseif (preg_match('/project (\w+)/i', $normalizedQuery, $matches)) {
            // Specific project by ID
            $projectId = $matches[1];
            $sql = "SELECT * FROM project WHERE p_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $projectId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $data = [];
                while($row = $result->fetch_assoc()) {
                    $data[] = $row;
                }
                $response['message'] = "Here is the information for project ID $projectId:";
                $response['data'] = $data;
            } else {
                $response['message'] = "No project found with ID $projectId.";
            }
        }
        elseif (stripos($normalizedQuery, 'my allocated project') !== false || stripos($normalizedQuery, 'my project') !== false) {
            // This would typically use session data to identify the current user
            // For demo purposes, we'll use a placeholder registration number
            $registrationNo = isset($_SESSION['registration_no']) ? $_SESSION['registration_no'] : 'demo_user';
            
            $sql = "SELECT ap.*, p.pname, p.pdesc, p.project_type, p.project_domain_type 
                   FROM allocated_project ap 
                   JOIN project p ON ap.p_id = p.p_id 
                   WHERE ap.registration_no = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $registrationNo);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $data = [];
                while($row = $result->fetch_assoc()) {
                    $data[] = $row;
                }
                $response['message'] = "Here is your allocated project:";
                $response['data'] = $data;
            } else {
                $response['message'] = "You don't have any allocated projects yet.";
            }
        }
        else {
            $response['message'] = "I can help you with projects. Try asking about all projects, projects by faculty, or projects in a specific domain.";
        }
    }
    
    // Faculty-related queries
    elseif (stripos($normalizedQuery, 'faculty') !== false || stripos($normalizedQuery, 'teacher') !== false || stripos($normalizedQuery, 'professor') !== false) {
        if (stripos($normalizedQuery, 'all') !== false || stripos($normalizedQuery, 'list') !== false || stripos($normalizedQuery, 'show') !== false) {
            // Query for all faculty
            $sql = "SELECT fid, fname, email, specialization, designation FROM faculty";
            $result = $conn->query($sql);
            
            if ($result->num_rows > 0) {
                $data = [];
                while($row = $result->fetch_assoc()) {
                    $data[] = $row;
                }
                $response['message'] = "Here are all faculty members:";
                $response['data'] = $data;
            } else {
                $response['message'] = "No faculty found in the database.";
            }
        }
        elseif (preg_match('/faculty (\w+)/i', $normalizedQuery, $matches)) {
            // Specific faculty by ID
            $facultyId = $matches[1];
            $sql = "SELECT fid, fname, email, specialization, designation FROM faculty WHERE fid = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $facultyId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $data = [];
                while($row = $result->fetch_assoc()) {
                    $data[] = $row;
                }
                $response['message'] = "Here is the information for faculty ID $facultyId:";
                $response['data'] = $data;
            } else {
                $response['message'] = "No faculty found with ID $facultyId.";
            }
        }
        else {
            $response['message'] = "I can help you with faculty information. Try asking about all faculty or a specific faculty by ID.";
        }
    }
    
    // Student-related queries
    elseif (stripos($normalizedQuery, 'student') !== false) {
        if (preg_match('/student (\w+)/i', $normalizedQuery, $matches)) {
            // Specific student by registration number
            $regNo = $matches[1];
            $sql = "SELECT registration_no, name, email, section, semester, year FROM student WHERE registration_no = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $regNo);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $data = [];
                while($row = $result->fetch_assoc()) {
                    $data[] = $row;
                }
                $response['message'] = "Here is the information for student registration number $regNo:";
                $response['data'] = $data;
            } else {
                $response['message'] = "No student found with registration number $regNo.";
            }
        }
        elseif (stripos($normalizedQuery, 'semester') !== false && preg_match('/semester (\d+)/i', $normalizedQuery, $matches)) {
            // Students in specific semester
            $semester = $matches[1];
            $sql = "SELECT registration_no, name, section FROM student WHERE semester = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $semester);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $data = [];
                while($row = $result->fetch_assoc()) {
                    $data[] = $row;
                }
                $response['message'] = "Here are the students in semester $semester:";
                $response['data'] = $data;
            } else {
                $response['message'] = "No students found in semester $semester.";
            }
        }
        else {
            $response['message'] = "I can help you with student information. Try asking about a specific student by registration number or students in a specific semester.";
        }
    }
    
    // Notification-related queries
    elseif (stripos($normalizedQuery, 'notification') !== false || stripos($normalizedQuery, 'notice') !== false) {
        if (stripos($normalizedQuery, 'recent') !== false || stripos($normalizedQuery, 'latest') !== false) {
            // Recent notifications
            $sql = "SELECT * FROM notifications ORDER BY datetime DESC LIMIT 5";
            $result = $conn->query($sql);
            
            if ($result->num_rows > 0) {
                $data = [];
                while($row = $result->fetch_assoc()) {
                    $data[] = $row;
                }
                $response['message'] = "Here are the recent notifications:";
                $response['data'] = $data;
            } else {
                $response['message'] = "No recent notifications found.";
            }
        }
        elseif (stripos($normalizedQuery, 'circular') !== false) {
            // Circular notices
            $sql = "SELECT * FROM circular_notices ORDER BY notice_date DESC LIMIT 5";
            $result = $conn->query($sql);
            
            if ($result->num_rows > 0) {
                $data = [];
                while($row = $result->fetch_assoc()) {
                    $data[] = $row;
                }
                $response['message'] = "Here are the recent circular notices:";
                $response['data'] = $data;
            } else {
                $response['message'] = "No circular notices found.";
            }
        }
        else {
            $response['message'] = "I can help you with notifications. Try asking about recent notifications or circular notices.";
        }
    }
    
    // Project marks queries
    elseif (stripos($normalizedQuery, 'mark') !== false || stripos($normalizedQuery, 'grade') !== false || stripos($normalizedQuery, 'score') !== false) {
        if (preg_match('/mark(?:s)? for (?:student|enrollment) (\w+)/i', $normalizedQuery, $matches)) {
            // Marks for specific student
            $studentId = $matches[1];
            $sql = "SELECT * FROM add_project_mark WHERE student_enrollment = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $studentId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $data = [];
                while($row = $result->fetch_assoc()) {
                    $data[] = $row;
                }
                $response['message'] = "Here are the project marks for student enrollment $studentId:";
                $response['data'] = $data;
            } else {
                $response['message'] = "No marks found for student enrollment $studentId.";
            }
        }
        elseif (preg_match('/mark(?:s)? for project (\w+)/i', $normalizedQuery, $matches)) {
            // Marks for specific project
            $projectId = $matches[1];
            $sql = "SELECT * FROM add_project_mark WHERE project_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $projectId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $data = [];
                while($row = $result->fetch_assoc()) {
                    $data[] = $row;
                }
                $response['message'] = "Here are the marks for project ID $projectId:";
                $response['data'] = $data;
            } else {
                $response['message'] = "No marks found for project ID $projectId.";
            }
        }
        else {
            $response['message'] = "I can help you with project marks. Try asking about marks for a specific student enrollment or project ID.";
        }
    }
    
    // Feedback queries
    elseif (stripos($normalizedQuery, 'feedback') !== false) {
        if (stripos($normalizedQuery, 'my') !== false || stripos($normalizedQuery, 'pending') !== false) {
            // This would typically use session data to identify the current user
            $registrationNo = isset($_SESSION['registration_no']) ? $_SESSION['registration_no'] : 'demo_user';
            
            $sql = "SELECT * FROM feedback WHERE registration_no = ? ORDER BY submitted_at DESC";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $registrationNo);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $data = [];
                while($row = $result->fetch_assoc()) {
                    $data[] = $row;
                }
                $response['message'] = "Here is your feedback history:";
                $response['data'] = $data;
            } else {
                $response['message'] = "You haven't submitted any feedback yet.";
            }
        }
        else {
            $response['message'] = "I can help you with feedback information. Try asking about your feedback history.";
        }
    }
    
    // Domain types queries
    elseif (stripos($normalizedQuery, 'domain') !== false) {
        $sql = "SELECT * FROM admin_add_contain";
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            $data = [];
            while($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            $response['message'] = "Here are the available project domains:";
            $response['data'] = $data;
        } else {
            $response['message'] = "No project domains found in the database.";
        }
    }
    
    // Help or general queries
    elseif (stripos($normalizedQuery, 'help') !== false || stripos($normalizedQuery, 'what can you do') !== false) {
        $response['message'] = "I can help you with various aspects of the project management system. You can ask me about:
        
        - Projects (all projects, specific project, projects by faculty)
        - Faculty information
        - Student information
        - Notifications and circulars
        - Project marks and feedback
        - Project domains
        
        Try asking specific questions like 'Show all projects', 'List faculty members', or 'What are my project marks?'";
    }
    
    // If no specific match was found, try a more general approach
    else {
        // Use keywords to determine intent
        $keywords = [
            'project' => ['project', 'task', 'assignment'],
            'faculty' => ['faculty', 'teacher', 'professor', 'instructor'],
            'student' => ['student', 'learner', 'pupil'],
            'notification' => ['notification', 'alert', 'notice', 'announcement'],
            'mark' => ['mark', 'grade', 'score', 'evaluation'],
            'feedback' => ['feedback', 'comment', 'review']
        ];
        
        $detectedCategory = null;
        foreach ($keywords as $category => $terms) {
            foreach ($terms as $term) {
                if (stripos($normalizedQuery, $term) !== false) {
                    $detectedCategory = $category;
                    break 2;  // Break out of both loops once a match is found
                }
            }
        }
        
        if ($detectedCategory) {
            $response['message'] = "It looks like you're asking about $detectedCategory. Can you be more specific? For example:
            - For projects: 'Show all projects' or 'What projects are in the AI domain?'
            - For faculty: 'List all faculty' or 'Show faculty F001'
            - For students: 'Show student S001' or 'List students in semester 3'";
        } else {
            // No specific intent detected
            $response['message'] = "I'm not sure what you're asking about. I can help with projects, faculty, students, notifications, marks, or feedback. Try asking something like 'Show all projects' or 'List faculty members'.";
        }
    }
} catch (Exception $e) {
    // Handle any errors
    $response['message'] = "An error occurred: " . $e->getMessage();
    
    // Log the error (in a production environment)
    // error_log("Query processing error: " . $e->getMessage());
}

// Close the database connection
$conn->close();

// Return the response as JSON
header('Content-Type: application/json');
echo json_encode($response);
?>