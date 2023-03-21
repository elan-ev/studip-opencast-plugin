const path = require("path");

const { VueLoaderPlugin } = require('vue-loader');

module.exports = {
    entry: {
        register : "./vueapp/register.js"
    },

    output: {
        filename: "[name].js",
        path: path.resolve(__dirname, "../static_cw"),
        library: "courseware-plugin-opencast-video",
        libraryTarget: "umd",
        umdNamedDefine: true
    },

    module: {
        rules: [
            {
                test: /\.vue$/,
                use: 'vue-loader'
            },
            {
                test: /\.js$/,
                exclude: /node_modules/,
                loader: "babel-loader"
            }
        ]
    },

    plugins: [
        new VueLoaderPlugin()
    ]
};
