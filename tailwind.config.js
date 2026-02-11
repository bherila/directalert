module.exports = {
  content: [
    './resources/**/*.blade.php',
    './resources/**/*.js',
    './resources/**/*.vue',
  ],
  theme: {
    extend: {
      colors: {
        teal: {
          500: '#6cb5f9', // Adjust this hex code to match the exact teal color in the screenshot
        },
        lime: {
          500: '#9CCC65', // Adjust this hex code to match the exact green color of the button
          600: '#8BC34A', // Slightly darker shade for hover state
        },
      },
    },
  },
  plugins: [],
}
