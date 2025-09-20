// theme switcher
const html = document.querySelector('html');
let darkMode = localStorage.getItem('dark-mode');

// Don't force theme-light - check localStorage first
if (darkMode !== "enabled" && !html.dataset.theme) {
  html.dataset.theme = `theme-light`;
}

function switchTheme(theme) {
  html.dataset.theme = `theme-${theme}`;
}

function switchAssets(theme) {

    if (theme === "dark") {
        document.getElementById("dark-mode-btn").innerHTML = '<svg width="16pt" height="16pt" viewBox="0 0 16 18" fill="white"><path d="M8 15A7 7 0 1 0 8 1v14zm0 1A8 8 0 1 1 8 0a8 8 0 0 1 0 16z"/></svg>';
        document.getElementById("dark-mode-btn-desktop").innerHTML = '<svg width="16pt" height="16pt" viewBox="0 0 16 18" fill="white"><path d="M8 15A7 7 0 1 0 8 1v14zm0 1A8 8 0 1 1 8 0a8 8 0 0 1 0 16z"/></svg>';
        document.getElementById("header-nav").style.color = "#fff";
        // document.getElementById("mc_embed_signup").getElementsByClassName("button")[0].style.backgroundColor = "#d0000a";
        // document.getElementById("header-nav-desktop").style.color = "#fff";

    } else {
        document.getElementById("dark-mode-btn").innerHTML = '<svg width="16pt" height="16pt" viewBox="0 0 16 18"><path d="M8 15A7 7 0 1 0 8 1v14zm0 1A8 8 0 1 1 8 0a8 8 0 0 1 0 16z"/></svg>';
        document.getElementById("dark-mode-btn-desktop").innerHTML = '<svg width="16pt" height="16pt" viewBox="0 0 16 18" fill="inherit"><path d="M8 15A7 7 0 1 0 8 1v14zm0 1A8 8 0 1 1 8 0a8 8 0 0 1 0 16z"/></svg>';
        document.getElementById("header-nav").style.color = "#000";
        // document.getElementById("mc_embed_signup").getElementsByClassName("button")[0].style.backgroundColor = "#111";
        // document.getElementById("header-nav-desktop").style.removeProperty("color");
        // document.getElementById("header-nav-desktop").style.color = "000";
    }
}

// logos switcher
function toggleTheme() {
    if (html.dataset.theme === 'theme-light') {
        switchTheme('dark');
        switchAssets('dark');
        localStorage.setItem("dark-mode", "enabled");
    } else {
        switchTheme('light');
        switchAssets('light');
        localStorage.setItem("dark-mode", "disabled");
    }
}

// Apply assets after DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    if (darkMode === "enabled") {
      switchAssets('dark');
    }
  });

// AJAX'ify the ProMailer subscribe form https://processwire.com/talk/topic/22121-promailer-%C2%A0ajax-subscription/#comment-189997
document.addEventListener('DOMContentLoaded', function() {
  const form = document.getElementById('promailer-form');
  if (form) {
    form.addEventListener('submit', function(e) {
      e.preventDefault();
      
      const formData = new FormData(form);
      const button = form.querySelector('button[type="submit"]');
      if (button && button.name) {
        formData.append(button.name, '1');
      }
      
      fetch(form.action, {
        method: 'POST',
        body: formData
      })
      .then(response => response.text())
      .then(html => {
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        const promailerElement = doc.getElementById('promailer');
        const targetElement = document.getElementById('promailer');
        
        if (promailerElement && targetElement) {
          targetElement.innerHTML = promailerElement.innerHTML;
        }
      })
      .catch(error => {
        console.error('Error:', error);
      });
    });
  }
});