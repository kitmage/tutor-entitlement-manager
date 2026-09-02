document.addEventListener('click',function(e){var b=e.target.closest('.kte-copy');if(!b)return;navigator.clipboard.writeText(b.dataset.url).then(function(){b.textContent='Copied';});});
