let webpack = require('webpack');
let path = require('path');

module.exports = {
    entry: [
        './app/app.jsx'
    ],
    plugins: [
        new webpack.optimize.UglifyJsPlugin({
            compressor: {
                warnings: false
            }
        }),

    ],
    output: {
        path: __dirname,
        filename: './public/bundle.js'
    },
    resolve: {
      root: __dirname,
        modulesDirectories:[
            'node_modules',
            './app/components',
            './app/api'
        ],
        alias: {
            app:'app',
            applicationStyles:'app/styles/app.scss'
        },
        extensions: ['', '.js', '.jsx', '.json']
    },
    module: {
      loaders: [
          {
            loader: 'babel-loader',
              query: {
                presets: ['react', 'es2015', 'stage-0']
              },
              test: /\.jsx?$/,
              exclude: /(node_modules|bower_components)/
          }
      ]
    },
    devtool: 'cheap-module-eval-source-map'
};
