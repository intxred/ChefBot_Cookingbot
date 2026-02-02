let sidebarOpen = true;
let currentChatId = null;
let chats = {};
let userMenuOpen = false;

// ── Stop-generation state ──
let stopRequested = false;       // set true when user clicks Stop
let abortController = null;      // aborts the in-flight fetch
let typingResolve = null;        // holds the current typing-promise resolver so Stop can call it

// ─────────────────────────────────────────────
// Button helpers – swap Send ↔ Stop on #sendBtn
// ─────────────────────────────────────────────
const SEND_ICON = `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
    <line x1="22" y1="2" x2="11" y2="13"/>
    <polygon points="22 2 15 22 11 13 2 9 22 2"/>
</svg>`;

const STOP_ICON = `<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" stroke="none">
    <rect x="6" y="6" width="12" height="12" rx="2" ry="2"/>
</svg>`;

function showSendButton() {
    const btn = document.getElementById('sendBtn');
    btn.innerHTML = SEND_ICON;
    btn.onclick = () => sendMessage();
    btn.style.color = '';
    btn.disabled = false;
}

function showStopButton() {
    const btn = document.getElementById('sendBtn');
    btn.innerHTML = STOP_ICON;
    btn.onclick = () => stopGeneration();
    btn.style.color = '#ef4444';   // red
    btn.disabled = false;
}

function stopGeneration() {
    stopRequested = true;
    // Kill the network request if it hasn't resolved yet
    if (abortController) abortController.abort();
    // If we're mid-typing, the loop will see the flag on the next tick;
    // but if we're still on the loading dots (no typing started), resolve manually
    if (typingResolve) {
        typingResolve();
        typingResolve = null;
    }
}

// ─────────────────────────────────────────────
// Init
// ─────────────────────────────────────────────
window.addEventListener('load', () => {
    loadChats();
    renderRecentChats();
    document.getElementById('userInput').focus();
    showSendButton();

    document.addEventListener('click', (e) => {
        const userMenu = document.getElementById('userMenu');
        const userMenuBtn = document.getElementById('userMenuBtn');
        if (userMenuOpen && !userMenu.contains(e.target) && !userMenuBtn.contains(e.target)) {
            toggleUserMenu();
        }
    });
});

// ─────────────────────────────────────────────
// localStorage
// ─────────────────────────────────────────────
function loadChats() {
    const stored = localStorage.getItem('chefbot_chats');
    if (stored) chats = JSON.parse(stored);
}

function saveChats() {
    localStorage.setItem('chefbot_chats', JSON.stringify(chats));
}

// ─────────────────────────────────────────────
// Sidebar
// ─────────────────────────────────────────────
function toggleSidebar() {
    sidebarOpen = !sidebarOpen;
    document.getElementById('sidebar').style.width = sidebarOpen ? '16rem' : '0';
}

function generateChatId() {
    return 'chat_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
}

function getChatTitle(message) {
    return message.length > 30 ? message.substring(0, 30) + '...' : message;
}

// ─────────────────────────────────────────────
// Chat management
// ─────────────────────────────────────────────
function newChat() {
    currentChatId = null;
    document.getElementById('messageContainer').innerHTML = `
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
                        <p class="text-sm leading-relaxed">Hi! I'm ChefBot, your cooking assistant. I can help with recipes, techniques, ingredients, and everything food-related. What would you like to cook today?</p>
                    </div>
                </div>
            </div>
        </div>
    `;
    document.getElementById('userInput').value = '';
    document.getElementById('userInput').focus();
    document.querySelectorAll('#recentChats button').forEach(btn => btn.classList.remove('bg-gray-800'));
}

function loadChat(chatId) {
    currentChatId = chatId;
    const chat = chats[chatId];
    if (!chat) return;

    document.getElementById('messageContainer').innerHTML = '';

    chat.messages.forEach(msg => {
        if (msg.role === 'user') addUserMessageToDOM(msg.content, false);
        else                     addBotMessageToDOM(msg.content, false, false);
    });

    document.querySelectorAll('#recentChats button').forEach(btn => btn.classList.remove('bg-gray-800'));
    document.querySelector(`button[data-chat-id="${chatId}"]`)?.classList.add('bg-gray-800');
    document.getElementById('userInput').focus();
}

function renderRecentChats() {
    const container = document.getElementById('recentChats');
    const chatList = Object.entries(chats).sort((a, b) => b[1].timestamp - a[1].timestamp);

    if (chatList.length === 0) {
        container.innerHTML = '<div class="text-xs text-gray-500 px-3 py-2">No recent chats</div>';
        return;
    }

    container.innerHTML = chatList.map(([chatId, chat]) => `
        <div class="relative group">
            <button 
                class="flex items-center gap-3 w-full p-3 rounded-lg hover:bg-gray-800 transition text-left"
                data-chat-id="${chatId}"
                onclick="loadChat('${chatId}')"
            >
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="flex-shrink-0">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                </svg>
                <span class="text-sm truncate flex-1">${escapeHtml(chat.title)}</span>
            </button>
            <button 
                onclick="deleteChat('${chatId}', event)"
                class="absolute right-2 top-1/2 -translate-y-1/2 p-1.5 hover:bg-gray-700 rounded opacity-0 group-hover:opacity-100 transition-opacity"
                title="Delete chat"
            >
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="3 6 5 6 21 6"/>
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                </svg>
            </button>
        </div>
    `).join('');
}

// ─────────────────────────────────────────────
// DOM message helpers
// ─────────────────────────────────────────────
function addUserMessageToDOM(message, scroll = true) {
    const container  = document.getElementById('messageContainer');
    const messagesDiv = document.getElementById('messages');

    const div = document.createElement('div');
    div.className = 'mb-8 flex justify-end';
    div.innerHTML = `
        <div class="flex gap-4 flex-row-reverse max-w-[80%]">
            <div class="w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center flex-shrink-0">
                <span class="text-white text-sm font-medium">U</span>
            </div>
            <div class="flex-1 text-right">
                <div class="inline-block rounded-2xl px-4 py-3 bg-blue-500 text-white text-left">
                    <p class="text-sm leading-relaxed whitespace-pre-wrap">${escapeHtml(message)}</p>
                </div>
            </div>
        </div>
    `;
    container.appendChild(div);
    if (scroll) messagesDiv.scrollTop = messagesDiv.scrollHeight;
}

/*
 * addBotMessageToDOM
 * Returns a Promise that resolves when typing finishes OR stopRequested becomes true.
 * The caller checks stopRequested afterward to know which path was taken.
 */
function addBotMessageToDOM(message, scroll = true, animate = true) {
    const container   = document.getElementById('messageContainer');
    const messagesDiv = document.getElementById('messages');

    const botDiv = document.createElement('div');
    botDiv.className = 'mb-8';
    botDiv.innerHTML = `
        <div class="flex gap-4">
            <div class="w-8 h-8 rounded-full bg-orange-500 flex items-center justify-center flex-shrink-0">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="white" stroke="white" stroke-width="2">
                    <path d="M6 13.87A4 4 0 0 1 7.41 6a5.11 5.11 0 0 1 1.05-1.54 5 5 0 0 1 7.08 0A5.11 5.11 0 0 1 16.59 6 4 4 0 0 1 18 13.87V21H6Z"/>
                    <line x1="6" y1="17" x2="18" y2="17"/>
                </svg>
            </div>
            <div class="flex-1">
                <div class="inline-block rounded-2xl px-4 py-3 bg-white border border-gray-200 text-gray-800">
                    <p class="text-sm leading-relaxed whitespace-pre-wrap typing-text"></p>
                </div>
            </div>
        </div>
    `;
    container.appendChild(botDiv);

    const textEl = botDiv.querySelector('.typing-text');

    return new Promise((resolve) => {
        typingResolve = resolve;   // expose so stopGeneration() can call it

        if (animate) {
            let i = 0;
            const speed = 15;

            (function tick() {
                if (stopRequested) { typingResolve = null; resolve(); return; }

                if (i < message.length) {
                    textEl.textContent += message[i++];
                    if (scroll) messagesDiv.scrollTop = messagesDiv.scrollHeight;
                    setTimeout(tick, speed);
                } else {
                    typingResolve = null;
                    resolve();
                }
            })();
        } else {
            textEl.textContent = message;
            if (scroll) messagesDiv.scrollTop = messagesDiv.scrollHeight;
            typingResolve = null;
            resolve();
        }
    });
}

// ─────────────────────────────────────────────
// Textarea: auto-resize + Enter key
// ─────────────────────────────────────────────
const textarea = document.getElementById('userInput');
textarea.addEventListener('input', function () {
    this.style.height = '24px';
    this.style.height = Math.min(this.scrollHeight, 128) + 'px';
});
textarea.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
});

// ─────────────────────────────────────────────
// sendMessage – main flow
// ─────────────────────────────────────────────
async function sendMessage() {
    const input       = document.getElementById('userInput');
    const container   = document.getElementById('messageContainer');
    const messagesDiv = document.getElementById('messages');
    const message     = input.value.trim();

    if (!message) return;

    // ── Fresh stop state for this exchange ──
    stopRequested  = false;
    abortController = new AbortController();

    // ── Create chat if needed ──
    if (!currentChatId) {
        currentChatId = generateChatId();
        chats[currentChatId] = {
            id: currentChatId,
            title: getChatTitle(message),
            messages: [],
            timestamp: Date.now()
        };
    }

    // ── Lock input, show Stop button ──
    input.disabled = true;
    showStopButton();

    // ── Persist user message ──
    chats[currentChatId].messages.push({ role: 'user', content: message });
    chats[currentChatId].timestamp = Date.now();
    saveChats();
    renderRecentChats();

    addUserMessageToDOM(message);
    input.value = '';
    input.style.height = '24px';

    // ── Loading dots ──
    const loadingDiv = document.createElement('div');
    loadingDiv.className = 'mb-8';
    loadingDiv.id = 'loading';
    loadingDiv.innerHTML = `
        <div class="flex gap-4">
            <div class="w-8 h-8 rounded-full bg-orange-500 flex items-center justify-center flex-shrink-0">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="white" stroke="white" stroke-width="2">
                    <path d="M6 13.87A4 4 0 0 1 7.41 6a5.11 5.11 0 0 1 1.05-1.54 5 5 0 0 1 7.08 0A5.11 5.11 0 0 1 16.59 6 4 4 0 0 1 18 13.87V21H6Z"/>
                    <line x1="6" y1="17" x2="18" y2="17"/>
                </svg>
            </div>
            <div class="flex-1">
                <div class="inline-block rounded-2xl px-4 py-3 bg-white border border-gray-200">
                    <div class="flex gap-1">
                        <div class="w-2 h-2 bg-gray-400 rounded-full dot-bounce"></div>
                        <div class="w-2 h-2 bg-gray-400 rounded-full dot-bounce"></div>
                        <div class="w-2 h-2 bg-gray-400 rounded-full dot-bounce"></div>
                    </div>
                </div>
            </div>
        </div>
    `;
    container.appendChild(loadingDiv);
    messagesDiv.scrollTop = messagesDiv.scrollHeight;

    // ── Fetch ──
    try {
        const response = await fetch('http://127.0.0.1:5000/chat', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ user_input: message }),
            signal:  abortController.signal
        });

        const data = await response.json();
        loadingDiv.remove();

        // User hit Stop while we were still waiting on the network
        if (stopRequested) {
            chats[currentChatId].messages.push({ role: 'assistant', content: '[Generation stopped]' });
            saveChats();
        } else {
            const botResponse = data.response || data.error || 'No response';

            // Save full response first
            chats[currentChatId].messages.push({ role: 'assistant', content: botResponse });
            saveChats();

            // Type it out – blocks until complete or stopped
            await addBotMessageToDOM(botResponse, true, true);

            // If stopped mid-type, trim the saved message to what was actually shown
            if (stopRequested) {
                const rendered = document.querySelector('.mb-8:last-child .typing-text');
                const shown = rendered ? rendered.textContent : '';
                chats[currentChatId].messages[chats[currentChatId].messages.length - 1].content =
                    shown || '[Generation stopped]';
                saveChats();
            }
        }

    } catch (error) {
        loadingDiv.remove();

        if (error.name === 'AbortError') {
            // Fetch aborted by Stop – just note it
            chats[currentChatId].messages.push({ role: 'assistant', content: '[Generation stopped]' });
            saveChats();
        } else {
            const errorMessage = 'Sorry, I encountered an error. Please try again.';
            chats[currentChatId].messages.push({ role: 'assistant', content: errorMessage });
            saveChats();
            await addBotMessageToDOM(errorMessage, true, true);
        }
    }

    // ── Re-enable input, restore Send button ──
    abortController = null;
    input.disabled  = false;
    showSendButton();
    input.focus();
}

// ─────────────────────────────────────────────
// Utilities
// ─────────────────────────────────────────────
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function toggleUserMenu() {
    userMenuOpen = !userMenuOpen;
    document.getElementById('userMenu').classList.toggle('hidden', !userMenuOpen);
}

function openSettings() {
    toggleUserMenu();
    alert('Settings panel coming soon!\n\nFeatures:\n- Theme customization\n- Language preferences\n- Notification settings\n- Data management');
}

// ─────────────────────────────────────────────
// Delete chat – glassmorphism modal
// ─────────────────────────────────────────────
function deleteChat(chatId, event) {
    event.stopPropagation();

    const existing = document.getElementById("glassDeleteModal");
    if (existing) existing.remove();

    const modal = document.createElement("div");
    modal.id = "glassDeleteModal";
    modal.innerHTML = `
        <div class="glass-overlay">
            <div class="glass-box">
                <h3>Delete conversation?</h3>
                <p>This action cannot be undone.</p>
                <div class="glass-actions">
                    <button id="glassCancel">Cancel</button>
                    <button id="glassConfirm">Delete</button>
                </div>
            </div>
        </div>
    `;
    document.body.appendChild(modal);

    const style = document.createElement("style");
    style.textContent = `
        .glass-overlay {
            position: fixed; inset: 0;
            backdrop-filter: blur(12px) saturate(180%);
            background: rgba(255,255,255,0.20);
            display: flex; align-items: center; justify-content: center;
            z-index: 99999; animation: fadeIn .25s ease;
        }
        .glass-box {
            background: rgba(255,255,255,0.4);
            backdrop-filter: blur(20px) saturate(200%);
            border-radius: 20px; padding: 20px 24px; width: 300px;
            text-align: center;
            box-shadow: 0 8px 30px rgba(0,0,0,0.15);
            animation: scaleIn .25s ease;
        }
        .glass-box h3 { margin:0; font-size:1.1rem; font-weight:600; color:#333; }
        .glass-box p  { margin:10px 0 18px; font-size:0.9rem; color:#555; }
        .glass-actions { display:flex; gap:10px; }
        .glass-actions button {
            flex:1; padding:10px 0; border-radius:12px; border:none;
            cursor:pointer; font-weight:500; transition:0.2s ease;
        }
        #glassCancel       { background:rgba(255,255,255,0.6); }
        #glassCancel:hover { background:rgba(255,255,255,0.75); }
        #glassConfirm       { background:rgba(255,74,74,0.8); color:white; }
        #glassConfirm:hover { background:rgba(255,45,45,0.9); }
        @keyframes fadeIn  { from{opacity:0} to{opacity:1} }
        @keyframes scaleIn { from{transform:scale(.85)} to{transform:scale(1)} }
    `;
    document.body.appendChild(style);

    document.getElementById("glassCancel").onclick  = () => modal.remove();
    document.getElementById("glassConfirm").onclick = () => {
        delete chats[chatId];
        saveChats();
        renderRecentChats();
        if (currentChatId === chatId) newChat();
        modal.remove();
    };
}