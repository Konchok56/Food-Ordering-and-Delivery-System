<?php
// templates/chatbot.php
$base_url = !file_exists('core/db.php') ? '../' : '';
?>
<style>
  /* Chatbot FAB */
  .chat-fab {
    position: fixed;
    bottom: 200px;
    right: 40px;
    z-index: 9999;
    width: 64px;
    height: 64px;
    border: none;
    border-radius: 50%;
    background: linear-gradient(135deg, #4f46e5, #4338ca);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    cursor: pointer;
    box-shadow: 0 10px 40px rgba(79, 70, 229, 0.4);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  }
  .chat-fab:hover {
    transform: scale(1.1) translateY(-4px);
    box-shadow: 0 14px 44px rgba(79, 70, 229, 0.55);
  }

  /* Chat Window */
  .chat-window {
    position: fixed;
    bottom: 280px;
    right: 40px;
    width: 350px;
    height: 500px;
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 15px 50px rgba(0,0,0,0.15);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    z-index: 10000;
    opacity: 0;
    visibility: hidden;
    transform: translateY(20px) scale(0.95);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    transform-origin: bottom right;
    border: 1px solid rgba(0,0,0,0.05);
  }
  .chat-window.active {
    opacity: 1;
    visibility: visible;
    transform: translateY(0) scale(1);
  }

  /* Chat Header */
  .chat-header {
    background: linear-gradient(135deg, #4f46e5, #4338ca);
    color: #fff;
    padding: 18px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }
  .chat-header-info {
    display: flex;
    align-items: center;
    gap: 12px;
  }
  .chat-avatar {
    width: 40px;
    height: 40px;
    background: rgba(255,255,255,0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
  }
  .chat-title {
    font-weight: 700;
    font-size: 1.1rem;
    font-family: 'Syne', sans-serif;
  }
  .chat-status {
    font-size: 0.8rem;
    opacity: 0.9;
    display: flex;
    align-items: center;
    gap: 6px;
  }
  .chat-status::before {
    content: '';
    display: block;
    width: 8px;
    height: 8px;
    background: #10b981;
    border-radius: 50%;
  }
  .chat-close {
    background: transparent;
    border: none;
    color: #fff;
    font-size: 1.5rem;
    cursor: pointer;
    opacity: 0.8;
    transition: opacity 0.2s;
  }
  .chat-close:hover {
    opacity: 1;
  }

  /* Chat Body */
  .chat-body {
    flex: 1;
    padding: 20px;
    overflow-y: auto;
    background: #f8fafc;
    display: flex;
    flex-direction: column;
    gap: 16px;
  }
  .chat-msg {
    max-width: 85%;
    display: flex;
    flex-direction: column;
    gap: 4px;
  }
  .chat-msg.bot {
    align-self: flex-start;
  }
  .chat-msg.user {
    align-self: flex-end;
  }
  .chat-bubble {
    padding: 12px 16px;
    border-radius: 16px;
    font-size: 0.95rem;
    line-height: 1.4;
  }
  .chat-msg.bot .chat-bubble {
    background: #fff;
    color: #1e293b;
    border-bottom-left-radius: 4px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    border: 1px solid rgba(0,0,0,0.02);
  }
  .chat-msg.user .chat-bubble {
    background: #4f46e5;
    color: #fff;
    border-bottom-right-radius: 4px;
    box-shadow: 0 2px 8px rgba(79, 70, 229, 0.2);
  }
  .chat-time {
    font-size: 0.75rem;
    color: #94a3b8;
    align-self: flex-end;
  }
  .chat-msg.bot .chat-time {
    align-self: flex-start;
  }

  /* Quick Chips */
  .chat-chips-wrap {
    align-self: flex-start;
    width: 100%;
  }
  .chat-chips-label {
    font-size: 0.75rem;
    font-weight: 700;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
    margin-top: 4px;
    padding-left: 2px;
  }
  .chat-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
  }
  .chat-chip {
    background: #e0e7ff;
    color: #4f46e5;
    border: 1px solid #c7d2fe;
    border-radius: 999px;
    padding: 8px 14px;
    font-size: 0.85rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s;
  }
  .chat-chip:hover {
    background: #4f46e5;
    color: #fff;
    transform: translateY(-2px);
  }

  /* Chat Typing Indicator */
  .typing-indicator {
    display: flex;
    gap: 4px;
    padding: 16px 20px;
    background: #fff;
    border-radius: 16px;
    border-bottom-left-radius: 4px;
    width: fit-content;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    align-self: flex-start;
    display: none;
  }
  .typing-indicator.active {
    display: flex;
  }
  .typing-dot {
    width: 6px;
    height: 6px;
    background: #94a3b8;
    border-radius: 50%;
    animation: typingBounce 1.4s infinite ease-in-out both;
  }
  .typing-dot:nth-child(1) { animation-delay: -0.32s; }
  .typing-dot:nth-child(2) { animation-delay: -0.16s; }
  @keyframes typingBounce {
    0%, 80%, 100% { transform: scale(0); }
    40% { transform: scale(1); }
  }

  /* Chat Footer */
  .chat-footer {
    padding: 16px;
    background: #fff;
    border-top: 1px solid #e2e8f0;
    display: flex;
    gap: 12px;
  }
  .chat-input {
    flex: 1;
    border: 1px solid #cbd5e1;
    border-radius: 24px;
    padding: 12px 16px;
    font-size: 0.95rem;
    outline: none;
    transition: border-color 0.2s;
    font-family: inherit;
  }
  .chat-input:focus {
    border-color: #4f46e5;
  }
  .chat-send {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: #4f46e5;
    color: #fff;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background 0.2s, transform 0.2s;
  }
  .chat-send:hover {
    background: #4338ca;
    transform: scale(1.05);
  }
</style>

<button class="chat-fab" id="chatFab" aria-label="Open support chat">
  <i class="fa-solid fa-comment"></i>
</button>

<div class="chat-window" id="chatWindow">
  <div class="chat-header">
    <div class="chat-header-info">
      <div class="chat-avatar"><i class="fa-solid fa-robot"></i></div>
      <div>
        <div class="chat-title">SwiftBite AI</div>
        <div class="chat-status">Online and ready to help</div>
      </div>
    </div>
    <button class="chat-close" id="chatClose">&times;</button>
  </div>
  
  <div class="chat-body" id="chatBody">
    <div class="chat-msg bot">
      <div class="chat-bubble">Hi there! <i class="fa-solid fa-hand-wave"></i> I'm the SwiftBite AI assistant. How can I help you today?</div>
      <div class="chat-time">Just now</div>
    </div>
    
    <div class="chat-chips-wrap" id="chatChips">
      <div class="chat-chips-label">Quick questions</div>
      <div class="chat-chips">
        <button class="chat-chip"><i class="fa-solid fa-box"></i> Where is my order?</button>
        <button class="chat-chip"><i class="fa-solid fa-pizza-slice"></i> Recommend me food</button>
        <button class="chat-chip"><i class="fa-solid fa-cart-shopping"></i> Add burger to cart</button>
      </div>
    </div>

    <div class="typing-indicator" id="typingIndicator">
      <div class="typing-dot"></div>
      <div class="typing-dot"></div>
      <div class="typing-dot"></div>
    </div>
  </div>

  <div class="chat-footer">
    <input type="text" class="chat-input" id="chatInput" placeholder="Type your message..." autocomplete="off">
    <button class="chat-send" id="chatSend">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
    </button>
  </div>
</div>

<script>
  window.swiftBiteBaseUrl = '<?php echo $base_url; ?>';
</script>
<script src="<?php echo $base_url; ?>assets/js/chatbot.js?v=2"></script>
