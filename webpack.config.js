const Encore = require('@symfony/webpack-encore');
const path = require('path');

// Manually configure the runtime environment if not already configured yet by the "encore" command.
// It's useful when you use tools that rely on webpack.config.js file.
if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore
    .setOutputPath('public/build/')
    .setPublicPath('/build')
    // Copy images from assets and vendor directory
    .copyFiles({
        from: './vendor/maqueta/diba/v3/assets/img',
        to: 'img/[path][name].[ext]',
        pattern: /\.(ico|gif|svg|png|jpg|jpeg)$/,
    })
    // Pages and entries.
    .addEntry('app', './assets/js/app.js')
    .addEntry('back', './assets/js/back.js')
    .addEntry('example', './assets/js/pages/example.js')
    // .autoProvideVariables()
    // When enabled, Webpack "splits" your files into smaller pieces for greater optimization.
    .splitEntryChunks()
    // will require an extra script tag for runtime.js
    // but, you probably want this, unless you're building a single-page app
    .enableSingleRuntimeChunk()
    /*
     * FEATURE CONFIG
     *
     * Enable & configure other features below. For a full
     * list of features, see:
     * https://symfony.com/doc/current/frontend.html#adding-more-features
     */
    .cleanupOutputBeforeBuild()
    .enableBuildNotifications()
    .enableSourceMaps(!Encore.isProduction())
    // enables hashed filenames (e.g. app.abc123.css)
    .enableVersioning()

    // Use external Babel configuration
    .configureBabel(null)

    .addAliases({
        '@maqueta': path.resolve(__dirname, 'vendor/maqueta/diba/v3'),
    })
    .enablePostCssLoader((options) => {
        options.postcssOptions = {
            parser: 'postcss-scss',
            plugins: [
                // Add any PostCSS plugins you need here
            ]
        };
    })
    // enables Sass/SCSS support
    .enableSassLoader()

    // Add this line to resolve SCSS files
    .addLoader({
        test: /\.scss$/,
        use: [
            'resolve-url-loader',
            {
                loader: 'sass-loader',
                options: {
                    sourceMap: true,
                },
            },
        ],
    })
    // uncomment if you use TypeScript
    // .enableTypeScriptLoader()
    .enableIntegrityHashes(Encore.isProduction())
    // uncomment if you're having problems with a jQuery plugin
    // .autoProvidejQuery()
    // uncomment if you use API Platform Admin (composer req api-admin)
    // .enableReactPreset()
    // .addEntry('admin', './assets/js/admin.js')
;

module.exports = Encore.getWebpackConfig();
