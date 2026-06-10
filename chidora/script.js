/*--------------------
Vars
--------------------*/
let progress = 10
let startX = 0
let active = 0
let isDown = false

/*--------------------
Contants
--------------------*/
const speedWheel = 0.02
const speedDrag = -0.1

/*--------------------
Get Z
--------------------*/
const getZindex = (array, index) => (array.map((_, i) => (index === i) ? array.length : array.length - Math.abs(index - i)))

/*--------------------
Items
--------------------*/
const $items = document.querySelectorAll('.carousel-item')
const $cursors = document.querySelectorAll('.cursor')

const displayItems = (item, index, active) => {
  const zIndex = getZindex([...$items], active)[index]
  item.style.setProperty('--zIndex', zIndex)
  item.style.setProperty('--active', (index-active)/$items.length)
}

/*--------------------
Animate
--------------------*/
const animate = () => {
  progress = Math.max(0, Math.min(progress, 100))
  active = Math.floor(progress/100*($items.length-1))
  
  $items.forEach((item, index) => displayItems(item, index, active))
}
animate()

/*--------------------
Click on Items
--------------------*/
$items.forEach((item, i) => {
  item.addEventListener('click', () => {
    progress = (i/$items.length) * 100 + 10
    animate()
  })
})

/*--------------------
Handlers
--------------------*/
const handleWheel = e => {
  const wheelProgress = e.deltaY * speedWheel
  progress = progress + wheelProgress
  animate()
}

const handleMouseMove = (e) => {
  if (e.type === 'mousemove') {
    $cursors.forEach(($cursor) => {
      $cursor.style.transform = `translate(${e.clientX}px, ${e.clientY}px)`
    })
  }
  if (!isDown) return
  const x = e.clientX || (e.touches && e.touches[0].clientX) || 0
  const mouseProgress = (x - startX) * speedDrag
  progress = progress + mouseProgress
  startX = x
  animate()
}

const handleMouseDown = e => {
  isDown = true
  startX = e.clientX || (e.touches && e.touches[0].clientX) || 0
}

const handleMouseUp = () => {
  isDown = false
}

/*--------------------
Listeners
--------------------*/
document.addEventListener('mousewheel', handleWheel)
document.addEventListener('mousedown', handleMouseDown)
document.addEventListener('mousemove', handleMouseMove)
document.addEventListener('mouseup', handleMouseUp)
document.addEventListener('touchstart', handleMouseDown)
document.addEventListener('touchmove', handleMouseMove)
document.addEventListener('touchend', handleMouseUp)

document.querySelectorAll('.carousel-item').forEach(item => {
  item.addEventListener('click', () => {
    const slideId = item.getAttribute('data-id') || 'unknown';
    history.pushState(null, '', `?view=${slideId}`);

    const clone = item.cloneNode(true);
    clone.classList.add('fullscreen-view');

    // Add share buttons
    const shareContainer = document.createElement('div');
    shareContainer.className = 'share-icon-group';
    
    const shareURL = encodeURIComponent(`https://taoremtls.in/chidora/${slideId}.html`);

    
    const icons = [
      {
        href: `https://api.whatsapp.com/send?text=${shareURL}`,
        img: 'https://cdn-icons-png.flaticon.com/512/733/733585.png',
        alt: 'WhatsApp'
      },
      {
        href: `https://www.facebook.com/sharer/sharer.php?u=${shareURL}`,
        img: 'https://cdn-icons-png.flaticon.com/512/733/733547.png',
        alt: 'Facebook'
      },
      {
        href: `https://www.instagram.com/`,
        img: 'https://cdn-icons-png.flaticon.com/512/733/733558.png',
        alt: 'Instagram'
      }
    ];
    
    
    icons.forEach(({ href, img, alt }) => {
      const link = document.createElement('a');
      link.href = href;
      link.target = '_blank';
      link.className = 'share-icon';
      link.innerHTML = `<img src="${img}" alt="${alt}" title="${alt}" />`;
      shareContainer.appendChild(link);
    });
    
    const titleDiv = clone.querySelector('.title');
    if (titleDiv) {
      titleDiv.insertAdjacentElement('afterend', shareContainer);
    }
    

    const overlay = document.createElement('div');
    overlay.classList.add('fullscreen-overlay');
    overlay.appendChild(clone);

    overlay.addEventListener('click', () => {
      document.body.removeChild(overlay);
      history.pushState(null, '', location.pathname); // reset URL
    });

    document.body.appendChild(overlay);
  });
});

window.addEventListener('DOMContentLoaded', () => {
  const params = new URLSearchParams(location.search);
  const view = params.get('view');
  if (view) {
    const target = document.querySelector(`.carousel-item[data-id="${view}"]`);
    if (target) target.click(); // simulate click to open fullscreen
  }
  const audio = document.getElementById('bg-music');
  if (!audio) return;

  audio.volume = 0;
  const fade = setInterval(() => {
    if (audio.volume < 0.9) {
      audio.volume = Math.min(audio.volume + 0.05, 0.9); // Cap volume to 90%
    } else {
      clearInterval(fade);
    }
  }, 300); // Adjust interval for smoother fade

  audio.play().catch(() => {
    // Autoplay blocked by browser — will play on user interaction
const audio = document.getElementById('bg-music');
const toggleBtn = document.getElementById('audio-toggle');

if (toggleBtn && audio) {
  toggleBtn.addEventListener('click', () => {
    if (audio.paused) {
      audio.play();
      toggleBtn.innerHTML = '<span class="icon pause-icon"></span>';
    } else {
      audio.pause();
      toggleBtn.innerHTML = '<span class="icon play-icon"></span>';
    }
  });
}


    document.body.addEventListener('click', () => audio.play(), { once: true });
  });
  

});