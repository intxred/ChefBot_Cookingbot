<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'config.php';

// If already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        $conn = getDBConnection();

        $stmt = $conn->prepare("SELECT id, email, password FROM users WHERE email = ? AND is_active = TRUE");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password'])) {

                // Set session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];

                    // REDIRECT AFTER SUCCESSFUL LOGIN
                header('Location: index.php');
                exit;

            } else {
                $error = "Invalid email or password.";
            }
        } else {
            $error = "Invalid email or password.";
        }

        $stmt->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log in - ChefBot</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        * {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        body {
            background: #f5f3f0;
            margin: 0;
            padding: 0;
        }

      .video-container {
    position: relative;
    width: 100%;
    max-width: 550px;
    aspect-ratio: 4 / 5;
    background: #d27d4f;
    border-radius: 35px;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;

    transform: translateX(-20px); /* adjust this value */
}



        .video-placeholder {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .google-btn {
            border: 1px solid #dadce0;
            background: white;
            color: #3c4043;
            font-weight: 500;
            transition: all 0.2s;
        }

        .google-btn:hover {
            background: #f8f9fa;
            border-color: #d2d4d8;
        }

        .continue-btn {
            background: #1a1a1a;
            color: white;
            font-weight: 500;
            transition: all 0.2s;
        }

        .continue-btn:hover {
            background: #2d2d2d;
        }

        .form-input {
            border: 1px solid #d1d5db;
            transition: all 0.2s;
        }

        .form-input:focus {
            outline: none;
            border-color: #1a1a1a;
            box-shadow: 0 0 0 3px rgba(26, 26, 26, 0.1);
        }

        /* Decorative elements */
        .confetti {
            position: absolute;
            width: 100%;
            height: 100%;
            pointer-events: none;
        }

        .confetti-piece {
            position: absolute;
            width: 8px;
            height: 16px;
            background: #fff;
            opacity: 0.6;
        }
    </style>
</head>
<body class="min-h-screen">
    
    <!-- Navigation -->
    <nav class="fixed top-0 left-0 right-0 z-50 bg-white/80 backdrop-blur-md border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#f97316" stroke-width="2">
                    <path d="M6 13.87A4 4 0 0 1 7.41 6a5.11 5.11 0 0 1 1.05-1.54 5 5 0 0 1 7.08 0A5.11 5.11 0 0 1 16.59 6 4 4 0 0 1 18 13.87V21H6Z"/>
                    <line x1="6" y1="17" x2="18" y2="17"/>
                </svg>
                <span class="text-xl font-semibold text-gray-900">ChefBot</span>
            </div>
            
            <div class="flex items-center gap-6">
                <a href="#" class="text-sm text-gray-600 hover:text-gray-900">About</a>
                <a href="#" class="text-sm text-gray-600 hover:text-gray-900">Features</a>
                
                <a href="register.php" class="text-sm bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-lg transition">
                    Sign up
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="min-h-screen pt-20 grid lg:grid-cols-2">
        
        <!-- Left Side - Login Form -->
        <div class="flex items-center justify-center px-6 py-12">
            <div class="w-full max-w-md">
                
                <!-- Header -->
                <div class="mb-10">
                    <h1 class="text-5xl font-normal text-gray-900 mb-2" style="line-height: 1.1;">
                        Impossible?<br>
                        <span class="font-light">Possible.</span>
                    </h1>
                    <p class="text-lg text-gray-600 mt-4">The AI for problem solvers</p>
                </div>

                <!-- Error Message -->
                <?php if ($error): ?>
                <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm">
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>

                <!-- Login Form -->
                <div class="space-y-4">
                    
                    <!-- Google Sign In -->
                    <button type="button" class="google-btn w-full px-6 py-3.5 rounded-xl flex items-center justify-center gap-3">
                        <svg width="20" height="20" viewBox="0 0 24 24">
                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                        </svg>
                        <span>Continue with Google</span>
                    </button>

                    <!-- Divider -->
                    <div class="relative flex items-center justify-center my-6">
                        <div class="border-t border-gray-300 w-full"></div>
                        <div class="absolute bg-[#f5f3f0] px-4 text-sm text-gray-500">OR</div>
                    </div>

                    <!-- Email Form -->
                    <form method="POST" action="" class="space-y-4">
                        <div>
                            <input 
                                type="email" 
                                name="email" 
                                placeholder="Enter your email"
                                required
                                class="form-input w-full px-4 py-3.5 rounded-xl text-gray-900"
                            >
                        </div>

                        <div>
                            <input 
                                type="password" 
                                name="password" 
                                placeholder="Password"
                                required
                                class="form-input w-full px-4 py-3.5 rounded-xl text-gray-900"
                            >
                        </div>

                        <button 
                            type="submit"
                            class="continue-btn w-full py-3.5 rounded-xl"
                        >
                            Continue with email
                        </button>
                    </form>

                    <!-- Terms -->
                    <p class="text-xs text-gray-500 text-center mt-4">
                        By continuing, you acknowledge Anthropic's 
                        <button onclick="openModal('privacy')" class="underline hover:text-gray-700">Privacy Policy</button>.
                    </p>

                    <!-- Sign Up Link -->
                    <div class="text-center mt-6 pt-6 border-t border-gray-200">
                        <p class="text-sm text-gray-600">
                            Don't have an account? 
                            <a href="register.php" class="text-gray-900 font-medium hover:underline">Sign up</a>
                        </p>
                    </div>
                </div>

            </div>
        </div>

        <!-- Right Side - Video -->
        <div class="video-container relative hidden lg:block">
            <!-- Video placeholder - replace with your actual video -->
            <video 
                class="video-placeholder" 
                autoplay 
                muted 
                loop 
                playsinline
                poster="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='800' height='800'%3E%3Crect fill='%23c67c4e' width='800' height='800'/%3E%3C/svg%3E"
            >
                <source src="home.mp4" >
                <!-- Fallback background -->
            </video>
            
            <!-- Decorative confetti elements -->
            <div class="confetti">
                <div class="confetti-piece" style="left: 20%; top: 15%; background: #4ade80; transform: rotate(15deg);"></div>
                <div class="confetti-piece" style="left: 45%; top: 25%; background: #fbbf24; transform: rotate(-20deg);"></div>
                <div class="confetti-piece" style="left: 70%; top: 35%; background: #60a5fa; transform: rotate(30deg);"></div>
                <div class="confetti-piece" style="left: 30%; top: 60%; background: #f87171; transform: rotate(-15deg);"></div>
                <div class="confetti-piece" style="left: 80%; top: 70%; background: #a78bfa; transform: rotate(25deg);"></div>
                <div class="confetti-piece" style="left: 15%; top: 80%; background: #34d399; transform: rotate(-30deg);"></div>
            </div>

            
    </div>

    <!-- Modal -->
    <div id="modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
        <div onclick="closeModal()" class="absolute inset-0 bg-black/60 backdrop-blur-md"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[80vh] overflow-hidden">
            <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
                <h3 id="modalTitle" class="text-xl font-semibold text-gray-800"></h3>
                <button onclick="closeModal()" class="p-2 hover:bg-gray-100 rounded-lg transition">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>
            <div id="modalContent" class="px-6 py-6 overflow-y-auto max-h-[calc(80vh-80px)] text-gray-700 text-sm leading-relaxed">
            </div>
        </div>
    </div>

    <script>
        const modalContent = {
            terms: {
                title: 'Terms of Service',
                content: `
                    <h4 class="font-semibold text-lg mb-3">1. Acceptance of Terms</h4>
                    <p class="mb-4">By accessing and using ChefBot, you accept and agree to be bound by the terms and provision of this agreement.</p>

                    <h4 class="font-semibold text-lg mb-3">2. Use License</h4>
                    <p class="mb-4">Permission is granted to temporarily use ChefBot for personal, non-commercial transitory viewing only. This is the grant of a license, not a transfer of title, and under this license you may not:</p>
                    <ul class="list-disc pl-6 mb-4 space-y-2">
                        <li>Modify or copy the materials</li>
                        <li>Use the materials for any commercial purpose</li>
                        <li>Attempt to decompile or reverse engineer any software contained on ChefBot</li>
                        <li>Remove any copyright or other proprietary notations from the materials</li>
                    </ul>

                    <h4 class="font-semibold text-lg mb-3">3. Disclaimer</h4>
                    <p class="mb-4">The materials on ChefBot are provided on an 'as is' basis. ChefBot makes no warranties, expressed or implied, and hereby disclaims and negates all other warranties including, without limitation, implied warranties or conditions of merchantability, fitness for a particular purpose, or non-infringement of intellectual property or other violation of rights.</p>

                    <h4 class="font-semibold text-lg mb-3">4. Limitations</h4>
                    <p class="mb-4">In no event shall ChefBot or its suppliers be liable for any damages (including, without limitation, damages for loss of data or profit, or due to business interruption) arising out of the use or inability to use ChefBot.</p>

                    <h4 class="font-semibold text-lg mb-3">5. Accuracy of Materials</h4>
                    <p class="mb-4">The materials appearing on ChefBot could include technical, typographical, or photographic errors. ChefBot does not warrant that any of the materials on its website are accurate, complete, or current.</p>

                    <h4 class="font-semibold text-lg mb-3">6. Links</h4>
                    <p class="mb-4">ChefBot has not reviewed all of the sites linked to its website and is not responsible for the contents of any such linked site.</p>

                    <h4 class="font-semibold text-lg mb-3">7. Modifications</h4>
                    <p class="mb-4">ChefBot may revise these terms of service at any time without notice. By using this website you are agreeing to be bound by the then current version of these terms of service.</p>

                    <h4 class="font-semibold text-lg mb-3">8. Governing Law</h4>
                    <p class="mb-4">These terms and conditions are governed by and construed in accordance with applicable laws and you irrevocably submit to the exclusive jurisdiction of the courts in that location.</p>
                `
            },
            privacy: {
                title: 'Privacy Policy',
                content: `
                    <h4 class="font-semibold text-lg mb-3">1. Information We Collect</h4>
                    <p class="mb-4">We collect information that you provide directly to us, including:</p>
                    <ul class="list-disc pl-6 mb-4 space-y-2">
                        <li>Email address and account credentials</li>
                        <li>Chat history and conversations with ChefBot</li>
                        <li>Usage data and preferences</li>
                        <li>Device information and IP address</li>
                    </ul>

                    <h4 class="font-semibold text-lg mb-3">2. How We Use Your Information</h4>
                    <p class="mb-4">We use the information we collect to:</p>
                    <ul class="list-disc pl-6 mb-4 space-y-2">
                        <li>Provide, maintain, and improve ChefBot services</li>
                        <li>Personalize your experience and provide relevant content</li>
                        <li>Send you technical notices and support messages</li>
                        <li>Monitor and analyze trends, usage, and activities</li>
                        <li>Detect, prevent, and address technical issues</li>
                    </ul>

                    <h4 class="font-semibold text-lg mb-3">3. Information Sharing</h4>
                    <p class="mb-4">We do not sell, trade, or rent your personal information to third parties. We may share your information only in the following circumstances:</p>
                    <ul class="list-disc pl-6 mb-4 space-y-2">
                        <li>With your consent</li>
                        <li>To comply with legal obligations</li>
                        <li>To protect our rights and prevent fraud</li>
                        <li>With service providers who assist in our operations</li>
                    </ul>

                    <h4 class="font-semibold text-lg mb-3">4. Data Security</h4>
                    <p class="mb-4">We implement appropriate technical and organizational measures to protect your personal information against unauthorized access, alteration, disclosure, or destruction.</p>

                    <h4 class="font-semibold text-lg mb-3">5. Cookies and Tracking</h4>
                    <p class="mb-4">We use cookies and similar tracking technologies to track activity on our service and hold certain information. You can instruct your browser to refuse all cookies or to indicate when a cookie is being sent.</p>

                    <h4 class="font-semibold text-lg mb-3">6. Data Retention</h4>
                    <p class="mb-4">We retain your personal information for as long as necessary to fulfill the purposes outlined in this privacy policy, unless a longer retention period is required by law.</p>

                    <h4 class="font-semibold text-lg mb-3">7. Your Rights</h4>
                    <p class="mb-4">You have the right to:</p>
                    <ul class="list-disc pl-6 mb-4 space-y-2">
                        <li>Access and receive a copy of your personal data</li>
                        <li>Rectify inaccurate personal data</li>
                        <li>Request deletion of your personal data</li>
                        <li>Object to processing of your personal data</li>
                        <li>Request restriction of processing</li>
                        <li>Data portability</li>
                    </ul>

                    <h4 class="font-semibold text-lg mb-3">8. Children's Privacy</h4>
                    <p class="mb-4">ChefBot is not intended for children under 13 years of age. We do not knowingly collect personal information from children under 13.</p>

                    <h4 class="font-semibold text-lg mb-3">9. Changes to Privacy Policy</h4>
                    <p class="mb-4">We may update our Privacy Policy from time to time. We will notify you of any changes by posting the new Privacy Policy on this page and updating the "Last Updated" date.</p>

                    <h4 class="font-semibold text-lg mb-3">10. Contact Us</h4>
                    <p class="mb-4">If you have any questions about this Privacy Policy, please contact us at support@chefbot.com</p>

                    <p class="text-xs text-gray-500 mt-6">Last Updated: November 16, 2025</p>
                `
            }
        };

        function openModal(type) {
            const modal = document.getElementById('modal');
            const title = document.getElementById('modalTitle');
            const content = document.getElementById('modalContent');
            
            title.textContent = modalContent[type].title;
            content.innerHTML = modalContent[type].content;
            
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            const modal = document.getElementById('modal');
            modal.classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        // Close modal on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeModal();
            }
        });
    </script>
</body>
</html>