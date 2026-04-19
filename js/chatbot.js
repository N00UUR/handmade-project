(function () {
  var widget = document.querySelector('[data-chatbot-widget]');

  if (!widget) {
    return;
  }

  var toggle = widget.querySelector('[data-chatbot-toggle]');
  var closeButton = widget.querySelector('[data-chatbot-close]');
  var panel = widget.querySelector('[data-chatbot-panel]');
  var content = widget.querySelector('[data-chatbot-content]');
  var homeView = widget.querySelector('[data-chatbot-home]');
  var conversationView = widget.querySelector('[data-chatbot-conversation]');
  var welcomeMessages = widget.querySelector('[data-chatbot-welcome-messages]');
  var messages = widget.querySelector('[data-chatbot-messages]');
  var optionsContainer = widget.querySelector('[data-chatbot-options]');
  var topicLabel = widget.querySelector('[data-chatbot-topic-label]');
  var resetButton = widget.querySelector('[data-chatbot-reset]');
  var chatForm = widget.querySelector('[data-chatbot-chat-form]');
  var messageInput = widget.querySelector('#chatbot-message');
  var resultsContainer = widget.querySelector('[data-chatbot-search-results]');
  var configUrl = widget.getAttribute('data-config-url');
  var issueUrl = widget.getAttribute('data-issue-url');
  var messageUrl = widget.getAttribute('data-message-url');
  var productSearchUrl = widget.getAttribute('data-product-search-url');
  var initialWelcomeMessage = widget.getAttribute('data-welcome-message') || 'Welcome. Choose a topic to start.';
  var chatbotData = null;
  var activeOption = null;

  function setPanelState(open) {
    panel.hidden = !open;
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
  }

  function scrollToBottom() {
    content.scrollTop = content.scrollHeight;
  }

  function appendBubble(container, text, type) {
    var bubble = document.createElement('div');
    bubble.className = 'chatbot-bubble ' + (type === 'user' ? 'chatbot-bubble-user' : 'chatbot-bubble-system');
    bubble.textContent = text;
    container.appendChild(bubble);
    scrollToBottom();
  }

  function clearContainer(container) {
    container.innerHTML = '';
  }

  function escapeHtml(text) {
    return String(text || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function formatPrice(price) {
    var numeric = Number(price);

    if (Number.isNaN(numeric)) {
      return price;
    }

    return '$' + numeric.toFixed(2);
  }

  function renderSearchResults(products) {
    resultsContainer.innerHTML = '';

    products.forEach(function (product) {
      var card = document.createElement('article');
      card.className = 'chatbot-result-card';

      var imageHtml = product.image_path
        ? '<img class="chatbot-result-image" src="' + escapeHtml(product.image_path) + '" alt="' + escapeHtml(product.name) + '">'
        : '<div class="chatbot-result-image"></div>';

      card.innerHTML =
        imageHtml +
        '<div>' +
          '<h3 class="chatbot-result-title">' + escapeHtml(product.name) + '</h3>' +
          '<p class="chatbot-result-meta">Category: ' + escapeHtml(product.category) + '</p>' +
          '<p class="chatbot-result-meta">Price: ' + escapeHtml(formatPrice(product.price)) + '</p>' +
          '<p class="chatbot-result-meta">Available: ' + escapeHtml(product.available_count) + '</p>' +
          '<p class="chatbot-result-description">' + escapeHtml(product.description) + '</p>' +
          '<a class="chatbot-link" href="' + escapeHtml(product.product_url) + '">View product</a>' +
        '</div>';

      resultsContainer.appendChild(card);
    });

    resultsContainer.hidden = products.length === 0;
    scrollToBottom();
  }

  function showHomeView() {
    activeOption = null;
    topicLabel.textContent = '';
    homeView.hidden = false;
    conversationView.hidden = true;
    clearContainer(messages);
    resultsContainer.innerHTML = '';
    resultsContainer.hidden = true;
    messageInput.value = '';
    messageInput.placeholder = 'Choose, write your question';
    content.scrollTop = 0;
  }

  function openConversation(option) {
    activeOption = option;
    homeView.hidden = true;
    conversationView.hidden = false;
    clearContainer(messages);
    resultsContainer.innerHTML = '';
    resultsContainer.hidden = true;
    topicLabel.textContent = option.option_label;
    messageInput.value = '';
    messageInput.placeholder = 'Write your question here';
    appendBubble(messages, option.option_label, 'user');
    appendBubble(messages, option.option_response, 'system');
    messageInput.focus();
  }

  function renderWelcome(message) {
    clearContainer(welcomeMessages);
    var bubble = document.createElement('div');
    bubble.className = 'chatbot-bubble chatbot-bubble-system';
    bubble.textContent = message;
    welcomeMessages.appendChild(bubble);
  }

  function showOptions() {
    if (!chatbotData || !Array.isArray(chatbotData.options)) {
      optionsContainer.hidden = true;
      return;
    }

    optionsContainer.hidden = false;
  }

  function collectOptionsFromDom() {
    return Array.prototype.map.call(
      optionsContainer.querySelectorAll('[data-option-key]'),
      function (button) {
        return {
          option_key: button.getAttribute('data-option-key') || '',
          option_label: button.getAttribute('data-option-label') || button.textContent || '',
          option_response: button.getAttribute('data-option-response') || ''
        };
      }
    );
  }

  function bindOptionButtons(options) {
    var optionMap = {};

    options.forEach(function (option) {
      optionMap[option.option_key] = option;
    });

    Array.prototype.forEach.call(
      optionsContainer.querySelectorAll('[data-option-key]'),
      function (button) {
        var optionKey = button.getAttribute('data-option-key') || '';
        var option = optionMap[optionKey];

        if (!option || button.getAttribute('data-chatbot-bound') === 'true') {
          return;
        }

        button.setAttribute('data-chatbot-bound', 'true');
        button.addEventListener('click', function () {
          openConversation(option);
        });
      }
    );
  }

  function loadConfig() {
    var initialOptions = collectOptionsFromDom();

    if (initialOptions.length > 0) {
      chatbotData = {
        welcome_message: initialWelcomeMessage,
        options: initialOptions
      };
      bindOptionButtons(chatbotData.options);
      renderWelcome(chatbotData.welcome_message);
      showOptions();
      showHomeView();
      return;
    }

    widget.classList.add('chatbot-loading');
    renderWelcome('Loading chat...');

    fetch(configUrl, {
      headers: {
        Accept: 'application/json'
      }
    })
      .then(function (response) {
        if (!response.ok) {
          throw new Error('Request failed');
        }
        return response.json();
      })
      .then(function (data) {
        if (!data.success) {
          throw new Error('Invalid data');
        }

        chatbotData = data;
        bindOptionButtons(chatbotData.options);
        renderWelcome(chatbotData.welcome_message || 'Welcome. Choose a topic to start.');
        showOptions();
        showHomeView();
      })
      .catch(function () {
        renderWelcome('The chat is unavailable right now. Please try again.');
        optionsContainer.hidden = true;
      })
      .finally(function () {
        widget.classList.remove('chatbot-loading');
      });
  }

  function submitIssue(message) {
    var formData = new FormData();
    formData.append('issue_message', message);

    appendBubble(messages, message, 'user');
    appendBubble(messages, 'Sending your issue to the store team...', 'system');

    fetch(issueUrl, {
      method: 'POST',
      body: formData
    })
      .then(function (response) {
        return response.json().then(function (data) {
          return {
            ok: response.ok,
            data: data
          };
        });
      })
      .then(function (result) {
        if (!result.ok || !result.data.success) {
          throw new Error(result.data && result.data.message ? result.data.message : 'Submit failed');
        }

        appendBubble(messages, result.data.message || 'Your issue was sent successfully.', 'system');
      })
      .catch(function (error) {
        appendBubble(messages, error.message || 'Something went wrong while sending the issue.', 'system');
      });
  }

  function submitProductSearch(message) {
    appendBubble(messages, message, 'user');
    appendBubble(messages, 'Searching for matching products...', 'system');
    resultsContainer.innerHTML = '';
    resultsContainer.hidden = true;

    fetch(productSearchUrl + '?q=' + encodeURIComponent(message), {
      headers: {
        Accept: 'application/json'
      }
    })
      .then(function (response) {
        return response.json().then(function (data) {
          return {
            ok: response.ok,
            data: data
          };
        });
      })
      .then(function (result) {
        if (!result.ok || !result.data.success) {
          throw new Error(result.data && result.data.message ? result.data.message : 'Search failed');
        }

        appendBubble(messages, result.data.message || 'Here are the available results.', 'system');

        if (Array.isArray(result.data.products) && result.data.products.length > 0) {
          renderSearchResults(result.data.products);
        }
      })
      .catch(function (error) {
        appendBubble(messages, error.message || 'Something went wrong while searching.', 'system');
      });
  }

  function submitTopicMessage(message) {
    appendBubble(messages, message, 'user');

    fetch(messageUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json'
      },
      body: JSON.stringify({
        topic: activeOption.option_key,
        message: message
      })
    })
      .then(function (response) {
        return response.json().then(function (data) {
          return {
            ok: response.ok,
            data: data
          };
        });
      })
      .then(function (result) {
        if (!result.ok || !result.data.success) {
          throw new Error(result.data && result.data.message ? result.data.message : 'Reply failed');
        }

        appendBubble(messages, result.data.reply || activeOption.option_response, 'system');
      })
      .catch(function (error) {
        appendBubble(messages, error.message || 'The assistant could not answer right now.', 'system');
      });
  }

  toggle.addEventListener('click', function () {
    var isOpen = !panel.hidden;
    setPanelState(!isOpen);

    if (!isOpen && !chatbotData) {
      loadConfig();
    }
  });

  closeButton.addEventListener('click', function () {
    setPanelState(false);
  });

  resetButton.addEventListener('click', function () {
    showHomeView();
  });

  chatForm.addEventListener('submit', function (event) {
    var message;

    event.preventDefault();

    message = String(messageInput.value || '').trim();

    if (!message) {
      return;
    }

    if (!activeOption) {
      renderWelcome('Choose one of the cards first, then ask your question.');
      messageInput.value = '';
      return;
    }

    messageInput.value = '';

    if (activeOption.option_key === 'issue') {
      submitIssue(message);
      return;
    }

    if (activeOption.option_key === 'product_search') {
      submitProductSearch(message);
      return;
    }

    submitTopicMessage(message);
  });

  setPanelState(false);
})();
