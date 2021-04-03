const { VueLoaderPlugin } = require('vue-loader');
const path = require('path');
const { CleanWebpackPlugin } = require('clean-webpack-plugin');
const WebpackBar = require('webpackbar');

module.exports = {
    mode: 'development',
    entry: {
        UserToken:  './vue/UserToken/UserToken.js'
    },
    output: {
        filename: '[name].build.js',
        path: path.resolve(__dirname, 'dist'),
        publicPath: process.env.BASE_URL,
    },
    resolve: {
        alias: {
            vue$: "vue/dist/vue.runtime.js" // 'vue/dist/vue.common.js' for webpack 1
        },
        extensions: ["*", ".js", ".vue", ".json"]
    },
    module: {
        rules: [
            {
                test: /\.vue$/,
                use: 'vue-loader'
            },
            // this will apply to both plain `.js` files
            // AND `<script>` blocks in `.vue` files
            {
                test: /\.js$/,
                exclude: /node_modules/,
                loader: 'babel-loader',
                options: {
                    presets: ['@babel/preset-env']
                }
            },
            // this will apply to both plain `.css` files
            // AND `<style>` blocks in `.vue` files
            {
                test: /\.css$/,
                use: [
                    'vue-style-loader',
                    'css-loader'
                ]
            }
        ]
    },
    plugins: [
        new VueLoaderPlugin(),
        new WebpackBar(),
        new CleanWebpackPlugin()
    ]
}