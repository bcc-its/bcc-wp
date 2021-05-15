const defaultConfig = require('@wordpress/scripts/config/webpack.config')

defaultConfig.entry = {
  visibility: './src/visibility'
}

module.exports = defaultConfig
