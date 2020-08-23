const { src, dest, series } = require("gulp");
const concat = require("gulp-concat");
const css = require("gulp-clean-css");

const root = "../res";
const out = root + "/~generated/";

function taskCSS(){
    // noinspection JSUnusedGlobalSymbols
    const settings = {
        format: "keep-breaks",
        rebase: true,
        rebaseTo: out,
        level: {
            1: {
                all: false,
                removeWhitespace: true
            }
        }
    };
    
    return src([ root + "/~resources/css/main.css",
                 root + "/~resources/css/!(main).css" ])
        .pipe(concat("style.min.css"))
        .pipe(css(settings))
        .pipe(dest(out));
}

exports.css = taskCSS;
exports.default = series(taskCSS);
