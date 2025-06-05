// Remove no-js class from body when Javascript is activated
document.body.classList.remove('no-js');
document.body.classList.add('js');

document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.paragraph--type--question .faq-title').forEach(function (title) {
    title.addEventListener('click', function () {
      const parent = title.closest('.paragraph--type--question');
      parent.classList.toggle('is-open');
    });
  });
});