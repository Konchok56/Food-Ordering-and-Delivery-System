document.addEventListener('DOMContentLoaded', function() {
  const chatFab = document.getElementById('chatFab');
  const chatWindow = document.getElementById('chatWindow');
  const chatClose = document.getElementById('chatClose');
  const chatInput = document.getElementById('chatInput');
  const chatSend = document.getElementById('chatSend');
  const chatBody = document.getElementById('chatBody');
  const typingIndicator = document.getElementById('typingIndicator');
  const chatChips = document.getElementById('chatChips');

  const baseUrl = window.swiftBiteBaseUrl || '';

  // Toggle chat window
  chatFab.addEventListener('click', () => {
    chatWindow.classList.add('active');
    chatInput.focus();
  });

  chatClose.addEventListener('click', () => {
    chatWindow.classList.remove('active');
  });

  // Send message on Enter key
  chatInput.addEventListener('keypress', function (e) {
    if (e.key === 'Enter') {
      sendMessage(chatInput.value);
    }
  });

  // Send message on button click
  chatSend.addEventListener('click', () => sendMessage(chatInput.value));

  // Quick Reply Chips
  if (chatChips) {
    const chips = chatChips.querySelectorAll('.chat-chip');
    chips.forEach(chip => {
      chip.addEventListener('click', () => {
        // Strip out the emoji from the beginning of the chip text for cleaner sending
        const text = chip.textContent.replace(/^[^\w\s]+/, '').trim();
        sendMessage(text);
      });
    });
  }

  function sendMessage(textParam = null) {
    const message = (textParam || chatInput.value).trim();
    if (message === '') return;

    // Hide chips once user starts interacting
    if (chatChips) {
      chatChips.style.display = 'none';
    }

    // Add user message to UI
    appendMessage('user', message);
    chatInput.value = '';

    // Show typing indicator
    typingIndicator.classList.add('active');
    scrollToBottom();

    // Send to backend API
    fetch(baseUrl + 'actions/chat_api.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ message: message })
    })
    .then(response => response.json())
    .then(data => {
      typingIndicator.classList.remove('active');
      if (data && data.reply) {
        appendMessage('bot', data.reply);
      } else {
        appendMessage('bot', "Sorry, I didn't understand that.");
      }
    })
    .catch(error => {
      console.error('Error:', error);
      typingIndicator.classList.remove('active');
      appendMessage('bot', "Oops! Something went wrong. Please try again later.");
    });
  }

  function appendMessage(sender, text) {
    const msgDiv = document.createElement('div');
    msgDiv.className = `chat-msg ${sender}`;
    
    const bubble = document.createElement('div');
    bubble.className = 'chat-bubble';
    bubble.innerHTML = text; // allow basic HTML like <br> or <b> from bot
    
    const time = document.createElement('div');
    time.className = 'chat-time';
    const now = new Date();
    time.textContent = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

    msgDiv.appendChild(bubble);
    msgDiv.appendChild(time);
    
    // Insert before typing indicator
    chatBody.insertBefore(msgDiv, typingIndicator);
    scrollToBottom();
  }

  function scrollToBottom() {
    chatBody.scrollTop = chatBody.scrollHeight;
  }
});
