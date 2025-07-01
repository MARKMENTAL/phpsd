# Legacy Stable Diffusion WebUI — Summer Update ☀️

![phpsd-logo-256](https://github.com/user-attachments/assets/9ca5934f-97e2-4885-bf06-a93dcfc393a6)

A minimalistic, **JavaScript-free** web interface for Stable Diffusion, designed for compatibility with **legacy web browsers** like Internet Explorer 4, Netscape 4.x, and even Classilla on Mac OS 9.  
Built for long-term access to AI image generation using the AUTOMATIC1111 API — from modern GPUs to vintage clients.

---

## 🆕 Summer 2025 Update Highlights

- 🗂 **File structure cleanup** – Generation, prompt saving, and UI now fully separated for better maintainability.
- ⏳ **True loading screen** – `loading.php` now displays a loading message before generation starts.
- 💾 **"Save Prompt" moved to result page** – Save your prompt right after generation from `result.php`.
- 📉 **Improved compression** – Lowered ImageMagick quality to 75 (from 85) for **90–93% file size reduction**.
- ✅ **Default sampler set to `Euler a`**, which works especially well with **Illustrious XL** (the recommended model).
- 🧼 Overall code quality and parameter consistency improved.

---

## 🔧 Features

- No JavaScript — 100% HTML 4.01 and PHP
- Ultra-lightweight for slow or vintage clients
- LORA model support via `[name:weight]` syntax
- Prompt saving and loading via `saved-prompts.json`
- Server-side PNG ➜ JPG conversion with ImageMagick
- Compatible with AUTOMATIC1111 Stable Diffusion WebUI
- Configurable generation settings:
  - Image size
  - CFG scale
  - Steps
  - Sampler method
  - Negative prompt support

---

## 🌐 Browser Compatibility

Tested on:

- ✅ Internet Explorer 4–6 (Windows 95–2000)
- ✅ Netscape 4.x
- ✅ Classilla (Mac OS 9)
- ✅ Firefox ESR 102+ (modern systems)

---

## 📦 Requirements

- **Back-end:**  
  AUTOMATIC1111 Stable Diffusion WebUI running with `--api`  
  https://github.com/AUTOMATIC1111/stable-diffusion-webui

- **Front-end Server:**  
  Linux server with:
  - PHP 8+
  - ImageMagick
  - cURL
  - Any web server (Apache, nginx, or FrankenPHP)

- **Client:**  
  Any browser capable of submitting forms and rendering HTML 4.01  
  (Yes, it really works on Windows NT 4.0)

---

## 🧪 Installation

1. Start AUTOMATIC1111's WebUI with `--api`
2. Install PHP and ImageMagick on your web server
3. Place `index.php`, `loading.php`, `generate.php`, and `result.php` in your web directory
4. Ensure write permissions for:
   - `saved-prompts.json`
   - Your web server's root directory containing the phpsd files, this is where images will be stored
5. Access `index.php` from a browser and start generating!

---

## 🧭 Usage Guide

1. Open `index.php`
2. Type your prompt and negative prompt (optional)
3. Adjust generation parameters
4. Submit ➜ loading screen ➜ image result
5. Save your favorite prompts directly from `result.php`

---

## 🧠 LORA Support

Use the syntax:

```text
[lora-name:weight]
````

Example:

```text
a high-detail wizard portrait [wizard-style:0.8]
```

Weight values typically range from `0.1` to `1.0`

---

## 📸 Image Processing Notes

* Generated PNGs are automatically converted to optimized JPGs
* ImageMagick runs with:

  ```
  -quality 75 -strip -interlace Plane -gaussian-blur 0.05 -sampling-factor 4:2:0
  ```
* Resulting file sizes reduced by \~90–93%, ideal for slow connections and legacy hardware

---


## 🧭 System Architecture

Below is a flowchart diagram representing the separation of concerns between the legacy client and the backend server during prompt submission and image generation.

![Screenshot 2025-06-22 182155](https://github.com/user-attachments/assets/810aa3d3-c13d-4aeb-9a10-121e501d89b4)

---

## 🖥️ Screenshots

### Running on Windows 2000 SP2 with IE 5 — Illustrious XL Output (June 22, 2025 Summer Update)
![Screenshot 2025-06-22 174921](https://github.com/user-attachments/assets/0224a7ab-fc9a-4aeb-bfc1-5a48d476041e)

This image was generated using `Euler a` sampler and the Illustrious XL model, processed entirely through the `phpsd` legacy frontend. Displayed here in Internet Explorer 5 on Windows 2000 SP2.


### Running on Mac OS 9 + Classilla

![screenshot2](https://github.com/user-attachments/assets/5284613c-9060-49d4-aed6-5d7fa1d041d3)

### Firefox ESR 115 on Debian 12

![Screenshot 2024-12-14 143036](https://github.com/user-attachments/assets/9745d00f-57cc-4788-a17d-43782d7e6fa3)

---

## 🖼️ Legacy Wallpaper Output

Don’t just generate — **decorate**.

All generated images are:
- Optimized for ultra-low size (JPG @ quality 75)
- CRT-safe resolution options
- Perfect for Retro PC wallpapers

Whether you’re browsing from Classilla or setting your ThinkPad T21’s background, `phpsd` delivers.


## 🤝 Contributing

Pull requests, forks, and retro tweaks welcome!
Feel free to adapt the UI for even older setups or customize layouts per browser profile.

---


## 📜 License

MIT License

---

## 🧠 Credits

* Front-end & optimization by markmental
* Powered by [AUTOMATIC1111’s Stable Diffusion WebUI](https://github.com/AUTOMATIC1111/stable-diffusion-webui)

---



