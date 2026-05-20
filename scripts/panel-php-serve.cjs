const { spawn } = require("child_process");
const { resolvePhp } = require("./resolve-php.cjs");

const php = resolvePhp();
if (!php) {
  console.error(
    [
      "PHP not found for the admin panel.",
      "",
      "Create php.path.local in the project root with one line, e.g.:",
      "  C:\\xampp\\php\\php.exe",
      "",
      "Or set PHP_BIN for this terminal only:",
      "  $env:PHP_BIN = \"C:\\xampp\\php\\php.exe\"",
    ].join("\n"),
  );
  process.exit(1);
}

const args = ["-S", "127.0.0.1:8081", "-t", "_site"];
console.log("Admin panel:", `http://127.0.0.1:8081/admin/`);
console.log("Using PHP:", php);

const child = spawn(php, args, { stdio: "inherit", shell: false });
child.on("exit", (code) => process.exit(code ?? 0));
