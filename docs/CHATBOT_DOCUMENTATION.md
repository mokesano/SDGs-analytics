# Wizdam SDG Analytics - Chatbot Documentation

## 📖 Overview

Chatbot Wizdam SDG Assistant adalah sistem bantuan cerdas yang terintegrasi langsung dengan aplikasi untuk membantu pengguna memahami platform, format input, dan interpretasi hasil analisis SDG.

---

## 🎯 Fitur Utama

### 1. **Context-Aware Responses**
Chatbot memahami konteks pertanyaan dan memberikan jawaban yang relevan berdasarkan kata kunci:
- ORCID format & validation
- DOI format & usage
- SDG classification explanation
- Analysis process guidance
- Confidence score interpretation
- Error troubleshooting
- Platform features overview

### 2. **Quick Action Buttons**
Tombol aksi cepat untuk pertanyaan umum:
- ❓ **Help** - Daftar bantuan yang tersedia
- 🆔 **ORCID Format** - Penjelasan format ORCID
- 🔗 **DOI Format** - Penjelasan format DOI
- 📊 **How to Analyze** - Panduan analisis

### 3. **Conversation History**
- Menyimpan riwayat percakapan di localStorage (20 pesan terakhir)
- Auto-load saat chatbot dibuka kembali
- Timestamp untuk setiap pesan

### 4. **Typing Indicator**
Animasi "Assistant is typing..." untuk simulasi respons natural dengan delay 0.8-2 detik.

### 5. **Responsive Design**
- Modal chat yang responsive untuk mobile & desktop
- Animasi smooth saat open/close
- Scroll otomatis ke pesan terbaru

---

## 🏗️ Arsitektur

### File Structure
```
components/
└── chatbot.php          # Main chatbot component (HTML + JS)

assets/css/
└── theme.css            # Chatbot styles (buttons, modal, messages)

includes/
├── locale.php           # Localization system
└── markdown_parser.php  # Content parser (untuk future enhancement)
```

### Component Breakdown

#### 1. **Chatbot Button** (`#chatbotBtn`)
Floating button di pojok kanan bawah layar.

#### 2. **Chatbot Modal** (`#chatbotModal`)
Container utama dengan struktur:
- **Header**: Title + Close button
- **Body**: Welcome message, quick actions, conversation log
- **Input Group**: Text input + Send button

#### 3. **JavaScript Engine**
Located inline dalam `chatbot.php`:
- `chatbotResponses` - Database respons predefined
- `getResponse()` - Logic matching intent
- `addChatMessage()` - Render pesan ke UI
- `saveConversationHistory()` - Persist ke localStorage

---

## 💬 Response Categories

| Category | Keywords | Example Response |
|----------|----------|------------------|
| `hello` | hello, hi, hey | "Hello! How can I help you with SDG analysis today?" |
| `help` | help, assist | List of available assistance topics |
| `orcid` | orcid | Format explanation: 0000-0000-0000-0000 |
| `doi` | doi | Format example: 10.1038/nature12373 |
| `sdg` | sdg, sustainable | Explanation of 17 SDG goals |
| `analysis` | analysis, analyze | 4 components breakdown (keywords, similarity, substantive, causal) |
| `confidence` | confidence, score | Score ranges interpretation (90-100%, 70-89%, etc.) |
| `error` | error, problem, issue | Common issues & solutions |
| `how` | how to | Step-by-step analysis guide |
| `features` | feature, platform | Platform capabilities overview |
| `default` | (unrecognized) | Fallback response with suggestions |

---

## 🔧 Customization Guide

### Menambah Response Baru

Edit `chatbotResponses` object dalam `components/chatbot.php`:

```javascript
const chatbotResponses = {
    // ... existing categories ...
    
    'new_category': [
        'Response variant 1',
        'Response variant 2',
        'Response variant 3'
    ],
    
    // ...
};
```

Kemudian update logic di `getResponse()`:

```javascript
} else if (msg.includes('keyword1') || msg.includes('keyword2')) {
    responseKey = 'new_category';
}
```

### Mengubah Delay Respons

Edit nilai delay di fungsi `sendMessage()`:

```javascript
const delay = 800 + Math.random() * 1200; // Ubah nilai ini (dalam ms)
```

### Mengubah Quick Actions

Edit HTML di `chatbot-quick-actions`:

```html
<div class="chatbot-quick-actions">
    <button class="quick-action-btn" onclick="sendQuickMessage('your_query')">
        <i class="fas fa-icon"></i> Label
    </button>
</div>
```

---

## 🌐 Localization (Future Enhancement)

Saat ini chatbot menggunakan bahasa Inggris hardcoded. Untuk mendukung multi-bahasa:

### Option 1: Integration dengan Locale System

```php
// Di chatbot.php
<?php
$welcome_msg = __('chatbot.welcome');
$placeholder = __('chatbot.input_placeholder');
?>
```

Tambahkan keys ke `locale/*/LC_MESSAGES/messages.po`:
```po
msgid "chatbot.welcome"
msgstr "Halo! Saya asisten SDG Anda..."
```

### Option 2: JSON-based Translations

Buat file `assets/i18n/chatbot-en.json`, `chatbot-id.json`:
```json
{
    "welcome": "Hello! I'm your SDG Assistant...",
    "placeholder": "Type your question here...",
    "responses": {
        "hello": ["Hi there!", "Hello!"]
    }
}
```

---

## 🚀 Advanced Features (Roadmap)

### 1. **Database-Driven Knowledge Base**
Simpan FAQ dan responses di database untuk easy management via admin panel.

```sql
CREATE TABLE chatbot_responses (
    id INTEGER PRIMARY KEY,
    category TEXT,
    keywords TEXT, -- JSON array
    response TEXT,
    priority INTEGER DEFAULT 0
);
```

### 2. **AI-Powered Intent Recognition**
Integrasi dengan NLP library (natural.js atau compromise.js) untuk better intent detection.

### 3. **Analytics Dashboard**
Track popular questions, user satisfaction, unresolved queries.

### 4. **Human Handoff**
Escalate complex queries to human support via ticket system.

### 5. **Voice Input**
Web Speech API integration untuk voice queries.

---

## 🐛 Troubleshooting

### Chatbot tidak muncul
- Cek apakah `components/chatbot.php` di-include di layout
- Pastikan CSS loaded: cek `assets/css/theme.css`
- Verify JavaScript tidak ada error di console

### Respons tidak sesuai
- Review keyword matching di `getResponse()`
- Tambahkan lebih banyak variants ke response array
- Check typo di keywords

### History tidak tersimpan
- Browser mungkin disable localStorage
- Clear cache dan coba lagi
- Check quota localStorage (biasanya 5MB limit)

### Mobile display issue
- Inspect element di browser dev tools
- Cek media queries di CSS
- Test di berbagai viewport sizes

---

## 📝 Best Practices

1. **Keep responses concise** - Max 2-3 kalimat per response
2. **Use line breaks** - `\n` untuk readability
3. **Provide examples** - Especially for format questions
4. **Offer alternatives** - Multiple response variants untuk variety
5. **Test edge cases** - Empty input, special characters, very long queries
6. **Monitor usage** - Track which questions are asked most

---

## 🔒 Security Considerations

1. **Input sanitization** - XSS prevention via `textContent` (sudah implemented)
2. **Length limits** - `maxlength="500"` pada input field
3. **Rate limiting** - Future: prevent spam messages
4. **No sensitive data** - Jangan simpan PII di conversation history

---

## 📞 Support

Untuk pertanyaan teknis atau feature requests terkait chatbot:
- Email: support@wizdam.sangia.org
- GitHub Issues: https://github.com/wizdam/sdgs-analytics/issues

---

**Version**: 2.0  
**Last Updated**: May 2025  
**Maintained by**: PT. Sangia Research Media and Publishing
