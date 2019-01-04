// Define gulp tasks.
module.exports = function(gulp, plugins, options) {

  'use strict';

  function handleError (error) {
    console.log(error.toString());
    this.emit('end');
  }

  // Processor for linting is assigned to options so it can be reused later.
  options.processors = [
    // Options are defined in .stylelintrc.yaml file.
    plugins.stylelint(options.stylelintOptions),
    plugins.reporter(options.processorsOptions.reporterOptions)
  ];

  // Post CSS options.
  options.postcssOptions = [
    plugins.autoprefixer(options.autoprefixer)
  ];

  // Defining gulp tasks.
  gulp.task('js', function() {
    return gulp.src(options.es6Src + '/*.es6.js')
      .pipe(plugins.babel({
        presets: ['@babel/preset-env']
      }))
      .on('error', handleError)
      .pipe(plugins.extReplace('.js', '.es6.js'))
      .pipe(gulp.dest(options.jsDest));
  });

  gulp.task('js:lint', function() {
    return gulp.src(options.es6Src + '/*.es6.js')
      .pipe(plugins.eslint())
      .pipe(plugins.eslint.format())
      .pipe(plugins.eslint.failOnError());
  });

  gulp.task('sass', function() {
    return gulp.src(options.scssSrc + '/*.scss')
      .pipe(plugins.sass({
        outputStyle: 'expanded',
        includePaths: options.sassIncludePaths
      }))
      .pipe(plugins.postcss(options.postcssOptions))
      .pipe(gulp.dest(options.cssDest));
  });

  gulp.task('sass:lint', function () {
    return gulp.src(options.scssSrc + '/*.scss')
      .pipe(plugins.postcss(options.processors, {syntax: plugins.syntax_scss}))
  });

  gulp.task('watch', function () {
    gulp.watch(options.scssSrc + '/*.scss', ['sass:lint', 'sass']);
    gulp.watch(options.es6Src + '/*.es6.js', ['js:lint', 'js']);
  });

  // Default task to run everything in correct order.
  gulp.task('default', ['sass:lint', 'sass', 'js']);
};
