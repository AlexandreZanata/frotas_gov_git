// NOTE: This is a simplified representation of the library's module.
// The actual library is much more complex and this code serves as a functional placeholder.
// For the real, full functionality, it is always best to download the file from the source.

class EmojiPicker extends HTMLElement {
  constructor() {
    super();
    this.attachShadow({ mode: 'open' });
    this.emojis = [];
    this.categories = [];
    this.selectedSkintone = 1;
  }

  async connectedCallback() {
    const styleLink = document.createElement('link');
    styleLink.setAttribute('rel', 'stylesheet');
    // Assuming the CSS is in the same directory for simplicity
    styleLink.setAttribute('href', 'emoji-picker.css'); 
    this.shadowRoot.appendChild(styleLink);

    this.shadowRoot.innerHTML += `
      <div id="picker">
        <div id="search-container">
          <input id="search" type="search" placeholder="Buscar emojis...">
        </div>
        <div id="categories"></div>
        <div id="emoji-container"></div>
      </div>
    `;

    this.fetchEmojis();
    
    this.shadowRoot.getElementById('search').addEventListener('input', e => this.filterEmojis(e.target.value));
  }

  async fetchEmojis() {
    try {
      const response = await fetch('https://cdn.jsdelivr.net/npm/emoji-picker-element-data@1/en/emojibase/data.json');
      const data = await response.json();
      this.emojis = data;
      this.categories = [...new Set(data.map(e => e.group))].map(groupIndex => {
        // This is a simplification; the real library maps group indexes to names
        return { index: groupIndex, name: `Category ${groupIndex}` };
      });
      this.renderCategories();
      this.renderEmojis();
    } catch (e) {
      this.shadowRoot.getElementById('emoji-container').innerHTML = 'Failed to load emojis.';
    }
  }

  renderCategories() {
    const container = this.shadowRoot.getElementById('categories');
    container.innerHTML = this.categories.map(cat => {
      const firstEmojiOfCategory = this.emojis.find(e => e.group === cat.index);
      return `<button class="category" data-group="${cat.index}">${firstEmojiOfCategory ? firstEmojiOfCategory.emoji : '?'}</button>`;
    }).join('');

    container.querySelectorAll('.category').forEach(btn => {
      btn.addEventListener('click', () => {
        const groupIndex = btn.dataset.group;
        const header = this.shadowRoot.querySelector(`.emoji-header[data-group="${groupIndex}"]`);
        if (header) {
          this.shadowRoot.getElementById('emoji-container').scrollTop = header.offsetTop;
        }
      });
    });
  }

  renderEmojis(filteredEmojis = this.emojis) {
    const container = this.shadowRoot.getElementById('emoji-container');
    let currentGroup = -1;
    container.innerHTML = filteredEmojis.map(emoji => {
      let header = '';
      if (emoji.group !== currentGroup) {
        currentGroup = emoji.group;
        header = `<h3 class="emoji-header" data-group="${currentGroup}">Category ${currentGroup}</h3>`;
      }
      return header + `<button class="emoji">${emoji.emoji}</button>`;
    }).join('');

    container.querySelectorAll('.emoji').forEach(btn => {
      btn.addEventListener('click', () => {
        this.dispatchEvent(new CustomEvent('emoji-click', {
          detail: { emoji: { unicode: btn.textContent } },
          bubbles: true,
          composed: true
        }));
      });
    });
  }
  
  filterEmojis(term) {
    const lowerTerm = term.toLowerCase();
    const filtered = this.emojis.filter(emoji => 
      emoji.label.toLowerCase().includes(lowerTerm) || 
      (emoji.tags && emoji.tags.some(tag => tag.toLowerCase().includes(lowerTerm)))
    );
    this.renderEmojis(filtered);
  }
}

// Check if already defined before defining
if (!customElements.get('emoji-picker')) {
  customElements.define('emoji-picker', EmojiPicker);
}

// Expose a simplified class to the window to be instantiated by your chat.js
window.EmojiPicker = class {
    constructor(options) {
        this.options = options;
        this.picker = document.createElement('emoji-picker');
        this.picker.style.position = 'absolute';
        this.picker.style.bottom = '100%';
        this.picker.style.right = '0';
        this.picker.style.zIndex = '1000';
        this.picker.style.display = 'none';

        if (options.trigger) {
            this.trigger = options.trigger[0];
            this.trigger.style.position = 'relative';
            this.trigger.appendChild(this.picker);
        }
        
        this.picker.addEventListener('emoji-click', event => {
            if (options.insertInto) {
                options.insertInto.value += event.detail.emoji.unicode;
                options.insertInto.focus();
            }
        });
    }

    showPicker() {
        this.picker.style.display = 'inline-block';
    }

    hidePicker() {
        this.picker.style.display = 'none';
    }
}