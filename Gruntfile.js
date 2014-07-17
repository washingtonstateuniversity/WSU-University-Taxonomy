module.exports = function(grunt) {
	// Project configuration
	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),
		copy: {
			main: {
				files: [
					{
						expand: true,
						src: 'wsuwp-university-taxonomies.php',
						dest: 'dist-wp-plugin/'
					},
					{
						expand: true,
						src: 'css/*',
						dest: 'dist-wp-plugin/'
					}
				]
			}
		}
	});

	grunt.loadNpmTasks('grunt-contrib-copy');

	grunt.registerTask('wp-plugin', ['copy']);
};