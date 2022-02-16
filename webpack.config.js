const path = require('path'); // node.js uses CommonJS modules

const { VueLoaderPlugin }       = require('vue-loader');
const HtmlWebpackPlugin         = require('html-webpack-plugin');
const { CleanWebpackPlugin }    = require('clean-webpack-plugin');

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
            }, {
    			test: /.scss$/,
    			use: [
    				{
    					loader: 'file-loader',
    					options: {
    						name: 'styles.css',
    						outputPath: ''
    					}
    				},
                    {
    					loader: 'extract-loader',
    				},
    				{
    					loader: 'css-loader',
                        options: {
                            url: false
                        }
    				},
    				{
    					loader: 'sass-loader'
    				}
    			]
    		}, {
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
        ],
        resolve: {
            extensions: ['.vue', '.js'],
            alias: {
                '@': path.resolve(__dirname, 'vueapp'),
                '@studip': env.studip
            }
        }
    }
};
