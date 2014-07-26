module.exports = function (grunt) {
	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),
		csslint: {
			options: {
				csslintrc: 'tests/conf/.csslintrc'
			},
			src: ['css/*.css', 'themes/default.css', 'themes/night.css']
		},
		jsvalidate: {
			options: {
				globals: {},
				esprimaOptions: {},
				verbose: false
			},
			targetName: {
				files: {
					src: ['Gruntfile.js', 'js/*.js', 'lib/**/*.js']
				}
			}
		},
		xml_validator: {
			xsl: {
				src: ['*.xsl']
			}
		}
	});

	grunt.loadNpmTasks('grunt-contrib-csslint');
	grunt.loadNpmTasks('grunt-jsvalidate');
	grunt.loadNpmTasks('grunt-xml-validator');

	grunt.registerTask('default', [
		'csslint',
		'jsvalidate',
		'xml_validator:xsl'
	]);
};
