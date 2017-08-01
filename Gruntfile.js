module.exports = function( grunt ) {
	grunt.initConfig( {
		pkg: grunt.file.readJSON( "package.json" ),

		copy: {
			main: {
				files: [
					{
						expand: true,
						src: "wsuwp-university-taxonomies.php",
						dest: "dist-wp-plugin/"
					},
					{
						expand: true,
						src: "css/*",
						dest: "dist-wp-plugin/"
					}
				]
			}
		},

		jshint: {
			grunt_script: {
				src: [ "Gruntfile.js" ],
				options: {
					curly: true,
					eqeqeq: true,
					noarg: true,
					quotmark: "double",
					undef: true,
					unused: false,
					node: true     // Define globals available when running in Node.
				}
			},
            plugin_scripts: {
                src: [ "js/*.js" ],
                options: {
                    bitwise: true,
                    curly: true,
                    eqeqeq: true,
                    forin: true,
                    freeze: true,
                    noarg: true,
                    nonbsp: true,
                    quotmark: "double",
                    undef: true,
                    unused: true,
                    browser: true, // Define globals exposed by modern browsers.
                    jquery: true   // Define globals exposed by jQuery.
                }
            }
		},

		jscs: {
			scripts: {
				src: [ "Gruntfile.js", "js/*.js" ],
				options: {
					preset: "jquery",
					requireCamelCaseOrUpperCaseIdentifiers: false, // We rely on name_name too much to change them all.
					maximumLineLength: 250
				}
			}
		}
	} );

	grunt.loadNpmTasks( "grunt-contrib-copy" );
	grunt.loadNpmTasks( "grunt-contrib-jshint" );
	grunt.loadNpmTasks( "grunt-jscs" );

	// Default task(s).
	grunt.registerTask( "default", [ "jscs", "jshint" ] );
	grunt.registerTask( "wp-plugin", [ "copy" ] );
};
