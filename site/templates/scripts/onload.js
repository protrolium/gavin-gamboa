// online-world / offline-world experiment from rip.space
function checkOnlineStatus() {
  const offlineWorld = document.getElementById('offline-world');
  const onlineWorld = document.getElementById('online-world');
  
  // Only proceed if both elements exist
  if (!offlineWorld || !onlineWorld) {
    return;
  }
  
  if (navigator.onLine) {
      offlineWorld.style.display = 'none';
      onlineWorld.style.display = 'block';
  } else {
      offlineWorld.style.display = 'block';
      onlineWorld.style.display = 'none';
  }
}

window.addEventListener('load', checkOnlineStatus);
window.addEventListener('online', checkOnlineStatus);
window.addEventListener('offline', checkOnlineStatus);

/////////

document.addEventListener("DOMContentLoaded", function() {
    var lazyloadImages;    
  
    if ("IntersectionObserver" in window) {
      lazyloadImages = document.querySelectorAll(".lazyLoad");
      var imageObserver = new IntersectionObserver(function(entries, observer) {
        entries.forEach(function(entry) {
          if (entry.isIntersecting) {
            var image = entry.target;
            image.classList.remove("lazyLoad");
            imageObserver.unobserve(image);
          }
        });
      });
  
      lazyloadImages.forEach(function(image) {
        imageObserver.observe(image);
      });
    } else {  
      var lazyloadThrottleTimeout;
      lazyloadImages = document.querySelectorAll(".lazyLoad");
      
      function lazyload () {
        if(lazyloadThrottleTimeout) {
          clearTimeout(lazyloadThrottleTimeout);
        }    
  
        lazyloadThrottleTimeout = setTimeout(function() {
          var scrollTop = window.pageYOffset;
          lazyloadImages.forEach(function(img) {
              if(img.offsetTop < (window.innerHeight + scrollTop)) {
                img.src = img.dataset.src;
                img.classList.remove('lazyLoad');
              }
          });
          if(lazyloadImages.length == 0) { 
            document.removeEventListener("scroll", lazyload);
            window.removeEventListener("resize", lazyload);
            window.removeEventListener("orientationChange", lazyload);
          }
        }, 20);
      }
  
      document.addEventListener("scroll", lazyload);
      window.addEventListener("resize", lazyload);
      window.addEventListener("orientationChange", lazyload);
    }
})