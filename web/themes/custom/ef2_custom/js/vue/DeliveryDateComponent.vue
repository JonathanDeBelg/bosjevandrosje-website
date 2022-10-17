<template>
  <div class="webform-options-display-buttons-wrapper">
    <div v-for="(date, index) in dates" class="js-form-item form-item js-form-type-radio form-type-radio js-form-item-frequentie form-item-frequentie">
      <input
        :data-drupal-selector="`edit-bezorginggegevens-opt-${ index }`"
        type="radio"
        class="js-form-item form-item js-form-type-radio form-type-radio js-form-item-bezorginggegevens form-item-bezorggegevens btn-check"
        :id="`edit-bezorginggegevens-opt-${ index }`"
        name="bezorginggegevens"
        :value="`${ date }`"
      required>
      <label :for="`edit-bezorginggegevens-opt-${ index }`" class="btn btn-outline-success webform-options-display-buttons-label option">
        {{ date }}
      </label>
    </div>
  </div>
</template>

<script>
module.exports = {
  name: 'deliverydate',

  props: ['date'],

  data: function() {
    return {
      dateVar: this.date
    }
  },

  computed: {
    dates() {
      let dayInt = 5;
      let date = JSON.parse(this.dateVar).value;
      if(date === 'thursday') {
        dayInt = 4;
      }
      let actualDate = new Date();
      let daysToAdd = this.getNumberOfDays(actualDate);

      let nextThreeWeeksUpcomingDates = [];

      for(let i in [1, 2, 3]) {
        daysToAdd = daysToAdd * i;

        nextThreeWeeksUpcomingDates.push(new Date(
          actualDate.setDate(
            actualDate.getDate() + ((7 - actualDate.getDay() + dayInt) % 7 || 7) + daysToAdd,
          ),
        ));
      }
      return nextThreeWeeksUpcomingDates.map((value)=> {return value.toLocaleDateString() + ', ' +  this.getDayName(value, 'nl-NL')});
    },
  },

  created() {
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
    getNumberOfDays: function (actualDate) {
      let dayName = this.getDayName(actualDate, "en-EN");
      let daysToAdd = 7;
      if (['Monday', 'Tuesday', 'Saturday', 'Sunday'].includes(dayName)) {
        daysToAdd = 0;
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
    }
  },
}
</script>
