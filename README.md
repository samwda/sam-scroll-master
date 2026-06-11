# 🖥 Sam Scroll Master – WordPress Plugin

![Downloads](https://img.shields.io/wordpress/plugin/dt/sam-scroll-master.svg) 
![Rating](https://img.shields.io/wordpress/plugin/rating/sam-scroll-master.svg) 
![GitHub release](https://img.shields.io/github/v/release/samwda/ssm.svg)

**Sam Scroll Master (SSM)** is a modern, secure, and highly configurable WordPress plugin that enables smooth scrolling on your website. Its stylish admin panel allows you to enable/disable the feature on the frontend or admin, control access by user role and device type, and exclude pages, post types, custom post types, or taxonomy terms.


![Banner](https://ps.w.org/sam-scroll-master/assets/banner-1544x500.png)

---

## ✨ Features
- Smooth scroll for anchor links on the frontend and optionally the admin panel
- User role and device-based control
- Exclusions for pages, post types, custom post types, and taxonomies
- Fully responsive and RTL-compatible

---

## 🔧 Installation
1. Download the plugin ZIP or clone the repository into your WordPress plugin directory: `/wp-content/plugins/sam-scroll-master/`
2. Activate the plugin from the WordPress admin dashboard
3. Navigate to **Settings → Sam Scroll Master** to configure options
    - Set frontend/admin activation, user roles, devices, and exclusions as needed

---

## 🧩 Usage

- **Automatic Application**  
  Smooth scrolling applies to enabled sections of your site based on configuration.
- **Anchor Links**  
  Anchor links such as `<a href="#section">Go</a>` are smoothly scrolled.
- **Exclusions**  
  Exclude specific pages, post types, or taxonomy terms through the admin panel.
- **Control by Roles and Devices**  
  Manage which user roles and devices experience this effect.
- **Custom Content Support**  
  Works seamlessly with all post types and custom content.
- **Accessibility**: Fully respects `prefers-reduced-motion` media query. If a user has requested reduced motion in their system settings, the smooth scroll will not activate, preserving native browser behavior.

---

## 🧠 How It Works
1. Detects the current device type: desktop, tablet, or mobile.
2. Checks user roles and optional guest access.
3. Excludes configured pages, post types, custom post types, or terms.
4. Adds smooth scroll behavior via JavaScript.

---

## 🌍 SEO & Performance
- Optimized to work with all modern themes.
- Enhances user experience without compromising site speed.

---

## 🛡 License

Released under the GPLv2 or later. JavaScript Library Released under the MIT Licence

---

## ⚡ JavaScript Library Acknowledgment

This plugin uses [SmoothScroll for websites](https://github.com/gblazex/smoothscroll-for-websites) by [Balazs Galambosi](https://github.com/gblazex/). 

We sincerely thank Balazs for providing this lightweight and efficient smooth scrolling library. 🙏

---

## 👨‍💻 Author
**SAM Web Design Agency**  
Website: [https://samwda.ir](https://samwda.ir)  
GitHub: [https://github.com/samwda](https://github.com/samwda)

---

## 💬 Feedback & Contributions

Contributions are welcome! For major changes, please open an issue to discuss your ideas first. Pull requests are encouraged and appreciated.

---

Enjoy smooth, fast, and modern scrolling with **Sam Scroll Master** ⏩✨
