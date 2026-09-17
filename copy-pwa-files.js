const fs = require('fs');
const path = require('path');

const filesToCopy = ['manifest.webmanifest', 'sw.js', 'workbox-9c191d2f.js'];

filesToCopy.forEach((file) => {
    const src = path.join(__dirname, 'public', 'build', file);
    const dest = path.join(__dirname, 'public', file);

    if (fs.existsSync(src)) {
        fs.copyFileSync(src, dest);
        console.log(`Copied ${file} -> public/${file}`);
    } else {
        console.warn(`Skipped ${file} (not found in public/build)`);
    }
});