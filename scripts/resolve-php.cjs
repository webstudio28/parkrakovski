/**
 * Resolve PHP executable for this project only (no system PATH required).
 *
 * Order:
 * 1. php.path.local (gitignored, one line = full path to php.exe)
 * 2. PHP_BIN environment variable
 * 3. Common XAMPP / Laragon locations on Windows
 */

const fs = require("fs");
const path = require("path");

const root = path.join(__dirname, "..");

function readLocalPathFile() {
  const file = path.join(root, "php.path.local");
  if (!fs.existsSync(file)) return "";
  const line = fs.readFileSync(file, "utf8").split(/\r?\n/).find((l) => l.trim() && !l.trim().startsWith("#"));
  return line ? line.trim().replace(/^["']|["']$/g, "") : "";
}

function candidates() {
  const list = [process.env.PHP_BIN, readLocalPathFile()].filter(Boolean);

  if (process.platform === "win32") {
    list.push(
      "C:\\xampp\\php\\php.exe",
      "C:\\XAMPP\\php\\php.exe",
      "D:\\xampp\\php\\php.exe",
      path.join(process.env.LOCALAPPDATA || "", "Programs", "php", "php.exe"),
    );
  }

  list.push("php");
  return list;
}

function resolvePhp() {
  for (const bin of candidates()) {
    if (bin === "php") {
      try {
        require("child_process").execFileSync("php", ["-v"], { stdio: "ignore" });
        return "php";
      } catch {
        continue;
      }
    }
    if (fs.existsSync(bin)) return bin;
  }
  return null;
}

module.exports = { resolvePhp, root };
