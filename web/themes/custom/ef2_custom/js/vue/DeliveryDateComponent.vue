<template>
  <div class="js-webform-radios webform-options-display-buttons webform-options-display-buttons-horizontal form-radios">
    <div v-for="(date, index) in dates" class="webform-options-display-buttons-wrapper">
      <div class="js-form-item form-item js-form-type-radio form-type-radio js-form-item-frequentie form-item-frequentie">
        <input
          :data-drupal-selector="`edit-bezorginggegevens-opt-${ index }`"
          type="radio"
          class="js-form-item form-item js-form-type-radio form-type-radio js-form-item-bezorginggegevens form-item-bezorggegevens btn-check"
          :id="`edit-bezorginggegevens-opt-${ index }`"
          name="bezorginggegevens"
          :value="`${ date }`"
          v-on:change="updateFormField"
        required>
        <label :for="`edit-bezorginggegevens-opt-${ index }`" class="btn btn-outline-success webform-options-display-buttons-label option">
          {{ date }}
        </label>
      </div>
    </div>

    <div v-if="this.error !== false" class="webform-options-display-buttons-wrapper">
      <div class="js-form-item form-item js-form-type-radio form-type-radio js-form-item-frequentie form-item-frequentie">
        <label class="btn btn-outline-success webform-options-display-buttons-label option">
          {{ this.error }}
        </label>
      </div>
    </div>
  </div>
</template>

<script>
module.exports = {
  name: 'deliverydate',

  props: ['date'],

  data: function() {
    return {
      dateVar: this.date,
      error: false,
    }
  },

  computed: {
    dates() {
      let dayInt = 5;
      if(this.dateVar !== undefined) {
        let jsonDate = this.isJson(this.dateVar);
        if(jsonDate !== false) {
          let date = JSON.parse(this.dateVar).date;
          if(date === 'thursday') {
            dayInt = 4;
          }
          let actualDate = new Date();
          let daysToAdd = this.getNumberOfDays(date, actualDate);

          let nextThreeWeeksUpcomingDates = [];
          for(let i in [1, 2, 3]) {
            daysToAdd = daysToAdd * i;

            nextThreeWeeksUpcomingDates.push(new Date(
              actualDate.setDate(
                actualDate.getDate() + ((7 - actualDate.getDay() + dayInt) % 7 || 7) + daysToAdd,
              ),
            ));
          }

          this.error = false;
          return nextThreeWeeksUpcomingDates.map((value)=> {return value.toLocaleDateString() + ', ' +  this.getDayName(value, 'nl-NL')});
        }
      }
      this.error = "Kies eerst een woonplaats!"
      return [];
    },
  },

  mounted() {
    let placeElem = document.getElementById("edit-woonplaats");
    placeElem.addEventListener('change', this.updateDeliveryDates)
  },

  destroyed() {
    let placeElem = document.getElementById("edit-woonplaats");
    placeElem.removeEventListener('change', this.updateDeliveryDates)
  },

  methods: {
    getNumberOfDays: function (chosenDate,actualDate) {
      let dayName = this.getDayName(actualDate, "en-EN");
      let daysToAdd = 7;

      if(chosenDate === 'thursday') {
        if (['Monday', 'Tuesday', 'Wednesday', 'Saturday', 'Sunday'].includes(dayName)) {
          daysToAdd = 0;
        }
      } else {
        if (['Monday', 'Tuesday', 'Saturday', 'Sunday'].includes(dayName)) {
          daysToAdd = 0;
        }
      }
      return daysToAdd;

    },

    getDayName(dateStr, locale) {
      var date = new Date(dateStr);
      return date.toLocaleDateString(locale, { weekday: 'long' });
    },

    updateDeliveryDates(event) {
      let placeElem = document.getElementById("edit-woonplaats");
      this.dateVar = placeElem.options[placeElem.selectedIndex].value;
    },

    isJson(str) {
      try {
        JSON.parse(str);
      } catch (e) {
        return false;
      }
      return true;
    },

    updateFormField: function(value) {
      document.getElementById("edit-bezorgdatum").setAttribute('value', value.target.value);
    }
  },
}
</script>
