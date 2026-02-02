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
7. Don't forget and remember the previous conversation or response.

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

@app.route("/", methods=["GET"])
def home():
    """
    Health check endpoint
    """
    return jsonify({
        "status": "running",
        "message": "ChefBot Flask server is running",
        "version": "1.0.0"
    })

@app.route("/chat", methods=["POST"])
def chat():
    """
    Main chat endpoint for processing user messages
    
    Expected JSON payload:
    {
        "user_input": "How do I make pasta carbonara?"
    }
    
    Returns JSON response:
    {
        "response": "To make pasta carbonara..."
    }
    """
    try:
        # Get user input from request
        data = request.json
        user_input = data.get("user_input", "").strip()
        
        # Validate input
        if not user_input:
            return jsonify({
                "error": "No input provided",
                "message": "Please provide a message in 'user_input' field"
            }), 400
        
        # Build the full prompt
        full_prompt = f"{SYSTEM_PROMPT}\n\nUser: {user_input}\n\nChefBot:"
        
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
            return jsonify({"response": clean_response})
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
                "response": "ChefBot has reached its daily cooking limit 🍳🔥 Please try again tomorrow or use a different API key."
            }), 200  # Return 200 so the frontend shows the message properly
        
        # Generic error
        return jsonify({
            "error": "Failed to generate response",
            "response": f"Sorry, I encountered an error: {error_msg}. Please try again.",
        }), 200  # Return 200 so error message displays properly


@app.route("/health", methods=["GET"])
def health():
    """
    Detailed health check endpoint
    """
    return jsonify({
        "status": "healthy",
        "api_configured": bool(API_KEY),
        "endpoints": {
            "chat": "/chat (POST)",
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
        "available_endpoints": ["/", "/chat", "/health"]
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
    print("=" * 50)
    print("📍 Available endpoints:")
    print("   GET  /          - Server info")
    print("   GET  /health    - Health check")
    print("   POST /chat      - Chat with ChefBot")
    print("=" * 50)
    print("🔥 Ready to cook! Press CTRL+C to stop.")
    print("=" * 50)
    
    # Run Flask server
    app.run(
        host="127.0.0.1",
        port=5000,
        debug=True,
        use_reloader=True
    )