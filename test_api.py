from google import genai

API_KEY = "AIzaSyD0A6A-YAQ4u5wt7TWetKY2v8ZSZ_9wZPs"  # your new key
client = genai.Client(api_key=API_KEY)

try:
    # Try to list models (simple API call) to verify the key works
    models = client.models.list()
    print("✅ API key is working! Available models:")
    for model in models:
        print(f" - {model.name}")
except Exception as e:
    print("❌ API key not working or quota exceeded.")
    print("Error:", e)
