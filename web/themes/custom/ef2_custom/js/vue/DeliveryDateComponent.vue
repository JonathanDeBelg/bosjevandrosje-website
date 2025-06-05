<template>
  <div class="js-webform-radios webform-options-display-buttons webform-options-display-buttons-horizontal form-radios">
    <div v-for="(date, index) in dates" :key="index" class="webform-options-display-buttons-wrapper">
      <div class="js-form-item form-item js-form-type-radio form-type-radio js-form-item-frequentie form-item-frequentie">
        <input
          :data-drupal-selector="`edit-bezorginggegevens-opt-${ index }`"
          type="radio"
          class="js-form-item form-item js-form-type-radio form-type-radio js-form-item-bezorginggegevens form-item-bezorggegevens btn-check"
          :id="`edit-bezorginggegevens-opt-${ index }`"
          name="bezorginggegevens"
          :value="date"
          @change="updateFormField"
          required
        >
        <label :for="`edit-bezorginggegevens-opt-${ index }`" class="btn btn-outline-success webform-options-display-buttons-label option">
          {{ date }}
        </label>
      </div>
    </div>

    <div v-if="error !== false" class="webform-options-display-buttons-wrapper">
      <div class="js-form-item form-item js-form-type-radio form-type-radio js-form-item-frequentie form-item-frequentie">
        <label class="btn btn-outline-success webform-options-display-buttons-label option">
          {{ error }}
        </label>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import moment from 'moment'

const props = defineProps({
  date: String
})

const dateVar = ref(props.date)
const error = ref(false)

const isJson = (str) => {
  try {
    JSON.parse(str)
  } catch (e) {
    return false
  }
  return true
}

const getDayName = (dateStr, locale) => {
  const date = new Date(dateStr)
  return date.toLocaleDateString(locale, { weekday: 'long' })
}

const getNumberOfWeeksToAdd = (chosenDate, actualDate) => {
  let dayName = getDayName(actualDate, "en-EN")
  let weeksToAdd = [1, 2, 3]
  const allowedDays = ['Monday', 'Tuesday']
  const deliveryDates = ['thursday', 'friday']

  if (deliveryDates.includes(chosenDate) && allowedDays.includes(dayName)) {
    weeksToAdd = [0, 1, 2]
  }
  return weeksToAdd
}

const dates = computed(() => {
  let dayInt = 5
  if (dateVar.value !== undefined) {
    let jsonDate = isJson(dateVar.value)
    if (jsonDate !== false) {
      let date = JSON.parse(dateVar.value).date
      if (date === 'thursday') {
        dayInt = 4
      }

      let now = new Date()
      let weeksToAdd = getNumberOfWeeksToAdd(date, now)

      let nextThreeWeeksUpcomingDates = []

      weeksToAdd.forEach((week) => {
        nextThreeWeeksUpcomingDates.push(moment().add(week, 'weeks').isoWeekday(dayInt))
      })

      error.value = false
      return nextThreeWeeksUpcomingDates.map((value) => value.format("D-MM-YYYY") + ', ' + value.format("dddd"))
    }
  }
  error.value = "Kies eerst een woonplaats!"
  return []
})

const updateDeliveryDates = () => {
  let placeElem = document.getElementById("edit-woonplaats")
  if (placeElem) {
    dateVar.value = placeElem.options[placeElem.selectedIndex].value
  }
}

const updateFormField = (event) => {
  let fullDate = event.target.value
  let rawDate = fullDate.split(',')[0]

  let bezorgdatumElem = document.getElementById("edit-bezorgdatum")
  if (bezorgdatumElem) {
    bezorgdatumElem.setAttribute('value', fullDate)
  }

  let rawDateField = document.getElementsByName("bezorgdatum_raw")[0]
  if (rawDateField) {
    rawDateField.setAttribute("value", rawDate)
  }

  let rawResidenceField = document.getElementsByName("woonplaats_raw")[0]
  if (rawResidenceField && isJson(dateVar.value)) {
    rawResidenceField.setAttribute("value", JSON.parse(dateVar.value).value)
  }
}

onMounted(() => {
  let placeElem = document.getElementById("edit-woonplaats")
  if (placeElem) {
    placeElem.addEventListener('change', updateDeliveryDates)
  }
})

onBeforeUnmount(() => {
  let placeElem = document.getElementById("edit-woonplaats")
  if (placeElem) {
    placeElem.removeEventListener('change', updateDeliveryDates)
  }
})

// Optional: Watch for prop changes
watch(() => props.date, (newVal) => {
  dateVar.value = newVal
})
</script>