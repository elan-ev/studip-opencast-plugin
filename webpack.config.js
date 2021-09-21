const path = require("path");

module.exports = {
  entry: {
    application: "./javascripts/application.js",
    embed: "./javascripts/embed.js"
  },
  output: {
    filename: "[name].js",
    path: path.resolve(__dirname, "static")
  },
  module: {
    rules: [{ test: /\.js$/, exclude: /node_modules/, loader: "babel-loader" }]
  }
};
