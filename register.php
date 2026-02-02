<?php
session_start();
require_once 'config.php';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'Please fill in all fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        // Connect to database
        $conn = getDBConnection();
        
        // Check if user already exists
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error = 'An account with this email already exists.';
            $check_stmt->close();
        } else {
            $check_stmt->close();
            
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user
            $insert_stmt = $conn->prepare("INSERT INTO users (email, password) VALUES (?, ?)");
            $insert_stmt->bind_param("ss", $email, $hashed_password);
            
            if ($insert_stmt->execute()) {
                $user_id = $insert_stmt->insert_id;
                
                // Auto login after registration
                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_email'] = $email;
                
                $insert_stmt->close();
                $conn->close();
                
                header('Location: index.php');
                exit;
            } else {
                $error = 'Registration failed. Please try again.';
            }
            
            $insert_stmt->close();
        }
        
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign up - ChefBot</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background: linear-gradient(135deg, #0a0a0a 0%, #1a1a2e 25%, #16213e 50%, #0f0f23 75%, #000000 100%);
            position: relative;
            overflow: hidden;
        }
        
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 50%, rgba(139, 69, 19, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(72, 61, 139, 0.08) 0%, transparent 50%),
                radial-gradient(circle at 40% 80%, rgba(25, 25, 112, 0.06) 0%, transparent 50%);
            animation: subtleMove 20s ease-in-out infinite;
        }
        
        body::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: 
                repeating-linear-gradient(0deg, rgba(255,255,255,0.03) 0px, transparent 1px, transparent 2px, rgba(255,255,255,0.03) 3px),
                repeating-linear-gradient(90deg, rgba(255,255,255,0.03) 0px, transparent 1px, transparent 2px, rgba(255,255,255,0.03) 3px);
            opacity: 0.3;
        }
        
        @keyframes subtleMove {
            0%, 100% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(10px, 10px) scale(1.05); }
        }
        
        .content-wrapper {
            position: relative;
            z-index: 1;
        }
        
        /* Velvet texture overlay */
        .velvet-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url("data:image/svg+xml,%3Csvg width='200' height='200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' /%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)' opacity='0.05'/%3E%3C/svg%3E");
            pointer-events: none;
            z-index: 0;
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="velvet-overlay"></div>
    
    <div class="content-wrapper w-full max-w-md">
        <!-- Logo/Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center gap-2 mb-2">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#f97316" stroke-width="2">
                    <path d="M6 13.87A4 4 0 0 1 7.41 6a5.11 5.11 0 0 1 1.05-1.54 5 5 0 0 1 7.08 0A5.11 5.11 0 0 1 16.59 6 4 4 0 0 1 18 13.87V21H6Z"/>
                    <line x1="6" y1="17" x2="18" y2="17"/>
                </svg>
                <h1 class="text-2xl font-bold text-gray-100">ChefBot</h1>
            </div>
        </div>

        <!-- Register Card -->
        <div class="bg-white rounded-2xl shadow-2xl p-8">
            <h2 class="text-2xl font-semibold text-center mb-2">Create your account</h2>
            <p class="text-gray-600 text-center mb-6 text-sm">
                Join ChefBot and start cooking smarter today.
            </p>

            <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm" style="display: none;">
                Error message here
            </div>

            <form method="POST" action="">
                <div class="space-y-4">
                    <div>
                        <input 
                            type="email" 
                            name="email" 
                            placeholder="Email address"
                            required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                        >
                    </div>

                    <div>
                        <input 
                            type="password" 
                            name="password" 
                            placeholder="Password (min. 6 characters)"
                            required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                        >
                    </div>

                    <div>
                        <input 
                            type="password" 
                            name="confirm_password" 
                            placeholder="Confirm password"
                            required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                        >
                    </div>

                    <button 
                        type="submit"
                        class="w-full bg-orange-500 hover:bg-orange-600 text-white font-medium py-3 rounded-lg transition duration-200"
                    >
                        Continue
                    </button>
                </div>
            </form>

            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600">
                    Already have an account? 
                    <a href="login.php" class="text-orange-500 hover:text-orange-600 font-medium">Log in</a>
                </p>
            </div>
        </div>

        <p class="text-xs text-gray-400 text-center mt-6">
            By signing up, you agree to our 
            <button onclick="openModal('terms')" class="text-orange-400 hover:text-orange-300 underline cursor-pointer">Terms</button> 
            and 
            <button onclick="openModal('privacy')" class="text-orange-400 hover:text-orange-300 underline cursor-pointer">Privacy Policy</button>.
        </p>
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