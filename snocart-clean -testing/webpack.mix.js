const mix = require('laravel-mix');
const webpack = require('webpack');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel applications. By default, we are compiling the CSS
 | file for the application as well as bundling up all the JS files.
 |
 */

// Add environment variables to Mix
mix.webpackConfig({
    plugins: [
        new webpack.DefinePlugin({
            'process.env': {
                MIX_PUSHER_APP_KEY: JSON.stringify(process.env.MIX_PUSHER_APP_KEY),
                MIX_PUSHER_APP_CLUSTER: JSON.stringify(process.env.MIX_PUSHER_APP_CLUSTER),
                MIX_APP_URL: JSON.stringify(process.env.APP_URL),
            }
        })
    ],
    resolve: {
        fallback: {
            "path": require.resolve("path-browserify"),
            "os": require.resolve("os-browserify/browser"),
            "crypto": require.resolve("crypto-browserify"),
            "stream": require.resolve("stream-browserify")
        }
    }
});

mix.js('resources/js/app.js', 'public/js')
    .postCss('resources/css/app.css', 'public/css', [])
    .version() // Add versioning for cache busting
    .sourceMaps(); // Enable source maps for debugging