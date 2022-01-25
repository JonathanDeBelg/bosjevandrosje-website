const mix = require('laravel-mix');

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

mix.js('js/vue/app.js', 'js').vue({version: 2})
    .js('js/theme/*.js', 'js/bundle.js')
    .sass('scss/main.scss', 'css')
    .sass('scss/editor.scss', 'css')
    .minify(['css/main.css', 'js/app.js', 'js/bundle.js'])
    .sourceMaps(true, 'source-map')
    .polyfill({
        enabled: true,
        useBuiltIns: "entry",
        targets: {"ie": 11}
    });
