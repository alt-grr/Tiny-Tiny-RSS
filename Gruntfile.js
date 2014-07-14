module.exports = function (grunt) {
	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),
		csslint: {
			options: {
				csslintrc: 'tests/.csslintrc'
			},
			src: ['css/*.css']
		}
	});

	grunt.loadNpmTasks('grunt-contrib-csslint');
	grunt.registerTask('default', ['csslint']);
};
