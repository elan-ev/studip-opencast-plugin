const path = require('path');
const { VueLoaderPlugin } = require('vue-loader');
const rspack = require('@rspack/core');

module.exports = (env, argv) => {
  const mode = argv.mode || 'production';

  return {
    mode,
    entry: path.resolve(__dirname, '../vueapp/vue2/register.js'),
    output: {
      path: path.resolve(__dirname, '../../static_cw'),
      filename: 'register-vue2.umd.js',
      library: {
        name: 'courseware-plugin-opencast-video',
        type: 'umd',
      },
      globalObject: 'this',
      clean: false,
    },
    resolve: {
      alias: {
        'vue/compiler-sfc': 'vue/compiler-sfc',
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
          loader: 'vue-loader',
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
      new rspack.DefinePlugin({
        'process.env.NODE_ENV': JSON.stringify(mode),
        process: JSON.stringify({ env: { NODE_ENV: mode } }),
      }),
    ],
    devtool: false,
  };
};
