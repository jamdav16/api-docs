module.exports = function(grunt) {

  grunt.initConfig({

    copy: {
      main: {
        files: [
          {
            expand: true,
            cwd: './bower_components/highlightjs',
            src: '**',
            dest: './dist/lib/highlightjs'
          },
        ]
      }
    },

    less: {
      api_docs: {
        options: {
          strictMath: true
        },
        files: {
          "./dist/css/api-docs.css": "./src/less/api-docs.less"
        }
      }
    },

    concat: {
      options: {
        separator: ';'
      },
      "api-docs": {
        src: [
          './bower_components/jquery/dist/jquery.js',
          './bower_components/underscore/underscore.js',
          './bower_components/backbone/backbone.js',
          './bower_components/waypoints/lib/jquery.waypoints.js',
          './src/js/hljs-curl.js',
          './src/js/main.js'
        ],
        dest: './dist/js/api-docs.js'
      }
    },

    autoprefixer: {
      options: {
        browsers: [
          'Android >= 4',
          'Chrome >= 20',
          'Firefox >= 24', // Firefox 24 is the latest ESR
          'Explorer >= 9',
          'iOS >= 6',
          'Opera >= 16',
          'Safari >= 6'
        ]
      },
      api_docs: {
        options: {
          map: true
        },
        src: './dist/css/api-docs.css'
      }
    },

    shell: {
      htmlbuild: {
        command: 'php -f build.php'
      }
    },

    watch: {
      less: {
        files: [
          './src/less/**/*'
        ],  //watched files
        tasks: ['less', 'autoprefixer' /*, 'csssplit' */],                          //tasks to run
        options: {
          livereload: true                        //reloads the browser
        }
      },
      js: {
        files: [
          './src/js/**/*'
        ],
        tasks: ['concat'],
        options: {
          livereload: true
        }
      },
      build: {
        files: [
          './**/*.php',
          './build.config.json',
          './src/content/**/*',
          './src/templates/**/*'
        ],
        tasks: ['shell:htmlbuild'],
        options: {
          livereload: true
        }
      }
    }

  });


  
  require('load-grunt-tasks')(grunt, { scope: 'devDependencies' });  

  grunt.registerTask('dist', ['copy', 'less', 'autoprefixer', 'concat', 'shell:htmlbuild']);

  grunt.registerTask('default', ['watch']);

};
