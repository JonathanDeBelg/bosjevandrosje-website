// Remove no-js class from body when Javascript is activated
document.body.classList.remove('no-js');
document.body.classList.add('js')

var rad = document.querySelectorAll('.js-form-item-bezorginggegevens');
for (var i = 0; i < rad.length; i++) {
  rad[i].addEventListener('change', function() {
    var radioChoice = document.querySelector('input[name="bezorginggegevens"]:checked').value;
    document.getElementById("edit-bezorgdatum").setAttribute('value', radioChoice);
  });
}g
