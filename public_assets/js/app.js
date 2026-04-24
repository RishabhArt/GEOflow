document.querySelectorAll('.lang-switcher a').forEach((link) => {
  link.addEventListener('click', () => {
    document.body.classList.add('language-switching');
  });
});

