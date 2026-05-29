const wpPlugin = require('@wordpress/eslint-plugin');
const globals = require('globals');

module.exports = [
	// Exclude third-party and generated directories.
	{
		ignores: ['lib/**', 'vendor/**', 'node_modules/**', 'build/**', 'eslint.config.js'],
	},

	// WordPress recommended rules (flat config array).
	...wpPlugin.configs.recommended,

	// Block source files — WordPress runtime externals + browser globals.
	{
		files: ['src/**/*.{js,jsx,ts,tsx}'],
		languageOptions: {
			globals: {
				...globals.browser,
			},
		},
		rules: {
			// @wordpress/* packages are WordPress runtime externals provided by
			// wp-scripts webpack config — they are not installed npm packages.
			'import/no-unresolved': 'off',
			'import/no-extraneous-dependencies': 'off',
		},
	},
];
