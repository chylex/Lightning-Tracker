const { src, dest, series } = require("gulp");
const concat = require("gulp-concat");
const css = require("gulp-clean-css");
const del = require("del");

const php = "../src";
const res = "../res";
const out = "../out";

function taskClean(){
    return del(out + "/**", { force: true });
}

async function taskCopy(){
    // noinspection JSUnusedGlobalSymbols
    const basePhp = { base: php, dot: true };
    const baseRes = { base: res };
    
    await src(php + "/**/*", basePhp)
        .pipe(dest(out));
    
    await src(res + "/~database/**/*", baseRes)
        .pipe(dest(out));
    
    await src(res + "/~resources/fonts/**/*", baseRes)
        .pipe(dest(out));
    
    await src(res + "/~resources/img/**/*", baseRes)
        .pipe(dest(out));
    
    await src(res + "/~resources/js/**/*", baseRes)
        .pipe(dest(out));
}

function taskCSS(){
    const resources = res + "/~resources";
    
    // noinspection JSUnusedGlobalSymbols
    const settings = {
        format: "keep-breaks",
        rebase: true,
        rebaseTo: resources,
        level: {
            1: {
                all: false,
                removeWhitespace: true
            }
        }
    };
    
    return src([ resources + "/css/main.css",
                 resources + "/css/!(main).css" ])
        .pipe(concat("style.min.css"))
        .pipe(css(settings))
        .pipe(dest(out + "/~resources"));
}

exports.default = series(taskClean, taskCopy, taskCSS);
