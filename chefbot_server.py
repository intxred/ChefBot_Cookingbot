from flask import Flask, request, jsonify
from flask_cors import CORS
from google import genai
import re
import os

app = Flask(__name__)
CORS(app)  # Enable CORS for all routes

# === Gemini API Configuration ===
API_KEY = os.getenv("GENAI_API_KEY")

client = genai.Client(api_key=API_KEY)

# === Conversation Storage ===
# In-memory storage for conversations (use Redis/database for production)
conversations = {}

# === System Prompt ===
SYSTEM_PROMPT = """
You are ChefBot, an expert cooking assistant with extensive culinary knowledge.

Your expertise includes:
- Recipe recommendations and instructions
- Cooking techniques and methods
- Ingredient substitutions and alternatives
- Kitchen equipment and tools
- Flavor profiles and food pairings
- Cuisines from around the world
- Dietary restrictions and modifications
- Food storage and preservation
- Meal planning and preparation tips

Guidelines:
1. Only answer questions related to cooking, food, recipes, ingredients, and culinary topics
2. If asked about non-food topics, politely decline with: "Sorry — I can only help with cooking and food. How can I help with a recipe or ingredient?"
3. Be friendly, helpful, and enthusiastic about cooking
4. Provide clear, step-by-step instructions when explaining recipes
5. Offer helpful tips and tricks when appropriate
6. If you don't know something food-related, be honest and suggest alternatives
7. Remember the conversation history and refer back to previous messages when relevant

Remember: You are a cooking expert, not a general-purpose assistant.
"""

def clean_markdown(text: str) -> str:
    """
    Removes Markdown formatting from text to make responses cleaner.
    
    Args:
        text: Raw text with potential Markdown formatting
        
    Returns:
        Cleaned text without Markdown syntax
    """
    # Remove bold (**text** or __text__)
    text = re.sub(r'\*\*(.*?)\*\*', r'\1', text)
    text = re.sub(r'__(.*?)__', r'\1', text)
    
    # Remove italic (*text* or _text_)
    text = re.sub(r'\*(.*?)\*', r'\1', text)
    text = re.sub(r'_(.*?)_', r'\1', text)
    
    # Remove bullet points at start of lines
    text = re.sub(r'^\*\s+', '', text, flags=re.MULTILINE)
    text = re.sub(r'^-\s+', '', text, flags=re.MULTILINE)
    text = re.sub(r'^•\s+', '', text, flags=re.MULTILINE)
    
    # Remove numbered lists
    text = re.sub(r'^\d+\.\s+', '', text, flags=re.MULTILINE)
    
    # Remove code blocks
    text = re.sub(r'```.*?```', '', text, flags=re.DOTALL)
    text = re.sub(r'`(.*?)`', r'\1', text)
    
    # Remove headers (# ## ###)
    text = re.sub(r'^#{1,6}\s+', '', text, flags=re.MULTILINE)
    
    # Clean up extra whitespace
    text = re.sub(r'\n{3,}', '\n\n', text)
    
    return text.strip()

def get_conversation_history(session_id: str) -> list:
    """
    Retrieves conversation history for a given session
    
    Args:
        session_id: Unique identifier for the conversation session
        
    Returns:
        List of conversation messages
    """
    if session_id not in conversations:
        conversations[session_id] = []
    return conversations[session_id]

def build_conversation_prompt(session_id: str, user_input: str) -> str:
    """
    Builds a full prompt including system prompt and conversation history
    
    Args:
        session_id: Unique identifier for the conversation session
        user_input: Latest user message
        
    Returns:
        Full prompt string with conversation context
    """
    history = get_conversation_history(session_id)
    
    # Start with system prompt
    prompt = SYSTEM_PROMPT + "\n\n"
    
    # Add conversation history
    if history:
        prompt += "Previous conversation:\n"
        for msg in history:
            prompt += f"{msg['role']}: {msg['content']}\n"
        prompt += "\n"
    
    # Add current user input
    prompt += f"User: {user_input}\n\nChefBot:"
    
    return prompt

def save_to_history(session_id: str, user_input: str, bot_response: str):
    """
    Saves user message and bot response to conversation history
    
    Args:
        session_id: Unique identifier for the conversation session
        user_input: User's message
        bot_response: Bot's response
    """
    history = get_conversation_history(session_id)
    
    # Add user message
    history.append({
        "role": "User",
        "content": user_input
    })
    
    # Add bot response
    history.append({
        "role": "ChefBot",
        "content": bot_response
    })
    
    # Optional: Limit history to last 20 messages (10 exchanges) to avoid token limits
    if len(history) > 20:
        conversations[session_id] = history[-20:]

@app.route("/", methods=["GET"])
def home():
    """
    Health check endpoint
    """
    return jsonify({
        "status": "running",
        "message": "ChefBot Flask server is running",
        "version": "2.0.0",
        "features": ["conversation_memory", "session_management"]
    })

@app.route("/chat", methods=["POST"])
def chat():
    """
    Main chat endpoint for processing user messages with conversation memory
    
    Expected JSON payload:
    {
        "user_input": "How do I make pasta carbonara?",
        "session_id": "unique-session-id"  // Optional, will be generated if not provided
    }
    
    Returns JSON response:
    {
        "response": "To make pasta carbonara...",
        "session_id": "unique-session-id"
    }
    """
    try:
        # Get user input from request
        data = request.json
        user_input = data.get("user_input", "").strip()
        session_id = data.get("session_id", "default")
        
        # Validate input
        if not user_input:
            return jsonify({
                "error": "No input provided",
                "message": "Please provide a message in 'user_input' field"
            }), 400
        
        # Build the full prompt with conversation history
        full_prompt = build_conversation_prompt(session_id, user_input)
        
        # Call Gemini API
        response = client.models.generate_content(
            model="gemini-2.5-flash",
            contents=full_prompt,
            config={
                "temperature": 0.7,
                "top_p": 0.95,
                "top_k": 40,
                "max_output_tokens": 2048,
            }
        )
        
        # Extract and clean response text
        if response and response.text:
            clean_response = clean_markdown(response.text)
            
            # Save conversation to history
            save_to_history(session_id, user_input, clean_response)
            
            return jsonify({
                "response": clean_response,
                "session_id": session_id
            })
        else:
            return jsonify({
                "error": "Empty response from AI",
                "message": "The AI didn't generate a response. Please try again."
            }), 500
            
    except Exception as e:
        error_msg = str(e)
        
        # Check for quota/rate limit errors
        if "RESOURCE_EXHAUSTED" in error_msg or "429" in error_msg or "quota" in error_msg.lower():
            return jsonify({
                "error": "Quota exceeded",
                "response": "ChefBot has reached its daily cooking limit, Please try again tomorrow."
            }), 200  # Return 200 so the frontend shows the message properly
        
        # Generic error
        return jsonify({
            "error": "Failed to generate response",
            "response": f"Sorry, I encountered an error: {error_msg}. Please try again.",
        }), 200  # Return 200 so error message displays properly


@app.route("/clear", methods=["POST"])
def clear_conversation():
    """
    Clears conversation history for a session
    
    Expected JSON payload:
    {
        "session_id": "unique-session-id"
    }
    """
    try:
        data = request.json
        session_id = data.get("session_id", "default")
        
        if session_id in conversations:
            del conversations[session_id]
            return jsonify({
                "message": "Conversation history cleared",
                "session_id": session_id
            })
        else:
            return jsonify({
                "message": "No conversation found for this session",
                "session_id": session_id
            })
            
    except Exception as e:
        return jsonify({
            "error": "Failed to clear conversation",
            "message": str(e)
        }), 500


@app.route("/history", methods=["GET"])
def get_history():
    """
    Gets conversation history for a session
    
    Query parameter:
        session_id: unique-session-id (default: "default")
    """
    try:
        session_id = request.args.get("session_id", "default")
        history = get_conversation_history(session_id)
        
        return jsonify({
            "session_id": session_id,
            "message_count": len(history),
            "history": history
        })
            
    except Exception as e:
        return jsonify({
            "error": "Failed to retrieve history",
            "message": str(e)
        }), 500


@app.route("/health", methods=["GET"])
def health():
    """
    Detailed health check endpoint
    """
    return jsonify({
        "status": "healthy",
        "api_configured": bool(API_KEY),
        "active_sessions": len(conversations),
        "endpoints": {
            "chat": "/chat (POST)",
            "clear": "/clear (POST)",
            "history": "/history (GET)",
            "health": "/health (GET)",
            "home": "/ (GET)"
        }
    })

# Error handlers
@app.errorhandler(404)
def not_found(e):
    return jsonify({
        "error": "Endpoint not found",
        "message": "The requested endpoint does not exist",
        "available_endpoints": ["/", "/chat", "/clear", "/history", "/health"]
    }), 404

@app.errorhandler(405)
def method_not_allowed(e):
    return jsonify({
        "error": "Method not allowed",
        "message": "This endpoint does not support the requested HTTP method"
    }), 405

@app.errorhandler(500)
def internal_error(e):
    return jsonify({
        "error": "Internal server error",
        "message": "An unexpected error occurred on the server"
    }), 500

if __name__ == "__main__":
    print("=" * 50)
    print("🍳 ChefBot Flask Server Starting...")
    print("=" * 50)
    print(f"📡 Server running at: http://127.0.0.1:5000")
    print(f"🤖 AI Model: gemini-2.5-flash")
    print(f"✅ API Key configured: {'Yes' if API_KEY else 'No'}")
    print(f"💬 Conversation memory: Enabled")
    print("=" * 50)
    print("📍 Available endpoints:")
    print("   GET  /          - Server info")
    print("   GET  /health    - Health check")
    print("   POST /chat      - Chat with ChefBot")
    print("   POST /clear     - Clear conversation history")
    print("   GET  /history   - View conversation history")
    print("=" * 50)
    print("🔥 Ready to cook! Press CTRL+C to stop.")
    print("=" * 50)
    
    # Run Flask server
    app.run(
        host="0.0.0.0",
        port=5000,
        debug=True,
        use_reloader=True
    )