import { createApp } from "vue";

window.axios = require('axios');
window.lodash = require('lodash');

import DeliveryDateComponent from './DeliveryDateComponent';
import YoutubeWrapper from './YoutubeWrapper'

require('moment/locale/nl')

const app = createApp({})
app.component('delivery-date', DeliveryDateComponent);
app.component('ef2-youtube-wrapper', YoutubeWrapper);