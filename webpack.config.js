const path = require('path');

module.exports = {
    entry: './compiled/js/main.js',
    output: {
        filename: 'main.js',
        path: path.resolve(__dirname, 'dist/js')
    },
    mode: 'production'
};