<?php
/**
 * Chatbot API Endpoint (PHP Backend Proxy)
 * Project: Student Feedback and Course Evaluation System
 *
 * Securely handles student questions, interfaces with the OpenAI API,
 * protects API keys from frontend exposure, and includes an intelligent
 * offline fallback knowledge engine.
 */

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/chatbot_config.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
    exit;
}

// Read JSON input
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (!isset($data['message']) || trim($data['message']) === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Message is required']);
    exit;
}

$userMessage = trim($data['message']);
$history = isset($data['history']) && is_array($data['history']) ? $data['history'] : [];

// Basic sanitization and length restriction
$userMessage = strip_tags($userMessage);
if (mb_strlen($userMessage) > 500) {
    $userMessage = mb_substr($userMessage, 0, 500);
}

// Fetch non-sensitive institutional context for prompt enrichment
$academicContext = [
    'departments'   => [],
    'total_courses' => 0,
    'total_faculty' => 0
];

try {
    if (isset($pdo)) {
        $deptStmt = $pdo->query("SELECT department_code FROM departments ORDER BY department_code ASC");
        $academicContext['departments'] = $deptStmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

        $academicContext['total_courses'] = (int)$pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn();
        $academicContext['total_faculty'] = (int)$pdo->query("SELECT COUNT(*) FROM faculty")->fetchColumn();
    }
} catch (Exception $e) {
    // Graceful fallback if database connection fails
}

// ---------------------------------------------------------------------
// Built-in Academic Knowledge Engine (Offline Fallback & FAQ matcher)
// ---------------------------------------------------------------------
function getKnowledgeBaseResponse($msg, $context = []) {
    $msgLower = strtolower($msg);

    // 1. Greetings
    if (preg_match('/\b(hi|hello|hey|good morning|good afternoon|greetings)\b/i', $msgLower)) {
        return "Hello! I am your **Campus Assistant**. I can help you understand how to evaluate courses, explain our 5-star rating rubric, guide your registration, or clarify our anonymous feedback guarantees. What would you like to know?";
    }

    // 2. Anonymous feedback & privacy
    if (strpos($msgLower, 'anonym') !== false || strpos($msgLower, 'privacy') !== false || strpos($msgLower, 'identity') !== false || strpos($msgLower, 'safe') !== false) {
        return "🔒 **How Anonymous Feedback Works:**\n\n" .
               "• When submitting your evaluation, toggle the **'Submit as Anonymous Feedback'** switch.\n" .
               "• The system records a separate submission receipt in `feedback_submissions` so you cannot be asked to submit twice, but in the evaluation table (`feedback`), your `student_id` is permanently set to **NULL**.\n" .
               "• In the Admin Dashboard and all appraisal reports, your name and roll number are **completely hidden** and replaced with an 'Anonymous' badge. Teachers and administrators cannot trace your ratings or comments back to you!";
    }

    // 3. 5-star rating rubric & criteria
    if (strpos($msgLower, 'star') !== false || strpos($msgLower, 'rubric') !== false || strpos($msgLower, 'criteri') !== false || strpos($msgLower, 'scale') !== false || strpos($msgLower, 'rating') !== false) {
        return "⭐ **5-Star Rating System & 7 Academic Criteria:**\n\n" .
               "Our evaluation uses a scale from **1 (Poor)** to **5 (Excellent)** across seven distinct criteria:\n\n" .
               "1. **Teaching Effectiveness & Methodology:** Clarity of instruction and structured lectures.\n" .
               "2. **Subject Knowledge & Expertise:** Depth of subject mastery and real-world examples.\n" .
               "3. **Communication Skills:** Clarity and audibility of delivery.\n" .
               "4. **Course Content Quality:** Thorough syllabus coverage and pacing.\n" .
               "5. **Classroom Interaction:** Encouraging discussion and active participation.\n" .
               "6. **Doubt Clarification:** Patience, availability, and helpful answers.\n" .
               "7. **Overall Teaching Satisfaction:** Overall educational value gained.\n\n" .
               "As you click the stars, a live average score updates in real-time at the top of the form!";
    }

    // 4. How to submit feedback / evaluation steps
    if (strpos($msgLower, 'how to submit') !== false || strpos($msgLower, 'submit feedback') !== false || strpos($msgLower, 'evaluate') !== false || strpos($msgLower, 'steps') !== false) {
        return "📝 **Steps to Submit Course Feedback:**\n\n" .
               "1. **Sign In:** Log in with your Roll Number at `auth/student_login.php`.\n" .
               "2. **Open Evaluation:** On your dashboard, find any course labeled **'Pending Evaluation'** and click **'Evaluate'** (or select 'Submit Evaluation' from the top navbar).\n" .
               "3. **Select Course & Faculty:** Pick your course subject; the assigned professor will be automatically loaded.\n" .
               "4. **Provide 1–5 Star Ratings:** Rate each of the 7 academic parameters.\n" .
               "5. **Add Comments (Optional):** Enter constructive suggestions (up to 500 characters).\n" .
               "6. **Toggle Anonymity:** Keep 'Anonymous Feedback' enabled if you wish to decouple your identity.\n" .
               "7. **Submit:** Click 'Submit Course Evaluation' and receive your official digital receipt!";
    }

    // 5. Selecting course and faculty (dynamic cascade)
    if (strpos($msgLower, 'select course') !== false || strpos($msgLower, 'faculty') !== false || strpos($msgLower, 'instructor') !== false || strpos($msgLower, 'teacher') !== false) {
        return "👨‍🏫 **Course & Faculty Selection:**\n\n" .
               "• The system automatically filters courses based on your enrolled **Department** and current **Semester**.\n" .
               "• Once you select a course from the dropdown, our AJAX engine automatically queries and populates the assigned faculty member teaching that course.\n" .
               "• If a subject displays 'Faculty assignment pending', the administrator has not yet mapped an instructor to that course batch.";
    }

    // 6. Registration & Account Creation
    if (strpos($msgLower, 'register') !== false || strpos($msgLower, 'create account') !== false || strpos($msgLower, 'sign up') !== false) {
        return "🎓 **How to Register as a Student:**\n\n" .
               "1. Go to the **Register** page (`auth/student_register.php`).\n" .
               "2. Enter your **Full Name**, unique **Roll Number** (e.g., `2023CSE045`), and college email address.\n" .
               "3. Select your **Department** (e.g., CSE, ECE, IT, MECH) and your current **Semester** (1 to 8).\n" .
               "4. Create a secure password (minimum 6 characters).\n" .
               "5. Click **'Register Student Account'**, then log in with your new credentials!";
    }

    // 7. Login and credentials
    if (strpos($msgLower, 'login') !== false || strpos($msgLower, 'sign in') !== false || strpos($msgLower, 'password') !== false || strpos($msgLower, 'credential') !== false) {
        return "🔑 **Student & Admin Portals:**\n\n" .
               "• **Student Sign In:** Use your unique Roll Number (or College Email) and Password at `auth/student_login.php`.\n" .
               "• **Default Test Student:** Roll Number `2023CSE001`, Password `Student@123`.\n" .
               "• **Administrator Console:** Login at `auth/admin_login.php` with Username `admin`, Password `Admin@123`.\n" .
               "• Passwords are protected with strong bcrypt hashing.";
    }

    // 8. Viewing feedback status & duplicate submission
    if (strpos($msgLower, 'status') !== false || strpos($msgLower, 'duplicate') !== false || strpos($msgLower, 'again') !== false || strpos($msgLower, 'edit') !== false) {
        return "📊 **Submission Status & Multiple Submissions:**\n\n" .
               "• You can check which courses you have completed on your **Student Dashboard** (`student/dashboard.php`).\n" .
               "• Completed courses display a green **'Submitted'** badge with submission date, while pending courses display an orange **'Pending'** badge.\n" .
               "• **Can I submit twice?** No. To guarantee fairness and avoid skewed ratings, each student can submit only once per course/faculty per semester.";
    }

    // 9. Technical assistance / setup check
    if (strpos($msgLower, 'setup') !== false || strpos($msgLower, 'database') !== false || strpos($msgLower, 'error') !== false || strpos($msgLower, 'xampp') !== false) {
        return "🛠️ **System Setup & Diagnostics:**\n\n" .
               "• If database tables are missing or not connected, open `config/setup.php` in your browser.\n" .
               "• Click **'Run One-Click Database Setup'** to auto-create all tables and seed sample data.\n" .
               "• Ensure Apache and MySQL are running in your XAMPP Control Panel.";
    }

    // Default polite response
    return "I can help you with anything related to our **Student Feedback and Course Evaluation System**! You can ask me:\n\n" .
           "• *\"How does anonymous feedback protect my privacy?\"*\n" .
           "• *\"What are the 7 criteria in the 5-star rating system?\"*\n" .
           "• *\"How do I register a new student account?\"*\n" .
           "• *\"How do I select my courses and faculty?\"*\n" .
           "• *\"What are the default test login credentials?\"*";
}

// ---------------------------------------------------------------------
// Primary Response Logic: Live OpenAI API Call with Fallback
// ---------------------------------------------------------------------
$apiKey = defined('OPENAI_API_KEY') ? trim(OPENAI_API_KEY) : '';

if (!empty($apiKey)) {
    // Format conversation history for OpenAI chat format
    $messages = [
        [
            'role' => 'system',
            'content' => getChatbotSystemPrompt($academicContext)
        ]
    ];

    // Include up to 4 recent conversation messages
    $historyCount = count($history);
    $startIndex = max(0, $historyCount - 4);
    for ($i = $startIndex; $i < $historyCount; $i++) {
        if (isset($history[$i]['role']) && isset($history[$i]['content'])) {
            $role = ($history[$i]['role'] === 'user') ? 'user' : 'assistant';
            $messages[] = [
                'role' => $role,
                'content' => strip_tags(mb_substr($history[$i]['content'], 0, 400))
            ];
        }
    }

    // Add current user message
    $messages[] = [
        'role' => 'user',
        'content' => $userMessage
    ];

    $payload = [
        'model'       => OPENAI_MODEL,
        'messages'    => $messages,
        'max_tokens'  => OPENAI_MAX_TOKENS,
        'temperature' => OPENAI_TEMPERATURE
    ];

    // Execute cURL request to OpenAI
    $ch = curl_init(OPENAI_API_ENDPOINT);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 12);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response !== false && $httpCode === 200) {
        $result = json_decode($response, true);
        if (isset($result['choices'][0]['message']['content'])) {
            echo json_encode([
                'success' => true,
                'reply'   => trim($result['choices'][0]['message']['content']),
                'mode'    => 'openai'
            ]);
            exit;
        }
    }

    // If OpenAI API encountered an error (e.g. invalid key, quota, or network drop),
    // fall back smoothly to the Knowledge Engine
    $fallbackReply = getKnowledgeBaseResponse($userMessage, $academicContext);
    echo json_encode([
        'success' => true,
        'reply'   => $fallbackReply,
        'mode'    => 'knowledge_base',
        'note'    => 'Served by Academic Knowledge Engine (OpenAI status: ' . ($httpCode ?: 'offline') . ')'
    ]);
    exit;
}

// When no OpenAI API key is specified, respond using the built-in Academic Knowledge Engine
$reply = getKnowledgeBaseResponse($userMessage, $academicContext);
echo json_encode([
    'success' => true,
    'reply'   => $reply,
    'mode'    => 'knowledge_base'
]);
exit;

