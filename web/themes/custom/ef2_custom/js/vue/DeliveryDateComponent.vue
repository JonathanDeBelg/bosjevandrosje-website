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

          let now = new Date();
          let weeksToAdd = this.getNumberOfWeeksToAdd(date, now);

          let nextThreeWeeksUpcomingDates = [];

          weeksToAdd.forEach((week) => nextThreeWeeksUpcomingDates.push(this.$moment().add(week, 'weeks').isoWeekday(dayInt)))

          this.error = false;
          return nextThreeWeeksUpcomingDates.map((value)=> {return value.format("D-MM-YYYY") + ', ' +  value.format("dddd")});
        }
      }
      this.error = "Kies eerst een woonplaats!"
      return [];
    },
  },

  mounted() {
    let placeElem = document.getElementById("edit-woonplaats");
    placeElem.addEventListener('change', this.updateDeliveryDates);
  },

  destroyed() {
    let placeElem = document.getElementById("edit-woonplaats");
    placeElem.removeEventListener('change', this.updateDeliveryDates)
  },

  methods: {
    getNumberOfWeeksToAdd: function (chosenDate, actualDate) {
      let dayName = this.getDayName(actualDate, "en-EN");
      let weeksToAdd = [1, 2, 3];
      const allowedDays = ['Monday', 'Tuesday', 'Saturday', 'Sunday'];
      const deliveryDates = ['thursday', 'friday'];

      if(deliveryDates.includes(chosenDate) && allowedDays.includes(dayName)) {
          weeksToAdd = [0, 1, 2];
      }

      return weeksToAdd;
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
    },
  },
}
</script>
