const path = require('path'); // node.js uses CommonJS modules

const { VueLoaderPlugin }       = require('vue-loader');
const HtmlWebpackPlugin         = require('html-webpack-plugin');
const { CleanWebpackPlugin }    = require('clean-webpack-plugin');
const MiniCssExtractPlugin      = require("mini-css-extract-plugin");


module.exports = (env) => {
    return {
        entry: [
            './vueapp/app.js'
            ], // the entry point
        output: {
            filename: '[name].[contenthash].js', // the output filename
            path: path.resolve(__dirname, 'static'), // fully qualified path
            publicPath: '/'
        },
        module: {
            rules: [{
                test: /\.vue$/,
                use: 'vue-loader'
            },
            {
                test: /\.css$/,
                use: [
                    {
                        loader: MiniCssExtractPlugin.loader
                    },
                    {
                        loader: "css-loader",
                        options: {
                            url: false,
                            importLoaders: 1
                        }
                    },
                    {
                        loader: "postcss-loader"
                    }
                ]
            },
            {
                test: /\.scss$/,
                use: [
                    {
                        loader: MiniCssExtractPlugin.loader
                    },
                    {
                        loader: "css-loader",
                        options: {
                            url: false,
                            importLoaders: 2
                        }
                    },
                    {
                        loader: "postcss-loader"
                    },
                    {
                        loader: "sass-loader"
                    }
                ]
            },
            {
                test: /\.js?$/,
                exclude: /node_modules/,
                loader: 'babel-loader',
                options: {
                    presets: ['@babel/preset-env']
                },
                include: [ path.resolve(__dirname, 'app') ]
            }]
        },
        plugins: [
            new CleanWebpackPlugin(),
            new VueLoaderPlugin(),
            new HtmlWebpackPlugin({
                template: 'vueapp/templates/course_index.php',
                inject: false,
                minify: false,
                filename: '../app/views/course/index.php'
            }),
            new HtmlWebpackPlugin({
                template: 'vueapp/templates/admin_index.php',
                inject: false,
                minify: false,
                filename: '../app/views/admin/index.php'
            }),
            new MiniCssExtractPlugin({
                filename: "static/[name].css",
                chunkFilename: "static/[name].css?h=[chunkhash]"
            }),
        ],
        resolve: {
            extensions: ['.vue', '.js'],
            alias: {
                '@': path.resolve(__dirname, 'vueapp'),
                //'@studip': env.studip,
                '@studip': path.resolve(__dirname, 'vueapp/components/Studip'),
                '@popperjs/core': path.resolve(__dirname, 'node_modules/@popperjs/core')
            }
        }
    }
};
