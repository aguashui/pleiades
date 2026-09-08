const fs = require('fs');
const path = require('path');

const rootDir = path.resolve(__dirname, '..');
const webroot = path.join(rootDir, 'app', 'webroot');

const filesToCopy = [
  {
    src: path.join(rootDir, 'node_modules', 'jquery', 'dist', 'jquery.min.js'),
    dest: path.join(webroot, 'js', 'jquery.js')
  },
  {
    src: path.join(rootDir, 'node_modules', 'bootstrap', 'dist', 'js', 'bootstrap.min.js'),
    dest: path.join(webroot, 'js', 'bootstrap.js')
  },
  {
    src: path.join(rootDir, 'node_modules', 'bootstrap', 'dist', 'css', 'bootstrap.min.css'),
    dest: path.join(webroot, 'css', 'bootstrap.css')
  }
];

const fontsSrcDir = path.join(rootDir, 'node_modules', 'bootstrap', 'dist', 'fonts');
const fontsDestDir = path.join(webroot, 'fonts');

// Copy individual files
for (const file of filesToCopy) {
  if (fs.existsSync(file.src)) {
    fs.mkdirSync(path.dirname(file.dest), { recursive: true });
    fs.copyFileSync(file.src, file.dest);
    console.log(`Copied ${path.relative(rootDir, file.src)} -> ${path.relative(rootDir, file.dest)}`);
  } else {
    console.warn(`Source not found: ${file.src}`);
  }
}

// Copy fonts directory
if (fs.existsSync(fontsSrcDir)) {
  fs.mkdirSync(fontsDestDir, { recursive: true });
  for (const fontFile of fs.readdirSync(fontsSrcDir)) {
    const srcPath = path.join(fontsSrcDir, fontFile);
    const destPath = path.join(fontsDestDir, fontFile);
    fs.copyFileSync(srcPath, destPath);
    console.log(`Copied font ${fontFile} -> ${path.relative(rootDir, destPath)}`);
  }
}
