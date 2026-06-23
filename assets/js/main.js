
const toggleSwitch = document.querySelector('.theme-switch input[type="checkbox"]');

function switchTheme(e) {
    if (e.target.checked) {
        document.documentElement.setAttribute('data-theme', 'dark');
    }
    else {
        document.documentElement.setAttribute('data-theme', 'light');
    }    
}

toggleSwitch.addEventListener('change', switchTheme, false);


function switchTheme(e) {
    if (e.target.checked) {
        document.documentElement.setAttribute('data-theme', 'dark');
        localStorage.setItem('theme', 'dark'); //add this
    }
    else {
        document.documentElement.setAttribute('data-theme', 'light');
        localStorage.setItem('theme', 'light'); //add this
    }    
}


const currentTheme = localStorage.getItem('theme') ? localStorage.getItem('theme') : null;

if (currentTheme) {
    document.documentElement.setAttribute('data-theme', currentTheme);

    if (currentTheme === 'dark') {
        toggleSwitch.checked = true;
    }
}




(function() {
  "use strict";

  /**
   * Easy selector helper function
   */
  const select = (el, all = false) => {
    el = el.trim()
    if (all) {
      return [...document.querySelectorAll(el)]
    } else {
      return document.querySelector(el)
    }
  }

  /**
   * Easy event listener function
   */
  const on = (type, el, listener, all = false) => {
    let selectEl = select(el, all)
    if (selectEl) {
      if (all) {
        selectEl.forEach(e => e.addEventListener(type, listener))
      } else {
        selectEl.addEventListener(type, listener)
      }
    }
  }

  /**
   * Easy on scroll event listener 
   */
  const onscroll = (el, listener) => {
    el.addEventListener('scroll', listener)
  }

  /**
   * Navbar links active state on scroll
   */
  let navbarlinks = select('#navbar .scrollto', true)
  const navbarlinksActive = () => {
    let position = window.scrollY + 200
    navbarlinks.forEach(navbarlink => {
      if (!navbarlink.hash) return
      let section = select(navbarlink.hash)
      if (!section) return
      if (position >= section.offsetTop && position <= (section.offsetTop + section.offsetHeight)) {
        navbarlink.classList.add('active')
      } else {
        navbarlink.classList.remove('active')
      }
    })
  }
  window.addEventListener('load', navbarlinksActive)
  onscroll(document, navbarlinksActive)

  /**
   * Scrolls to an element with header offset
   */
  const scrollto = (el) => {
    let header = select('#header')
    let offset = header.offsetHeight

    if (!header.classList.contains('header-scrolled')) {
      offset -= 20
    }

    let elementPos = select(el).offsetTop
    window.scrollTo({
      top: elementPos - offset,
      behavior: 'smooth'
    })
  }

  /**
   * Toggle .header-scrolled class to #header when page is scrolled
   */
  let selectHeader = select('#header')
  if (selectHeader) {
    const headerScrolled = () => {
      if (window.scrollY > 100) {
        selectHeader.classList.add('header-scrolled')
      } else {
        selectHeader.classList.remove('header-scrolled')
      }
    }
    window.addEventListener('load', headerScrolled)
    onscroll(document, headerScrolled)
  }

  /**
   * Back to top button
   */
  let backtotop = select('.back-to-top')
  if (backtotop) {
    const toggleBacktotop = () => {
      if (window.scrollY > 100) {
        backtotop.classList.add('active')
      } else {
        backtotop.classList.remove('active')
      }
    }
    window.addEventListener('load', toggleBacktotop)
    onscroll(document, toggleBacktotop)
  }

  /**
   * Mobile nav toggle
   */
 document.addEventListener("DOMContentLoaded", () => {
  const mobileNavToggle = document.querySelector('.mobile-nav-toggle');
  const navbar = document.querySelector('#navbar');

  if (mobileNavToggle) {
    mobileNavToggle.addEventListener('click', function() {
      // Toggle the menu visibility class
      navbar.classList.toggle('navbar-mobile');
      
      // Swap the icon from Hamburger (bi-list) to Close (bi-x)
      this.classList.toggle('bi-list');
      this.classList.toggle('bi-x');
    });
  }

  // Auto-close the mobile menu when a navigation link is clicked
  const navLinks = document.querySelectorAll('#navbar .nav-link');
  navLinks.forEach(link => {
    link.addEventListener('click', () => {
      if (navbar.classList.contains('navbar-mobile')) {
        navbar.classList.remove('navbar-mobile');
        mobileNavToggle.classList.remove('bi-x');
        mobileNavToggle.classList.add('bi-list');
      }
    });
  });


  const terminalCloseBtn = document.querySelector('.terminal-buttons .close');
  const terminalWindow = document.querySelector('.terminal-window');

  // If both exist on the page, add the click listener
  if (terminalCloseBtn && terminalWindow) {
    terminalCloseBtn.addEventListener('click', () => {
      // Add the 'closed' class to trigger the CSS fade-out animation
      terminalWindow.classList.add('closed');
    });
  }

  // Background:
    const canvas = document.getElementById('neural-bg');
    const ctx = canvas.getContext('2d');
    let width, height;

    // Configuration - Optimized & Colorful
    const config = {
      particleCount: 80,
      particleBaseSize: 2.5,  // Slightly larger to show off colors
      connectDistance: 130,
      mouseRadius: 150,
      speed: 0.5,
      // Vibrant but professional color palettes (RGB values)
      paletteLight: ['236, 72, 153', '59, 130, 246', '16, 185, 129', '139, 92, 246'], // Pink, Blue, Emerald, Purple
      paletteDark: ['244, 114, 182', '96, 165, 250', '52, 211, 153', '167, 139, 250'],
      accentLight: '112, 0, 255',
      accentDark: '0, 243, 255'
    };

    let particles = [];
    let mouse = { x: null, y: null };
    let lastScrollY = window.scrollY || document.documentElement.scrollTop;

    // Theme Setup
    let currentPalette = config.paletteDark;
    let currentLineColor = config.accentDark;

    const observer = new MutationObserver((mutations) => {
      mutations.forEach((m) => {
        if (m.attributeName === "class") updateTheme();
      });
    });
    observer.observe(document.body, { attributes: true });

    function updateTheme() {
      if (document.body.classList.contains('light-mode')) {
        currentPalette = config.paletteLight;
        currentLineColor = config.accentLight;
      } else {
        currentPalette = config.paletteDark;
        currentLineColor = config.accentDark;
      }
    }
    updateTheme();

    // Event Listeners
    window.addEventListener('mousemove', e => {
      mouse.x = e.clientX;
      mouse.y = e.clientY;
    });

    window.addEventListener('mouseleave', () => {
      mouse.x = null;
      mouse.y = null;
    });

    // Scroll Interaction Effect
    window.addEventListener('scroll', () => {
      let currentScrollY = window.scrollY || document.documentElement.scrollTop;
      let scrollDelta = currentScrollY - lastScrollY;
      lastScrollY = currentScrollY;

      // Apply scroll momentum to particles
      particles.forEach(p => {
        p.vy -= scrollDelta * 0.03; // Push particles based on scroll speed
        
        // FIX: Moved inside the loop! Teleports dots to the other side if pushed off screen
        if (p.y < 0) p.y += height;
        if (p.y > height) p.y -= height;
      });
    });

    window.addEventListener('resize', init);

    // Particle Class
    class Particle {
      constructor() {
        this.x = Math.random() * width;
        this.y = Math.random() * height;
        // Store base speed to recover from scroll momentum
        this.baseVx = (Math.random() - 0.5) * config.speed;
        this.baseVy = (Math.random() - 0.5) * config.speed;
        this.vx = this.baseVx;
        this.vy = this.baseVy;
        this.size = Math.random() * config.particleBaseSize + 1.5;
        // Assign a random color from the palette
        this.colorIndex = Math.floor(Math.random() * currentPalette.length);
      }

      update() {
        // Smoothly recover from scroll pushes back to normal speed
        this.vy += (this.baseVy - this.vy) * 0.05;

        // Move particles
        this.x += this.vx;
        this.y += this.vy;

        // FIX: X-Axis (Sides) - Bounce, but prevent getting trapped in the wall
        if (this.x < 0) { this.x = 0; this.vx *= -1; this.baseVx *= -1; }
        if (this.x > width) { this.x = width; this.vx *= -1; this.baseVx *= -1; }

        // FIX: Y-Axis (Top/Bottom) - Wrap around infinitely instead of bouncing
        if (this.y < 0) { this.y += height; }
        if (this.y > height) { this.y -= height; }

        // Mouse Interaction (Push away slightly)
        if (mouse.x !== null) {
          let dx = mouse.x - this.x;
          let dy = mouse.y - this.y;
          let distance = Math.sqrt(dx * dx + dy * dy);

          if (distance < config.mouseRadius) {
            let forceDirectionX = dx / distance;
            let forceDirectionY = dy / distance;
            let force = (config.mouseRadius - distance) / config.mouseRadius;

            this.x -= forceDirectionX * force * 2;
            this.y -= forceDirectionY * force * 2;
          }
        }
      }

      draw() {
        ctx.beginPath();
        ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
        // Draw colorful dots with low opacity so they don't distract
        ctx.fillStyle = `rgba(${currentPalette[this.colorIndex]}, 0.6)`;
        ctx.fill();
      }
    }

    function init() {
      width = canvas.width = window.innerWidth;
      height = canvas.height = window.innerHeight;
      particles = [];

      for (let i = 0; i < config.particleCount; i++) {
        particles.push(new Particle());
      }
    }

    function animate() {
      ctx.clearRect(0, 0, width, height);

      // Update and draw all particles
      for (let i = 0; i < particles.length; i++) {
        particles[i].update();
        particles[i].draw();

        // Connect particles to each other
        for (let j = i + 1; j < particles.length; j++) {
          let dx = particles[i].x - particles[j].x;
          let dy = particles[i].y - particles[j].y;
          let distance = Math.sqrt(dx * dx + dy * dy);

          if (distance < config.connectDistance) {
            let opacity = 1 - (distance / config.connectDistance);
            ctx.beginPath();
            // Lines stay subtle and uniform to keep text readable
            ctx.strokeStyle = `rgba(${currentLineColor}, ${opacity * 0.25})`;
            ctx.lineWidth = 1;
            ctx.moveTo(particles[i].x, particles[i].y);
            ctx.lineTo(particles[j].x, particles[j].y);
            ctx.stroke();
          }
        }

        // Connect particles to mouse
        if (mouse.x !== null) {
          let dx = particles[i].x - mouse.x;
          let dy = particles[i].y - mouse.y;
          let distance = Math.sqrt(dx * dx + dy * dy);

          if (distance < config.mouseRadius) {
            let opacity = 1 - (distance / config.mouseRadius);
            ctx.beginPath();
            ctx.strokeStyle = `rgba(${currentLineColor}, ${opacity * 0.5})`;
            ctx.lineWidth = 1.2;
            ctx.moveTo(particles[i].x, particles[i].y);
            ctx.lineTo(mouse.x, mouse.y);
            ctx.stroke();
          }
        }
      }

      requestAnimationFrame(animate);
    }

    init();
    animate();
});

  /**
   * Mobile nav dropdowns activate
   */
  on('click', '.navbar .dropdown > a', function(e) {
    if (select('#navbar').classList.contains('navbar-mobile')) {
      e.preventDefault()
      this.nextElementSibling.classList.toggle('dropdown-active')
    }
  }, true)

  /**
   * Scrool with ofset on links with a class name .scrollto
   */
  on('click', '.scrollto', function(e) {
    if (select(this.hash)) {
      e.preventDefault()

      let navbar = select('#navbar')
      if (navbar.classList.contains('navbar-mobile')) {
        navbar.classList.remove('navbar-mobile')
        let navbarToggle = select('.mobile-nav-toggle')
        navbarToggle.classList.toggle('bi-list')
        navbarToggle.classList.toggle('bi-x')
      }
      scrollto(this.hash)
    }
  }, true)

  /**
   * Scroll with ofset on page load with hash links in the url
   */
  window.addEventListener('load', () => {
    if (window.location.hash) {
      if (select(window.location.hash)) {
        scrollto(window.location.hash)
      }
    }
  });

  /**
   * Skills animation
   */
  let skilsContent = select('.skills-content');
  if (skilsContent) {
    new Waypoint({
      element: skilsContent,
      offset: '80%',
      handler: function(direction) {
        let progress = select('.progress .progress-bar', true);
        progress.forEach((el) => {
          el.style.width = el.getAttribute('aria-valuenow') + '%'
        });
      }
    })
  }

})()


document.addEventListener("DOMContentLoaded", () => {
  const container = document.getElementById("gameContainer");

  fetch("https://luckytaorem.github.io/index-games.json")
    .then(res => res.json())
    .then(data => {
      const allGames = data?.segments?.[0]?.hits || [];
      const maxGames = Math.min(30, allGames.length);

      for (let i = 0; i < maxGames; i++) {
        const game = allGames[i];
        const card = document.createElement("div");
        card.className = "game-card";
        card.innerHTML = `
          <h2 class="game-title" data-title="${game.title}">${game.title}</h2>
          <img src="${game.images[0]}" alt="${game.title}" onclick="openGameWindow('${game.gameURL}')">
        `;
        container.appendChild(card);
      }
    })
    .catch(() => {
      container.innerHTML = '<p style="text-align:center;">Failed to load games.</p>';
    });


    const terminalText = document.getElementById("terminal-text");
  if (!terminalText) return;

  const codeSnippet = `> Executing LuckyOS startup sequence...
> Loading WordPress core & Elementor modules... [OK]
> Integrating international payment gateways... [SECURE]
> Running technical SEO audit & injecting Schema... [OPTIMIZED]
> Compiling Java and Python scripts... 
> System ready. Awaiting new challenges...`;

  let i = 0;
  function typeWriter() {
    if (i < codeSnippet.length) {
      terminalText.innerHTML += codeSnippet.charAt(i) === '\n' ? '<br>' : codeSnippet.charAt(i);
      i++;
      setTimeout(typeWriter, Math.random() * 50 + 20); // Random typing speed
    }
  }

  // Start typing after a short delay
  setTimeout(typeWriter, 1500);


  const chatBtn = document.getElementById("ai-chat-btn");
  const chatWindow = document.getElementById("ai-chat-window");
  const closeBtn = document.getElementById("close-chat-btn");
  const chatInput = document.getElementById("chat-input");
  const sendBtn = document.getElementById("send-chat-btn");
  const messagesContainer = document.getElementById("chat-messages");

  // 👉 REPLACE THIS WITH YOUR RENDER URL (Keep the /chat at the end)
  const API_URL = "https://portfolio-chatbot-583v.onrender.com/chat";

  chatBtn.addEventListener("click", () => chatWindow.classList.toggle("hidden"));
  closeBtn.addEventListener("click", () => chatWindow.classList.add("hidden"));

async function handleSendMessage() {
  const text = chatInput.value.trim();
  if (!text) return;

  addMessage(text, "user-msg");
  chatInput.value = "";
  const typingId = addMessage("Typing...", "bot-msg", true);

  try {
    const response = await fetch(API_URL, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ message: text })
    });

    if (!response.ok) throw new Error("Server error");
    const data = await response.json();
    
    document.getElementById(typingId).remove();
    
    // Format and display the AI's reply
    const formattedReply = formatBotText(data.reply);
    addMessage(formattedReply, "bot-msg");

    // ==========================================
    // NEW AUTO-SCROLL LOGIC
    // ==========================================
    // Scan the formatted reply to see if it contains an internal link (href="#something")
    // ==========================================
    // NEW AUTO-SCROLL LOGIC (Instant & Restricted)
    // ==========================================
    const internalLinkMatch = formattedReply.match(/href="(#[a-zA-Z0-9_-]+)"/);
    
    if (internalLinkMatch) {
      const targetId = internalLinkMatch[1]; 
      
      // 1. Define ONLY your main navigation sections
      const mainNavSections = [
        "#hero", "#about", "#resume", "#blogs", 
        "#internship-main", "#project", "#github", 
        "#certificate", "#contact"
      ];

      // 2. Check if the link is actually in your main nav
      if (mainNavSections.includes(targetId)) {
        const targetSection = document.querySelector(targetId);

        if (targetSection) {
          targetSection.scrollIntoView({ behavior: 'smooth' }); 
        }
      }
    }
    // ==========================================
    // ==========================================

  } catch (error) {
    document.getElementById(typingId).remove();
    addMessage("Server is waking up or offline. Please try again later!", "bot-msg");
  }
}

  sendBtn.addEventListener("click", handleSendMessage);
  chatInput.addEventListener("keypress", (e) => {
    if (e.key === "Enter") handleSendMessage();
  });

// Listen for clicks on ANY link inside the chat window
messagesContainer.addEventListener("click", (e) => {
  const link = e.target.closest("a"); 
  if (!link) return; 

  const href = link.getAttribute("href");

  if (href && href.startsWith("#")) {
    e.preventDefault(); 

    // Smoothly scroll to the section, but leave the chat open
    const targetSection = document.querySelector(href);
    if (targetSection) {
      targetSection.scrollIntoView({ behavior: 'smooth' });
    }
  }
});

// Close chat window when clicking outside of it
document.addEventListener("click", (e) => {
  // Check if the chat is currently open
  if (!chatWindow.classList.contains("hidden")) {
    
    // Check if the click happened OUTSIDE the chat window 
    // AND OUTSIDE the chat button (so clicking the button doesn't instantly close it)
    if (!chatWindow.contains(e.target) && !chatBtn.contains(e.target)) {
      chatWindow.classList.add("hidden");
    }
    
  }
});

  function addMessage(text, className, isTyping = false) {
    const msgDiv = document.createElement("div");
    msgDiv.className = `msg ${className}`;
    msgDiv.innerHTML = text;
    if (isTyping) msgDiv.id = "typing-indicator";
    messagesContainer.appendChild(msgDiv);
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
    return msgDiv.id;
  }
});

function openGameWindow(url) {
  const screenWidth = window.screen.availWidth;
  const screenHeight = window.screen.availHeight;

  const gameWin = window.open(
    "", 
    "_blank", 
    `width=${screenWidth},height=${screenHeight},left=0,top=0`
  );

  gameWin.document.write(`
  <html>
      <head>
        <title>Play Game</title>
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <style>
          html, body {
            margin: 0;
            padding: 0;
            background: #000;
            overflow: hidden;
            width: 100vw;
            height: 100vh;
            font-family: 'Segoe UI', sans-serif;
          }
          iframe {
            border: none;
            width: 100vw;
            height: 100vh;
          }
          .fs-button {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background-color: #0073e6;
            color: #fff;
            border: none;
            padding: 10px 18px;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
            z-index: 9999;
            box-shadow: 0 2px 6px rgba(0,0,0,0.4);
            animation: fadeIn 1s ease forwards;
          }
          @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
          }
        </style>
      </head>
      <body>
        <iframe src="${url}" allowfullscreen></iframe>
        <button class="fs-button" onclick="enterFullscreen()">Tap for Fullscreen</button>
        <script>
          function enterFullscreen() {
            const docEl = document.documentElement;
            if (docEl.requestFullscreen) {
              docEl.requestFullscreen();
            } else if (docEl.webkitRequestFullscreen) {
              docEl.webkitRequestFullscreen();
            } else if (docEl.msRequestFullscreen) {
              docEl.msRequestFullscreen();
            }
            document.querySelector('.fs-button').style.display = 'none';
          }

          const isMobile = /Mobi|Android|iPhone|iPad|iPod/i.test(navigator.userAgent);
          if (!isMobile) {
            document.querySelector('.fs-button').style.display = 'none';
          }
        <\/script>
        </body>
    </html>
        `);
  gameWin.document.close();
}

function formatBotText(text) {
  // 0. Catch stubborn AI Markdown links like [Check out my Blogs](#blogs)
  const markdownLinkRegex = /\[([^\]]+)\]\(([^)]+)\)/g;
  let formattedText = text.replace(markdownLinkRegex, '<a href="$2">$1</a>');

  // 1. Detect standard URLs and Social Links...
  const urlRegex = /(?<!href="|href=')(https?:\/\/[^\s()<>]+|((?:www\.)?(?:linkedin\.com|github\.com|facebook\.com|instagram\.com|x\.com|twitter\.com)[^\s()<>]*))/gi;

  // 2. Detect Email Addresses (ignore if already in a mailto tag)
  const emailRegex = /(?<!mailto:|">)([a-zA-Z0-9._-]+@[a-zA-Z0-9._-]+\.[a-zA-Z0-9_-]+)(?!<\/a>)/gi;
  
  formattedText = formattedText.replace(emailRegex, function(email) {
    let cleanEmail = email.replace(/[.,;:]$/, ''); 
    return `<a href="mailto:${cleanEmail}">${cleanEmail}</a>`;
  });

  // 3. Detect stray internal links like #contact (ignore if already in an HTML tag)
  const anchorRegex = /(?<!href="|href=')(#[a-zA-Z0-9_-]+)/g;
  
  formattedText = formattedText.replace(anchorRegex, function(anchor) {
    let cleanAnchor = anchor.replace(/[.,;:]$/, ''); 
    // Just return a clean anchor tag. The Event Listener below will handle the scrolling!
    return `<a href="${cleanAnchor}">${cleanAnchor}</a>`;
  });

  // 4. Handle standard AI markdown (bold text and line breaks)
  formattedText = formattedText.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
  formattedText = formattedText.replace(/\n/g, '<br>');

  return formattedText;
}

document.addEventListener("DOMContentLoaded", () => {
  const toggleSwitch = document.querySelector('.theme-switch input[type="checkbox"]');
  const currentTheme = localStorage.getItem('theme');

  // Helper function to force Dark Mode
  function enableDarkMode() {
    document.body.classList.remove('light-mode');
    document.body.removeAttribute('data-theme'); // Or set it: document.body.setAttribute('data-theme', 'dark');
    if (toggleSwitch) toggleSwitch.checked = true;
  }

  // Helper function to force Light Mode
  function enableLightMode() {
    document.body.classList.add('light-mode');
    document.body.setAttribute('data-theme', 'light'); // This triggers your CSS data-theme!
    if (toggleSwitch) toggleSwitch.checked = false;
  }

  // 1. On Page Load: Check memory or default to Dark
  if (currentTheme === 'light') {
    enableLightMode();
  } else {
    // If it's their first time (null) or they chose dark, force Dark Mode
    enableDarkMode();
    localStorage.setItem('theme', 'dark'); 
  }

  // 2. Listen for the user clicking the switch
  if (toggleSwitch) {
    toggleSwitch.addEventListener('change', function(e) {
      if (e.target.checked) {
        enableDarkMode();
        localStorage.setItem('theme', 'dark');
      } else {
        enableLightMode();
        localStorage.setItem('theme', 'light');
      }
    });
  }
});