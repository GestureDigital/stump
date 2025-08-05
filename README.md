# 🌲 Stump Starter Theme

[![Build Status](https://travis-ci.com/timber/starter-theme.svg?branch=master)](https://travis-ci.com/github/timber/starter-theme)  
[![Packagist Version](https://img.shields.io/packagist/v/upstatement/timber-starter-theme?include_prereleases)](https://packagist.org/packages/upstatement/timber-starter-theme)

A modern WordPress starter theme using:

- 🪵 [**Timber**](https://timber.github.io/) + Twig templates  
- 💨 [**Tailwind CSS**](https://tailwindcss.com/) for utility-first styling  
- ⚡️ [**Vite**](https://vitejs.dev/) for ultra-fast bundling and dev server  

Built to help developers move quickly with clean markup, flexible structure, and modern tooling.

---

## 🚀 Features

- 🔧 Clean structure based on Timber
- 🌿 Modern front-end workflow using Vite
- 🎨 Tailwind CSS pre-configured
- 🧱 Modular PHP with Composer autoloading
- 🧩 Twig templating for better separation of logic and presentation
- 🖼 Easy asset management with hot module reload in dev

---

## 📦 Installation

1. Clone this theme into your WordPress site's `wp-content/themes/` folder:

```bash
git clone https://github.com/your-username/stump-theme.git
cd stump-theme
```

2. Install dependencies:

```bash
composer install
npm install
```

3. Activate the theme in WordPress:  
   Go to **Appearance → Themes** and activate **Stump**.

4. Start the dev server:

```bash
npm run dev
```

5. Done! You're now developing with live reload + Tailwind + Timber.

---

## ⚙️ Using Vite

This theme uses [Vite](https://vitejs.dev/) for asset bundling.

### 🛠 Development

```bash
npm run dev
```

Starts Vite’s dev server with Hot Module Replacement (HMR). Styles and JS update instantly.

### 📦 Production

```bash
npm run build
```

Builds optimized JS and CSS files into `public/build/` using Vite’s manifest. The theme reads these from the manifest when `WP_ENV !== 'development'`.

---

## 🗂 File Structure

```
stump/
├── public/             # Built CSS/JS assets (from Vite)
├── resources/          # Source JS, CSS, SVGs, etc.
│   └── js/
│   └── css/
│   └── views/          # Twig templates
├── app/                # PHP classes (e.g. StarterSite.php)
├── functions.php       # Theme bootstrapping
├── style.css           # WP theme header (not used for styling)
├── package.json        # Front-end dependencies
├── composer.json       # PHP dependencies
```

---

## 🌲 The `StarterSite` Class

Located in `src/StarterSite.php`, this class is instantiated in `functions.php` and:

- Adds theme support
- Registers post types and taxonomies
- Sets up menus and custom functionality

This is your base class for extending Timber in your theme. All custom logic can be organized here or within other PHP classes, autoloaded via Composer (`PSR-4`).

---

## 🧩 Using `vite()` and `vite_asset()` in Twig

The Stump theme includes Twig helpers to easily reference your compiled Vite assets.

### JavaScript & CSS

Use the `vite()` function to include your main JS and CSS files:

```twig
{{ vite(['resources/js/app.js', 'resources/css/app.css']) }}
```

This will load the appropriate files based on whether you are in development (via the Vite dev server) or production (compiled via manifest).

### Images and Static Assets

To reference images or other static files in your Twig templates, use `vite_asset()`:

```twig
<img src="{{ vite_asset('resources/img/logo.svg') }}" alt="Logo">
```

This ensures correct paths are used depending on your environment and helps with cache busting.

> These helpers require the Vite integration to be properly configured in your `functions.php` or equivalent loader.

---

## 📚 Resources

- 🪵 [Timber Documentation](https://timber.github.io/docs/)
- 📝 [Twig for Timber Cheatsheet](http://notlaura.com/the-twig-for-timber-cheatsheet/)
- 🧵 [Tailwind CSS Docs](https://tailwindcss.com/docs)
- ⚡️ [Vite Documentation](https://vitejs.dev/)
- 🧠 [ACF + Timber Example](https://github.com/laras126/timber-starter-theme/tree/tackle-box)
- 🎥 [Timber Video Tutorials](http://timber.github.io/timber/#video-tutorials)
- 🔍 [Real Timber Theme Example](https://github.com/laras126/yuling-theme)

---

## 🙏 Credits

This theme is inspired by the official [Timber Starter Theme](https://github.com/timber/starter-theme), modernized with Tailwind, Vite.