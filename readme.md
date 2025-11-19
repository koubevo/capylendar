# Capylendar

Capylendar is a mobile-first web application designed for shared event management between partners. It utilizes a
chronological feed layout rather than a traditional calendar grid to streamline event visibility on smaller devices.

The project serves as a technical playground for implementing modern full-stack patterns using Laravel 12, Vue 3, and
TypeScript.

![Status](https://img.shields.io/badge/status-active_development-yellow)
![PHP](https://img.shields.io/badge/PHP-8.4-777BB4)
![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20)
![Vue](https://img.shields.io/badge/Vue.js-3-4FC08D)
![TypeScript](https://img.shields.io/badge/TypeScript-5-3178C6)

## Technology Stack

The application is built on a monolithic architecture using Inertia.js to avoid the complexity of a separate API layer.

* **Backend:** Laravel 12
* **Frontend:** Vue 3 (Composition API) + TypeScript
* **Routing/Glue:** Inertia.js
* **UI Framework:** Nuxt UI (Tailwind CSS v4)
* **Database:** PostgreSQL
* **Environment:** Docker via Laravel Sail
* **Static Analysis:** PHPStan (Level 9)
* **Code Review:** Gemini

## Architecture & Design Patterns

The codebase adheres to strict typing and separation of concerns.

### Database Design

* **Pivot Tables:** Many-to-Many (M:N) relationships are used to manage event visibility between users.
* **Standardization:** Dates are stored in standard SQL `datetime` format and formatted for the user locale on the
  frontend (or backend via Carbon, depending on context).

## Key Features

* **User Differentiation:** Events are visually coded based on the participant (Blue/Self, Pink/Partner, Yellow/Shared).
* **Grouped Feed:** Events are dynamically grouped by date headers (Today, Tomorrow, specific dates) on the frontend.
* **Mobile-First UI:** Interface elements, including the Floating Action Button (FAB) and input controls, are optimized
  for touch interaction and bottom-thumb zones.

---

This README file was generated with assistance from Google's Gemini assistant 3 Pro version, based on specifications
provided by the project author.
