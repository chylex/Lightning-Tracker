const { src, dest, series } = require("gulp");
const concat = require("gulp-concat");
const replace = require("gulp-replace");
const css = require("gulp-clean-css");
const autoprefix = require("gulp-autoprefixer");
const del = require("del");
const glob = require("glob");
const merge = require("merge-stream");

const childProcess = require("child_process");
const crypto = require("crypto");
const fs = require("fs");
const fse = require("fs-extra");
const path = require("path");

const php = "../src";
const res = "../res";
const out = "../out";

function taskClean(){
    return del(out + "/**", { force: true });
}

function taskCopy(){
    // noinspection JSUnusedGlobalSymbols
    const basePhp = { base: php, dot: true };
    const baseRes = { base: res };
    
    const tasks = [
        src(php + "/**/*", basePhp)
            .pipe(dest(out)),
        
        src(res + "/~database/**/*", baseRes)
            .pipe(dest(out)),
        
        src(res + "/~resources/fonts/**/*", baseRes)
            .pipe(dest(out)),
        
        src(res + "/~resources/img/**/*", baseRes)
            .pipe(dest(out)),
        
        src(res + "/~resources/js/**/*", baseRes)
            .pipe(dest(out))
    ];
    
    return merge.apply(this, tasks);
}

function taskCSS(){
    const resources = res + "/~resources";
    
    // noinspection JSUnusedGlobalSymbols
    const prefixSettings = {
        cascade: false,
        remove: false,
        overrideBrowserslist: [ "last 1000 versions" ]
    };
    
    // noinspection JSUnusedGlobalSymbols
    const cssSettings = {
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
        .pipe(autoprefix(prefixSettings))
        .pipe(css(cssSettings))
        .pipe(dest(out + "/~resources"));
}

function taskHash(){
    const hasher = crypto.createHash("sha1");
    
    for(const file of glob.sync(out + "/~resources/**/*")){
        if (fs.lstatSync(file).isFile()){
            hasher.update(fs.readFileSync(file));
        }
    }
    
    const hash = hasher.digest("base64").replace(/[^a-zA-Z0-9]/g, "0").substr(0, 8);
    
    return src(out + "/bootstrap.php")
        .pipe(replace("define('TRACKER_RESOURCE_VERSION', '');", "define('TRACKER_RESOURCE_VERSION', '" + hash + "');"))
        .pipe(dest(out));
}

function taskPrepareTests(cb){
    const www = "../server/www";
    const backup = "../server/www-backup";
    const output = "../tests/_output";
    
    if (!fs.existsSync(backup)){
        fs.renameSync(www, backup);
    }
    else{
        fs.rmdirSync(www, { recursive: true, maxRetries: 10 });
    }
    
    fs.mkdirSync(www);
    fse.copySync(out, www);
    
    if (fs.existsSync(output)){
        for(const file of fs.readdirSync(output)){
            if (file !== ".gitignore" && !file.endsWith("coverage.serialized") && !file.endsWith("coverage.xml")){
                const fullPath = path.join(output, file);
                
                if (fs.lstatSync(fullPath).isFile()){
                    fs.unlinkSync(fullPath);
                }
            }
        }
    }
    
    cb();
}

function taskPrepareCoverage(cb){
    const tests = "../tests";
    const www = "../server/www";
    const wwwtests = www + "/tests";
    
    fse.copySync("./coverage", www);
    fs.mkdirSync(wwwtests);
    
    for(const file of glob.sync(tests + "/*.yml")){
        fs.copyFileSync(file, wwwtests + "/" + path.basename(file));
    }
    
    childProcess.execSync("composer install", { cwd: www });
    cb();
}

exports.default = series(taskClean, taskCopy, taskCSS, taskHash);
// noinspection JSUnusedGlobalSymbols
exports.prepareTests = series(taskClean, taskCopy, taskCSS, taskHash, taskPrepareTests);
// noinspection JSUnusedGlobalSymbols
exports.prepareCoverage = series(taskClean, taskCopy, taskCSS, taskHash, taskPrepareTests, taskPrepareCoverage);
