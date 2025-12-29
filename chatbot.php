<button id="chatbot-toggler" onclick="toggleChatbot()">
    <i class="fas fa-robot"></i> Chat Help
</button>

<div class="chatbot-widget-container" id="chatbot-widget">
    <div class="chatbot-header">
        <span><i class="fas fa-robot"></i> Recipe Assistant</span>
        <button onclick="toggleChatbot()" style="background:none; border:none; color:white; cursor:pointer;">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <div class="chatbot-keyword-buttons">
        <button onclick="sendKeyword('pizza')"> Pizza</button>
        <button onclick="sendKeyword('cake')"> Cake</button>
        <button onclick="sendKeyword('non-veg')"> Non-Veg</button>
        <button onclick="sendKeyword('soups')"> Soups</button>
        <button onclick="sendKeyword('salad')"> Salad</button>
        <button onclick="sendKeyword('breakfast')"> Breakfast</button>
        <button onclick="sendKeyword('baking tips')"> Baking</button>
    </div>

    <div class="chatbot-messages" id="chat-area">
        <div class="chat-message bot">Hello! Ask me for recipe tips.</div>
    </div>

    <div class="chatbot-input-area">
        <input type="text" id="bot-user-input" placeholder="Ask something..." onkeypress="handleEnter(event)" />
        <button onclick="sendBotMessage()"><i class="fas fa-paper-plane"></i></button>
    </div>
</div>

<style>
    /* Toggle Button - Matches your Cart Button Style */
    #chatbot-toggler {
        position: fixed;
        bottom: 20px;
        right: 20px;
        background-color: #000; /* Black background */
        color: #fff;
        border: none;
        border-radius: 30px; /* Pill shape */
        padding: 12px 24px;
        font-size: 16px;
        font-weight: bold;
        cursor: pointer;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        z-index: 9999;
        transition: transform 0.3s ease, background-color 0.3s;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    #chatbot-toggler:hover {
        background-color: #333;
        transform: scale(1.05);
    }

    /* Chat Window Container */
    .chatbot-widget-container {
        position: fixed;
        bottom: 80px; /* Just above the button */
        right: 20px;
        width: 350px;
        height: 500px;
        background: #fff;
        border-radius: 15px;
        box-shadow: 0 5px 25px rgba(0,0,0,0.2);
        z-index: 9999;
        display: none; /* Hidden by default */
        flex-direction: column;
        overflow: hidden;
        font-family: 'Arial', sans-serif;
        border: 1px solid #e0e0e0;
    }

    /* Header */
    .chatbot-header {
        background-color: #000;
        color: #fff;
        padding: 15px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-weight: bold;
    }

    /* Quick Action Buttons Area */
    .chatbot-keyword-buttons {
        padding: 10px;
        background: #f8f8f8;
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
        justify-content: center;
        border-bottom: 1px solid #eee;
    }

    .chatbot-keyword-buttons button {
        background-color: #fff;
        border: 1px solid #ddd;
        border-radius: 15px;
        padding: 5px 10px;
        font-size: 12px;
        cursor: pointer;
        transition: 0.2s;
    }

    .chatbot-keyword-buttons button:hover {
        background-color: #000;
        color: white;
    }

    /* Chat Area */
    .chatbot-messages {
        flex: 1;
        padding: 15px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 10px;
        background-color: #fff;
    }

    .chat-message {
        padding: 8px 12px;
        border-radius: 12px;
        max-width: 80%;
        font-size: 14px;
        line-height: 1.4;
    }

    .chat-message.user {
        background-color: #000;
        color: #fff;
        align-self: flex-end;
        border-bottom-right-radius: 2px;
    }

    .chat-message.bot {
        background-color: #f1f1f1;
        color: #333;
        align-self: flex-start;
        border-bottom-left-radius: 2px;
    }

    /* Input Area */
    .chatbot-input-area {
        padding: 10px;
        border-top: 1px solid #eee;
        display: flex;
        gap: 10px;
        background: #fff;
    }

    .chatbot-input-area input {
        flex: 1;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 20px;
        outline: none;
    }

    .chatbot-input-area button {
        background-color: #000;
        color: white;
        border: none;
        border-radius: 50%;
        width: 40px;
        height: 40px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
    }
</style>

<script>
    // --- Toggle Visibility ---
    function toggleChatbot() {
        const widget = document.getElementById('chatbot-widget');
        if (widget.style.display === 'none' || widget.style.display === '') {
            widget.style.display = 'flex';
        } else {
            widget.style.display = 'none';
        }
    }

    // --- Chat Logic ---
    const chatArea = document.getElementById("chat-area");

    // EXPANDED DATA: Covers all categories from your index.php
    const botResponses = {
        // --- Category: Pizza ---
        "pizza": "For the best pizza, cook at the highest temperature your oven allows (475°F-500°F). Use a pizza stone if you have one!",
        "dough": "Let your pizza dough rise slowly in the fridge for 24 hours to develop more flavor.",
        "crust": "Want a crispy crust? Brush the edges with a little olive oil before baking.",

        // --- Category: Cake ---
        "cake": "Baking cakes? Make sure your butter and eggs are at room temperature so they mix evenly.",
        "frosting": "Wait until the cake is completely cool before frosting, or the icing will melt right off!",
        "baking tips": "Don't open the oven door too often while baking! It releases heat and can make your cake collapse.",

        // --- Category: Non-Veg ---
        "non-veg": "Cooking meat? Always let it rest for 5-10 minutes after cooking. This keeps the juices inside.",
        "chicken": "For juicy chicken breast, try pounding it to an even thickness before grilling or frying.",
        "fish": "Fish cooks fast! It's done when it flakes easily with a fork. Don't overcook it.",

        // --- Category: Soups ---
        "soups": "The secret to great soup is a good stock. Simmer it low and slow. If it's too salty, add a potato slice to absorb the salt.",
        "stew": "Stews taste better the next day! The flavors have more time to meld together.",

        // --- Category: Salad ---
        "salad": "Keep your salad crisp by drying the leaves thoroughly. Wet leaves make the dressing slide off.",
        "dressing": "Add dressing just before serving to prevent your salad from getting soggy.",

        // --- Category: Breakfast ---
        "breakfast": "For fluffier pancakes, don't overmix the batter. Lumps are okay! Let the batter rest for 5 mins.",
        "eggs": "Scrambled eggs? Cook them on low heat and take them off the stove while they still look slightly wet.",

        // --- Category: Lunch & Dinner ---
        "lunch": "Wraps and grain bowls are perfect for quick lunches. Keep sauces separate until you eat.",
        "dinner": "Stuck on dinner ideas? Roasting vegetables (400°F) is the easiest side dish. Add protein and you're set.",

        // --- General Tech / Default ---
        "hello": "Hi there! I can help you with cooking tips for Pizza, Cake, Soups, and more.",
        "default": "I'm not sure about that specific recipe, but I can give you tips on Pizza, Cake, Non-Veg, Soups, or Salads. Try clicking a button!"
    };

    function appendBotMessage(text, sender) {
        const msg = document.createElement("div");
        msg.className = `chat-message ${sender}`;
        msg.innerText = text;
        chatArea.appendChild(msg);
        chatArea.scrollTop = chatArea.scrollHeight;
    }

    function sendBotMessage() {
        const input = document.getElementById("bot-user-input");
        const text = input.value.trim();
        if (text !== "") {
            appendBotMessage(text, "user");
            input.value = "";
            
            // Simulate thinking delay
            setTimeout(() => {
                const lowerText = text.toLowerCase();
                let response = botResponses["default"];
                
                // Improved keyword matching
                // It now checks if the user's sentence contains any key from our list
                for (let key in botResponses) {
                    if (lowerText.includes(key)) {
                        response = botResponses[key];
                        break; 
                    }
                }
                appendBotMessage(response, "bot");
            }, 500);
        }
    }

    function sendKeyword(keyword) {
        // Send the button text as a user message
        appendBotMessage(keyword, "user");
        setTimeout(() => {
            // Look up the response (converting to lowercase to match keys)
            const searchKey = keyword.toLowerCase().replace("🍕 ", "").replace("🍰 ", "").replace("🍗 ", "").replace("🥣 ", "").replace("🥗 ", "").replace("🍳 ", "").replace("👩‍🍳 ", "");
            
            // Find response or default
            let response = botResponses["default"];
            if (botResponses[searchKey]) {
                response = botResponses[searchKey];
            } else {
                // Fallback search if exact key missing
                for (let key in botResponses) {
                    if (searchKey.includes(key)) {
                        response = botResponses[key];
                        break;
                    }
                }
            }
            
            appendBotMessage(response, "bot");
        }, 500);
    }

    function handleEnter(event) {
        if (event.key === "Enter") {
            sendBotMessage();
        }
    }
</script>