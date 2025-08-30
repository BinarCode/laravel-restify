import { execSync } from 'node:child_process';

function getPackageManagerVersion(name) {
  return execSync(`${name} --version`).toString("utf8").trim();
}

export { getPackageManagerVersion as g };
