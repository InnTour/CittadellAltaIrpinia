const fs = require('fs');
const files = ['RestaurantsPage.js', 'AccommodationsPage.js', 'B2GPage.js'];
files.forEach(fn => {
  const c = fs.readFileSync('C:/Users/InnTour/Dropbox/MetaBorghi-main/assets/' + fn, 'utf8');
  let stack = [];
  let inStr = false, strCh = '';
  let found = false;
  for (let i = 0; i < c.length; i++) {
    const ch = c[i];
    if (inStr) {
      if (ch === '\\') { i++; continue; }
      if (ch === strCh) inStr = false;
      continue;
    }
    if (ch === '"' || ch === "'") { inStr = true; strCh = ch; continue; }
    if (ch === '{' || ch === '(' || ch === '[') stack.push({ ch, pos: i });
    else if (ch === '}' || ch === ')' || ch === ']') {
      const exp = { '}': '{', ')': '(', ']': '[' };
      if (stack.length === 0) { console.log(fn + ': Extra ' + ch + ' at ' + i); found = true; break; }
      const top = stack.pop();
      if (top.ch !== exp[ch]) {
        console.log(fn + ': MISMATCH at pos ' + i + ': got [' + ch + '] expected close for [' + top.ch + '] opened at ' + top.pos);
        console.log('  Opened:', c.substring(Math.max(0, top.pos - 30), top.pos + 50));
        console.log('  Closed:', c.substring(Math.max(0, i - 30), i + 30));
        found = true;
        break;
      }
    }
  }
  if (!found) {
    if (stack.length > 0) console.log(fn + ': Unclosed brackets:', stack.length);
    else console.log(fn + ': OK');
  }
});
