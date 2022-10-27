import Vue from 'vue';

window.axios = require('axios');
window.lodash = require('lodash');

import DeliveryDateComponent from './DeliveryDateComponent';

const moment = require('moment')
require('moment/locale/nl')

Vue.use(require('vue-moment'), {
  moment
})

new Vue({
    'el': '#page-wrapper',
    components: {
      'delivery-date': DeliveryDateComponent,
    },
    comments: true,
});


