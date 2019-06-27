module.exports = function(grunt) {

    const sass = require('node-sass');

    require('load-grunt-tasks')(grunt);

	// Project configuration.
	grunt.initConfig({

		pkg: grunt.file.readJSON('package.json'),

        // Sass linting with Stylelint.
		stylelint: {
			options: {
				configFile: '.stylelintrc'
			},
			all: [
				'assets/css/*.scss'
			]
        },

		sass: {
			compile: {
				options: {
					implementation: sass,
					sourceMap: false
				},
				files: [{
					expand: true,
					cwd: 'assets/css/',
					src: ['*.scss'],
					dest: 'assets/css/',
					ext: '.css'
				}]
			}
		},
        
        // Minify all .css files.
		cssmin: {
			options: {
				mergeIntoShorthands: false,
			},
			target: {
				files: [
					{
						expand: true,
						cwd: 'assets/css',
						src: ['*.css', '!*.min.css'],
						dest: 'assets/css',
						ext: '.min.css'
					}
				],
			}
        },

		uglify: {
			options: {
				mangle: false,
			},
			target: {
				files: [{
					expand: true,
					cwd: 'assets/js',
					src: [ '*.js', '!*.min.js', '!*jquery*.js' ],
					dest: 'assets/js',
					ext: '.min.js',
					extDot: 'last',
				}]
			}
        },
        
        // Watch changes for assets.
		watch: {
			css: {
				files: ['assets/css/*.scss'],
				tasks: ['sass', 'cssmin' ]
			},
			js: {
				files: [
					'assets/js/*js',
					'!assets/js/*.min.js'
				],
				tasks: ['uglify']
			}
		},

		checktextdomain: {
			options:{
				text_domain: 'wp-job-manager-jobadder',
				keywords: [
					'__:1,2d',
					'_e:1,2d',
					'_x:1,2c,3d',
					'esc_html__:1,2d',
					'esc_html_e:1,2d',
					'esc_html_x:1,2c,3d',
					'esc_attr__:1,2d',
					'esc_attr_e:1,2d',
					'esc_attr_x:1,2c,3d',
					'_ex:1,2c,3d',
					'_n:1,2,3,4d',
					'_nx:1,2,4c,5d',
					'_n_noop:1,2,3d',
					'_nx_noop:1,2,3c,4d',
					' __ngettext:1,2,3d',
					'__ngettext_noop:1,2,3d',
					'_c:1,2d',
					'_nc:1,2,4c,5d'
				]
			},
			files: {
				src: [
					'**/*.php', // Include all files
					'!node_modules/**', // Exclude node_modules/
					'!build/**', // Exclude build/
					'!logs/**'
				],
				expand: true
			}
		},

		// Clean up build directory
		clean: {
			main: ['build/<%= pkg.name %>']
		},

		// Copy the plugin into the build directory
		copy: {
			main: {
				src:  [
                    'assets/**',
                    '!assets/**/*.scss',
					'includes/**',
					'languages/**',
                    'templates/**',
                    'vendor/**',
					'*.php',
					'*.txt',
					'!logs/**'
				],
				dest: 'build/<%= pkg.name %>/'
			}
		},

		// Compress build directory into <name>.zip and <name>-<version>.zip
		compress: {
			main: {
				options: {
					mode: 'zip',
					archive: './build/<%= pkg.name %>.zip'
				},
				expand: true,
				cwd: 'build/<%= pkg.name %>/',
				src: ['**/*'],
                dest: '<%= pkg.name %>/'
			}
		},

    });
    
	// Load NPM tasks to be used here.
	grunt.loadNpmTasks( 'grunt-sass' );
	grunt.loadNpmTasks( 'grunt-wp-i18n' );
	grunt.loadNpmTasks( 'grunt-checktextdomain' );
	grunt.loadNpmTasks( 'grunt-contrib-uglify' );
	grunt.loadNpmTasks( 'grunt-contrib-cssmin' );
	grunt.loadNpmTasks( 'grunt-contrib-copy' );
	grunt.loadNpmTasks( 'grunt-contrib-watch' );

    // Register tasks.
	grunt.registerTask( 'default', [ 'uglify', 'sass', 'cssmin' ] );
	grunt.registerTask( 'build', [ 'cssmin', 'uglify', 'force:checktextdomain', 'clean', 'copy', 'compress' ] );
};