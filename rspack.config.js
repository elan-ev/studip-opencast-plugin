const path = require('path');
const { VueLoaderPlugin } = require('rspack-vue-loader');

class ViteManifestPlugin {
  apply(compiler) {
    compiler.hooks.thisCompilation.tap('ViteManifestPlugin', (compilation) => {
      compilation.hooks.processAssets.tap(
        {
          name: 'ViteManifestPlugin',
          stage: compiler.webpack.Compilation.PROCESS_ASSETS_STAGE_REPORT,
        },
        () => {
          const entrypoint = compilation.entrypoints.get('app');

          if (!entrypoint) {
            return;
          }

          const files = entrypoint.getFiles();
          const entryFile = files.find((file) => file.endsWith('.js'));
          const css = files.filter((file) => file.endsWith('.css'));

          compilation.emitAsset(
            '.rspack/manifest.json',
            new compiler.webpack.sources.RawSource(
              JSON.stringify({
                'vueapp/app.js': {
                  file: entryFile,
                  src: 'vueapp/app.js',
                  isEntry: true,
                  ...(css.length ? { css } : {}),
                },
              }, null, 2)
            )
          );
        }
      );
    });
  }
}

module.exports = {
  mode: 'production',
  entry: {
    app: path.resolve(__dirname, 'vueapp/app.js'),
  },
  output: {
    path: path.resolve(__dirname, 'static'),
    filename: '[name].[contenthash:8].js',
    chunkFilename: '[name].[contenthash:8].js',
    assetModuleFilename: '[name].[contenthash:8][ext]',
    clean: true,
  },
  resolve: {
    alias: {
      '@': path.resolve(__dirname, 'vueapp'),
      '@components': path.resolve(__dirname, 'vueapp/components'),
      '@studip': path.resolve(__dirname, 'vueapp/components/Studip'),
    },
    extensions: ['.vue', '.js', '...'],
  },
  experiments: {
    css: true,
  },
  module: {
    rules: [
      {
        test: /\.vue$/,
        loader: 'rspack-vue-loader',
        options: {
          experimentalInlineMatchResource: true,
        },
      },
      {
        test: /\.css$/,
        type: 'css/auto',
      },
      {
        test: /\.(png|jpe?g|gif|svg|webp|woff2?|ttf|eot)$/i,
        type: 'asset/resource',
      },
    ],
  },
  plugins: [
    new VueLoaderPlugin(),
    new ViteManifestPlugin(),
  ],
  devtool: false,
};
