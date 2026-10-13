<?php
session_start();
include '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$lang        = $_SESSION['language'] ?? 'english';
$ai_response = '';
$user_input  = '';
$error       = '';
$db_results  = [];
$search_done = false;

function ask_groq($question, $lang) {
    $api_key = GROQ_API_KEY;
    $url     = 'https://api.groq.com/openai/v1/chat/completions';

    $lang_instruction = match($lang) {
        'kiswahili' => 'Respond in Kiswahili.',
        'maa'       => 'Respond in simple English — the response will be translated to Maa.',
        default     => 'Respond in English.'
    };

    $system_prompt = "You are a health education assistant for rural communities in Samburu County, Kenya.
You ONLY provide health information based on WHO guidelines and Kenya Ministry of Health recommendations.
You do NOT diagnose conditions. You do NOT prescribe medicine.
Keep responses clear, simple, and short — users may have low literacy.
Always recommend seeing a Community Health Worker or visiting a health facility for serious symptoms.
If asked anything outside health topics, politely decline and redirect to health questions.
" . $lang_instruction;

    $data = json_encode([
        'model'       => 'llama3-8b-8192',
        'messages'    => [
            ['role' => 'system', 'content' => $system_prompt],
            ['role' => 'user',   'content' => $question]
        ],
        'max_tokens'  => 400,
        'temperature' => 0.3
    ]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($response, true);
    return $result['choices'][0]['message']['content'] ?? null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['symptom_input'])) {
    $user_input  = trim($_POST['symptom_input']);
    $search_done = true;

    if (empty($user_input)) {
        $error = 'Please describe your symptom or health question.';
    } else {
        $stmt = $conn->prepare("SELECT * FROM symptom_guidance WHERE symptom LIKE ? OR possible_cause LIKE ? ORDER BY urgency DESC");
        $like = '%' . $user_input . '%';
        $stmt->bind_param("ss", $like, $like);
        $stmt->execute();
        $result     = $stmt->get_result();
        $db_results = $result->fetch_all(MYSQLI_ASSOC);

        $ai_response = ask_groq($user_input, $lang);
    }
}

function urgency_style($urgency) {
    return match($urgency) {
        'high'   => ['color' => '#c62828', 'bg' => '#ffebee', 'label' => '🚨 Seek care immediately'],
        'medium' => ['color' => '#e65100', 'bg' => '#fff3e0', 'label' => '⚠️ Visit health facility soon'],
        default  => ['color' => '#2e7d32', 'bg' => '#e8f5e9', 'label' => '✅ Can manage at home'],
    };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Symptom Guidance — Samburu Health</title>
    <link rel="stylesheet" href="/multilingual-health-platform/assets/css/style.css">
    <style>
        .page-banner {
            background: #1b4332;
            padding: 50px 7%;
            color: white;
        }
        .page-banner h1 { font-size: 36px; color: white; margin-bottom: 8px; }
        .page-banner p  { color: #c8e6c9; font-size: 16px; }
        .symptom-section {
            padding: 50px 7%;
            max-width: 900px;
            margin: 0 auto;
        }
        .search-card {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            padding: 32px;
            margin-bottom: 36px;
        }
        .search-card h2 { font-size: 20px; color: #1b4332; margin-bottom: 8px; }
        .search-card > p { color: #78909c; font-size: 14px; margin-bottom: 20px; }
        .input-row { display: flex; gap: 12px; }
        .input-row input {
            flex: 1;
            padding: 13px 16px;
            border: 1.5px solid #ddd;
            border-radius: 8px;
            font-size: 15px;
            color: #263238;
        }
        .input-row input:focus { outline: none; border-color: #2e7d32; }
        .input-row button {
            padding: 13px 24px;
            background: #2e7d32;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
        }
        .input-row button:hover { background: #256628; }
        .quick-symptoms {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 18px;
        }
        .quick-btn {
            padding: 8px 16px;
            border: 1.5px solid #2e7d32;
            border-radius: 20px;
            background: white;
            color: #2e7d32;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .quick-btn:hover { background: #2e7d32; color: white; }
        .disclaimer {
            margin-top: 16px;
            padding: 10px 14px;
            background: #fff8e1;
            border-left: 3px solid #f9a825;
            border-radius: 4px;
            font-size: 13px;
            color: #f57f17;
        }
        .ai-card {
            background: white;
            border: 1.5px solid #2e7d32;
            border-radius: 12px;
            margin-bottom: 28px;
            overflow: hidden;
        }
        .ai-header {
            background: #2e7d32;
            padding: 14px 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .ai-header h3 { color: white; font-size: 15px; }
        .ai-header span {
            background: rgba(255,255,255,0.2);
            color: white;
            font-size: 11px;
            padding: 3px 8px;
            border-radius: 4px;
        }
        .ai-body {
            padding: 22px 24px;
            font-size: 15px;
            color: #37474f;
            line-height: 1.8;
            white-space: pre-wrap;
        }
        .results-label {
            font-size: 13px;
            font-weight: bold;
            color: #78909c;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 16px;
        }
        .symptom-card {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            margin-bottom: 18px;
            overflow: hidden;
        }
        .symptom-card-header {
            padding: 16px 22px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #f0f0f0;
        }
        .symptom-card-header h3 { font-size: 17px; color: #1b4332; }
        .urgency-badge {
            font-size: 12px;
            font-weight: bold;
            padding: 5px 12px;
            border-radius: 20px;
        }
        .symptom-card-body { padding: 18px 22px; }
        .symptom-row { margin-bottom: 14px; }
        .symptom-row-label {
            font-size: 12px;
            font-weight: bold;
            color: #78909c;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        .symptom-row-value { font-size: 14px; color: #37474f; line-height: 1.6; }
        .no-results {
            text-align: center;
            padding: 40px;
            color: #78909c;
            background: white;
            border-radius: 10px;
            border: 1px solid #e0e0e0;
        }
        .lang-switcher-links { display: flex; align-items: center; gap: 6px; }
        .lang-link {
            padding: 6px 10px;
            border: 1.5px solid #ddd;
            border-radius: 6px;
            color: #607d8b;
            font-size: 12px;
            font-weight: bold;
            text-decoration: none;
            transition: all 0.2s;
        }
        .lang-link:hover { border-color: #2e7d32; color: #2e7d32; }
        .lang-active { background: #2e7d32; color: white !important; border-color: #2e7d32; }
        @media (max-width: 768px) {
            .input-row { flex-direction: column; }
            .symptom-card-header { flex-direction: column; align-items: flex-start; gap: 8px; }
        }
    </style>
</head>
<body>

    <header class="navbar">
        <div class="logo">Samburu Health</div>
        <nav>
            <a href="../dashboard/index.php">Home</a>
            <a href="../health/index.php">Health Education</a>
            <a href="../myth/index.php">Myth vs Fact</a>
            <a href="index.php">Symptom Guidance</a>
            <div class="lang-switcher-links">
                <a href="../dashboard/language.php?lang=english&redirect=../symptom/index.php"
                   class="lang-link <?= $lang === 'english'   ? 'lang-active' : '' ?>">🌍 EN</a>
                <a href="../dashboard/language.php?lang=kiswahili&redirect=../symptom/index.php"
                   class="lang-link <?= $lang === 'kiswahili' ? 'lang-active' : '' ?>">🇰🇪 SW</a>
                <a href="../dashboard/language.php?lang=maa&redirect=../symptom/index.php"
                   class="lang-link <?= $lang === 'maa'       ? 'lang-active' : '' ?>">🐄 MAA</a>
            </div>
            <a href="../auth/logout.php" class="nav-login">Logout</a>
        </nav>
    </header>

    <div class="page-banner">
        <h1>🩺 Symptom Guidance</h1>
        <p>Describe your symptoms and get health guidance based on WHO and Kenya Ministry of Health guidelines.</p>
    </div>

    <div class="symptom-section">

        <div class="search-card">
            <h2>What symptoms are you experiencing?</h2>
            <p>Type your symptom or health question below.</p>

            <form method="POST" action="index.php" id="symptom-form">
                <div class="input-row">
                    <input
                        type="text"
                        name="symptom_input"
                        id="symptom-input"
                        placeholder="e.g. My child has fever and vomiting..."
                        value="<?= htmlspecialchars($user_input) ?>"
                        autocomplete="off">
                    <button type="submit">Get Guidance</button>
                </div>
            </form>

            <div class="quick-symptoms">
                <p style="font-size:13px;color:#78909c;width:100%;margin-bottom:4px;">Quick select:</p>
                <button type="button" class="quick-btn" onclick="setInput('Fever')">🌡️ Fever</button>
                <button type="button" class="quick-btn" onclick="setInput('Diarrhoea')">💧 Diarrhoea</button>
                <button type="button" class="quick-btn" onclick="setInput('Cough')">😮‍💨 Cough</button>
                <button type="button" class="quick-btn" onclick="setInput('Headache')">😵 Headache</button>
                <button type="button" class="quick-btn" onclick="setInput('Chest pain')">❤️ Chest Pain</button>
                <button type="button" class="quick-btn" onclick="setInput('Vomiting')">🤢 Vomiting</button>
                <button type="button" class="quick-btn" onclick="setInput('Abdominal pain')">🫃 Abdominal Pain</button>
            </div>

            <div class="disclaimer">
                ⚠️ This tool provides health education only — it does not diagnose conditions.
                Always visit a health facility or contact a Community Health Worker for medical advice.
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($search_done && !$error): ?>

            <?php if ($ai_response): ?>
            <div class="ai-card">
                <div class="ai-header">
                    <h3>🤖 AI Health Assistant</h3>
                    <span>Based on WHO guidelines</span>
                </div>
                <div class="ai-body"><?= htmlspecialchars($ai_response) ?></div>
            </div>
            <?php else: ?>
            <div class="alert alert-error" style="margin-bottom:24px;">
                AI assistant is currently unavailable. Showing database results only.
            </div>
            <?php endif; ?>

            <?php if (!empty($db_results)): ?>
                <p class="results-label">📋 Related guidance from our database</p>
                <?php foreach ($db_results as $entry):
                    $u = urgency_style($entry['urgency']);
                ?>
                    <div class="symptom-card">
                        <div class="symptom-card-header">
                            <h3><?= htmlspecialchars($entry['symptom']) ?></h3>
                            <span class="urgency-badge"
                                style="color:<?= $u['color'] ?>;background:<?= $u['bg'] ?>">
                                <?= $u['label'] ?>
                            </span>
                        </div>
                        <div class="symptom-card-body">
                            <div class="symptom-row">
                                <p class="symptom-row-label">Possible Causes</p>
                                <p class="symptom-row-value"><?= htmlspecialchars($entry['possible_cause']) ?></p>
                            </div>
                            <div class="symptom-row">
                                <p class="symptom-row-label">Self Care at Home</p>
                                <p class="symptom-row-value"><?= htmlspecialchars($entry['self_care']) ?></p>
                            </div>
                            <div class="symptom-row">
                                <p class="symptom-row-label">When to Seek Care</p>
                                <p class="symptom-row-value"><?= htmlspecialchars($entry['when_to_seek_care']) ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-results">
                    <p>No specific database entry found for that symptom.</p>
                    <p>Please refer to the AI response above or visit your nearest health facility.</p>
                </div>
            <?php endif; ?>

        <?php endif; ?>

    </div>

    <footer>
        <p>© 2026 Samburu Health Platform</p>
        <p>Health education and basic telemedicine guidance</p>
    </footer>

    <script>
        function setInput(val) {
            document.getElementById('symptom-input').value = val;
            document.getElementById('symptom-form').submit();
        }
    </script>

</body>
</html>