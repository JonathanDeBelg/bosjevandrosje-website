import Vue from 'vue';

window.axios = require('axios');
window.lodash = require('lodash');

import DeliveryDateComponent from './DeliveryDateComponent';

new Vue({
    'el': '#page-wrapper',
    components: {
      'delivery-date': DeliveryDateComponent,
    },
    comments: true,
});


