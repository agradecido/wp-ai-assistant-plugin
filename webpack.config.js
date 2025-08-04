const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const { WebpackManifestPlugin } = require('webpack-manifest-plugin');


module.exports = {
  mode: process.env.NODE_ENV === 'production' ? 'production' : 'development',

  entry: {
    'chatbot': './assets/src/js/index.js',
    'admin': './assets/src/js/admin.js',
    'history': './assets/src/js/history.js',
    'summary': './assets/src/js/summary.js',
    'tailwind': './assets/src/scss/tailwind.scss' // Add Tailwind CSS entry
  },

  output: {
    filename: 'js/[name].[contenthash].js',
    path: path.resolve(__dirname, 'assets/dist'),
    clean: true,
  },

  devtool: 'source-map',

  module: {
    rules: [
      {
        test: /\.js$/,
        exclude: /node_modules/,
        use: {
          loader: 'babel-loader',
          options: {
            presets: ['@babel/preset-env']
          }
        }
      },
      {
        test: /\.(scss|sass)$/,
        use: [
          MiniCssExtractPlugin.loader,
          'css-loader',
          'postcss-loader',
          'sass-loader'
        ]
      },
      {
        test: /\.css$/,
        use: [
          MiniCssExtractPlugin.loader,
          'css-loader',
          'postcss-loader'
        ]
      }
    ]
  },

  plugins: [
    new MiniCssExtractPlugin({
      filename: 'css/[name].[contenthash].css'
    }),
    new WebpackManifestPlugin({
      fileName: 'manifest.json',
      publicPath: '',
    })
  ]
};
