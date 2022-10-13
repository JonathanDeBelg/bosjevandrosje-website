// Remove no-js class from body when Javascript is activated
document.body.classList.remove('no-js');
document.body.classList.add('js')

var rad = document.querySelectorAll('.js-form-item-bezorginggegevens');
for (var i = 0; i < rad.length; i++) {
  rad[i].addEventListener('change', function() {
    var radioChoice = document.querySelector('input[name="bezorginggegevens"]:checked').value;
    document.getElementById("edit-bezorgdatum").setAttribute('value', radioChoice);
  });
}

function getNextDayOfWeek(date, dayOfWeek) {
  // Code to check that date and dayOfWeek are valid left as an exercise ;)
  var resultDate = new Date(date.getTime());
  resultDate.setDate(date.getDate() + (7 + dayOfWeek - date.getDay()) % 7);
  return resultDate;
}

function createBoxes(index, date) {
  return "" +
    "<input\n" +
    "        data-drupal-selector=\"edit-bezorginggegevens-opt-" + index + "\"\n" +
    "        type=\"radio\"\n" +
    "        class=\"js-form-item form-item js-form-type-radio form-type-radio js-form-item-bezorginggegevens form-item-bezorggegevens btn-check\"\n" +
    "        id=\"edit-bezorginggegevens-opt-" + index + "\"\n" +
    "        name=\"bezorginggegevens\"\n" +
    "        value=\" " + date + " \n" +
    "        required>\n" +
    "      <label for=\edit-bezorginggegevens-opt-" + index + "\" class=\"btn btn-outline-success webform-options-display-buttons-label option\">" +date + </label>";
}
