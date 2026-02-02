<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_email = $_SESSION['user_email'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChefBot - Cooking Assistant</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Scrollbar */
        .chat-scroll::-webkit-scrollbar { 
            width: 8px; 
        }
        .chat-scroll::-webkit-scrollbar-track { 
            background: #f1f1f1; 
        }
        .chat-scroll::-webkit-scrollbar-thumb { 
            background: #888; 
            border-radius: 4px; 
        }
        .chat-scroll::-webkit-scrollbar-thumb:hover { 
            background: #555; 
        }

        /* Typing indicator animation */
        @keyframes bounce {
            0%, 100% { 
                transform: translateY(0); 
            }
            50% { 
                transform: translateY(-5px); 
            }
        }
        .dot-bounce {
            animation: bounce 0.6s infinite;
        }
        .dot-bounce:nth-child(1) { animation-delay: 0s; }
        .dot-bounce:nth-child(2) { animation-delay: 0.15s; }
        .dot-bounce:nth-child(3) { animation-delay: 0.3s; }

        /* Dark mode styles */
        .dark {
            color-scheme: dark;
        }
        .dark body {
            background: #111827;
        }
        .dark .bg-gray-50 {
            background: #1f2937;
        }
        .dark .bg-white {
            background: #111827;
            color: #f9fafb;
        }
        .dark .border-gray-200 {
            border-color: #374151;
        }
        .dark .text-gray-800 {
            color: #f9fafb;
        }
        .dark .text-gray-500 {
            color: #9ca3af;
        }
        .dark .text-gray-600 {
            color: #9ca3af;
        }
        .dark .text-gray-700 {
            color: #d1d5db;
        }
        .dark .hover\:bg-gray-50:hover {
            background: #374151;
        }
        .dark .hover\:bg-gray-100:hover {
            background: #374151;
        }
        .dark #userMenu {
            background: #1f2937;
            border-color: #374151;
        }
        .dark #userMenu .bg-gray-50 {
            background: #111827;
        }
        .dark .chat-scroll::-webkit-scrollbar-track { 
            background: #374151; 
        }
    </style>
</head>

<body class="bg-gray-50 m-0 p-0 overflow-hidden">
    <div class="flex h-screen">

        <!-- Sidebar -->
        <div id="sidebar" class="w-64 bg-gray-900 text-white transition-all duration-300 flex flex-col overflow-hidden">
            <div class="p-4 border-b border-gray-700">
                <button onclick="newChat()" class="flex items-center gap-2 w-full p-3 rounded-lg border border-gray-600 hover:bg-gray-800 transition">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"/>
                        <line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    <span class="text-sm font-medium">New chat</span>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto p-2 chat-scroll">
                <div class="text-xs text-gray-400 px-3 py-2 font-semibold">Recent Chats</div>
                <div class="space-y-1" id="recentChats">
                    <div class="text-xs text-gray-500 px-3 py-2">No recent chats</div>
                </div>
            </div>

            <div class="p-4 border-t border-gray-700">
                <div class="relative">
                    <div class="flex items-center gap-2">
                        <button onclick="toggleUserMenu()" id="userMenuBtn" class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-800 cursor-pointer flex-1 text-left">
                            <div class="w-8 h-8 rounded-full bg-orange-500 flex items-center justify-center flex-shrink-0">
                                <span class="text-white text-sm font-bold"><?php echo strtoupper(substr($user_email, 0, 1)); ?></span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-medium truncate"><?php echo htmlspecialchars($user_email); ?></div>
                                <div class="text-xs text-gray-400">Free Plan</div>
                            </div>
                        </button>
                        <button onclick="toggleDarkMode(event)" id="darkModeToggle" class="p-2 hover:bg-gray-700 rounded-lg transition flex-shrink-0" title="Toggle dark mode">
                            <!-- Sun icon (light mode) -->
                            <svg id="sunIcon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="hidden">
                                <circle cx="12" cy="12" r="5"/>
                                <line x1="12" y1="1" x2="12" y2="3"/>
                                <line x1="12" y1="21" x2="12" y2="23"/>
                                <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/>
                                <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
                                <line x1="1" y1="12" x2="3" y2="12"/>
                                <line x1="21" y1="12" x2="23" y2="12"/>
                                <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/>
                                <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
                            </svg>
                            <!-- Moon icon (dark mode) -->
                            <svg id="moonIcon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                            </svg>
                        </button>
                    </div>
                    
                    <!-- User Menu Dropdown -->
                    <div id="userMenu" class="hidden absolute bottom-full left-0 right-0 mb-2 bg-white rounded-lg shadow-2xl border border-gray-200 overflow-hidden">
                        <div class="p-3 border-b border-gray-100 bg-gray-50">
                            <div class="flex items-center gap-2 text-gray-600">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                    <circle cx="12" cy="7" r="4"/>
                                </svg>
                                <span class="text-sm font-medium truncate"><?php echo htmlspecialchars($user_email); ?></span>
                            </div>
                        </div>
                        
                        <div class="py-1">
                            <button onclick="modernAlert('Upgrade feature coming soon!')" class="w-full px-4 py-2.5 text-left hover:bg-gray-50 transition flex items-center gap-3 text-gray-700">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                                </svg>
                                <span class="text-sm font-medium">Upgrade plan</span>
                            </button>
                            
                            <button onclick="modernAlert('Personalization coming soon!')" class="w-full px-4 py-2.5 text-left hover:bg-gray-50 transition flex items-center gap-3 text-gray-700">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="3"/>
                                    <path d="M12 1v6m0 6v6m5.66-15.66l-4.24 4.24m0 6l-4.24 4.24M1 12h6m6 0h6M4.34 4.34l4.24 4.24m6 0l4.24 4.24"/>
                                </svg>
                                <span class="text-sm font-medium">Personalization</span>
                            </button>
                            
                            <button onclick="openSettings()" class="w-full px-4 py-2.5 text-left hover:bg-gray-50 transition flex items-center gap-3 text-gray-700">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="3"/>
                                    <path d="M12 1v6m0 6v6"/>
                                    <path d="M17 12h5M2 12h5"/>
                                    <path d="M16.24 7.76l3.54-3.54M4.22 19.78l3.54-3.54m8.48 0l3.54 3.54M4.22 4.22l3.54 3.54"/>
                                </svg>
                                <span class="text-sm font-medium">Settings</span>
                            </button>
                        </div>
                        
                        <div class="border-t border-gray-200 py-1">
                            <button onclick="alert('Help center coming soon!')" class="w-full px-4 py-2.5 text-left hover:bg-gray-50 transition flex items-center gap-3 text-gray-700">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"/>
                                    <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/>
                                    <line x1="12" y1="17" x2="12.01" y2="17"/>
                                </svg>
                                <span class="text-sm font-medium">Help</span>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="ml-auto">
                                    <polyline points="9 18 15 12 9 6"/>
                                </svg>
                            </button>
                            
                        <a href="logout.php"
   onclick="return confirm('🔒 Log out?\n\nAre you sure you want to end your session?');"
   class="w-full px-4 py-2.5 text-left flex items-center gap-3 text-red-600 
          hover:bg-red-50 active:bg-red-100 transition-all duration-200 
          hover:pl-6 rounded-lg cursor-pointer select-none">

    <svg width="18" height="18" viewBox="0 0 24 24" 
         class="transition-transform duration-200 group-hover:-translate-x-1"
         fill="none" stroke="currentColor" stroke-width="2">
        <path d="M9 21H5a2 2 0 0 1-2-2V5a 2 2 0 0 1 2-2h4" />
        <polyline points="16 17 21 12 16 7" />
        <line x1="21" y1="12" x2="9" y2="12" />
    </svg>

    <span class="text-sm font-medium">Log out</span>
</a>



                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">

            <!-- Header -->
            <div class="bg-white border-b border-gray-200 px-4 py-3 flex items-center gap-4 flex-shrink-0">
                <button onclick="toggleSidebar()" class="p-2 hover:bg-gray-100 rounded-lg transition">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="3" y1="12" x2="21" y2="12"/>
                        <line x1="3" y1="6" x2="21" y2="6"/>
                        <line x1="3" y1="18" x2="21" y2="18"/>
                    </svg>
                </button>

                <div class="flex items-center gap-2">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#f97316" stroke-width="2">
                        <path d="M6 13.87A4 4 0 0 1 7.41 6a5.11 5.11 0 0 1 1.05-1.54 5 5 0 0 1 7.08 0A5.11 5.11 0 0 1 16.59 6 4 4 0 0 1 18 13.87V21H6Z"/>
                        <line x1="6" y1="17" x2="18" y2="17"/>
                    </svg>
                    <h1 class="text-lg font-semibold">ChefBot</h1>
                </div>
            </div>

            <!-- Messages -->
            <div id="messages" class="flex-1 overflow-y-auto chat-scroll bg-gray-50">
                <div class="max-w-3xl mx-auto px-4 py-8" id="messageContainer">

                    <!-- Welcome message -->
                    <div class="mb-8">
                        <div class="flex gap-4">
                            <div class="w-8 h-8 rounded-full bg-orange-500 flex items-center justify-center flex-shrink-0">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="white" stroke="white" stroke-width="2">
                                    <path d="M6 13.87A4 4 0 0 1 7.41 6a5.11 5.11 0 0 1 1.05-1.54 5 5 0 0 1 7.08 0A5.11 5.11 0 0 1 16.59 6 4 4 0 0 1 18 13.87V21H6Z"/>
                                    <line x1="6" y1="17" x2="18" y2="17"/>
                                </svg>
                            </div>

                            <div class="flex-1">
                                <div class="inline-block rounded-2xl px-4 py-3 bg-white border border-gray-200 text-gray-800">
                                    <p class="text-sm leading-relaxed">
                                        Hi! I'm ChefBot, your cooking assistant. I can help with recipes, techniques, ingredients, and everything food-related. What would you like to cook today?
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Input Area -->
            <div class="bg-white border-t border-gray-200 p-4 flex-shrink-0">
                <div class="max-w-3xl mx-auto">
                    <div class="flex gap-3 items-end bg-gray-50 rounded-2xl border border-gray-200 p-3">
                        <textarea 
                            id="userInput"
                            placeholder="Ask ChefBot about recipes, ingredients, techniques..."
                            class="flex-1 bg-transparent resize-none outline-none text-sm"
                            rows="1"
                            style="max-height: 128px; min-height: 24px;"
                        ></textarea>

                        <button 
                            onclick="sendMessage()"
                            id="sendBtn"
                            class="p-2 bg-orange-500 text-white rounded-xl hover:bg-orange-600 transition disabled:opacity-50 disabled:cursor-not-allowed flex-shrink-0"
                        >
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="22" y1="2" x2="11" y2="13"/>
                                <polygon points="22 2 15 22 11 13 2 9 22 2" fill="currentColor"/>
                            </svg>
                        </button>
                    </div>

                    <p class="text-xs text-gray-500 text-center mt-3">
                        ChefBot can make mistakes. Check important info.
                    </p>
                </div>
            </div>

        </div>
    </div>
    <script>
        // Pass user email from PHP to JavaScript
        const USER_EMAIL = <?php echo json_encode($user_email); ?>;

        // Dark mode toggle function
        function toggleDarkMode(event) {
            event.stopPropagation(); // Prevent triggering the user menu
            const html = document.documentElement;
            const isDark = html.classList.toggle('dark');
            
            // Toggle icons
            const sunIcon = document.getElementById('sunIcon');
            const moonIcon = document.getElementById('moonIcon');
            
            if (isDark) {
                sunIcon.classList.remove('hidden');
                moonIcon.classList.add('hidden');
            } else {
                sunIcon.classList.add('hidden');
                moonIcon.classList.remove('hidden');
            }
            
            // Save preference
            localStorage.setItem('darkMode', isDark ? 'true' : 'false');
        }

        // Initialize dark mode from localStorage
        function initDarkMode() {
            const darkMode = localStorage.getItem('darkMode');
            const html = document.documentElement;
            const sunIcon = document.getElementById('sunIcon');
            const moonIcon = document.getElementById('moonIcon');
            
            if (darkMode === 'true') {
                html.classList.add('dark');
                sunIcon.classList.remove('hidden');
                moonIcon.classList.add('hidden');
            } else {
                sunIcon.classList.add('hidden');
                moonIcon.classList.remove('hidden');
            }
        }

        // Initialize dark mode on page load
        document.addEventListener('DOMContentLoaded', initDarkMode);
    </script>
    <script src="script.js"></script>
</body>
</html>