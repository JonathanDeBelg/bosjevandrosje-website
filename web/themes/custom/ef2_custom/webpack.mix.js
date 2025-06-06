const mix = require('laravel-mix');
const webpack = require('webpack');

require('laravel-mix-polyfill');
/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */

mix.options({ processCssUrls: false });

mix.js('js/vue/app.js', 'js').vue({version: 3})
    .js('js/theme/*.js', 'js/bundle.js')
    .sass('scss/main.scss', 'css')
    .sass('scss/editor.scss', 'css')
    .minify(['css/main.css', 'js/app.js', 'js/bundle.js'])
    .sourceMaps(true, 'source-map')
    .polyfill({
        enabled: true,
        useBuiltIns: "entry",
        targets: {"ie": 11}
    }).webpackConfig({
        plugins: [
            new webpack.ProvidePlugin({
                $: 'jquery',
                jQuery: 'jquery',
                'window.jQuery': 'jquery'
                })    
        ]
    }).webpackConfig({
        resolve: {
            alias: {
                lodash: 'lodash-es'
            }
        },
        plugins: [
            new webpack.ProvidePlugin({
                $: 'jquery',
                jQuery: 'jquery',
                'window.jQuery': 'jquery'
                // ❗ Do NOT add '_' here — let Drupal own `_`
            })
        ]
    });
