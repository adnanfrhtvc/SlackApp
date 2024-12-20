const API_URL = 'http://localhost/SlackApp/api/messages.php';
let currentMessageId = null;

// Initialize Quill editor
const quill = new Quill('#editor', {
  theme: 'snow',
  placeholder: 'Write your message here...',
  modules: {
    toolbar: [
      ['bold', 'italic', 'underline', 'strike'],
      ['blockquote', 'code-block'],
      [{ 'list': 'ordered' }, { 'list': 'bullet' }],
      [{ 'script': 'sub' }, { 'script': 'super' }],
      [{ 'indent': '-1' }, { 'indent': '+1' }],
      [{ 'direction': 'rtl' }],
      [{ 'size': ['small', false, 'large', 'huge'] }],
      [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
      [{ 'color': [] }, { 'background': [] }],
      [{ 'font': [] }],
      [{ 'align': [] }],
      ['clean'], ['emoji']
    ],
  },
});

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
      messageDiv.innerHTML = message.message; // Render rich text
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
  quill.root.innerHTML = message.message;
  currentMessageId = message.id;

  document.getElementById('deleteMessage').style.display = 'inline';
  document.getElementById('gifResults').innerHTML = ''; // Clear GIF results
}

// Save the current message (add new or update existing)
document.getElementById('saveMessage').addEventListener('click', async () => {
  const messageContent = quill.root.innerHTML;

  if (!messageContent.trim()) {
    alert('Message cannot be empty!');
    return;
  }

  try {
    const method = currentMessageId ? 'PUT' : 'POST';
    const endpoint = API_URL; // Use the same endpoint for both POST and PUT

    const body = currentMessageId
      ? { id: currentMessageId, message: messageContent }
      : { message: messageContent };

    const response = await fetch(endpoint, {
      method,
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body),
    });

    const result = await response.json();
    if (result.success) {
      alert('Message saved successfully!');
      currentMessageId = null;
      quill.setContents([{ insert: '\n' }]); // Clear the editor
      document.getElementById('deleteMessage').style.display = 'none';
      document.getElementById('gifResults').innerHTML = ''; // Clear GIF results
      loadMessages();
    } else {
      alert(`Error saving message: ${result.error}`);
    }
  } catch (error) {
    console.error('Error saving message:', error);
  }
});

// Delete the current message
document.getElementById('deleteMessage').addEventListener('click', async () => {
  if (!currentMessageId) return;

  try {
    const response = await fetch(`${API_URL}?id=${currentMessageId}`, { method: 'DELETE' });
    const result = await response.json();

    if (result.success) {
      alert('Message deleted successfully!');
      currentMessageId = null;
      quill.root.innerHTML = '';
      document.getElementById('deleteMessage').style.display = 'none';
      loadMessages();
    } else {
      alert(`Error deleting message: ${result.error}`);
    }
  } catch (error) {
    console.error('Error deleting message:', error);
  }
});

// GIF search functionality
document.getElementById('gifSearchButton').addEventListener('click', async () => {
  const query = document.getElementById('gifSearchInput').value.trim();
  if (!query) return;

  try {
    const response = await fetch(`https://api.giphy.com/v1/gifs/search?api_key=S4HoqaCPQVXehbVm4Knwxr8GptE95a4V&q=${query}&limit=10`);
    const data = await response.json();

    const gifResults = document.getElementById('gifResults');
    gifResults.innerHTML = ''; // Clear previous GIF results

    data.data.forEach((gif) => {
      const img = document.createElement('img');
      img.src = gif.images.fixed_height.url;
      img.alt = gif.title;
      img.addEventListener('click', () => addGifToEditor(gif.images.fixed_height.url));
      gifResults.appendChild(img);
    });

    document.getElementById('gifSearchInput').value = ''; // Clear the search bar
  } catch (error) {
    console.error('Error fetching GIFs:', error);
  }
});

function addGifToEditor(gifUrl) {
  const range = quill.getSelection(); // Get current cursor position in the editor
  if (range) {
    quill.insertEmbed(range.index, 'image', gifUrl); // Insert the GIF as an image
    quill.setSelection(range.index + 1); // Move the cursor after the inserted image
  } else {
    // If no selection, append the GIF to the end
    quill.insertEmbed(quill.getLength(), 'image', gifUrl);
  }
}
