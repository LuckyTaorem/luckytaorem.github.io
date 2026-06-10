const lyricsMap = [
  { time: 0.0, text: "I wanna make you the happiest girl in the world (I wanna, I wanna, I wanna)" },
  { time: 7.5, text: "Think I could make you the happiest girl in the world (whole wide world)" },
  { time: 15, text: "If you let me, won't you let me, 'cause I'm sure ('cause I'm sure)" },
  { time: 22, text: "Sure, I could make you the happiest girl in the world (I wanna, I wanna, I wanna)" }
];

document.addEventListener('DOMContentLoaded', () => {
  const overlay = document.getElementById('entry-overlay');
  const audio = document.getElementById('bg-music');
  const lyricDisplay = document.getElementById("lyric-line");
  const toggleBtn = document.getElementById('audio-toggle');

  let audioCtx, sourceNode;
  let lastLyricIndex = -1;

  const setupAudio = () => {
    if (!audioCtx) {
      audioCtx = new (window.AudioContext || window.webkitAudioContext)();
      sourceNode = audioCtx.createMediaElementSource(audio);
      sourceNode.connect(audioCtx.destination);
    }
  };

  const syncLyrics = () => {
    const currentTime = audio.currentTime;
    for (let i = 0; i < lyricsMap.length; i++) {
      const curr = lyricsMap[i];
      const next = lyricsMap[i + 1];

      if (
        currentTime >= curr.time &&
        (!next || currentTime < next.time) &&
        lastLyricIndex !== i
      ) {
        lyricDisplay.textContent = curr.text;
        lastLyricIndex = i;
        break;
      }
    }

    if (!audio.paused) requestAnimationFrame(syncLyrics);
  };

  const playAudio = () => {
    setupAudio();
    audio.play().catch(() => {});
    if (overlay) {
      overlay.style.opacity = '0';
      setTimeout(() => overlay.style.display = 'none', 1000);
    }
    requestAnimationFrame(syncLyrics);
  };

  if (overlay) {
    overlay.addEventListener('click', () => {
      const c = setTimeout(() => {
        document.body.classList.remove("not-loaded");
        clearTimeout(c);
        setTimeout(() => {
          document.getElementById("carousel").classList.add("show");
document.getElementById("butterfly1").classList.add("show");
document.getElementById("butterfly2").classList.add("show");
document.getElementById("butterfly3").classList.add("show");
        }, 3000);
        setTimeout(() => {
          document.getElementById("lyric-line").classList.add("show");
        }, 50);
      }, 500);
      playAudio();
    });
  }

  if (toggleBtn) {
    toggleBtn.addEventListener('click', () => {
      setupAudio();
      if (audio.paused) {
        audio.play();
        toggleBtn.innerHTML = '<span class="icon pause-icon"></span>';
        requestAnimationFrame(syncLyrics);
      } else {
        audio.pause();
        toggleBtn.innerHTML = '<span class="icon play-icon"></span>';
      }
    });
  }
});
