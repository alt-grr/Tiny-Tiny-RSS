module.exports = function (grunt) {
	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),
		csslint: {
			options: {
				csslintrc: 'tests/.csslintrc'
			},
			src: ['css/*.css', 'themes/*.css']
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
		}
	});

	grunt.loadNpmTasks('grunt-contrib-csslint');
	grunt.loadNpmTasks('grunt-jsvalidate');

	grunt.registerTask('default', [
		'csslint',
		'jsvalidate'
	]);
};
