const API_URL = 'http://localhost/SlackApp/api/messages.php';
let currentMessageId = null;

// Load messages on page load
document.addEventListener('DOMContentLoaded', () => {
  loadMessages();
});

// Fetch messages from the database
async function loadMessages() {
  try {
    const response = await fetch(`${API_URL}`);
    const messages = await response.json();

    const messagesList = document.getElementById('messagesList');
    messagesList.innerHTML = '';

    messages.forEach((message) => {
      const messageDiv = document.createElement('div');
      messageDiv.classList.add('message');
      messageDiv.textContent = message.message;
      messageDiv.dataset.id = message.id;
      messageDiv.addEventListener('click', () => loadMessageIntoEditor(message));
      messagesList.appendChild(messageDiv);
    });
  } catch (error) {
    console.error('Error loading messages:', error);
  }
}

// Load a message into the editor for editing
function loadMessageIntoEditor(message) {
  document.getElementById('messageEditor').value = message.message;
  currentMessageId = message.id;

  document.getElementById('deleteMessage').style.display = 'inline';
}

// Save the current message (add new or update existing)
document.getElementById('saveMessage').addEventListener('click', async () => {
  const messageContent = document.getElementById('messageEditor').value;

  if (!messageContent.trim()) {
    alert('Message cannot be empty!');
    return;
  }

  try {
    const method = currentMessageId ? 'PUT' : 'POST';
    const endpoint = currentMessageId ? `${API_URL}/${currentMessageId}` : API_URL;

    await fetch(endpoint, {
      method,
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ message: messageContent }),
    });

    alert('Message saved successfully!');
    currentMessageId = null;
    document.getElementById('messageEditor').value = '';
    document.getElementById('deleteMessage').style.display = 'none';
    loadMessages();
  } catch (error) {
    console.error('Error saving message:', error);
  }
});

// Delete the current message
document.getElementById('deleteMessage').addEventListener('click', async () => {
  if (!currentMessageId) return;

  try {
    await fetch(`${API_URL}/${currentMessageId}`, { method: 'DELETE' });
    alert('Message deleted successfully!');
    currentMessageId = null;
    document.getElementById('messageEditor').value = '';
    document.getElementById('deleteMessage').style.display = 'none';
    loadMessages();
  } catch (error) {
    console.error('Error deleting message:', error);
  }
});
