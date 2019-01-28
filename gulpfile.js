var gulp = require('gulp');
var less = require('gulp-less');
var LessPluginAutoprefix = require('less-plugin-autoprefix');
var autoprefix = new LessPluginAutoprefix({browsers: ['> 1%', 'last 2 versions', 'not ie <= 9']});

var lessSources = 'app/Resources/less/source/*.less'
var lessBuilds = 'app/Resources/less/*.less'
var cssDest = 'web/public/css/companies/'

gulp.task('default', function (done) {
    return gulp.src(lessBuilds)
        .pipe(less({ plugins: [autoprefix] })
          .on('error', function(error) { done(error); }))
        .pipe(gulp.dest(cssDest));
});

var watcher = gulp.watch([lessSources, lessBuilds], ['default']);
watcher.on('change', function(event) {
  console.log('File ' + event.path + ' was ' + event.type + ', running tasks...');
});