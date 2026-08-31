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

/////////

// shop-item.latte: live-updating price = unit price (cents) x quantity, generic across
// any shop-item product/form. Unit price comes from whichever source the form actually
// has: a score_type-style variant select, if present, is authoritative once the customer
// picks an option; otherwise falls back to the form's flat base price (shop-item.php
// exposes it as data-unit-price-cents, since a form with no variant selector has no field
// for JS to read a live price from at all).
function updateShopItemPrice() {
  var scoreType = document.getElementById('Inputfield_score_type');
  var quantity = document.getElementById('Inputfield_quantity');
  var priceEl = document.getElementById('shop-item-price');

  // Only present on shop-item pages; no-op everywhere else.
  if (!priceEl) {
    return;
  }

  var basePriceCents = parseInt(priceEl.getAttribute('data-unit-price-cents'), 10) || 0;

  // Derive which score_type value keeps quantity visible from its own data-show-if
  // attribute (e.g. "score_type=3000"), rather than hardcoding that value again here —
  // stays in sync if the FormBuilder dependency is ever changed in admin. Only relevant
  // when a score_type field actually exists on this form.
  // data-show-if lives on the wrapping .Inputfield div (id="wrap_Inputfield_quantity"),
  // not on the <input> itself — closest() walks up to find it rather than assuming
  // a fixed wrapper id/depth.
  var quantityShowIfValue = null;
  if (scoreType && quantity) {
    var showIfEl = quantity.closest('[data-show-if]');
    var showIf = showIfEl ? (showIfEl.getAttribute('data-show-if') || '') : '';
    var match = showIf.match(/score_type=([^,]+)/);
    if (match) quantityShowIfValue = match[1];
  }

  function render() {
    var unitCents = scoreType ? (parseInt(scoreType.value, 10) || 0) : basePriceCents;
    var qty = quantity ? (parseInt(quantity.value, 10) || 1) : 1;
    var total = (unitCents * qty) / 100;
    priceEl.textContent = '$' + total.toFixed(2);
  }

  if (scoreType) {
    scoreType.addEventListener('change', function() {
      // data-show-if only hides the field — its stale value still gets submitted. Quantity
      // only means anything for the physical option, so reset it rather than silently
      // carrying a leftover count (e.g. 2) into a digital order that should only ever be 1.
      if (quantity && quantityShowIfValue !== null && scoreType.value !== quantityShowIfValue) {
        quantity.value = '1';
      }
      render();
    });
  }
  if (quantity) {
    quantity.addEventListener('input', render);
    quantity.addEventListener('change', render);
  }
  render();
}

document.addEventListener('DOMContentLoaded', updateShopItemPrice);