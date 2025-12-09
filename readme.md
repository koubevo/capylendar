# 🗓️ Capylendar

**Capylendar** is a modern, shared calendar application built on **Laravel 12** and **Vue 3**, focusing on simplicity,
speed, and... capybaras. 🦦

The app is designed primarily for sharing events between users (e.g., couples), featuring private notes and color-coded
themes (based on "Capybaras").

---

## 🛠 Tech Stack

The project utilizes the latest standards in modern PHP and JavaScript development:

* **Backend:** [Laravel 12](https://laravel.com) (PHP 8.4+)
* **Frontend:** [Vue 3](https://vuejs.org) (Composition API, `<script setup>`)
* **Language:** TypeScript & PHP (Strict types)
* **Bridge:** [Inertia.js](https://inertiajs.com) (Monolith feel, SPA speed)
* **Styling:** [Tailwind CSS](https://tailwindcss.com) & [Nuxt UI](https://ui.nuxt.com)
* **Database:** PostgreSQL 17
* **Tooling:** Vite, ESLint, Prettier, PHPStan, Pest

---

## ✨ Key Features

* **Shared vs. Private Events:**
    * Users can create events visible to all assigned users (via `event_user` Pivot table).
    * Option to toggle "Private" mode (visible only to the author).
* **Capybara Themes:**
    * Events are categorized using the `Capybara` Enum (Blue, Pink, Yellow).
    * Each category has its own color scheme and avatar.
* **Dashboard:**
    * Separated into **Upcoming** and **History** tabs.
* **Smart Features:**
    * 💖 **Love Detection:** The backend automatically detects keywords in the title (e.g., "date", "love") and adds a
      subtle heart pattern to the event card background.
    * 📝 **Duplication:** Create a copy of an event with pre-filled data in one click.
    * 🕒 **Human Readable Dates:** Creation and update times are formatted as "5 minutes ago".

---

## 🏗 Architecture & Best Practices

The project places a strong emphasis on **Clean Code** and maintainability:

### Backend (Laravel)

* **Service Layer:** Data manipulation logic (Create, Update) is isolated in `EventService`. The Controller only manages
  the data flow.
* **API Resources:** Data transformation for the frontend is handled exclusively via `EventResource`. No raw Eloquent
  models are exposed.
* **Enums:** Native PHP Enums are used for states and types (e.g., `Capybara`, `EventType`) with methods for frontend
  consumption (colors, labels).
* **Optimization:**
    * Usage of DB Transactions when saving M:N relationships.

### Frontend (Vue + TypeScript)

* **Composables:** Form logic is extracted into `EventForm.ts`. Components handle UI only.
* **Type Safety:** All backend data (Props) have defined TypeScript interfaces (`types/Event.ts`).
* **Atomic Design:** UI is broken down into small, reusable components (`ActionCard`, `DeleteModal`, `InfoCard`).

---

This README file was generated with assistance from Google's Gemini assistant 3 Pro version, based on specifications
provided by the project author.

Made with ❤️ and 🦦 by Vojtěch Koubek
