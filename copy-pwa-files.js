const fs = require('fs');
const path = require('path');

// sw.js must sit at the site root so it can control every page (scope "/").
const filesToCopy = ['manifest.webmanifest', 'sw.js'];

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