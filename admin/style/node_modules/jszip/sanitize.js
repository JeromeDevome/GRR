const assert = require("assert");

function resolve(path) {
    const parts = path.split(/[\/\\]/);
    const result = [];
    for (let index = 0; index < parts.length; index++) {
        const part = parts[index];

        if (part === "." || part === "") {
            continue;
        } else if (part === "..") {
            result.pop();
        } else {
            result.push(part);
        }
    }
    return result.join("/");
}

assert.strictEqual(resolve("root\\a\\b"), "root/a/b");
assert.strictEqual(resolve("root/a/b"), "root/a/b");
assert.strictEqual(resolve("root/a/.."), "root");
assert.strictEqual(resolve("root/a/../b"), "root/b");
assert.strictEqual(resolve("root/a/./b"), "root/a/b");
assert.strictEqual(resolve("root/../../../"), "");
assert.strictEqual(resolve("////"), "");
assert.strictEqual(resolve("/a/b/c"), "a/b/c");
assert.strictEqual(resolve("a/b/c/"), "a/b/c/");
assert.strictEqual(resolve("../../../../../a"), "a");
assert.strictEqual(resolve("../app.js"), "app.js");

const failures = [];
const posixyCwd = "";

const resolveTests = [
    // [null,
    // // Arguments                               result
    // [[['c:/blah\\blah', 'd:/games', 'c:../a'], 'c:\\blah\\a'],
    // [['c:/ignore', 'd:\\a/b\\c/d', '\\e.exe'], 'd:\\e.exe'],
    // [['c:/ignore', 'c:/some/file'], 'c:\\some\\file'],
    // [['d:/ignore', 'd:some/dir//'], 'd:\\ignore\\some\\dir'],
    // [['.'], process.cwd()],
    // [['//server/share', '..', 'relative\\'], '\\\\server\\share\\relative'],
    // [['c:/', '//'], 'c:\\'],
    // [['c:/', '//dir'], 'c:\\dir'],
    // [['c:/', '//server/share'], '\\\\server\\share\\'],
    // [['c:/', '//server//share'], '\\\\server\\share\\'],
    // [['c:/', '///some//dir'], 'c:\\some\\dir'],
    // [['C:\\foo\\tmp.3\\', '..\\tmp.3\\cycles\\root.js'],
    //     'C:\\foo\\tmp.3\\cycles\\root.js'],
    // ],
    // ],
    [null,
    // Arguments                    result
    [[['/var/lib', '../', 'file/'], '/var/file'],
    [['/var/lib', '/../', 'file/'], '/file'],
    [['a/b/c/', '../../..'], posixyCwd],
    [['.'], posixyCwd],
    [['/some/dir', '.', '/absolute/'], '/absolute'],
    [['/foo/tmp.3/', '../tmp.3/cycles/root.js'], '/foo/tmp.3/cycles/root.js'],
    ],
    ],
];

resolveTests.forEach(([_, tests]) => {
    tests.forEach(([test, expected]) => {
        const actual = resolve(test.join("/"));
        let actualAlt;

        const message =
            `resolve(${test.join('/')})\n  expect=${JSON.stringify(expected)}\n  actual=${JSON.stringify(actual)}`;
        if (actual !== expected && actualAlt !== expected)
            failures.push(message);
    });
});
assert.strictEqual(failures.length, 0, failures.join('\n'));