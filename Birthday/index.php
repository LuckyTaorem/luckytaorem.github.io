<?php
session_start(); // Start a session to remember users.
// --- Generate URLs for Social Media Meta Tags ---
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$domainName = $_SERVER['HTTP_HOST'];
// Construct the base path, removing the script name if it exists
$basePath = dirname($protocol . $domainName . $_SERVER['SCRIPT_NAME']);
$currentPageUrl = $protocol . $domainName . $_SERVER['REQUEST_URI'];
// Construct the absolute URL to the avatar image for the preview
$previewImageUrl = $basePath . '/lucky_birthday.png';
$canonicalUrl = $protocol . $domainName . strtok($_SERVER['REQUEST_URI'], '?');
// --- Configuration ---
$birthdayPersonName = 'Taorem Lucky Singh'; // The person whose birthday it is.
$dataFile = 'wishes.json'; // File path for storing wishes.

// --- Helper Functions ---

// Function to get all wishes from the JSON file
function getWishes($filePath) {
    if (!file_exists($filePath)) {
        file_put_contents($filePath, '[]');
        return [];
    }
    $json = file_get_contents($filePath);
    $data = json_decode($json, true);
    return is_array($data) ? $data : [];
}

// Function to save all wishes to the JSON file
function saveWishes($filePath, $wishes) {
    $json = json_encode($wishes, JSON_PRETTY_PRINT);
    file_put_contents($filePath, $json);
}

// --- API Logic: Handle asynchronous requests from the frontend JavaScript ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['fetch'])) {
    
    header('Content-Type: application/json');

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // --- FIX: Add simple rate-limiting to prevent DoS attacks ---
        if (isset($_SESSION['last_post_time']) && (time() - $_SESSION['last_post_time']) < 10) { // 10-second cooldown
            http_response_code(429); // Too Many Requests
            echo json_encode(['success' => false, 'error' => 'Please wait a moment before sending another wish.']);
            exit;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $allWishes = getWishes($dataFile);

        if (!empty($input['from']) && !empty($input['message'])) {
            $fromName = htmlspecialchars(trim($input['from']));
            $message = htmlspecialchars(trim($input['message']));
            
            // --- FIX: Corrected security logic to prevent name spamming/impersonation ---
            $isAllowed = false;
            if (isset($_SESSION['claimed_name'])) {
                // User already has a claimed name in their session.
                // They can ONLY post using that name.
                if ($_SESSION['claimed_name'] === $fromName) {
                    $isAllowed = true;
                }
            } else {
                // User does not have a claimed name yet.
                // Check if the name they want is already taken by someone else (case-insensitively).
                $nameIsTaken = false;
                foreach ($allWishes as $wish) {
                    if (strcasecmp($wish['from'], $fromName) === 0) {
                        $nameIsTaken = true;
                        break;
                    }
                }
                if (!$nameIsTaken) {
                    $isAllowed = true; // The name is available, allow them to claim it.
                }
            }
            // --- End Security Check ---

            if ($isAllowed) {
                 $newWish = [
                    'to' => $birthdayPersonName,
                    'from' => $fromName,
                    'message' => $message,
                    'timestamp' => time()
                ];
                $allWishes[] = $newWish;
                saveWishes($dataFile, $allWishes);

                // Claim the name in the session if not already set.
                if (!isset($_SESSION['claimed_name'])) {
                    $_SESSION['claimed_name'] = $fromName;
                }
                
                // Set the rate-limiting timestamp
                $_SESSION['last_post_time'] = time();
                
                echo json_encode(['success' => true, 'message' => 'Wish sent!', 'wish' => $newWish]);

            } else {
                // Block the user if they try to use a name claimed by someone else.
                echo json_encode(['success' => false, 'error' => 'This name is already in use by another person.']);
            }

        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid input.']);
        }
    } else { // This block handles GET requests to fetch wishes.
        $allWishes = getWishes($dataFile);
        $filteredWishes = [];
        
        foreach ($allWishes as $wish) {
            if (isset($wish['to']) && $wish['to'] === $birthdayPersonName) {
                $filteredWishes[] = $wish;
            }
        }
        echo json_encode($filteredWishes);
    }
    
    exit;
}

// --- Page Load Logic ---
$wishesForThisPersonOnLoad = [];
$allWishesOnLoad = getWishes($dataFile);
foreach ($allWishesOnLoad as $wish) {
    if (isset($wish['to']) && $wish['to'] === $birthdayPersonName) {
        $wishesForThisPersonOnLoad[] = $wish;
    }
}
// Pass the claimed name from the session to the frontend.
$claimedNameOnLoad = isset($_SESSION['claimed_name']) ? $_SESSION['claimed_name'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- --- Social Media Meta Tags (for sharing) --- -->
    <link rel="icon" type="image/png" href="<?php echo $previewImageUrl; ?>">
    
    <meta property="og:title" content="Happy Birthday <?php echo $birthdayPersonName; ?>!" />
    <meta property="og:description" content="Join in wishing <?php echo $birthdayPersonName; ?> a very happy birthday! Leave your own message on this special page." />
    <meta property="og:image" content="<?php echo $previewImageUrl; ?>" />
    <meta property="og:url" content="<?php echo $currentPageUrl; ?>" />
    <meta property="og:type" content="website" />
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:site" content="taorem_lucky" />
    <meta name="twitter:title" content="Happy Birthday <?php echo $birthdayPersonName; ?>!" />
    <meta name="twitter:description" content="Join in wishing <?php echo $birthdayPersonName; ?> a very happy birthday and leave your message!" />
    <meta name="twitter:image" content="<?php echo $previewImageUrl; ?>" />
    <meta name="description" content="Celebrate <?php echo $birthdayPersonName; ?>'s birthday! Visit this special page to leave a personal birthday wish and see messages from friends and family." />
    <link rel="canonical" href="<?php echo $canonicalUrl; ?>" />
    <title>Happy Birthday!</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&family=Pacifico&display=swap" rel="stylesheet">
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "WebPage",
      "name": "Happy Birthday <?php echo $birthdayPersonName; ?>!",
      "url": "<?php echo $canonicalUrl; ?>",
      "description": "A digital celebration page for friends and family to leave birthday wishes for <?php echo $birthdayPersonName; ?>.",
      "about": {
        "@type": "SocialEvent",
        "name": "Birthday Celebration for <?php echo $birthdayPersonName; ?>",
        "description": "A digital celebration page for friends and family to leave birthday wishes.",
        "performer": {
          "@type": "Person",
          "name": "<?php echo $birthdayPersonName; ?>"
        },
        "image": "<?php echo $previewImageUrl; ?>"
      }
    }
    </script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            overflow-x: hidden; /* Prevents scrollbars from decorations */
            user-select: none;
        }
        .pacifico {
            font-family: 'Pacifico', cursive;
        }
        /* This is for the original falling confetti from JS */
        .confetti {
            position: fixed;
            top: -20px;
            width: 12px;
            height: 20px;
            border-radius: 50%;
            opacity: 0.8;
            animation: fall linear forwards;
            z-index: 0;
        }
        @keyframes fall {
            to {
                transform: translateY(105vh) rotate(720deg);
                opacity: 0;
            }
        }
        /* Custom scrollbar for wishers list */
        #wishers-list::-webkit-scrollbar, #chat-body::-webkit-scrollbar {
            height: 8px;
            width: 8px;
        }
        #wishers-list::-webkit-scrollbar-track, #chat-body::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        #wishers-list::-webkit-scrollbar-thumb, #chat-body::-webkit-scrollbar-thumb {
            background: #f472b6;
            border-radius: 10px;
        }
        #wishers-list::-webkit-scrollbar-thumb:hover, #chat-body::-webkit-scrollbar-thumb:hover {
            background: #ec4899;
        }
        #wishers-list {
            scrollbar-width: thin;
            scrollbar-color: #f472b6 #f1f1f1;
        }
        .wisher-avatar {
            cursor: pointer;
            transition: all 0.2s ease-in-out;
        }
        .wisher-avatar.active {
            transform: scale(1.1);
            border-color: #ec4899; /* pink-500 */
            box-shadow: 0 0 15px rgba(236, 72, 153, 0.5);
        }

        /* Chat container styles */
        #chat-container {
            transition: transform 0.3s ease-in-out, opacity 0.3s ease-in-out;
        }
        
        .message-bubble {
            background-color: #dcf8c6; /* WhatsApp-like green */
            align-self: flex-start; /* Changed to flex-start */
            margin-left: auto;
        }

        /* Blinking Lights Decoration */
        .lights-container {
            position: absolute;
            top: 10px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 15px;
            z-index: 25;
        }
        .light {
            width: 15px;
            height: 15px;
            border-radius: 50%;
            animation: blink 1.8s infinite;
        }
        .light:nth-child(1) { background-color: #ef4444; animation-delay: 0s; }
        .light:nth-child(2) { background-color: #f59e0b; animation-delay: 0.2s; }
        .light:nth-child(3) { background-color: #84cc16; animation-delay: 0.4s; }
        .light:nth-child(4) { background-color: #3b82f6; animation-delay: 0.6s; }
        .light:nth-child(5) { background-color: #a855f7; animation-delay: 0.8s; }
        .light:nth-child(6) { background-color: #ec4899; animation-delay: 1s; }
        .light:nth-child(7) { background-color: #14b8a6; animation-delay: 1.2s; }


        @keyframes blink {
            0%, 100% { opacity: 1; box-shadow: 0 0 8px currentColor; }
            50% { opacity: 0.4; box-shadow: none; }
        }

        /* Birthday Cake Decoration */
        .cake-container {
            z-index: -1;
            position: relative;
            width: 150px;
            height: 160px;
            margin: 20px auto 20px auto;
        }
        #cake {
            display: block;
            position: relative;
            margin: -20em auto 0 -1em;
        }
        .candle {
            background: #d3d3d3ff;
            border-radius: 10px;
            position: absolute;
            top: 55px;
            left: 90px;
            width: 5px;
            height: 35px;
            transform: translateY(-100px);
            -webkit-backface-visibility: hidden;
                    backface-visibility: hidden;
            /* FIX: Hide candle before animation starts */
            opacity: 0;
            -webkit-animation: in 500ms 6s ease-out forwards;
                    animation: in 500ms 6s ease-out forwards;
        }
        #candle2 {
            background: #d3d3d3ff;
            border-radius: 10px;
            position: absolute;
            top: 65px;
            left: 50px;
            width: 5px;
            height: 35px;
            transform: translateY(-100px);
            -webkit-backface-visibility: hidden;
                    backface-visibility: hidden;
            /* FIX: Hide candle before animation starts */
            opacity: 0;
            -webkit-animation: in 500ms 6s ease-out forwards;
                    animation: in 500ms 6s ease-out forwards;
        }
        #candle3 {
            background: #d3d3d3ff;
            border-radius: 10px;
            position: absolute;
            top: 65px;
            left: 130px;
            width: 5px;
            height: 35px;
            transform: translateY(-100px);
            -webkit-backface-visibility: hidden;
                    backface-visibility: hidden;
            /* FIX: Hide candle before animation starts */
            opacity: 0;
            -webkit-animation: in 500ms 6s ease-out forwards;
                    animation: in 500ms 6s ease-out forwards;
        }
        .candle:after,
        .candle:before {
            background: rgba(255, 0, 0, 0.4);
            content: "";
            position: absolute;
            width: 100%;
            height: 2.22222222px;
        }
        .candle:after {
            top: 25%;
            left: 0;
        }
        .candle:before {
            top: 45%;
            left: 0;
            display:none;
        }
        .fire {
            border-radius: 100%;
            position: absolute;
            top: -20px;
            left: 50%;
            margin-left: -5px;
            width: 6.66666667px;
            height: 18px;
        }
        .fire:nth-child(1) {
            -webkit-animation: fire 2s 6.5s infinite;
                    animation: fire 2s 6.5s infinite;
        }
        .fire:nth-child(2) {
            -webkit-animation: fire 1.5s 6.5s infinite;
                    animation: fire 1.5s 6.5s infinite;
        }
        .fire:nth-child(3) {
            -webkit-animation: fire 1s 6.5s infinite;
                    animation: fire 1s 6.5s infinite;
        }
        .fire:nth-child(4) {
            -webkit-animation: fire 0.5s 6.5s infinite;
                    animation: fire 0.5s 6.5s infinite;
        }
        .fire:nth-child(5) {
            -webkit-animation: fire 0.2s 6.5s infinite;
                    animation: fire 0.2s 6.5s infinite;
        }
        @-webkit-keyframes fire {
            0%, 100% {
                background: rgba(254, 248, 97, 0.5);
                box-shadow: 0 0 40px 10px rgba(248, 233, 209, 0.2);
                transform: translateY(0) scale(1);
            }
            50% {
                background: rgba(255, 50, 0, 0.1);
                box-shadow: 0 0 40px 20px rgba(248, 233, 209, 0.2);
                transform: translateY(-20px) scale(0);
            }
        }
        @keyframes fire {
            0%, 100% {
                background: rgba(254, 248, 97, 0.5);
                box-shadow: 0 0 40px 10px rgba(248, 233, 209, 0.2);
                transform: translateY(0) scale(1);
            }
            50% {
                background: rgba(255, 50, 0, 0.1);
                box-shadow: 0 0 40px 20px rgba(248, 233, 209, 0.2);
                transform: translateY(-20px) scale(0);
            }
        }
        @-webkit-keyframes in {
            to {
                transform: translateY(0);
                /* FIX: Make candle visible after animation */
                opacity: 1;
            }
        }
        @keyframes in {
            to {
                transform: translateY(0);
                /* FIX: Make candle visible after animation */
                opacity: 1;
            }
        }
        
        /* Balloons Decoration */
        .balloon-container {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            overflow: hidden;
            z-index: 0;
        }
        .balloon {
            position: absolute;
            width: 60px;
            height: 80px;
            border-radius: 50%;
            animation: float 15s infinite ease-in-out;
        }
        .balloon::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 50%;
            transform: translateX(-50%);
            width: 1px;
            height: 40px;
            background-color: rgba(0,0,0,0.2);
        }
        .balloon1 {
            background-color: rgba(236, 72, 153, 0.7); /* pink-500 */
            left: 10%;
            animation-duration: 20s;
        }
        .balloon2 {
            background-color: rgba(167, 139, 250, 0.7); /* violet-400 */
            left: 85%;
            animation-duration: 18s;
            animation-delay: 3s;
        }
         .balloon3 {
            background-color: rgba(59, 130, 246, 0.7); /* blue-500 */
            left: 40%;
            animation-duration: 22s;
            animation-delay: 7s;
        }

        @keyframes float {
            0% {
                bottom: -100px;
                transform: translateX(0);
            }
            50% {
                transform: translateX(40px) rotate(10deg);
            }
            100% {
                bottom: 110%;
                transform: translateX(-30px) rotate(-10deg);
            }
        }
        
        /* Interactive Avatar Styles */
        #avatar-container {
            cursor: pointer;
            transition: transform 0.2s ease;
            margin-top:36px;
        }
        #avatar-container.jiggle {
            animation: jiggle 0.5s;
        }
        @keyframes jiggle {
            0%, 100% { transform: rotate(0); }
            25% { transform: rotate(-3deg); }
            75% { transform: rotate(3deg); }
        }
        .eye-container {
            position: absolute;
            top: 30%;
            left: 54%;
            transform: translate(-50%, -50%);
            width: 60px;
            display: flex;
            justify-content: space-between;
        }
        .eye {
            width: 25px;
            height: 25px;
            background-color: white;
            border-radius: 50%;
            position: relative;
            overflow: hidden;
            border: 2px solid #6b7280;
            animation: eye-blink 5s infinite;
        }
        .eye:first-child { animation-delay: 0.2s; }
        .pupil {
            width: 12px;
            height: 12px;
            background-color: #1f2937;
            border-radius: 50%;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            transition: transform 0.1s ease-out;
        }
        @keyframes eye-blink {
            0%, 95%, 100% { transform: scaleY(1); }
            97.5% { transform: scaleY(0.1); }
        }
        #birthday-person-name{
            margin:20px 0;
        }

        .birthday-hat-img {
            position: absolute;
            top: -50px; /* Adjust to sit on top of the head */
            left: 50%;
            transform: translateX(-60%) rotate(-15deg); /* Center and tilt */
            width: 120px; /* Adjust size as needed */
            height: 120px;
            z-index: 10;
            pointer-events: none; /* Make sure it doesn't block clicks on the avatar */
        }
        /* --- Animated CSS Party Popper Decorations --- */
        .decoration-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            overflow: hidden;
            z-index: 5;
        }
        .party-popper {
            position: absolute;
            width: 200px;
            height: 250px;
            pointer-events: none;
        }
        .popper-right {
            bottom: -30px;
            right: -20px;
            transform: rotate(-45deg);
        }
        .popper-left {
            bottom: -30px;
            left: -20px;
            transform: rotate(45deg);
        }
        .popper-cone {
            width: 0;
            height: 0;
            border-left: 40px solid transparent;
            border-right: 40px solid transparent;
            border-top: 80px solid #f87171;
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
        }
        .popper-left .popper-cone {
            border-top-color: #60a5fa;
        }
        /* RENAMED to avoid conflict with JS confetti */
        .popper-confetti {
            position: absolute;
            width: 10px;
            height: 10px;
            background-color: var(--color);
            top: 65%;
            left: 50%;
            opacity: 0;
            animation: burst 2.5s ease-out infinite;
            will-change: transform, opacity;
        }
        @keyframes burst {
            0% {
                transform: translate(-50%, 0) rotate(0deg) scale(1);
                opacity: 1;
            }
            100% {
                transform: translate(var(--x), var(--y)) rotate(720deg) scale(0);
                opacity: 0;
            }
        }
        .popper-confetti:nth-child(1) { --color: #f472b6; --x: -80px; --y: -180px; animation-delay: 0.1s; }
        .popper-confetti:nth-child(2) { --color: #fbbf24; --x: 90px;  --y: -160px; animation-delay: 0.2s; }
        .popper-confetti:nth-child(3) { --color: #84cc16; --x: -50px; --y: -200px; animation-delay: 0.3s; }
        .popper-confetti:nth-child(4) { --color: #3b82f6; --x: 40px;  --y: -220px; animation-delay: 0.4s; }
        .popper-confetti:nth-child(5) { --color: #a855f7; --x: -90px; --y: -150px; animation-delay: 0.5s; }
        .popper-confetti:nth-child(6) { --color: #ec4899; --x: 100px; --y: -190px; animation-delay: 0.6s; }
        .popper-confetti:nth-child(7) { --color: #f59e0b; --x: -30px; --y: -170px; animation-delay: 0.7s; }
        .popper-confetti:nth-child(8) { --color: #14b8a6; --x: 60px;  --y: -210px; animation-delay: 0.8s; }
        .popper-confetti:nth-child(9) { --color: #ef4444; --x: -100px; --y: -200px; animation-delay: 0.9s; }
        .popper-confetti:nth-child(10) { --color: #6366f1; --x: 70px; --y: -150px; animation-delay: 1.0s; }
    </style>
</head>
<body class="bg-gradient-to-br from-pink-100 via-rose-100 to-purple-100 text-gray-800 antialiased relative">    
    <div class="decoration-container">
        <div class="party-popper popper-left">
            <div class="popper-cone"></div>
            <!-- RENAMED class to popper-confetti -->
            <div class="popper-confetti"></div>
            <div class="popper-confetti"></div>
            <div class="popper-confetti"></div>
            <div class="popper-confetti"></div>
            <div class="popper-confetti"></div>
            <div class="popper-confetti"></div>
            <div class="popper-confetti"></div>
            <div class="popper-confetti"></div>
            <div class="popper-confetti"></div>
            <div class="popper-confetti"></div>
        </div>
        <div class="party-popper popper-right">
            <div class="popper-cone"></div>
            <!-- RENAMED class to popper-confetti -->
            <div class="popper-confetti"></div>
            <div class="popper-confetti"></div>
            <div class="popper-confetti"></div>
            <div class="popper-confetti"></div>
            <div class="popper-confetti"></div>
            <div class="popper-confetti"></div>
            <div class="popper-confetti"></div>
            <div class="popper-confetti"></div>
            <div class="popper-confetti"></div>
            <div class="popper-confetti"></div>
        </div>
    </div>
    <div class="balloon-container">
        <div class="balloon balloon1"></div>
        <div class="balloon balloon2"></div>
        <div class="balloon balloon3"></div>
    </div>

    <button id="play-pause-btn" class="fixed top-4 right-4 z-50 bg-pink-500 text-white w-12 h-12 rounded-full flex items-center justify-center shadow-lg focus:outline-none hover:bg-pink-600 transition-transform transform hover:scale-110">
        <svg id="play-icon" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <svg id="pause-icon" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
    </button>
    
    <audio id="birthday-song" loop muted>
        <source src="birthday.mp3" type="audio/mpeg">
        Your browser does not support the audio element.
    </audio>

    <div id="confetti-container" class="absolute top-0 left-0 w-full h-full pointer-events-none z-10"></div>

    <div class="container mx-auto p-4 md:p-8 min-h-screen flex flex-col items-center justify-center relative z-20">
        
        <div class="lights-container">
            <div class="light"></div><div class="light"></div><div class="light"></div><div class="light"></div><div class="light"></div><div class="light"></div><div class="light"></div>
        </div>
<div class="relative mx-auto mb-4">
            <img class="birthday-hat-img" src="party-hat.png" alt="Birthday Hat">
            <div id="avatar-container" class="relative w-40 h-40 bg-pink-200 rounded-full flex items-center justify-center border-4 border-white shadow-lg overflow-hidden">
                <img src="lucky.webp" alt="Taorem Lucky Singh" class="w-full h-full object-cover">
                 <div class="eye-container">
                    <div class="eye"><div class="pupil"></div></div>
                    <div class="eye"><div class="pupil"></div></div>
                </div>
            </div>
        </div>

        <header class="text-center mb-1">
            <h1 class="pacifico text-5xl md:text-7xl text-pink-500 drop-shadow-lg">Happy Birthday</h1>
            <h2 id="birthday-person-name" class="text-3xl md:text-4xl font-bold mt-2 text-rose-400 px-2 transition-all"><?php echo $birthdayPersonName; ?>!</h2>
        </header>
        
        <div class="cake-container">
            <div class="candle">
                <div class="fire"></div>
                <div class="fire"></div>
                <div class="fire"></div>
                <div class="fire"></div>
                <div class="fire"></div>
            </div>
            <div class="candle" id="candle2">
                <div class="fire"></div>
                <div class="fire"></div>
                <div class="fire"></div>
                <div class="fire"></div>
                <div class="fire"></div>
            </div>
            <div class="candle" id="candle3">
                <div class="fire"></div>
                <div class="fire"></div>
                <div class="fire"></div>
                <div class="fire"></div>
                <div class="fire"></div>
            </div>
            <svg id="cake" version="1.1" x="0px" y="0px" width="200px" height="500px" viewBox="0 0 200 500" enable-background="new 0 0 200 500" xml:space="preserve">
                <path fill="#a88679" d="M173.667-13.94c-49.298,0-102.782,0-147.334,0c-3.999,0-4-16.002,0-16.002
        c44.697,0,96.586,0,147.334,0C177.667-29.942,177.668-13.94,173.667-13.94z">
        <animate id="bizcocho_3" attributeName="d" calcMode="spline" keySplines="0 0 1 1; 0 0 1 1" begin="relleno_2.end" dur="0.3s" fill="freeze" values="
                                  M173.667-13.94c-49.298,0-102.782,0-147.334,0c-3.999,0-4-16.002,0-16.002
        c44.697,0,96.586,0,147.334,0C177.667-29.942,177.668-13.94,173.667-13.94z
                                  ;
                                  M173.667,411.567c-47.995,12.408-102.955,12.561-147.334,0
        c-3.848-1.089-0.189-16.089,3.661-15.002c44.836,12.66,90.519,12.753,139.427,0.07
        C173.293,395.631,177.541,410.566,173.667,411.567z
                                  ;
                                  M173.667,427.569c-49.795,0-101.101,0-147.334,0c-3.999,0-4-16.002,0-16.002
        c46.385,0,97.539,0,147.334,0C177.668,411.567,177.667,427.569,173.667,427.569z
                                  " />
    </path>
    <path fill="#8b6a60" d="M100-178.521c1.858,0,3.364,1.506,3.364,3.363c0,0,0,33.17,0,44.227
        c0,19.144,0,57.431,0,76.574c0,10.152,0,40.607,0,40.607c0,1.858-1.506,3.364-3.364,3.364l0,0c-1.858,0-3.364-1.506-3.364-3.364c0,0,0-30.455,0-40.607c0-19.144,0-57.432,0-76.575c0-11.057,0-44.226,0-44.226C96.636-177.015,98.142-178.521,100-178.521
        L100-178.521z">
        <animate id="relleno_2" attributeName="d" calcMode="spline" keySplines="0 0 1 1; 0 0 1 1; 0 0 0.58 1" begin="bizcocho_2.end" dur="0.5s" fill="freeze" values="
                                  M100-178.521c1.858,0,3.364,1.506,3.364,3.363c0,0,0,33.17,0,44.227
        c0,19.144,0,57.431,0,76.574c0,10.152,0,40.607,0,40.607c0,1.858-1.506,3.364-3.364,3.364l0,0c-1.858,0-3.364-1.506-3.364-3.364c0,0,0-30.455,0-40.607c0-19.144,0-57.432,0-76.575c0-11.057,0-44.226,0-44.226C96.636-177.015,98.142-178.521,100-178.521
        L100-178.521z
                                  ;
                                  M100,267.257c1.858,0,3.364,1.506,3.364,3.363c0,0,0,33.17,0,44.227
        c0,19.143,0,57.43,0,76.574c0,10.151,0,40.606,0,40.606c0,1.858-1.506,3.364-3.364,3.364l0,0c-1.858,0-3.364-1.506-3.364-3.364
        c0,0,0-30.455,0-40.606c0-19.145,0-57.432,0-76.576c0-11.057,0-44.225,0-44.225C96.636,268.763,98.142,267.257,100,267.257
        L100,267.257z
                                  ;
                                  M93.928,405.433c-0.655,6.444-0.102,9.067,2.957,11.798c0,0,8.083,5.571,16.828,3.503
        c18.629-4.406,43.813,6.194,50.792,7.791c14.75,3.375,9.162,6.867,9.162,6.867c-2.412,2.258-58.328,0-73.667,0l0,0
        c-1.858,0-69.995,2.133-73.667,0c0,0-3.337-2.439,6.172-5.992c11.375-4.25,52.875,8.822,47.139-9.442
        c-6.333-20.167,5.226-21.514,5.226-21.514c3.435-0.915,12.78-6.663,10.923-0.546L93.928,405.433z
                                  ;
                                  M102.242,427.569c5.348,0,14.079,0,17.462,0c0,0,17.026,0,27.504,0
        c19.143,0,20.39-3.797,26.459,0c3,1.877,0,7.823,0,7.823c-2.412,2.258-58.328,0-73.667,0l0,0c-1.858,0-67.187,0-73.667,0
        c0,0-4.125-4.983,0-7.823c5.201-3.58,16.085,0,23.725,0c8.841,0,20.762,0,20.762,0c3.686,0,8.597,0,19.511,0H102.242z
                                  " />
    </path>
    <path fill="#a88679" d="M173.667-15.929c-46.512,0-105.486,0-147.334,0c-3.999,0-4-16.002,0-16.002
        c43.566,0,97.96,0,147.334,0C177.667-31.931,177.666-15.929,173.667-15.929z">
        <animate id="bizcocho_2" attributeName="d" calcMode="spline" keySplines="0 0 1 1; 0 0 1 1; 0.25 0 0.58 1" begin="relleno_1.end" dur="0.5s" fill="freeze" values="
                                  M173.667-15.929c-46.512,0-105.486,0-147.334,0c-3.999,0-4-16.002,0-16.002
        c43.566,0,97.96,0,147.334,0C177.667-31.931,177.666-15.929,173.667-15.929z
                                  ;
                                  M173.434,445.393c-47.269,8.001-105.245,8.001-147.334,0c-3.929-0.747-0.692-16.543,3.243-15.824
        c43.828,8.001,92.165,8.001,140.739,0C174.029,428.918,177.377,444.726,173.434,445.393z
                                  ;
                                  M173.667,449.514c-47.576-5.454-102.799-5.744-147.333,0c-3.966,0.512-3.938-15.297,0-16.002
        c43.683-7.823,97.646-8.026,147.333,0C177.616,434.15,177.642,449.969,173.667,449.514z
                                  ;
                                  M173.667,451.394c-49.298,0-102.782,0-147.334,0c-3.999,0-4-16.002,0-16.002
        c44.697,0,96.586,0,147.334,0C177.667,435.392,177.668,451.394,173.667,451.394z
                                  " />
    </path>
    <path fill="#8b6a60" d="M101.368-73.685c0,12.164,0,15.18,0,28.519c0,22.702,0-13.661,0,8.304c0,14.48,0,18.233,0,30.512
        c0,1.753-2.958,1.847-2.958,0c0-12.68,0-16.277,0-30.401c0-21.983,0,11.66,0-8.305c0-13.027,0-15.992,0-28.628
        C98.411-75.883,101.368-75.592,101.368-73.685z">
        <animate id="relleno_1" attributeName="d" calcMode="spline" keySplines="0 0 1 1; 0 0 1 1; 0 0 0.6 1" begin="bizcocho_1.end" dur="0.5s" fill="freeze" values="
                                  M101.368-73.685c0,12.164,0,15.18,0,28.519c0,22.702,0-13.661,0,8.304c0,14.48,0,18.233,0,30.512
        c0,1.753-2.958,1.847-2.958,0c0-12.68,0-16.277,0-30.401c0-21.983,0,11.66,0-8.305c0-13.027,0-15.992,0-28.628
        C98.411-75.883,101.368-75.592,101.368-73.685z
                                  ;
                                  M101.368,350.885c0,12.164,0,65.18,0,78.518c0,22.703,0-33.66,0-11.695c0,14.48,0,28.232,0,40.512
        c0,1.753-2.958,1.847-2.958,0c0-12.68,0-26.277,0-40.402c0-21.982,0,31.66,0,11.695c0-13.027,0-65.992,0-78.627
        C98.411,348.686,101.368,348.977,101.368,350.885z
                                  ;
                                  M128.38,447.567c37.626,6.312,39.303,13.658,26.833,12.833c-22.653-1.499-13.636-0.831-23.302-0.831
        c-14.48,0-17.884,0-30.163,0c-2.087,0-2.068,0-3.915,0c-13.333,0-8.963,0-23.088,0c-11.668,0-14.062,5.995-27.532,1.164
        c-12.629-4.529,38.667-3.167,46.833-17.333C100.077,432.94,105.546,443.736,128.38,447.567z
                                  ;
                                  M173.667,451.394c2.875,0,2.997,9.257,0,9.131c-22.662-0.956-32.09-0.956-41.756-0.956
        c-14.48,0-17.884,0-30.163,0c-2.087,0-2.068,0-3.915,0c-13.333,0-8.963,0-23.088,0c-11.668,0-34.99-0.294-48.412,1.831
        c-4.109,0.65-3.01-10.006,0-10.006C37.129,451.394,149.379,451.394,173.667,451.394z
                                  " />
    </path>
    <path fill="#a88679" d="M173.667,21.571c-33.174,0-111.467,0-147.334,0c-4,0-4-16.002,0-16.002c39.836,0,105.982,0,147.334,0
        C177.668,5.569,177.667,21.571,173.667,21.571z">
        <animate id="bizcocho_1" attributeName="d" calcMode="spline" keySplines="0 0 1 1; 0 0 1 1; 0 0 1 1; 0.25 0 1 1; 0 0 1 1; 0.25 0 0.6 1" begin="2s" dur="0.8s" fill="freeze" values="
                                  M173.667,21.571c-33.174,0-111.467,0-147.334,0c-4,0-4-16.002,0-16.002c39.836,0,105.982,0,147.334,0
        C177.668,5.569,177.667,21.571,173.667,21.571z
                                  ;
                                  M173.667,459.569c-33.197,16.002-110.782,16.002-147.334,0c-3.664-1.604,1.614-15.617,5.337-14.153
        c40.702,16.002,94.289,16.104,136.505,0.103C171.917,444.1,177.271,457.832,173.667,459.569z
                                  ;
                                  M171.817,475.571c-39.361-3.001-105.438-2.571-143.556,0c-3.991,0.27-7.377-14.736-3.387-15.014
        c41.553-2.888,104.421-3.121,150.51-0.233C179.378,460.574,175.806,475.875,171.817,475.571z
                                  ;
                                  M171.817,459.564c-38.8-12.188-104.504-13.762-143.556,0c-3.772,1.329-7.961-12.604-4.178-13.905
        c40.864-14.064,105.114-15.52,151.918-0.973C179.822,445.874,175.634,460.762,171.817,459.564z
                                  ;
                                  M173.667,475.571c-46.376-5.005-105.924-4.003-147.334,0c-3.981,0.385-3.479-15.421,0.479-16.002
        c43.087-6.327,97.705-7.083,146.855,0.438C177.621,460.613,177.644,476,173.667,475.571z
                                  ;
                                  M173.667,474.117c-46.376,1.866-105.638,2.01-147.334,0c-3.995-0.192-3.52-16.144,0.479-16.002
        c43.794,1.55,96.341,1.541,145.723,0C176.532,457.99,177.663,473.956,173.667,474.117z
                                  ;
                                  M173.667,475.571c-46.512,0-105.486,0-147.334,0c-3.999,0-4-16.002,0-16.002c43.566,0,97.96,0,147.334,0
        C177.667,459.569,177.666,475.571,173.667,475.571z
                                  " />
    </path>
    <path fill="#EC4899" d="M104.812,113.216c0,3.119-2.164,5.67-4.812,5.67c-2.646,0-4.812-2.551-4.812-5.67c0-5.594,0-16.782,0-22.375
    c0-5.143,0-15.427,0-20.568c0-7.333,0-21.998,0-29.33c0-5.523,0-16.569,0-22.092c0-3.295,0-9.885,0-13.181
    C95.188,2.551,97.353,0,100,0c2.648,0,4.812,2.551,4.812,5.669c0,3.248,0,9.743,0,12.991c0,5.428,0,16.284,0,21.711
    c0,7.618,0,22.854,0,30.472c0,4.952,0,14.854,0,19.807C104.812,96.292,104.812,107.576,104.812,113.216z">
        <animate id="crema" attributeName="d" calcMode="spline" keySplines="0 0 1 1; 0 0 1 1; 0 0 1 1; 0.25 0 1 1; 0 0 1 1; 0 0 0.58 1" begin="bizcocho_3.end" dur="2s" fill="freeze" values="
                                  M104.812,113.216c0,3.119-2.164,5.67-4.812,5.67c-2.646,0-4.812-2.551-4.812-5.67c0-5.594,0-16.782,0-22.375
    c0-5.143,0-15.427,0-20.568c0-7.333,0-21.998,0-29.33c0-5.523,0-16.569,0-22.092c0-3.295,0-9.885,0-13.181
    C95.188,2.551,97.353,0,100,0c2.648,0,4.812,2.551,4.812,5.669c0,3.248,0,9.743,0,12.991c0,5.428,0,16.284,0,21.711
    c0,7.618,0,22.854,0,30.472c0,4.952,0,14.854,0,19.807C104.812,96.292,104.812,107.576,104.812,113.216z
                                  ;
                                  M104.812,405.897c0,3.119-2.164,5.67-4.812,5.67c-2.646,0-4.812-2.551-4.812-5.67c0-5.594,0-16.782,0-22.376
    c0-5.143,0-15.426,0-20.568c0-7.332,0-21.997,0-29.33c0-5.522,0-16.568,0-22.092c0-3.295,0-9.885,0-13.181
    c0-3.118,2.165-5.669,4.812-5.669c2.648,0,4.812,2.551,4.812,5.669c0,3.247,0,9.743,0,12.991c0,5.428,0,16.283,0,21.711
    c0,7.618,0,22.854,0,30.473c0,4.951,0,14.854,0,19.807C104.812,388.972,104.812,400.256,104.812,405.897z
                                  ;
                                  M111.873,411.567c-3.119,0-9.226,0-11.874,0c-2.646,0-7.748,0-10.867,0c-7.086,0-12.698,0-18.292,0
    c-6.592,0-12.871,7.371-19.166,3.008c-10.043-6.961-7.776-10.169,2.991-17.745c12.61-8.873,27.713,1.994,25.919-7.531
    c-2.589-13.742,11.008-14.513,11.365-17.789c0.441-4.051,4.235-11.107,8.051-8.175c3.113,2.393,1.007,8.008,0,13.159
    c-1.871,9.569,8.058,2.113,9.494,14.155c2.592,21.732,21.184-0.675,29.309,7.976c5.216,5.553,18.413,5.552,15.426,12.942
    c-3.131,7.745-15.825-4.369-23.8,2.903C126.261,418.271,118.301,411.567,111.873,411.567z
                                  ;
                                  M111.873,411.567c-3.119,0-9.226,0-11.874,0c-2.646,0-9.734,4.069-12.853,4.069
    c-7.086,0-10.712-4.069-16.306-4.069c-6.592,0-12.12,6.013-19.166,3.008c-7.053-3.008-7.458,2.026-18.659,1.165
    c-6.832-0.525-7.522-3.034-7.533-6.265c-0.037-10.336,22.073-2.452,36.613-2.628c10.234-0.124,19.856-1.439,37.905-2.102
    c16.642-0.61,32.699,1.552,46.009,1.927c12.438,0.351,29.663-8.99,31.532,3.315c0.773,5.093-5.605,3.342-11.211,9.579
    c-5.093,5.667-7.59-4.605-12.965-3.832c-8.269,1.189-14.962-8.537-22.937-1.265C126.261,418.271,118.301,411.567,111.873,411.567z
                                  ;
                                  M110.946,413.652c-2.904-1.137-8.405-2.748-12.446-0.97c-6.099,2.685-7.273,10.358-13.253,8.242
    c-7.843-2.775-8.953-5.008-14.546-5.01c-24.653-0.011-4.849,26.507-18.264,26.507c-12.377,0,5.791-33.537-19.422-26.682
    c-7.703,2.095-9.806-0.942-9.817-4.173c-0.037-10.336,24.357-4.544,38.897-4.72c10.234-0.124,19.856-1.439,37.905-2.102
    c16.642-0.61,32.699,1.552,46.009,1.927c12.438,0.351,28.973-8.865,31.532,3.315c1.449,6.896,0.318,15.624-3.874,15.624
    c-7.619,0-1.788-15.192-19.243-7.111c-7.581,3.51-15.963-9.738-26.669,1.066C120.644,426.744,118.381,416.561,110.946,413.652z
                                  ;
                                  M111.547,413.9c-2.969-0.956-8.775-0.949-13.167-0.5c-14.667,1.5-8.325,16.508-14.667,16.666
    c-6.667,0.166-0.167-13.5-13.013-14.151c-30.471-1.545-5.572,46.651-18.987,46.651c-12.377,0,10.333-50.166-18.667-44.5
    c-7.835,1.531-9.537-1.417-9.548-4.647c-0.037-10.336,23.675-5.177,38.215-5.353c10.234-0.124,20.618-1.671,38.667-2.333
    c16.642-0.61,32.023,1.458,45.333,1.833c12.438,0.351,33.819-8.431,33.199,4.001c-0.532,10.666,0.414,26.166-5.245,25.833
    c-7.606-0.447-2.954-31.5-19.243-18.899c-7.985,6.177-17.658-5.969-27.377,5.732C118.88,434.066,121.38,417.067,111.547,413.9z
                                  ;
                                  M111.547,415.233c-6.667-0.834-9.667,4.667-13.833,3.333c-19.649-6.291-8.158,22.176-14.5,22.334
    c-6.667,0.166,2.833-18-13.333-22.167c-29.544-7.615-9.667,43.833-20.167,43.833c-10.333,0,8.004-55.006-16.833-39
    c-7.5,4.833-9.508-3.78-9.299-7.004c0.799-12.329,23.592-7.153,38.132-7.329c10.234-0.124,20.238-1.505,38.287-2.167
    c16.642-0.61,32.903,1.125,46.213,1.5c12.438,0.351,35.058-5.579,31.863,6.451c-5.532,20.833,1.25,28.216-4.409,27.883
    c-7.606-0.447-6.058-37.895-20.62-23.333c-10.167,10.166-15.972-0.747-25,12C119.547,443.568,121.798,416.515,111.547,415.233z
                                  " />
    </path>
                <rect x="10" y="475.571" fill="#EC4899" width="180" height="4" />
            </svg>
        </div>

        <div id="wishers-section" class="w-full max-w-4xl mb-6">
            <h3 class="text-lg font-bold text-center mb-3 text-pink-500">Wishes from your friends!</h3>
            <div id="wishers-list" class="grid grid-cols-5 sm:grid-cols-10 gap-x-4 gap-y-8 bg-white/50 backdrop-blur-sm p-4 rounded-2xl shadow-inner overflow-y-auto max-h-[24rem]">
                 <p id="wishes-placeholder" class="text-center text-gray-500 p-4 hidden col-span-full">Be the first to leave a wish!</p>
            </div>
        </div>


        <div class="w-full max-w-lg mx-auto flex flex-col items-center gap-8">
            <div class="bg-white/70 backdrop-blur-sm p-6 rounded-2xl shadow-lg border border-pink-100 w-full">
                <h3 class="text-2xl font-bold text-center mb-4 text-pink-500">Leave a Wish!</h3>
                <form id="wish-form" class="space-y-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Your Name</label>
                        <input type="text" id="name" name="name" required
                            class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-pink-500 focus:border-pink-500 sm:text-sm">
                    </div>
                    <div>
                        <label for="message" class="block text-sm font-medium text-gray-700">Your Message</label>
                        <textarea id="message" name="message" rows="4" required
                            class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-pink-500 focus:border-pink-500 sm:text-sm"></textarea>
                    </div>
                    <div>
                        <button type="submit"
                            class="w-full flex justify-center py-3 px-4 border border-transparent rounded-full shadow-lg text-sm font-medium text-white bg-pink-500 hover:bg-pink-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-pink-500 transition-transform transform hover:scale-105">
                            Send Your Wish!
                        </button>
                    </div>
                </form>
                 <div id="form-feedback" class="text-center mt-4 font-medium"></div>
            </div>
        </div>
         <footer class="text-center mt-8 text-gray-500 text-sm">
            <p>Made By Taorem Lucky Singh</p>
        </footer>
    </div>

    <div id="chat-container" class="fixed bottom-4 right-4 w-full max-w-sm h-3/5 bg-white rounded-2xl shadow-2xl flex flex-col z-50 transform translate-y-full opacity-0">
        <div id="chat-header" class="flex items-center justify-between p-3 bg-pink-500 text-white rounded-t-2xl">
            <h4 id="chat-header-name" class="font-bold"></h4>
            <button id="close-chat-btn" class="text-2xl leading-none">&times;</button>
        </div>
        <div id="chat-body" class="flex-grow p-4 overflow-y-auto flex flex-col gap-2">
            </div>
    </div>


<div id="poem-container" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-gradient-to-br from-indigo-50 to-purple-100 w-full max-w-lg rounded-2xl shadow-2xl border-4 border-white p-8 text-center relative overflow-hidden">
        <h2 class="text-2xl font-bold text-gray-700">A Poem Just For You...</h2>
        <p id="poem-wisher-name" class="text-5xl font-bold pacifico text-purple-600 my-4"></p>
        
        <div id="poem-text" class="text-gray-800 text-lg my-6 whitespace-pre-wrap font-serif">
            </div>

        <div class="flex justify-center gap-4">
            <button id="read-poem-btn" class="bg-purple-600 text-white py-3 px-6 rounded-full hover:bg-purple-700 font-bold flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" /></svg>
                Read Aloud
            </button>
            <button id="stop-poem-btn" class="bg-gray-500 text-white py-3 px-6 rounded-full hover:bg-gray-600 font-bold flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8 7a1 1 0 00-1 1v4a1 1 0 001 1h4a1 1 0 001-1V8a1 1 0 00-1-1H8z" clip-rule="evenodd" /></svg>
                Stop
            </button>
        </div>
        <button id="close-poem-btn" class="mt-8 text-sm text-gray-500 hover:text-gray-800">Close</button>
    </div>
</div>

    <script type="module">
        // --- DOM Elements ---
        const wishForm = document.getElementById('wish-form');
        const formFeedback = document.getElementById('form-feedback');
        const confettiContainer = document.getElementById('confetti-container');
        const nameInput = document.getElementById('name');
        const wishersList = document.getElementById('wishers-list');
        const wishesPlaceholder = document.getElementById('wishes-placeholder');
        const chatContainer = document.getElementById('chat-container');
        const chatHeaderName = document.getElementById('chat-header-name');
        const chatBody = document.getElementById('chat-body');
        const closeChatBtn = document.getElementById('close-chat-btn');
        let activeChatWisher = null;
        
        // --- Audio Elements ---
        const playPauseBtn = document.getElementById('play-pause-btn');
        const birthdaySong = document.getElementById('birthday-song');
        const playIcon = document.getElementById('play-icon');
        const pauseIcon = document.getElementById('pause-icon');

        // --- Avatar Elements ---
        const avatarContainer = document.getElementById('avatar-container');
        const pupils = document.querySelectorAll('.pupil');

        // --- Initial Data (Bootstrapped from PHP) ---
        let currentWishes = <?php echo json_encode($wishesForThisPersonOnLoad); ?>;
        let claimedName = '<?php echo $claimedNameOnLoad; ?>';

        // --- Funny Thank You Messages ---
        const thankYouMessages = [
            "Thanks! Your wish has been officially added to the birthday archives.",
            "Awesome! Your wish is now floating in the birthday cosmos.",
            "Got it! Your message has been delivered by a party pigeon.",
            "Success! You just made the birthday person 10% happier.",
            "Thank you! Your wish has been recorded on a golden scroll.",
            "Great! We've received your wish. The birthday spirits are pleased.",
            "Perfect! Your kind words are now part of the birthday legend."
        ];

        // --- URL Parsing ---
        function getUrlParams() {
            const params = new URLSearchParams(window.location.search);
            return { from: params.get('from') };
        }

        // --- Helper Functions ---
        function formatTimestamp(unixTimestamp) {
            if (!unixTimestamp) return '';
            const date = new Date(unixTimestamp * 1000);
            const options = { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit', hour12: true };
            return date.toLocaleString('en-IN', options);
        }
        
        // --- UI Logic ---
        function displayWishers(wishes) {
            // Sort oldest to newest to find the first wish of each person
            const sortedWishes = [...wishes].sort((a, b) => (a.timestamp || 0) - (b.timestamp || 0));
            
            // Get unique wishers, preserving the order of their first wish
            const uniqueWishersInOrder = Array.from(new Map(sortedWishes.map(w => [w.from, w])).values());

            wishersList.innerHTML = '';
            
            if (uniqueWishersInOrder.length > 0) {
                wishesPlaceholder.classList.add('hidden');
                
                uniqueWishersInOrder.forEach((wish, index) => {
                    const avatarWrapper = document.createElement('div');
                    avatarWrapper.className = 'relative flex flex-col items-center justify-center';

                    const avatar = document.createElement('div');
                    let avatarBorder = 'border-transparent';
                    if (wish.from === claimedName) {
                        avatarBorder = 'border-blue-500'; // Highlight border for the current user
                    }
                    avatar.className = `wisher-avatar w-14 h-14 bg-pink-200 rounded-full flex items-center justify-center font-bold text-pink-600 text-xl border-4 ${avatarBorder}`;
                    avatar.textContent = wish.from.charAt(0).toUpperCase();
                    avatar.title = wish.from;
                    avatar.dataset.wisherName = wish.from;
                    
                    avatar.addEventListener('click', () => {
                        openChatFor(wish.from);
                    });

                    const serialNumber = document.createElement('div');
                    serialNumber.className = 'absolute -top-2 -right-2 bg-rose-500 text-white text-xs font-bold w-6 h-6 rounded-full flex items-center justify-center border-2 border-white';
                    serialNumber.textContent = index + 1;
                    
                    avatarWrapper.appendChild(avatar);
                    avatarWrapper.appendChild(serialNumber);

                    if (wish.from === claimedName) {
                        const youLabel = document.createElement('div');
                        youLabel.className = 'mt-1 text-blue-600 text-xs font-bold';
                        youLabel.textContent = 'You';
                        avatarWrapper.appendChild(youLabel);
                    }
                    
                    wishersList.appendChild(avatarWrapper);
                });
            } else {
                 wishesPlaceholder.classList.remove('hidden');
            }
        }

        function openChatFor(wisherName) {
            document.querySelectorAll('.wisher-avatar').forEach(a => a.classList.remove('active'));
            // Since the avatar is now wrapped, we need to be more specific
            const avatarDiv = document.querySelector(`.wisher-avatar[data-wisher-name="${wisherName}"]`);
            if(avatarDiv) avatarDiv.classList.add('active');

            activeChatWisher = wisherName;
            chatHeaderName.textContent = `Messages from ${wisherName}`;
            
            const messagesFromWisher = currentWishes
                .filter(w => w.from === wisherName)
                .sort((a, b) => (a.timestamp || 0) - (b.timestamp || 0)); // Oldest first
            
            chatBody.innerHTML = '';
            messagesFromWisher.forEach(msg => {
                const bubble = document.createElement('div');
                bubble.className = 'message-bubble p-2 rounded-lg max-w-xs break-words flex flex-col';
                
                const messageText = document.createElement('span');
                messageText.textContent = msg.message;

                const metadata = document.createElement('div');
                metadata.className = 'flex items-center self-end text-xs text-gray-500 mt-1';
                
                const timestamp = document.createElement('span');
                timestamp.textContent = formatTimestamp(msg.timestamp);

                const ticks = document.createElement('span');
                ticks.innerHTML = `<svg viewBox="0 0 16 15" class="h-4 w-4 ml-1 text-blue-500" fill="currentColor"><path d="M15.01 3.316l-4.93 4.93-1.42-1.42L15.01 3.316zm-.454-1.416l-6.36 6.36-2.12-2.12-1.42 1.42 3.54 3.54 7.78-7.78-1.42-1.42zM4.01 13.316l-2.12-2.12-1.42 1.42 3.54 3.54 2.82-2.82-1.42-1.42-1.42 1.42z"></path></svg>`;
                
                metadata.appendChild(timestamp);
                metadata.appendChild(ticks);
                
                bubble.appendChild(messageText);
                bubble.appendChild(metadata);

                chatBody.appendChild(bubble);
            });

            // Scroll to the bottom
            chatBody.scrollTop = chatBody.scrollHeight;
            
            // Show container
            chatContainer.classList.remove('translate-y-full', 'opacity-0');
        }

        function closeChat() {
            document.querySelectorAll('.wisher-avatar').forEach(a => a.classList.remove('active'));
            chatContainer.classList.add('translate-y-full', 'opacity-0');
            activeChatWisher = null;
        }

        closeChatBtn.addEventListener('click', closeChat);

        // --- Data Fetching and Submission ---

        async function fetchLatestWishes() {
            try {
                const response = await fetch(`?fetch=true`);
                const wishes = await response.json();
                if (JSON.stringify(wishes) !== JSON.stringify(currentWishes)) {
                    currentWishes = wishes;
                    displayWishers(currentWishes);
                    // If a chat is open for a user who just sent a message, refresh it
                    if (activeChatWisher) {
                        openChatFor(activeChatWisher);
                    }
                }
            } catch (error) {
                console.error("Could not fetch wishes:", error);
            }
        }

        // --- Add these variables at the top of your script ---
const poemContainer = document.getElementById('poem-container');
const poemWisherName = document.getElementById('poem-wisher-name');
const poemTextDiv = document.getElementById('poem-text');
const readPoemBtn = document.getElementById('read-poem-btn');
const stopPoemBtn = document.getElementById('stop-poem-btn');
const closePoemBtn = document.getElementById('close-poem-btn');

// --- Word Banks for Poem Generation ---
const adjectives = ['great', 'awesome', 'superb', 'wonderful', 'bright', 'special', 'true', 'kind'];
const nouns = ['friend', 'smile', 'laugh', 'cheer', 'light', 'star', 'joy', 'fun'];
const verbs = ['shines', 'brings', 'shares', 'lights up', 'makes', 'inspires', 'creates'];

// --- Poem Templates ---
const poemTemplates = [
    `Hey [NAME]! Your [ADJECTIVE] wish just arrived,\nAnd my happy birthday mood has thrived!\nYour kindness [VERB] like a [NOUN],\nMaking my whole day feel brand new. Thank you!`,
    `A [ADJECTIVE] wish from [NAME], what a treat!\nYour kind words made my day complete.\nA friend like you is a [NOUN] so true,\nMy birthday cheer [VERB] all thanks to you!`,
    `My phone just buzzed, and guess what I see?\nAn [ADJECTIVE] wish, [NAME], from you to me!\nYour friendship [VERB] like a shining [NOUN],\nThanks for being the best friend around!`
];

// --- Function to Generate a Poem ---
function generatePoem(name) {
    // Pick a random template and random words
    let template = poemTemplates[Math.floor(Math.random() * poemTemplates.length)];
    const adj1 = adjectives[Math.floor(Math.random() * adjectives.length)];
    const adj2 = adjectives[Math.floor(Math.random() * adjectives.length)];
    const noun1 = nouns[Math.floor(Math.random() * nouns.length)];
    const noun2 = nouns[Math.floor(Math.random() * nouns.length)];
    const verb1 = verbs[Math.floor(Math.random() * verbs.length)];
    
    // Replace placeholders
    template = template.replace('[NAME]', name);
    template = template.replace('[ADJECTIVE]', adj1);
    template = template.replace('[NOUN]', noun1);
    template = template.replace('[VERB]', verb1);
    template = template.replace('[ADJECTIVE]', adj2); // Replace the second instance
    template = template.replace('[NOUN]', noun2);   // Replace the second instance
    
    return template;
}

// --- Function to customize the voice for excitement ---
function customizeVoice(utterance) {
    // --- Standard settings for a more human feel ---
    utterance.rate = 1.2;  // A little faster than normal (1.0 is default)
    utterance.pitch = 1.4; // A little higher pitch for excitement
    
    // --- Advanced: Try to find a more natural voice ---
    // Voices depend on the user's browser and OS (e.g., Chrome on Windows has great voices)
    const voices = window.speechSynthesis.getVoices();

    // Prioritize voices that are often higher quality
    let bestVoice = voices.find(voice => voice.name.includes('Sonia') && voice.lang.startsWith('en'));
    if (bestVoice) {
        utterance.voice = bestVoice;
    }
}

// --- IMPROVED Function to Read Text Aloud ---
let currentUtterance = null;
function readPoemAloud(text) {
    if ('speechSynthesis' in window) {
        window.speechSynthesis.cancel(); // Stop any previous speech
        
        currentUtterance = new SpeechSynthesisUtterance(text);
        
        // Apply our new exciting voice settings!
        customizeVoice(currentUtterance);
        
        // This is a small trick. Sometimes voices aren't loaded immediately.
        // If the voice isn't set, we wait and try again.
        if (!currentUtterance.voice) {
            window.speechSynthesis.onvoiceschanged = () => {
                customizeVoice(currentUtterance);
                window.speechSynthesis.speak(currentUtterance);
            };
        } else {
            window.speechSynthesis.speak(currentUtterance);
        }

    } else {
        alert("Sorry, your browser doesn't support reading text aloud.");
    }
}

// --- Event Listeners for the Poem Gift ---
readPoemBtn.addEventListener('click', () => {
    readPoemAloud(poemTextDiv.textContent);
});

stopPoemBtn.addEventListener('click', () => {
    window.speechSynthesis.cancel();
});

closePoemBtn.addEventListener('click', () => {
    window.speechSynthesis.cancel();
    poemContainer.classList.add('hidden');
});

// --- REPLACED wishForm Event Listener ---
wishForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const fromName = nameInput.value.trim();
    const message = wishForm.message.value.trim();
    const submitButton = wishForm.querySelector('button[type="submit"]');

    if (!fromName || !message) return;

    submitButton.disabled = true;
    submitButton.textContent = 'Sending...';

    try {
        const response = await fetch('', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ from: fromName, message: message })
        });
        const result = await response.json();

        if (result.success) {
            // --- GIFT LOGIC STARTS HERE ---
            const generatedPoem = generatePoem(fromName);

            // Populate and show the poem container
            poemWisherName.textContent = fromName;
            poemTextDiv.textContent = generatedPoem;
            poemContainer.classList.remove('hidden');

            // Automatically read the poem aloud after a short delay
            setTimeout(() => {
                readPoemAloud(generatedPoem);
            }, 800);
            // --- GIFT LOGIC ENDS HERE ---

            // Update the rest of the page
            claimedName = fromName;
            nameInput.readOnly = true;
            nameInput.classList.add('bg-gray-100');
            wishForm.message.value = '';
            submitButton.disabled = false;
            submitButton.textContent = 'Send Another Wish!';
            await fetchLatestWishes();

        } else {
            formFeedback.textContent = result.error || 'Something went wrong.';
            formFeedback.classList.add('text-orange-500');
            submitButton.disabled = false;
            submitButton.textContent = 'Send Your Wish!';
        }
    } catch (error) {
       formFeedback.textContent = 'A network error occurred. Please try again.';
       formFeedback.classList.add('text-red-500');
       submitButton.disabled = false;
       submitButton.textContent = 'Send Your Wish!';
    }
});
        
        // --- Fun Stuff: Confetti ---
        function createConfetti() {
            const colors = ['#f472b6', '#ec4899', '#fbbf24', '#a78bfa', '#60a5fa'];
            for (let i = 0; i < 50; i++) {
                const confetti = document.createElement('div');
                confetti.classList.add('confetti');
                confetti.style.left = `${Math.random() * 100}vw`;
                confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                const duration = Math.random() * 5 + 5;
                const delay = Math.random() * 5;
                confetti.style.animation = `fall ${duration}s linear ${delay}s forwards`;
                confettiContainer.appendChild(confetti);
                setTimeout(() => confetti.remove(), (duration + delay) * 1000);
            }
        }
        
        // --- Audio Control ---
        playPauseBtn.addEventListener('click', () => {

if (birthdaySong.muted) {
birthdaySong.muted = false;

// Manually remove the banner
const soundBanner = document.getElementById('sound-banner');
if (soundBanner) {
soundBanner.style.opacity = '0';
setTimeout(() => soundBanner.remove(), 500);
}
}

            if (birthdaySong.paused) {
                birthdaySong.play();
                playIcon.classList.add('hidden');
                pauseIcon.classList.remove('hidden');
            } else {
                birthdaySong.pause();
                playIcon.classList.remove('hidden');
                pauseIcon.classList.add('hidden');
            }
        });
        
        // --- Interactive Avatar Logic ---
        document.addEventListener('mousemove', (e) => {
            const { clientX, clientY } = e;
            
            pupils.forEach(pupil => {
                const eye = pupil.parentElement;
                const rect = eye.getBoundingClientRect();
                const eyeCenterX = rect.left + rect.width / 2;
                const eyeCenterY = rect.top + rect.height / 2;
                
                const angle = Math.atan2(clientY - eyeCenterY, clientX - eyeCenterX);
                
                const radius = eye.offsetWidth / 4;
                const x = radius * Math.cos(angle);
                const y = radius * Math.sin(angle);
                
                pupil.style.transform = `translate(-50%, -50%) translate(${x}px, ${y}px)`;
            });
        });
        
        avatarContainer.addEventListener('click', () => {
            avatarContainer.classList.add('jiggle');
            setTimeout(() => {
                avatarContainer.classList.remove('jiggle');
            }, 500);
        });

        // --- Main Execution ---
        function main() {
            const params = getUrlParams();
            // If a name is claimed in the session, lock the input field.
            if (claimedName) {
                nameInput.value = claimedName;
                nameInput.readOnly = true;
                nameInput.classList.add('bg-gray-100');
            } else if (params.from) {
                // Otherwise, if a name is in the URL, pre-fill it.
                nameInput.value = params.from;
            }
            
            displayWishers(currentWishes);
            setInterval(fetchLatestWishes, 5000); 
            createConfetti();
            setInterval(createConfetti, 20000);
        }

        main();
        // --- NEW Autoplay Logic ---

// Create a small banner to prompt the user to click for sound.
const soundBanner = document.createElement('div');
soundBanner.id = 'sound-banner';
soundBanner.innerHTML = 'Click anywhere to enable sound 🔊';
soundBanner.style.cssText = `
    position: fixed;
    bottom: 20px;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(0, 0, 0, 0.7);
    color: white;
    padding: 12px 20px;
    border-radius: 50px;
    font-weight: bold;
    z-index: 100;
    cursor: pointer;
    transition: opacity 0.5s ease;
`;
document.body.appendChild(soundBanner);


// When the page is fully loaded, start playing the muted song.
window.addEventListener('load', () => {
    birthdaySong.play().catch(error => {
        console.log("Autoplay was prevented. A user interaction is needed.");
    });
});

document.addEventListener('click', (e) => { // --- MODIFIED: Added 'e'
    // --- ADDED: Check if the click was on the button ---
    if (e.target.closest('#play-pause-btn')) {
        return; // Let the button's listener handle it
    }
    // --- END ADDED ---

  if (birthdaySong.muted) {
    birthdaySong.muted = false;
    birthdaySong.play();
    // Update the main play/pause button to show the 'pause' icon
    playIcon.classList.add('hidden');
    pauseIcon.classList.remove('hidden');
  }

  // Fade out and remove the sound banner
    const soundBanner = document.getElementById('sound-banner'); // --- MODIFIED: Get banner
  if (soundBanner) { // --- MODIFIED: Check if it exists
    soundBanner.style.opacity = '0';
    setTimeout(() => {
      soundBanner.remove();
    }, 500);
  }
}, { once: true });
    </script>
    
</body>
</html>
